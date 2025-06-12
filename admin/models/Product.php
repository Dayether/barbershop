<?php
/**
 * Product Model Class
 * Handles all database operations for products
 */
class Product {
    // Database connection and table name
    private $conn;
    private $table_name = "products";
    
    // Object properties
    public $id;
    public $name;
    public $description;
    public $price;
    public $image;
    public $stock;
    public $active;
    
    /**
     * Constructor - set database connection
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Read all products with pagination
     */
    public function read($page = 1, $perPage = 10) {
        // Calculate the offset
        $offset = ($page - 1) * $perPage;
        
        // Create query with pagination
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY product_id DESC LIMIT :limit OFFSET :offset";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind values with explicit type casting
        $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        // Execute query
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * Read single product by ID
     */
    public function readSingle() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE product_id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If product exists
        if($row) {
            $this->name = $row['name'];
            $this->description = $row['description'];
            $this->price = $row['price'];
            $this->image = $row['image'];
            $this->stock = $row['stock'];
            $this->active = $row['active'];
            return true;
        }
        
        return false;
    }
    
    /**
     * Count total number of products
     */
    public function count() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
    
    /**
     * Create new product
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (name, description, price, image, stock, active) 
                  VALUES 
                  (:name, :description, :price, :image, :stock, :active)";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind values
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->price = (float)$this->price;
        $this->stock = (int)$this->stock;
        $this->active = (int)$this->active;
        
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':image', $this->image);
        $stmt->bindParam(':stock', $this->stock);
        $stmt->bindParam(':active', $this->active);
        
        return $stmt->execute();
    }
    
    /**
     * Update product
     */
    public function update() {
        // If image is not changed
        if (empty($this->image)) {
            $query = "UPDATE " . $this->table_name . " 
                      SET name = :name, description = :description, price = :price, stock = :stock, active = :active 
                      WHERE product_id = :id";
            
            $stmt = $this->conn->prepare($query);
            
            // Bind values
            $stmt->bindParam(':name', $this->name);
            $stmt->bindParam(':description', $this->description);
            $stmt->bindParam(':price', $this->price);
            $stmt->bindParam(':stock', $this->stock);
            $stmt->bindParam(':active', $this->active);
            $stmt->bindParam(':id', $this->id);
        } else {
            // If image is changed
            $query = "UPDATE " . $this->table_name . " 
                      SET name = :name, description = :description, price = :price, image = :image, stock = :stock, active = :active 
                      WHERE product_id = :id";
            
            $stmt = $this->conn->prepare($query);
            
            // Bind all values including image
            $stmt->bindParam(':name', $this->name);
            $stmt->bindParam(':description', $this->description);
            $stmt->bindParam(':price', $this->price);
            $stmt->bindParam(':image', $this->image);
            $stmt->bindParam(':stock', $this->stock);
            $stmt->bindParam(':active', $this->active);
            $stmt->bindParam(':id', $this->id);
        }
        
        return $stmt->execute();
    }
    
    /**
     * Check if product is used in any orders
     * This helps decide whether we can delete the product or not
     */
    public function isUsedInOrders() {
        $query = "SELECT COUNT(*) FROM order_items WHERE product_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        
        return ($stmt->fetchColumn() > 0);
    }
    
    /**
     * Delete product - Fixed implementation for reliable deletion
     */
    public function delete() {
        try {
            // Check if product exists first
            $checkQuery = "SELECT product_id, image FROM " . $this->table_name . " WHERE product_id = :id";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':id', $this->id);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() === 0) {
                error_log("Product delete failed: Product ID {$this->id} does not exist");
                return false;
            }
            
            $product = $checkStmt->fetch(PDO::FETCH_ASSOC);
            $imagePath = $product['image'];
            
            // Check for order items with this product
            $orderItemsQuery = "SELECT COUNT(*) FROM order_items WHERE product_id = :id";
            $orderStmt = $this->conn->prepare($orderItemsQuery);
            $orderStmt->bindParam(':id', $this->id);
            $orderStmt->execute();
            
            $hasOrderItems = $orderStmt->fetchColumn() > 0;
            if ($hasOrderItems) {
                error_log("Notice: Deleting product ID {$this->id} that is referenced in orders");
                
                // We might need to add special handling for products with orders
                // For now, we'll delete it anyway due to ON DELETE CASCADE
            }
            
            // Perform the actual deletion
            $deleteQuery = "DELETE FROM " . $this->table_name . " WHERE product_id = :id";
            $deleteStmt = $this->conn->prepare($deleteQuery);
            $deleteStmt->bindParam(':id', $this->id);
            
            // Execute deletion
            if ($deleteStmt->execute()) {
                // Delete the image file if it exists
                if (!empty($imagePath)) {
                    $fullPath = '../' . $imagePath;
                    if (file_exists($fullPath)) {
                        @unlink($fullPath);
                    }
                }
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Database error deleting product: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Toggle product active status
     */
    public function toggleActive() {
        $query = "UPDATE " . $this->table_name . " SET active = NOT active WHERE product_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
}
?>
