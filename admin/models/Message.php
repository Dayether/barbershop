<?php
class Message {
    // Database connection and table name
    private $conn;
    private $table = 'contact_messages';

    // Object properties
    public $id;
    public $name;
    public $email;
    public $phone;
    public $subject;
    public $message;
    public $status;
    public $created_at;

    // Constructor with DB connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Read all messages with pagination
    public function read($page = 1, $per_page = 10, $status = null) {
        // Calculate offset for pagination
        $offset = ($page - 1) * $per_page;
        
        // Create query with optional status filter
        $query = "SELECT * FROM " . $this->table;
        
        if($status) {
            $query .= " WHERE status = :status";
        }
        
        $query .= " ORDER BY created_at DESC
                  LIMIT :limit OFFSET :offset";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind status if provided
        if($status) {
            $stmt->bindParam(':status', $status);
        }
        
        // Bind pagination parameters
        $stmt->bindParam(':limit', $per_page, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        
        // Execute query
        $stmt->execute();
        
        return $stmt;
    }

    // Count total messages with optional status filter
    public function count($status = null) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        
        if($status) {
            $query .= " WHERE status = :status";
        }
        
        $stmt = $this->conn->prepare($query);
        
        if($status) {
            $stmt->bindParam(':status', $status);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }

    // Read single message
    public function readSingle() {
        // Create query
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind ID
        $stmt->bindParam(':id', $this->id);
        
        // Execute query
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            // Set properties
            $this->name = $row['name'];
            $this->email = $row['email'];
            $this->phone = $row['phone'];
            $this->subject = $row['subject'];
            $this->message = $row['message'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            
            return true;
        }
        
        return false;
    }

    // Update message status (mark as read)
    public function updateStatus() {
        // Create query
        $query = "UPDATE " . $this->table . " SET status = :status WHERE id = :id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->status = htmlspecialchars(strip_tags($this->status));
        
        // Bind data
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':status', $this->status);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        // Print error if something goes wrong
        printf("Error: %s.\n", $stmt->error);
        
        return false;
    }

    // Delete message
    public function delete() {
        // Create query
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // Bind data
        $stmt->bindParam(':id', $this->id);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        // Print error if something goes wrong
        printf("Error: %s.\n", $stmt->error);
        
        return false;
    }

    // Dashboard stats: Get total messages by status
    public static function getMessageStats($db) {
        $query = "SELECT 
                    SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_messages,
                    SUM(CASE WHEN status != 'new' THEN 1 ELSE 0 END) as read_messages,
                    COUNT(*) as total
                  FROM contact_messages";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get recent messages for dashboard
    public static function getRecentMessages($db, $limit = 5) {
        $query = "SELECT * FROM contact_messages 
                  ORDER BY created_at DESC 
                  LIMIT :limit";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }
}
