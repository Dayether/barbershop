<?php
class User {
    // Database connection and table name
    private $conn;
    
    // Object properties
    public $id;
    public $name;
    public $email;
    public $password;
    public $profile_pic;
    public $phone;
    public $created_at;
    public $account_type;
    
    // Constructor with DB connection
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Read user details by ID
    public function readOne() {
        $query = "SELECT * FROM users WHERE id = :id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->name = $row['name'];
            $this->email = $row['email'];
            $this->profile_pic = $row['profile_pic'];
            $this->phone = $row['phone'];
            $this->created_at = $row['created_at'];
            $this->account_type = $row['account_type'];
            return true;
        }
        
        return false;
    }
    
    // Update user profile
    public function update() {
        $query = "UPDATE users 
                  SET name = :name, 
                      email = :email, 
                      phone = :phone
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize data
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        
        // Bind data
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':id', $this->id);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Update password
    public function updatePassword($new_password) {
        $query = "UPDATE users SET password = :password WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Hash password
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Bind data
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':id', $this->id);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Verify current password
    public function verifyPassword($password) {
        $query = "SELECT password FROM users WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row && password_verify($password, $row['password'])) {
            return true;
        }
        
        return false;
    }
    
    // Update profile picture
    public function updateProfilePicture($file_path) {
        $query = "UPDATE users SET profile_pic = :profile_pic WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize data
        $this->profile_pic = htmlspecialchars(strip_tags($file_path));
        
        // Bind data
        $stmt->bindParam(':profile_pic', $this->profile_pic);
        $stmt->bindParam(':id', $this->id);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Check if email already exists (for another user)
    public function emailExists($exclude_id = 0) {
        $query = "SELECT id FROM users WHERE email = :email AND id != :exclude_id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':exclude_id', $exclude_id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return true;
        }
        
        return false;
    }
}
?>
