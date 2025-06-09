<?php
session_start();

/**
 * Order Processing Class
 * Handles payment processing and order creation
 */
class OrderProcessor {
    private $db;
    
    /**
     * Constructor - initialize database connection
     */
    public function __construct() {
        try {
            $this->db = new PDO('mysql:host=localhost;dbname=barbershop', 'root', '');
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Validate form data
     * 
     * @param array $data Form data to validate
     * @return array Validation errors if any
     */
    public function validateFormData($data) {
        $errors = [];
        
        // Required fields
        $requiredFields = [
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'address' => 'Address',
            'city' => 'City',
            'zip' => 'ZIP Code',
            'country' => 'Country',
            'payment_method' => 'Payment Method'
        ];
        
        foreach ($requiredFields as $field => $label) {
            if (empty($data[$field])) {
                $errors[] = $label . " is required";
            }
        }
        
        // Email validation
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address";
        }
        
        // Payment method specific validation
        if (!empty($data['payment_method']) && $data['payment_method'] === 'credit_card') {
            $cardFields = [
                'card_number' => 'Card Number',
                'card_name' => 'Name on Card',
                'expiry_date' => 'Expiry Date',
                'cvv' => 'CVV'
            ];
            
            foreach ($cardFields as $field => $label) {
                if (empty($data[$field])) {
                    $errors[] = $label . " is required";
                }
            }
            
            // Validate card number format
            if (!empty($data['card_number'])) {
                $cardNumber = preg_replace('/\D/', '', $data['card_number']);
                if (strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
                    $errors[] = "Please enter a valid card number";
                }
            }
            
            // Validate expiry date format
            if (!empty($data['expiry_date'])) {
                if (!preg_match('/^\d{2}\/\d{2}$/', $data['expiry_date'])) {
                    $errors[] = "Expiry date should be in MM/YY format";
                }
            }
            
            // Validate CVV format
            if (!empty($data['cvv'])) {
                if (!preg_match('/^\d{3,4}$/', $data['cvv'])) {
                    $errors[] = "Please enter a valid CVV";
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Process the order
     * 
     * @param array $orderData Order data
     * @return array Order processing result
     */
    public function processOrder($orderData) {
        // Check if cart has items
        if (empty($_SESSION['cart'])) {
            return [
                'success' => false,
                'message' => 'Your cart is empty. Please add products before checkout.'
            ];
        }
        
        // Validate form data
        $validationErrors = $this->validateFormData($orderData);
        if (!empty($validationErrors)) {
            return [
                'success' => false,
                'message' => 'Please correct the following errors:',
                'errors' => $validationErrors
            ];
        }
        
        try {
            // Begin transaction for data consistency
            $this->db->beginTransaction();
            
            // Generate unique order reference
            $orderReference = 'ORD-' . strtoupper(uniqid());
            
            // Get user ID from session
            $userId = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : null;
            
            // Get total amount
            $totalAmount = floatval($orderData['total_amount']);
            
            // Create order record
            $stmt = $this->db->prepare("INSERT INTO orders (order_reference, user_id, total_amount, status, first_name, last_name, email, address, city, zip, country, phone) VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $orderReference,
                $userId,
                $totalAmount,
                $orderData['first_name'],
                $orderData['last_name'],
                $orderData['email'],
                $orderData['address'],
                $orderData['city'],
                $orderData['zip'],
                $orderData['country'],
                $orderData['phone']
            ]);
            
            // Get the new order ID
            $orderId = $this->db->lastInsertId();
            
            // Insert order items
            $itemStmt = $this->db->prepare("INSERT INTO order_items (order_id, product_id, name, quantity, price) VALUES (?, ?, ?, ?, ?)");
            
            foreach ($_SESSION['cart'] as $item) {
                $itemStmt->execute([
                    $orderId,
                    $item['id'],
                    $item['name'],
                    $item['quantity'],
                    $item['price']
                ]);
                
                // Update product stock (optional, depending on business logic)
                $stockStmt = $this->db->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
                $stockStmt->execute([$item['quantity'], $item['id'], $item['quantity']]);
            }
            
            // Commit the transaction
            $this->db->commit();
            
            // Clear the cart
            $_SESSION['cart'] = [];
            
            return [
                'success' => true,
                'message' => 'Order placed successfully!',
                'order_reference' => $orderReference,
                'order_id' => $orderId,
                'total' => $totalAmount
            ];
            
        } catch (PDOException $e) {
            // Rollback the transaction if an error occurs
            $this->db->rollBack();
            
            return [
                'success' => false,
                'message' => 'An error occurred while processing your order. Please try again.',
                'error_details' => $e->getMessage()
            ];
        }
    }
}

// Process the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $processor = new OrderProcessor();
        $result = $processor->processOrder($_POST);
        
        if ($result['success']) {
            // Store success data in session
            $_SESSION['order_success'] = [
                'reference' => $result['order_reference'],
                'total' => $result['total']
            ];
            
            // Redirect to success page
            header('Location: payment_success.php?order_ref=' . $result['order_reference']);
            exit;
        } else {
            // Store error data in session
            $_SESSION['payment_errors'] = isset($result['errors']) ? $result['errors'] : [$result['message']];
            
            // Redirect back to payment page
            header('Location: payment.php');
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['payment_errors'] = ['System error: ' . $e->getMessage()];
        header('Location: payment.php');
        exit;
    }
} else {
    // If not POST request, redirect to payment page
    header('Location: payment.php');
    exit;
}
