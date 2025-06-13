<?php
class Database {
    public $conn;

    // Optional: Static PDO connection if you want to use PDO elsewhere
    public static function getPDOConnection() {
        $host = 'localhost';
        $db_name = 'barbershop';
        $username = 'root';
        $password = '';
        try {
            $pdo = new PDO(
                "mysql:host=$host;dbname=$db_name",
                $username,
                $password
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        } catch (PDOException $e) {
            throw new Exception('PDO Connection Error: ' . $e->getMessage());
        }
    }

    public function __construct() {
        // Update these with your actual DB credentials or use includes/db_connection.php logic
        $host = 'localhost';
        $user = 'root';
        $pass = '';
        $dbname = 'barbershop';

        $this->conn = new mysqli($host, $user, $pass, $dbname);

        if ($this->conn->connect_error) {
            throw new Exception('Database connection failed: ' . $this->conn->connect_error);
        }
    }

    public function getAppointmentDetailsByReference($bookingReference) {
        $stmt = $this->conn->prepare(
            "SELECT a.*, b.name AS barber_name 
             FROM appointments a 
             LEFT JOIN barbers b ON a.barber_id = b.barber_id 
             WHERE a.booking_reference = ?"
        );
        $stmt->bind_param("s", $bookingReference);
        $stmt->execute();
        $result = $stmt->get_result();
        $details = $result->num_rows > 0 ? $result->fetch_assoc() : null;
        $stmt->close();
        return $details;
    }

    // Get unique active services (for admin appointment form)
    public function getUniqueActiveServices() {
        $stmt = $this->conn->prepare("SELECT service_id, name, description, duration, price, image FROM services WHERE active = 1 ORDER BY name ASC, service_id ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        $allServices = [];
        $serviceNames = [];
        while ($service = $result->fetch_assoc()) {
            if (!in_array($service['name'], $serviceNames)) {
                $allServices[] = $service;
                $serviceNames[] = $service['name'];
            }
        }
        $stmt->close();
        return $allServices;
    }

    // Get all active barbers (for admin appointment form)
    public function getActiveBarbers() {
        $stmt = $this->conn->prepare("SELECT barber_id, name FROM barbers WHERE active = 1 ORDER BY name ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        $barbers = [];
        while ($row = $result->fetch_assoc()) {
            $barbers[] = $row;
        }
        $stmt->close();
        return $barbers;
    }

    public function cancelAppointment($appointment_id, $user_id) {
        $stmt = $this->conn->prepare("SELECT * FROM appointments WHERE appointment_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $appointment_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $appointment = $result->fetch_assoc();
            $current_date = date('Y-m-d H:i:s');
            $appointment_datetime = $appointment['appointment_date'] . ' ' . $appointment['appointment_time'];

            if ($appointment_datetime < $current_date) {
                $stmt->close();
                return [
                    'success' => false,
                    'error_message' => "Cannot cancel past appointments."
                ];
            } elseif ($appointment['status'] === 'cancelled') {
                $stmt->close();
                return [
                    'success' => false,
                    'error_message' => "This appointment is already cancelled."
                ];
            } elseif ($appointment['status'] === 'completed') {
                $stmt->close();
                return [
                    'success' => false,
                    'error_message' => "Cannot cancel completed appointments."
                ];
            } else {
                $update = $this->conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE appointment_id = ?");
                $update->bind_param("i", $appointment_id);
                if ($update->execute()) {
                    $update->close();
                    $stmt->close();
                    return [
                        'success' => true,
                        'error_message' => ''
                    ];
                } else {
                    $error = $this->conn->error;
                    $update->close();
                    $stmt->close();
                    return [
                        'success' => false,
                        'error_message' => "Error cancelling appointment: " . $error
                    ];
                }
            }
        } else {
            $stmt->close();
            return [
                'success' => false,
                'error_message' => "Invalid appointment or you don't have permission to cancel it."
            ];
        }
    }

    public function cancelOrder($order_id, $user_id) {
        $stmt = $this->conn->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $order_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $order = $result->fetch_assoc();
            if ($order['status'] !== 'delivered' && $order['status'] !== 'cancelled') {
                // Update order status to cancelled
                $update = $this->conn->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ?");
                $update->bind_param("i", $order_id);
                if ($update->execute()) {
                    $update->close();
                    // Add cancellation record to order history (ignore errors if table missing)
                    try {
                        $comment = "Order cancelled by customer";
                        $history_stmt = $this->conn->prepare("INSERT INTO order_history (order_id, status, comment, created_at) VALUES (?, 'cancelled', ?, NOW())");
                        $history_stmt->bind_param("is", $order_id, $comment);
                        $history_stmt->execute();
                        $history_stmt->close();
                    } catch (mysqli_sql_exception $e) {
                        // Log error, but do not fail cancellation
                        error_log("Order history table error: " . $e->getMessage());
                    }
                    $stmt->close();
                    return [
                        'success' => true,
                        'error_message' => ''
                    ];
                } else {
                    $error = $this->conn->error;
                    $update->close();
                    $stmt->close();
                    return [
                        'success' => false,
                        'error_message' => "Failed to cancel order. Please try again."
                    ];
                }
            } else {
                $stmt->close();
                return [
                    'success' => false,
                    'error_message' => "This order cannot be cancelled."
                ];
            }
        } else {
            $stmt->close();
            return [
                'success' => false,
                'error_message' => "Order not found or you don't have permission to cancel it."
            ];
        }
    }

    public function getRecentOrdersWithItemCount($limit = 5) {
        $stmt = $this->conn->prepare(
            "SELECT o.*, COUNT(oi.id) as item_count 
             FROM orders o 
             LEFT JOIN order_items oi ON o.id = oi.order_id 
             GROUP BY o.id 
             ORDER BY o.created_at DESC 
             LIMIT ?"
        );
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        $stmt->close();
        return $orders;
    }

    public function getUserCheckoutData($user_id) {
        // Remove first_name and last_name, use name instead
        $stmt = $this->conn->prepare("SELECT name, email, phone, address, city, zip, country FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = [];
        if ($result->num_rows > 0) {
            $user_data = $result->fetch_assoc();
        }
        $stmt->close();
        return $user_data;
    }

    public function insertContactMessage($name, $email, $phone, $subject, $message) {
        $status = "new";
        $stmt = $this->conn->prepare(
            "INSERT INTO contact_messages (name, email, phone, subject, message, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->bind_param("ssssss", $name, $email, $phone, $subject, $message, $status);
        $success = $stmt->execute();
        $error = $stmt->error;
        $stmt->close();
        return [
            'success' => $success,
            'error' => $error
        ];
    }

    public function loginUser($email, $password) {
        $stmt = $this->conn->prepare("SELECT user_id, first_name, last_name, email, profile_pic, account_type, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            return [
                'user_id' => $user['user_id'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'email' => $user['email'],
                'profile_pic' => $user['profile_pic'],
                'account_type' => $user['account_type']
            ];
        }
        return false;
    }

    public function cancelUserAppointment($appointment_id, $user_id) {
        $stmt = $this->conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE appointment_id = ? AND user_id = ? AND status IN ('pending', 'confirmed')");
        $stmt->bind_param("ii", $appointment_id, $user_id);
        $stmt->execute();
        $stmt->close();
        // Optionally, add to appointment_history here if needed
    }

    public function deleteUserCancelledAppointment($appointment_id, $user_id) {
        // Allow deleting both cancelled and completed appointments
        $stmt = $this->conn->prepare("DELETE FROM appointments WHERE appointment_id = ? AND user_id = ? AND (status = 'cancelled' OR status = 'completed')");
        $stmt->bind_param("ii", $appointment_id, $user_id);
        $stmt->execute();
        $stmt->close();
        // Optionally, also delete related appointment_history entries here if needed
    }

    public function getUserAppointments($user_id, $status_filter = null) {
        $appointments = [];
        $where_clause = "WHERE a.user_id = ?";
        $params = [$user_id];
        $types = "i";
        if ($status_filter && in_array($status_filter, ['pending', 'confirmed', 'completed', 'cancelled'])) {
            $where_clause .= " AND a.status = ?";
            $params[] = $status_filter;
            $types .= "s";
        }
        $query = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, a.notes, 
                  s.name AS service_name, s.duration, s.price, s.service_id,
                  b.name AS barber_name, b.barber_id
                  FROM appointments a 
                  LEFT JOIN services s ON a.service_id = s.service_id 
                  LEFT JOIN barbers b ON a.barber_id = b.barber_id 
                  $where_clause
                  ORDER BY 
                    CASE 
                        WHEN a.status = 'pending' OR a.status = 'confirmed' THEN 0
                        ELSE 1
                    END,
                    CASE 
                        WHEN a.appointment_date >= CURDATE() THEN 0
                        ELSE 1
                    END,
                    a.appointment_date ASC, 
                    a.appointment_time ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }
        $stmt->close();
        return $appointments;
    }

    public function getOrderDetails($order_id) {
        $stmt = $this->conn->prepare(
            "SELECT o.*, DATE_FORMAT(o.created_at, '%M %d, %Y') as order_date 
             FROM orders o 
             WHERE o.order_id = ?"
        );
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->num_rows > 0 ? $result->fetch_assoc() : null;
        $stmt->close();
        return $order;
    }

    // Get order items for an order
    public function getOrderItems($order_id) {
        $stmt = $this->conn->prepare("SELECT oi.*, p.image as product_image FROM order_items oi LEFT JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        while ($item = $result->fetch_assoc()) {
            $items[] = $item;
        }
        $stmt->close();
        return $items;
    }

    public function updateOrderAddress($order_id, $user_id, $address, $city, $zip, $country) {
        $stmt = $this->conn->prepare("UPDATE orders SET address = ?, city = ?, zip = ?, country = ? WHERE order_id = ? AND user_id = ? AND status = 'pending'");
        $stmt->bind_param("ssssii", $address, $city, $zip, $country, $order_id, $user_id);
        $stmt->execute();
        $success = $stmt->affected_rows > 0;
        $error_message = $success ? '' : "Failed to update address. Please try again or check if the order is still pending.";
        $stmt->close();
        return [
            'success' => $success,
            'error_message' => $error_message
        ];
    }

    public function cancelUserOrder($order_id, $user_id) {
        $stmt = $this->conn->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ? AND user_id = ? AND status = 'pending'");
        $stmt->bind_param("ii", $order_id, $user_id);
        $stmt->execute();
        $success = $stmt->affected_rows > 0;
        $error_message = $success ? '' : "Failed to cancel order. It may have already been processed or cancelled.";
        $stmt->close();
        return [
            'success' => $success,
            'error_message' => $error_message
        ];
    }

    public function deleteUserCancelledOrder($order_id, $user_id) {
        // Allow deleting both cancelled and completed orders
        $stmt = $this->conn->prepare("DELETE FROM orders WHERE order_id = ? AND user_id = ? AND (status = 'cancelled' OR status = 'completed')");
        $stmt->bind_param("ii", $order_id, $user_id);
        $stmt->execute();
        $success = $stmt->affected_rows > 0;
        $error_message = $success ? '' : "Failed to delete order. Only cancelled or completed orders can be deleted.";
        $stmt->close();
        return [
            'success' => $success,
            'error_message' => $error_message
        ];
    }

    public function getUserOrdersSummary($user_id) {
        $orders = [];
        $stmt = $this->conn->prepare(
            "SELECT o.order_id, o.created_at as order_date, o.total_amount, o.status, 
                COUNT(oi.order_item_id) as item_count 
             FROM orders o 
             LEFT JOIN order_items oi ON o.order_id = oi.order_id 
             WHERE o.user_id = ? 
             GROUP BY o.order_id
             ORDER BY o.created_at DESC"
        );
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        $stmt->close();
        return $orders;
    }

    public function emailExists($email) {
        $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    // Fix the registerUser method to use the correct users table columns and check for prepare() errors.
    public function registerUser($first_name, $last_name, $email, $password) {
        // Check if email already exists
        $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE email = ?");
        if (!$stmt) {
            return [
                'success' => false,
                'error_message' => 'Database error: ' . $this->conn->error
            ];
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            return [
                'success' => false,
                'error_message' => 'Email already in use'
            ];
        }
        $stmt->close();

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $profile_pic = 'images/default-profile.png';
        $account_type = 0;
        $stmt = $this->conn->prepare(
            "INSERT INTO users (first_name, last_name, email, password, profile_pic, account_type, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );
        if (!$stmt) {
            return [
                'success' => false,
                'error_message' => 'Database error: ' . $this->conn->error
            ];
        }
        $stmt->bind_param("sssssi", $first_name, $last_name, $email, $hashed_password, $profile_pic, $account_type);
        $success = $stmt->execute();
        $error_message = $success ? '' : $stmt->error;
        $stmt->close();
        return [
            'success' => $success,
            'error_message' => $error_message
        ];
    }

    public function getAppointmentForReschedule($appointment_id, $user_id) {
        $stmt = $this->conn->prepare(
            "SELECT a.*, s.name as service_name, s.duration, s.price, b.name as barber_name 
             FROM appointments a 
             LEFT JOIN services s ON a.service_id = s.service_id
             LEFT JOIN barbers b ON a.barber_id = b.barber_id
             WHERE a.appointment_id = ? AND a.user_id = ? 
             AND (a.status = 'pending' OR a.status = 'confirmed')"
        );
        $stmt->bind_param("ii", $appointment_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $appointment = $result->fetch_assoc();
        $stmt->close();
        return $appointment;
    }

    public function rescheduleAppointment($appointment, $appointment_id, $user_id, $new_date, $new_time) {
        $barber_id = $appointment['barber_id'];
        if (empty($barber_id)) {
            $check_sql = "SELECT COUNT(*) FROM appointments WHERE appointment_date = ? AND appointment_time = ? AND service_id = ? AND appointment_id != ? AND status IN ('pending', 'confirmed')";
            $check_stmt = $this->conn->prepare($check_sql);
            $check_stmt->bind_param("ssii", $new_date, $new_time, $appointment['service_id'], $appointment_id);
        } else {
            $check_sql = "SELECT COUNT(*) FROM appointments WHERE appointment_date = ? AND appointment_time = ? AND barber_id = ? AND appointment_id != ? AND status IN ('pending', 'confirmed')";
            $check_stmt = $this->conn->prepare($check_sql);
            $check_stmt->bind_param("ssii", $new_date, $new_time, $barber_id, $appointment_id);
        }
        $check_stmt->execute();
        $check_stmt->bind_result($count);
        $check_stmt->fetch();
        $check_stmt->close();

        if ($count > 0) {
            return [
                'success' => false,
                'error_message' => "The selected date and time is already booked. Please choose another slot."
            ];
        }

        // Add to appointment history
        $notes = 'Appointment rescheduled by customer from ' .
            date('F j, Y', strtotime($appointment['appointment_date'])) .
            ' at ' . date('g:i A', strtotime($appointment['appointment_time'])) .
            ' to ' . date('F j, Y', strtotime($new_date)) .
            ' at ' . date('g:i A', strtotime($new_time));
        $stmt = $this->conn->prepare("INSERT INTO appointment_history (appointment_id, action, notes, user_id) VALUES (?, 'reschedule', ?, ?)");
        $stmt->bind_param("isi", $appointment_id, $notes, $user_id);
        $stmt->execute();
        $stmt->close();

        // Update appointment
        $stmt = $this->conn->prepare("UPDATE appointments SET appointment_date = ?, appointment_time = ? WHERE appointment_id = ? AND user_id = ?");
        $stmt->bind_param("ssii", $new_date, $new_time, $appointment_id, $user_id);
        $success = $stmt->execute();
        $error_message = $success ? '' : $stmt->error;
        $stmt->close();

        return [
            'success' => $success,
            'error_message' => $error_message ?: 'There was a problem rescheduling your appointment. Please try again.'
        ];
    }

    public function saveCartSession($data) {
        if (isset($data['cart']) && is_array($data['cart'])) {
            $cart = $data['cart'];
            $assocCart = [];
            foreach ($cart as $item) {
                // Use 'id' or 'product_id' as the key
                $pid = isset($item['id']) ? $item['id'] : (isset($item['product_id']) ? $item['product_id'] : null);
                if ($pid !== null) {
                    $item['id'] = $pid; // Ensure 'id' is set
                    $assocCart[$pid] = $item;
                }
            }
            $_SESSION['cart'] = $assocCart;
            return ['success' => true, 'message' => 'Cart saved successfully'];
        } else {
            return ['success' => false, 'message' => 'Invalid cart data'];
        }
    }

    public static function test_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    public function getActiveProductsWithCategory() {
        $products = [];
        $stmt = $this->conn->prepare("SELECT product_id, name, description, price, image, stock FROM products WHERE active = 1");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['category'] = $this->determineCategory($row['name'], $row['description']);
            $products[] = $row;
        }
        $stmt->close();
        return $products;
    }

    public function determineCategory($name, $description) {
        $name_lower = strtolower($name);
        $desc_lower = strtolower($description);
        if (strpos($name_lower, 'pomade') !== false || 
            strpos($name_lower, 'clay') !== false || 
            strpos($name_lower, 'spray') !== false ||
            strpos($desc_lower, 'hair') !== false) {
            return 'hair';
        } elseif (strpos($name_lower, 'beard') !== false || 
                 strpos($name_lower, 'shav') !== false) {
            return 'beard';
        } elseif (strpos($name_lower, 'razor') !== false || 
                 strpos($name_lower, 'comb') !== false) {
            return 'tools';
        }
        return 'all';
    }

    public function getUnavailableSlots($date, $service_id, $barber_id = null) {
        $unavailable = [];
        if ($date) {
            if ($barber_id === null) {
                $stmt = $this->conn->prepare(
                    "SELECT appointment_time FROM appointments WHERE appointment_date = ? AND service_id = ? AND status IN ('pending', 'confirmed')"
                );
                $stmt->bind_param("si", $date, $service_id);
            } else {
                $stmt = $this->conn->prepare(
                    "SELECT appointment_time FROM appointments WHERE appointment_date = ? AND barber_id = ? AND status IN ('pending', 'confirmed')"
                );
                $stmt->bind_param("si", $date, $barber_id);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $unavailable[] = $row['appointment_time'];
            }
            $stmt->close();
        }
        return $unavailable;
    }

    // Add product to cart (OOP, session-based)
    public function cartAddProduct($productId, $quantity) {
        $productId = (int)$productId;
        $quantity = (int)$quantity;
        if ($quantity <= 0) {
            return ['success' => false, 'message' => 'Invalid quantity. Please specify a positive number.'];
        }
        $stmt = $this->conn->prepare("SELECT product_id, name, price, image FROM products WHERE product_id = ? AND active = 1");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found or is not available.'];
        }
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$productId] = [
                'id' => $product['product_id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image'],
                'quantity' => $quantity
            ];
        }
        // Ensure all cart items have 'id'
        foreach ($_SESSION['cart'] as $k => &$item) {
            if (!isset($item['id'])) $item['id'] = $k;
        }
        unset($item);
        $cartTotals = $this->cartCalculateTotals();
        return [
            'success' => true,
            'message' => 'Product added to cart.',
            'itemQuantity' => $_SESSION['cart'][$productId]['quantity'],
            'itemSubtotal' => $_SESSION['cart'][$productId]['price'] * $_SESSION['cart'][$productId]['quantity'],
            'itemsCount' => $cartTotals['itemsCount'],
            'subtotal' => $cartTotals['subtotal'],
            'tax' => $cartTotals['tax'],
            'total' => $cartTotals['total'],
            'cart' => array_values($_SESSION['cart'])
        ];
    }

    // Update product quantity in cart (OOP, session-based)
    public function cartUpdateQuantity($productId, $quantity) {
        $productId = (int)$productId;
        $quantity = (int)$quantity;
        if (!isset($_SESSION['cart'][$productId])) {
            return ['success' => false, 'message' => 'Product not in cart.'];
        }
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$productId]);
            // Ensure all cart items have 'id'
            foreach ($_SESSION['cart'] as $k => &$item) {
                if (!isset($item['id'])) $item['id'] = $k;
            }
            unset($item);
            $cartTotals = $this->cartCalculateTotals();
            return [
                'success' => true,
                'message' => 'Product removed from cart.',
                'itemsCount' => $cartTotals['itemsCount'],
                'subtotal' => $cartTotals['subtotal'],
                'tax' => $cartTotals['tax'],
                'total' => $cartTotals['total'],
                'cart' => array_values($_SESSION['cart'])
            ];
        }
        $_SESSION['cart'][$productId]['quantity'] = $quantity;
        $_SESSION['cart'][$productId]['id'] = $productId;
        // Ensure all cart items have 'id'
        foreach ($_SESSION['cart'] as $k => &$item) {
            if (!isset($item['id'])) $item['id'] = $k;
        }
        unset($item);
        $cartTotals = $this->cartCalculateTotals();
        $itemSubtotal = $_SESSION['cart'][$productId]['price'] * $quantity;
        return [
            'success' => true,
            'message' => 'Quantity updated successfully.',
            'itemQuantity' => $quantity,
            'itemSubtotal' => $itemSubtotal,
            'itemsCount' => $cartTotals['itemsCount'],
            'subtotal' => $cartTotals['subtotal'],
            'tax' => $cartTotals['tax'],
            'total' => $cartTotals['total'],
            'cart' => array_values($_SESSION['cart'])
        ];
    }

    // Remove product from cart (OOP, session-based)
    public function cartRemoveProduct($productId) {
        $productId = (int)$productId;
        if (!isset($_SESSION['cart'][$productId])) {
            return ['success' => false, 'message' => 'Product not in cart.'];
        }
        unset($_SESSION['cart'][$productId]);
        // Ensure all cart items have 'id'
        foreach ($_SESSION['cart'] as $k => &$item) {
            if (!isset($item['id'])) $item['id'] = $k;
        }
        unset($item);
        $cartTotals = $this->cartCalculateTotals();
        return [
            'success' => true,
            'message' => 'Product removed from cart.',
            'itemsCount' => $cartTotals['itemsCount'],
            'subtotal' => $cartTotals['subtotal'],
            'tax' => $cartTotals['tax'],
            'total' => $cartTotals['total'],
            'cart' => array_values($_SESSION['cart'])
        ];
    }

    // Apply promo code to cart (OOP, session-based)
    public function cartApplyPromo($code, $discount) {
        $_SESSION['promo_code'] = [
            'code' => $code,
            'discount' => (float)$discount
        ];
        return [
            'success' => true,
            'message' => 'Promo code applied successfully.'
        ];
    }

    // Calculate cart totals (OOP, session-based)
    public function cartCalculateTotals() {
        $subtotal = 0;
        $itemsCount = 0;
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        foreach ($_SESSION['cart'] as $item) {
            $subtotal += $item['price'] * $item['quantity'];
            $itemsCount += $item['quantity'];
        }
        $shipping = 5.00;
        $taxRate = 0.08;
        $tax = $subtotal * $taxRate;
        $total = $subtotal + $shipping + $tax;
        if (isset($_SESSION['promo_code'])) {
            $total -= $_SESSION['promo_code']['discount'];
        }
        return [
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'tax' => $tax,
            'total' => $total,
            'itemsCount' => $itemsCount
        ];
    }

    public function updateOrdersTable() {
        $messages = [];
        // Check if the orders table exists
        $tableExists = $this->conn->query("SHOW TABLES LIKE 'orders'")->num_rows > 0;
        if (!$tableExists) {
            $messages[] = [
                'type' => 'info',
                'text' => "Orders table does not exist. Creating it now..."
            ];
            $sql = "CREATE TABLE orders (
                id INT(11) NOT NULL AUTO_INCREMENT,
                order_reference VARCHAR(50) NOT NULL,
                user_id INT(11) DEFAULT NULL,
                first_name VARCHAR(50) NOT NULL,
                last_name VARCHAR(50) NOT NULL,
                email VARCHAR(100) NOT NULL,
                address VARCHAR(255) NOT NULL,
                city VARCHAR(100) NOT NULL,
                zip VARCHAR(20) NOT NULL,
                country VARCHAR(50) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                total_amount DECIMAL(10,2) NOT NULL,
                status ENUM('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                CONSTRAINT orders_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            if ($this->conn->query($sql)) {
                $messages[] = [
                    'type' => 'success',
                    'text' => "Orders table created successfully with all required columns."
                ];
            } else {
                throw new Exception("Error creating orders table: " . $this->conn->error);
            }
        } else {
            $messages[] = [
                'type' => 'info',
                'text' => "Orders table exists. Checking for missing columns..."
            ];
            $requiredColumns = [
                'id' => 'INT(11) NOT NULL AUTO_INCREMENT',
                'order_reference' => 'VARCHAR(50) NOT NULL',
                'user_id' => 'INT(11) DEFAULT NULL',
                'first_name' => 'VARCHAR(50) NOT NULL',
                'last_name' => 'VARCHAR(50) NOT NULL',
                'email' => 'VARCHAR(100) NOT NULL',
                'address' => 'VARCHAR(255) NOT NULL',
                'city' => 'VARCHAR(100) NOT NULL',
                'zip' => 'VARCHAR(20) NOT NULL',
                'country' => 'VARCHAR(50) NOT NULL',
                'phone' => 'VARCHAR(20) NOT NULL',
                'total_amount' => 'DECIMAL(10,2) NOT NULL',
                'status' => "ENUM('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending'",
                'created_at' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
            ];
            $result = $this->conn->query("SHOW COLUMNS FROM orders");
            $existingColumns = [];
            while ($row = $result->fetch_assoc()) {
                $existingColumns[$row['Field']] = true;
            }
            $columnsAdded = false;
            foreach ($requiredColumns as $column => $definition) {
                if (!isset($existingColumns[$column])) {
                    $messages[] = [
                        'type' => 'info',
                        'text' => "Adding missing column: $column"
                    ];
                    $sql = "ALTER TABLE orders ADD COLUMN $column $definition";
                    if ($this->conn->query($sql)) {
                        $messages[] = [
                            'type' => 'success',
                            'text' => "Column '$column' added successfully."
                        ];
                        $columnsAdded = true;
                    } else {
                        throw new Exception("Error adding column '$column': " . $this->conn->error);
                    }
                }
            }
            if (!$columnsAdded) {
                $messages[] = [
                    'type' => 'success',
                    'text' => "All required columns already exist in the orders table."
                ];
            }
        }
        // Check if order_items table exists and create if missing
        $table_check = $this->conn->query("SHOW TABLES LIKE 'order_items'");
        if ($table_check->num_rows == 0) {
            $messages[] = [
                'type' => 'info',
                'text' => "Order_items table doesn't exist - creating it now"
            ];
            $create_order_items_table = "CREATE TABLE order_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                product_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                quantity INT NOT NULL DEFAULT 1,
                FOREIGN KEY (order_id) REFERENCES orders(id)
            )";
            if ($this->conn->query($create_order_items_table)) {
                $messages[] = [
                    'type' => 'success',
                    'text' => "Order_items table created successfully"
                ];
            } else {
                throw new Exception("Failed to create order_items table: " . $this->conn->error);
            }
        } else {
            $columns_result = $this->conn->query("SHOW COLUMNS FROM order_items");
            $columns = [];
            while ($column = $columns_result->fetch_assoc()) {
                $columns[$column['Field']] = true;
            }
            if (!isset($columns['product_id'])) {
                $this->conn->query("ALTER TABLE order_items ADD COLUMN product_id INT NOT NULL AFTER order_id");
                $messages[] = [
                    'type' => 'info',
                    'text' => "Added missing product_id column to order_items table"
                ];
            }
        }
        $messages[] = [
            'type' => 'success',
            'text' => "Database update completed successfully!"
        ];
        return ['messages' => $messages];
    }

    public function updateUsersTable() {
        $messages = [];
        // Add account_type column if missing
        $result = $this->conn->query("SHOW COLUMNS FROM users LIKE 'account_type'");
        if ($result->num_rows == 0) {
            $sql = "ALTER TABLE users ADD COLUMN account_type TINYINT(1) NOT NULL DEFAULT 0 AFTER email";
            if ($this->conn->query($sql) === TRUE) {
                $messages[] = "Column account_type added successfully to users table.";
            } else {
                $messages[] = "Error adding column account_type: " . $this->conn->error;
            }
        } else {
            $messages[] = "Column account_type already exists in the users table.";
        }

        // Create or update admin account
        $adminEmail = "admin@example.com";
        $checkAdmin = $this->conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $checkAdmin->bind_param("s", $adminEmail);
        $checkAdmin->execute();
        $adminResult = $checkAdmin->get_result();

        if ($adminResult->num_rows == 0) {
            $adminName = "Admin User";
            $adminPassword = password_hash("admin123", PASSWORD_DEFAULT);
            $adminType = 1;
            $stmt = $this->conn->prepare("INSERT INTO users (name, email, password, account_type) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $adminName, $adminEmail, $adminPassword, $adminType);
            if ($stmt->execute()) {
                $messages[] = "Admin account created successfully.";
            } else {
                $messages[] = "Error creating admin account: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $adminRow = $adminResult->fetch_assoc();
            $adminId = $adminRow['user_id'];
            $adminType = 1;
            $updateStmt = $this->conn->prepare("UPDATE users SET account_type = ? WHERE user_id = ?");
            $updateStmt->bind_param("ii", $adminType, $adminId);
            if ($updateStmt->execute()) {
                $messages[] = "Existing admin account updated with admin privileges.";
            } else {
                $messages[] = "Error updating admin account: " . $updateStmt->error;
            }
            $updateStmt->close();
        }
        $checkAdmin->close();

        return $messages;
    }

    public function validateOrderData($data) {
        $requiredFields = [
            'first_name', 'last_name', 'email', 'phone', 'address', 'city', 'zip', 'country', 'payment_method'
        ];
        $errors = [];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
            }
        }
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address";
        }
        // Payment method specific validation
        if (isset($data['payment_method']) && $data['payment_method'] === 'credit_card') {
            if (empty($data['card_number']) || !preg_match('/^\d{13,19}$/', preg_replace('/\D/', '', $data['card_number']))) {
                $errors[] = "Please enter a valid card number";
            }
            if (empty($data['card_name'])) {
                $errors[] = "Name on Card is required";
            }
            if (empty($data['expiry_date']) || !preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $data['expiry_date'])) {
                $errors[] = "Expiry date should be in MM/YY format";
            }
            if (empty($data['cvv']) || !preg_match('/^\d{3,4}$/', $data['cvv'])) {
                $errors[] = "Please enter a valid CVV";
            }
        }
        // For PayPal, do not require credit card fields
        return $errors;
    }

    public function isUserAdmin($user_id) {
        $stmt = $this->conn->prepare("SELECT account_type FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return isset($row['account_type']) && $row['account_type'] == 1;
    }

    // Static flash message helper
    public static function flash($name = '', $message = '', $class = 'alert alert-success') {
        if (!empty($name)) {
            if (!empty($message) && empty($_SESSION[$name])) {
                $_SESSION[$name] = $message;
                $_SESSION[$name . '_class'] = $class;
            } else if (empty($message) && !empty($_SESSION[$name])) {
                $class = !empty($_SESSION[$name . '_class']) ? $_SESSION[$name . '_class'] : '';
                echo '<div class="' . $class . '" id="msg-flash">' . $_SESSION[$name] . '</div>';
                unset($_SESSION[$name]);
                unset($_SESSION[$name . '_class']);
            }
        }
    }

    // Get a single appointment by ID (for admin)
    public function getAppointmentById($appointment_id) {
        $stmt = $this->conn->prepare(
            "SELECT a.*, s.name as service_name, b.name as barber_name
             FROM appointments a
             LEFT JOIN services s ON a.service_id = s.service_id
             LEFT JOIN barbers b ON a.barber_id = b.barber_id
             WHERE a.appointment_id = ?"
        );
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $appointment = $result->fetch_assoc();
        $stmt->close();
        return $appointment;
    }

    // Get order by ID
    public function getOrderById($order_id) {
        $stmt = $this->conn->prepare("SELECT * FROM orders WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();
        return $order;
    }

    // Update order status
    public function updateOrderStatus($order_id, $status) {
        // Get current status
        $stmt = $this->conn->prepare("SELECT status FROM orders WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();

        $current_status = $order ? $order['status'] : null;

        // If changing to 'processing' and wasn't already 'processing', decrease stock
        if ($current_status !== 'processing' && $status === 'processing') {
            // Get all order items
            $stmt_items = $this->conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $stmt_items->bind_param("i", $order_id);
            $stmt_items->execute();
            $items_result = $stmt_items->get_result();
            while ($item = $items_result->fetch_assoc()) {
                // Decrease stock for each product
                $update_stock = $this->conn->prepare("UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE product_id = ?");
                $update_stock->bind_param("ii", $item['quantity'], $item['product_id']);
                $update_stock->execute();
                $update_stock->close();
            }
            $stmt_items->close();
        }

        // Update the order status
        $stmt = $this->conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $stmt->bind_param("si", $status, $order_id);
        $success = $stmt->execute();
        $error_message = $success ? '' : $stmt->error;
        $stmt->close();
        return ['success' => $success, 'error_message' => $error_message];
    }

    // Delete an order (and its items)
    public function deleteOrder($order_id) {
        // Delete order items first
        $stmt_items = $this->conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt_items->bind_param("i", $order_id);
        $stmt_items->execute();
        $stmt_items->close();

        // Then delete the order
        $stmt = $this->conn->prepare("DELETE FROM orders WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $success = $stmt->execute();
        $error_message = $success ? '' : $stmt->error;
        $stmt->close();
        return ['success' => $success, 'error_message' => $error_message];
    }

    // Count orders (optionally by status)
    public function countOrders($status = '') {
        if ($status) {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM orders WHERE status = ?");
            $stmt->bind_param("s", $status);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            return (int)($row['total'] ?? 0);
        } else {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM orders");
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            return (int)($row['total'] ?? 0);
        }
    }

    // Get paginated orders (optionally by status)
    public function getOrders($status = '', $limit = 10, $offset = 0) {
        if ($status) {
            $stmt = $this->conn->prepare("SELECT * FROM orders WHERE status = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $stmt->bind_param("sii", $status, $limit, $offset);
        } else {
            $stmt = $this->conn->prepare("SELECT * FROM orders ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $stmt->bind_param("ii", $limit, $offset);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        $stmt->close();
        return $orders;
    }

    // Count appointments (optionally by status)
    public function countAppointments($status = '') {
        if ($status && in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'])) {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE status = ?");
            $stmt->bind_param("s", $status);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            return $row ? (int)$row['count'] : 0;
        } else {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM appointments");
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            return $row ? (int)$row['count'] : 0;
        }
    }

    // Get paginated appointments (optionally by status)
    public function getAppointments($status = '', $limit = 10, $offset = 0) {
        $appointments = [];
        // Fix: include 'completed' in the status filter
        if ($status && in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'])) {
            $stmt = $this->conn->prepare("SELECT * FROM appointments WHERE status = ? ORDER BY appointment_date DESC, appointment_time DESC LIMIT ? OFFSET ?");
            $stmt->bind_param("sii", $status, $limit, $offset);
        } else {
            $stmt = $this->conn->prepare("SELECT * FROM appointments ORDER BY appointment_date DESC, appointment_time DESC LIMIT ? OFFSET ?");
            $stmt->bind_param("ii", $limit, $offset);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }
        $stmt->close();
        return $appointments;
    }

    // Count all barbers
    public function countBarbers() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM barbers");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return (int)($row['total'] ?? 0);
    }

    // Get paginated barbers
    public function getBarbers($limit = 10, $offset = 0) {
        $stmt = $this->conn->prepare("SELECT * FROM barbers ORDER BY barber_id DESC LIMIT ? OFFSET ?");
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $barbers = [];
        while ($row = $result->fetch_assoc()) {
            $barbers[] = $row;
        }
        $stmt->close();
        return $barbers;
    }

    // Count all products
    public function countProducts() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM products");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return (int)($row['total'] ?? 0);
    }

    // Get paginated products
    public function getProducts($limit = 10, $offset = 0) {
        $stmt = $this->conn->prepare("SELECT * FROM products ORDER BY product_id DESC LIMIT ? OFFSET ?");
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $products = [];
        while ($row = $result->fetch_assoc()) {
            // Add 'id' key for compatibility with admin pages
            $row['id'] = $row['product_id'];
            $products[] = $row;
        }
        $stmt->close();
        return $products;
    }

    // Get a single barber by ID
    public function getBarberById($barber_id) {
        $stmt = $this->conn->prepare("SELECT * FROM barbers WHERE barber_id = ?");
        $stmt->bind_param("i", $barber_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $barber = $result->fetch_assoc();
        $stmt->close();
        if ($barber) {
            $barber['id'] = $barber['barber_id'];
        }
        return $barber;
    }

    // Create a new barber (with optional image upload)
    public function createBarber($data) {
        $name = trim($data['name']);
        $bio = trim($data['bio']);
        $active = (int)$data['active'];
        $imagePath = '';

        // Handle image upload if provided
        if ($data['image'] && isset($data['image']['tmp_name']) && $data['image']['tmp_name']) {
            $target_dir = "uploads/barbers/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            $file_extension = pathinfo($data['image']['name'], PATHINFO_EXTENSION);
            $filename = 'barber_' . time() . '.' . $file_extension;
            $target_file = $target_dir . $filename;
            if (move_uploaded_file($data['image']['tmp_name'], $target_file)) {
                $imagePath = $target_file;
            } else {
                return ['success' => false, 'error_message' => 'Failed to upload image.'];
            }
        }

        $stmt = $this->conn->prepare("INSERT INTO barbers (name, bio, image, active) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $name, $bio, $imagePath, $active);
        $success = $stmt->execute();
        $error_message = $success ? '' : $stmt->error;
        $stmt->close();
        return ['success' => $success, 'error_message' => $error_message];
    }

    // Update an existing barber (with optional image upload)
    public function updateBarber($data) {
        $barber_id = (int)$data['barber_id'];
        $name = trim($data['name']);
        $bio = trim($data['bio']);
        $active = (int)$data['active'];
        $imagePath = null;

        // Get current image
        $barber = $this->getBarberById($barber_id);
        $currentImage = $barber ? $barber['image'] : '';

        // Handle image upload if provided
        if ($data['image'] && isset($data['image']['tmp_name']) && $data['image']['tmp_name']) {
            $target_dir = "uploads/barbers/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            $file_extension = pathinfo($data['image']['name'], PATHINFO_EXTENSION);
            $filename = 'barber_' . time() . '.' . $file_extension;
            $target_file = $target_dir . $filename;
            if (move_uploaded_file($data['image']['tmp_name'], $target_file)) {
                $imagePath = $target_file;
                // Optionally remove old image
                if ($currentImage && file_exists($currentImage)) {
                    @unlink($currentImage);
                }
            } else {
                return ['success' => false, 'error_message' => 'Failed to upload image.'];
            }
        } else {
            $imagePath = $currentImage;
        }

        $stmt = $this->conn->prepare("UPDATE barbers SET name = ?, bio = ?, image = ?, active = ? WHERE barber_id = ?");
        $stmt->bind_param("sssii", $name, $bio, $imagePath, $active, $barber_id);
        $success = $stmt->execute();
        $error_message = $success ? '' : $stmt->error;
        $stmt->close();
        return ['success' => $success, 'error_message' => $error_message];
    }

    // Delete a barber
    public function deleteBarber($barber_id) {
        // Optionally remove image file
        $barber = $this->getBarberById($barber_id);
        if ($barber && $barber['image'] && file_exists($barber['image'])) {
            @unlink($barber['image']);
        }
        $stmt = $this->conn->prepare("DELETE FROM barbers WHERE barber_id = ?");
        $stmt->bind_param("i", $barber_id);
        $success = $stmt->execute();
        $error_message = $success ? '' : $stmt->error;
        $stmt->close();
        return ['success' => $success, 'error_message' => $error_message];
    }

    // Toggle barber active status
    public function toggleBarberActive($barber_id) {
        $barber = $this->getBarberById($barber_id);
        if (!$barber) {
            return ['success' => false, 'error_message' => 'Barber not found.'];
        }
        $newStatus = $barber['active'] ? 0 : 1;
        $stmt = $this->conn->prepare("UPDATE barbers SET active = ? WHERE barber_id = ?");
        $stmt->bind_param("ii", $newStatus, $barber_id);
        $success = $stmt->execute();
        $error_message = $success ? '' : $stmt->error;
        $stmt->close();
        return ['success' => $success, 'error_message' => $error_message];
    }

    // Update an existing product (with optional image upload)
    public function updateProduct($data) {
        $product_id = (int)$data['product_id'];
        $name = trim($data['name']);
        $description = trim($data['description']);
        $price = (float)$data['price'];
        $stock = (int)$data['stock'];
        $active = (int)$data['active'];
        $imagePath = null;

        $product = $this->getProductById($product_id);
        $currentImage = $product ? $product['image'] : '';

        if ($data['image'] && isset($data['image']['tmp_name']) && $data['image']['tmp_name']) {
            $target_dir = "uploads/products/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            $file_extension = pathinfo($data['image']['name'], PATHINFO_EXTENSION);
            $filename = 'product_' . time() . '.' . $file_extension;
            $target_file = $target_dir . $filename;
            if (move_uploaded_file($data['image']['tmp_name'], $target_file)) {
                $imagePath = $target_file;
                if ($currentImage && file_exists($currentImage)) {
                    @unlink($currentImage);
                }
            } else {
                return ['success' => false, 'error_message' => 'Failed to upload image.'];
            }
        } else {
            $imagePath = $currentImage;
        }

        $stmt = $this->conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, image = ?, active = ? WHERE product_id = ?");
        $stmt->bind_param("ssdisii", $name, $description, $price, $stock, $imagePath, $active, $product_id);
        $success = $stmt->execute();
        $error_message = $success ? '' : $stmt->error;
        $stmt->close();

        // --- Begin: Propagate price change to order_items and orders ---
        if ($success) {
            // 1. Update all order_items for this product
            $updateItems = $this->conn->prepare("UPDATE order_items SET price = ? WHERE product_id = ?");
            $updateItems->bind_param("di", $price, $product_id);
            $updateItems->execute();
            $updateItems->close();

            // 2. Get all affected order_ids
            $orderIds = [];
            $result = $this->conn->query("SELECT DISTINCT order_id FROM order_items WHERE product_id = $product_id");
            while ($row = $result->fetch_assoc()) {
                $orderIds[] = $row['order_id'];
            }

            // 3. For each order, recalculate totals and update orders table
            foreach ($orderIds as $order_id) {
                // Get all items for this order
                $items = [];
                $stmtItems = $this->conn->prepare("SELECT price, quantity FROM order_items WHERE order_id = ?");
                $stmtItems->bind_param("i", $order_id);
                $stmtItems->execute();
                $res = $stmtItems->get_result();
                $subtotal = 0;
                while ($item = $res->fetch_assoc()) {
                    $subtotal += $item['price'] * $item['quantity'];
                }
                $stmtItems->close();

                $shipping = 5.00;
                $tax = $subtotal * 0.08;
                $total = $subtotal + $shipping + $tax;

                // Update order total_amount
                $stmtOrder = $this->conn->prepare("UPDATE orders SET total_amount = ? WHERE order_id = ?");
                $stmtOrder->bind_param("di", $total, $order_id);
                $stmtOrder->execute();
                $stmtOrder->close();
            }
        }
        // --- End: Propagate price change ---

        return ['success' => $success, 'error_message' => $error_message];
    }

    public function getMessageById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM contact_messages WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $message = $result->fetch_assoc();
        $stmt->close();
        return $message;
    }

    public function updateMessageStatus($id, $status) {
        $stmt = $this->conn->prepare("UPDATE contact_messages SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $success = $stmt->execute();
        $error_message = $success ? '' : $stmt->error;
        $stmt->close();
        return ['success' => $success, 'error_message' => $error_message];
    }

    public function deleteMessage($id) {
        $stmt = $this->conn->prepare("DELETE FROM contact_messages WHERE id = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $error_message = $success ? '' : $stmt->error;
        $stmt->close();
        return ['success' => $success, 'error_message' => $error_message];
    }

    public function countMessages($status = '') {
        if ($status) {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM contact_messages WHERE status = ?");
            $stmt->bind_param("s", $status);
        } else {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM contact_messages");
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return (int)($row['total'] ?? 0);
    }

    public function getMessages($page = 1, $perPage = 10, $status = '') {
        $offset = ($page - 1) * $perPage;
        if ($status) {
            $stmt = $this->conn->prepare("SELECT * FROM contact_messages WHERE status = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $stmt->bind_param("sii", $status, $perPage, $offset);
        } else {
            $stmt = $this->conn->prepare("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $stmt->bind_param("ii", $perPage, $offset);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        $stmt->close();
        return $messages;
    }

    // Get user by ID
    public function getUserById($user_id) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user;
    }

    // Update user profile (name, email, phone)
    public function updateUserProfileFull($data) {
        $user_id = (int)$data['user_id'];
        $first_name = trim($data['first_name']);
        $last_name = trim($data['last_name']);
        $email = trim($data['email']);
        $phone = trim($data['phone']);

        // Check if email is already in use by another user
        $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            return ['success' => false, 'error_message' => 'Email already in use by another account'];
        }
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE user_id = ?");
        $stmt->bind_param("ssssi", $first_name, $last_name, $email, $phone, $user_id);
        $success = $stmt->execute();
        $error_message = $success ? '' : $stmt->error;
        $stmt->close();
        return ['success' => $success, 'error_message' => $error_message];
    }

    // Delete user profile picture (reset to default)

    public function deleteUserProfilePicture($user_id, $currentPic) {
        $defaultPic = 'uploads/default-profile.jpg';
        if ($currentPic && $currentPic !== $defaultPic && file_exists('../' . $currentPic)) {
            @unlink('../' . $currentPic);
        }
        $stmt = $this->conn->prepare("UPDATE users SET profile_pic = ? WHERE user_id = ?");
        $stmt->bind_param("si", $defaultPic, $user_id);
        $success = $stmt->execute();
        $error_message = $success ? '' : $stmt->error;
        $stmt->close();
        return [
            'success' => $success,
            'profile_pic' => $defaultPic,
            'error_message' => $error_message
        ];
    }

    // Check if user is logged in (session-based)
    public function isUserLoggedIn() {
        return isset($_SESSION['user']) && isset($_SESSION['user']['user_id']);
    }

    // Get barber id=>name map
    public function getBarberMap() {
        $stmt = $this->conn->prepare("SELECT barber_id, name FROM barbers");
        $stmt->execute();
        $result = $stmt->get_result();
        $map = [];
        while ($row = $result->fetch_assoc()) {
            $map[$row['barber_id']] = $row['name'];
        }
        $stmt->close();
        return $map;
    }

    // Get service id=>name map
    public function getServiceMap() {
        $stmt = $this->conn->prepare("SELECT service_id, name FROM services");
        $stmt->execute();
        $result = $stmt->get_result();
        $map = [];
        while ($row = $result->fetch_assoc()) {
            $map[$row['service_id']] = $row['name'];
        }
        $stmt->close();
        return $map;
    }

    // Get appointment history (for admin)
    public function getAppointmentHistory($appointment_id) {
        $stmt = $this->conn->prepare(
            "SELECT h.*, 
                    u.name as user_name
             FROM appointment_history h
             LEFT JOIN users u ON h.user_id = u.user_id
             WHERE h.appointment_id = ?
             ORDER BY h.created_at ASC"
        );
        if (!$stmt) {
            return []; // Return empty if table or columns do not exist
        }
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        $stmt->close();
        return $history;
    }

    // Update an appointment (for admin)
    public function updateAppointment($data) {
        $appointment_id = (int)$data['appointment_id'];
        $service_id = (int)$data['service_id'];
        $appointment_date = $data['appointment_date'];
        $appointment_time = $data['appointment_time'];
        $barber_id = !empty($data['barber_id']) ? (int)$data['barber_id'] : null;
        $client_name = $data['client_name'];
        $client_email = $data['client_email'];
        $client_phone = $data['client_phone'];
        $notes = $data['notes'];
        $status = $data['status'];

        $sql = "UPDATE appointments SET service_id = ?, appointment_date = ?, appointment_time = ?, barber_id = ?, client_name = ?, client_email = ?, client_phone = ?, notes = ?, status = ? WHERE appointment_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'error_message' => $this->conn->error];
        }
        $stmt->bind_param(
            "ississsssi",
            $service_id,
            $appointment_date,
            $appointment_time,
            $barber_id,
            $client_name,
            $client_email,
            $client_phone,
            $notes,
            $status,
            $appointment_id
        );
        $success = $stmt->execute();
        $error_message = $success ? '' : $stmt->error;
        $stmt->close();
        return ['success' => $success, 'error_message' => $error_message];
    }

    // Change user password
    public function changeUserPassword($user_id, $current_password, $new_password, $confirm_password) {
        // Fetch current hashed password
        $stmt = $this->conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        $stmt->close();

        if (!$user_data || !password_verify($current_password, $user_data['password'])) {
            return [
                'success' => false,
                'error_message' => 'Current password is incorrect'
            ];
        }
        if ($new_password !== $confirm_password) {
            return [
                'success' => false,
                'error_message' => 'New passwords do not match'
            ];
        }
        // Prevent using the same password as the current one
        if (password_verify($new_password, $user_data['password'])) {
            return [
                'success' => false,
                'error_message' => 'New password must be different from the current password'
            ];
        }
        if (strlen($new_password) < 6) {
            return [
                'success' => false,
                'error_message' => 'New password must be at least 6 characters'
            ];
        }
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        $success = $stmt->execute();
        $error_message = $success ? '' : $stmt->error;
        $stmt->close();
        return [
            'success' => $success,
            'error_message' => $error_message
        ];
    }

    // Create a new appointment
    public function createAppointment($data) {
        $service_id = (int)$data['service_id'];
        $appointment_date = $data['appointment_date'];
        $appointment_time = $data['appointment_time'];
        $barber_id = !empty($data['barber_id']) ? (int)$data['barber_id'] : null;
        $client_name = trim($data['client_name']);
        $client_email = trim($data['client_email']);
        $client_phone = trim($data['client_phone']);
        $notes = trim($data['notes']);
        $user_id = (int)$data['user_id'];
        $status = 'pending';

        // Generate a unique booking reference
        $booking_reference = 'TIP' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));

        // Check for double booking (same time, date, and barber or service)
        if ($barber_id) {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = ? AND appointment_time = ? AND barber_id = ? AND status IN ('pending','confirmed')");
            $stmt->bind_param("ssi", $appointment_date, $appointment_time, $barber_id);
        } else {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = ? AND appointment_time = ? AND service_id = ? AND status IN ('pending','confirmed')");
            $stmt->bind_param("ssi", $appointment_date, $appointment_time, $service_id);
        }
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        if ($count > 0) {
            return [
                'success' => false,
                'error_message' => 'The selected time slot is already booked. Please choose another slot.'
            ];
        }

        

        // Insert appointment
        $stmt = $this->conn->prepare("INSERT INTO appointments (service_id, appointment_date, appointment_time, barber_id, client_name, client_email, client_phone, notes, user_id, status, booking_reference) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            return [
                'success' => false,
                'error_message' => 'Database error: ' . $this->conn->error
            ];
        }
        $stmt->bind_param(
            "ississssiss",
            $service_id,
            $appointment_date,
            $appointment_time,
            $barber_id,
            $client_name,
            $client_email,
            $client_phone,
            $notes,
            $user_id,
            $status,
            $booking_reference
        );
        $success = $stmt->execute();
        $error_message = $success ? '' : $stmt->error;
        $stmt->close();

        if ($success) {
            return [
                'success' => true,
                'booking_reference' => $booking_reference
            ];
        } else {
            return [
                'success' => false,
                'error_message' => $error_message ?: 'Failed to create appointment.'
            ];
        }
    }

    // Create a new order (for process_payment.php)
    public function createOrder($orderData, $cartItems) {
        // Generate a unique order reference
        $order_reference = 'TIP' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));

        // --- Calculate total_amount from cart items, shipping, and tax ---
        $subtotal = 0;
        foreach ($cartItems as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        $shipping = 5.00;
        $tax = $subtotal * 0.08;
        $total_amount = $subtotal + $shipping + $tax;

        // Use calculated total_amount, ignore incoming total_amount/order_total for DB
        $stmt = $this->conn->prepare(
            "INSERT INTO orders (order_reference, user_id, first_name, last_name, email, address, city, zip, country, phone, total_amount, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        if (!$stmt) {
            return [
                'success' => false,
                'error_message' => 'Database error: ' . $this->conn->error
            ];
        }
        $user_id = isset($orderData['user_id']) ? $orderData['user_id'] : null;
        $first_name = $orderData['first_name'];
        $last_name = $orderData['last_name'];
        $email = $orderData['email'];
        $address = $orderData['address'];
        $city = $orderData['city'];
        $zip = $orderData['zip'];
        $country = $orderData['country'];
        $phone = $orderData['phone'];
        $status = 'pending';

        $stmt->bind_param(
            "sissssssssds",
            $order_reference,
            $user_id,
            $first_name,
            $last_name,
            $email,
            $address,
            $city,
            $zip,
            $country,
            $phone,
            $total_amount,
            $status
        );
        $success = $stmt->execute();
        if (!$success) {
            $error_message = $stmt->error;
            $stmt->close();
            return [
                'success' => false,
                'error_message' => $error_message
            ];
        }
        $order_id = $stmt->insert_id;
        $stmt->close();

        // Insert order items
        $stmt_items = $this->conn->prepare(
            "INSERT INTO order_items (order_id, product_id, name, price, quantity) VALUES (?, ?, ?, ?, ?)"
        );
        if (!$stmt_items) {
            return [
                'success' => false,
                'error_message' => 'Database error: ' . $this->conn->error
            ];
        }
        foreach ($cartItems as $item) {
            $product_id = $item['id'];
            $name = $item['name'];
            $price = $item['price'];
            $quantity = $item['quantity'];
            $stmt_items->bind_param("iisdi", $order_id, $product_id, $name, $price, $quantity);
            if (!$stmt_items->execute()) {
                $error_message = $stmt_items->error;
                $stmt_items->close();
                return [
                    'success' => false,
                    'error_message' => $error_message
                ];
            }
        }
        $stmt_items->close();

        return [
            'success' => true,
            'order_id' => $order_id,
            'order_reference' => $order_reference
        ];
    }

    // Update user profile picture
    public function updateUserProfilePicture($user_id, $file, $currentPic = null) {
        // Validate file
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'error_message' => 'No file uploaded.'];
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'error_message' => 'Invalid image type.'];
        }

        // Prepare upload directory
        $uploadDir = 'uploads/profile_pics/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
        $targetPath = $uploadDir . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['success' => false, 'error_message' => 'Failed to upload image.'];
        }

        // Remove old profile pic if not default and exists
        $defaultPic = 'images/default-profile.png';
        if ($currentPic && $currentPic !== $defaultPic && file_exists($currentPic)) {
            @unlink($currentPic);
        }

        // Update DB
        $stmt = $this->conn->prepare("UPDATE users SET profile_pic = ? WHERE user_id = ?");
        $stmt->bind_param("si", $targetPath, $user_id);
        $success = $stmt->execute();
        $stmt->close();

        if ($success) {
            return ['success' => true, 'profile_pic' => $targetPath];
        } else {
            return ['success' => false, 'error_message' => 'Failed to update profile picture in database.'];
        }
    }

    // Add this method near other appointment methods
    public function deleteAppointment($appointment_id) {
        // Optionally, delete appointment history first
        $stmt_history = $this->conn->prepare("DELETE FROM appointment_history WHERE appointment_id = ?");
        $stmt_history->bind_param("i", $appointment_id);
        $stmt_history->execute();
        $stmt_history->close();

        // Delete the appointment itself
        $stmt = $this->conn->prepare("DELETE FROM appointments WHERE appointment_id = ?");
        $stmt->bind_param("i", $appointment_id);
        $success = $stmt->execute();
        $error_message = $success ? '' : $stmt->error;
        $stmt->close();

        return [
            'success' => $success,
            'error_message' => $error_message
        ];
    }

    // Add this method to fetch a single product by ID
    public function getProductById($product_id) {
        $stmt = $this->conn->prepare("SELECT * FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();
        return $product;
    }

    // Create a new product (with optional image upload)
    public function createProduct($data) {
        $name = trim($data['name']);
        $description = trim($data['description']);
        $price = (float)$data['price'];
        $stock = (int)$data['stock'];
        $active = (int)$data['active'];
        $imagePath = '';

        // Handle image upload if provided
        if ($data['image'] && isset($data['image']['tmp_name']) && $data['image']['tmp_name']) {
            $target_dir = "uploads/products/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            $file_extension = pathinfo($data['image']['name'], PATHINFO_EXTENSION);
            $filename = 'product_' . time() . '.' . $file_extension;
            $target_file = $target_dir . $filename;
            if (move_uploaded_file($data['image']['tmp_name'], $target_file)) {
                $imagePath = $target_file;
            } else {
                return ['success' => false, 'error_message' => 'Failed to upload image.'];
            }
        }

        $stmt = $this->conn->prepare("INSERT INTO products (name, description, price, stock, image, active) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            return ['success' => false, 'error_message' => $this->conn->error];
        }
        $stmt->bind_param("ssdisi", $name, $description, $price, $stock, $imagePath, $active);
        $success = $stmt->execute();
        $error_message = $success ? '' : $stmt->error;
        $stmt->close();
        return ['success' => $success, 'error_message' => $error_message];
    }

    // Delete a product (and optionally its image)
    public function deleteProduct($product_id) {
        // Get current image path
        $product = $this->getProductById($product_id);
        if ($product && !empty($product['image']) && file_exists($product['image'])) {
            @unlink($product['image']);
        }
        // Delete product from database
        $stmt = $this->conn->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $success = $stmt->execute();
        $error_message = $success ? '' : $stmt->error;
        $stmt->close();
        return ['success' => $success, 'error_message' => $error_message];
    }

}