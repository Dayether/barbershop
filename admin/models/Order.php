<?php
class Order {
    private $conn;
    
    // Order properties
    public $id;
    public $order_reference;
    public $user_id;
    public $total_amount;
    public $status;
    public $created_at;
    public $first_name;
    public $last_name;
    public $email;
    public $address;
    public $city;
    public $zip;
    public $country;
    public $phone;
    
    // Constructor with database connection
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Read all orders with pagination and optional status filter
    public function read($page = 1, $perPage = 10, $status = null) {
        $offset = ($page - 1) * $perPage;
        
        $query = "SELECT o.*, u.name as user_name FROM orders o 
                  LEFT JOIN users u ON o.user_id = u.id";
                  
        if ($status) {
            $query .= " WHERE o.status = :status";
        }
        
        $query .= " ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        if ($status) {
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        }
        
        $stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt;
    }
    
    // Get total count of orders with optional status filter
    public function count($status = null) {
        $query = "SELECT COUNT(*) as total FROM orders";
        
        if ($status) {
            $query .= " WHERE status = :status";
        }
        
        $stmt = $this->conn->prepare($query);
        
        if ($status) {
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }
    
    // Read single order by ID
    public function readSingle() {
        $query = "SELECT o.*, u.name as user_name 
                  FROM orders o 
                  LEFT JOIN users u ON o.user_id = u.id 
                  WHERE o.id = :id";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            return false;
        }
        
        // Set properties
        $this->id = $row['id'];
        $this->order_reference = $row['order_reference'];
        $this->user_id = $row['user_id'];
        $this->total_amount = $row['total_amount'];
        $this->status = $row['status'];
        $this->created_at = $row['created_at'];
        $this->first_name = $row['first_name'];
        $this->last_name = $row['last_name'];
        $this->email = $row['email'];
        $this->address = $row['address'];
        $this->city = $row['city'];
        $this->zip = $row['zip'];
        $this->country = $row['country'];
        $this->phone = $row['phone'];
        
        return true;
    }
    
    // Get order items
    public function getOrderItems() {
        $query = "SELECT oi.*, p.image as product_image 
                  FROM order_items oi 
                  LEFT JOIN products p ON oi.product_id = p.id 
                  WHERE oi.order_id = :order_id";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $this->id);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Update order details
    public function update() {
        $query = "UPDATE orders
                  SET first_name = :first_name,
                      last_name = :last_name,
                      email = :email,
                      address = :address,
                      city = :city,
                      zip = :zip,
                      country = :country,
                      phone = :phone,
                      status = :status
                  WHERE id = :id";
                  
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':city', $this->city);
        $stmt->bindParam(':zip', $this->zip);
        $stmt->bindParam(':country', $this->country);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':status', $this->status);
        
        // Execute query
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Update just the order status
    public function updateStatus() {
        $query = "UPDATE orders SET status = :status WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':status', $this->status);
        
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Delete order
    public function delete() {
        // Delete order items first to maintain referential integrity
        $deleteItemsQuery = "DELETE FROM order_items WHERE order_id = :id";
        $stmt = $this->conn->prepare($deleteItemsQuery);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        
        // Then delete the order
        $query = "DELETE FROM orders WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Add item to order
    public function addItem($productId, $name, $quantity, $price) {
        $query = "INSERT INTO order_items (order_id, product_id, name, quantity, price)
                  VALUES (:order_id, :product_id, :name, :quantity, :price)";
                  
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':order_id', $this->id);
        $stmt->bindParam(':product_id', $productId);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':price', $price);
        
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Update order item
    public function updateItem($itemId, $quantity) {
        $query = "UPDATE order_items SET quantity = :quantity WHERE id = :id AND order_id = :order_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $itemId);
        $stmt->bindParam(':order_id', $this->id);
        $stmt->bindParam(':quantity', $quantity);
        
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Remove item from order
    public function removeItem($itemId) {
        $query = "DELETE FROM order_items WHERE id = :id AND order_id = :order_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $itemId);
        $stmt->bindParam(':order_id', $this->id);
        
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Recalculate order total based on items
    public function recalculateTotal() {
        $query = "SELECT SUM(quantity * price) as total FROM order_items WHERE order_id = :order_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $this->id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $newTotal = $result['total'];
        
        // Update the order total
        $updateQuery = "UPDATE orders SET total_amount = :total WHERE id = :id";
        $updateStmt = $this->conn->prepare($updateQuery);
        $updateStmt->bindParam(':id', $this->id);
        $updateStmt->bindParam(':total', $newTotal);
        
        if ($updateStmt->execute()) {
            $this->total_amount = $newTotal;
            return true;
        }
        
        return false;
    }
}
?>
