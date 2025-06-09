<?php
class User {
    private $conn;
    private $table = 'users';
    
    // User properties
    public $id;
    public $name;
    public $email;
    public $password;
    public $profile_pic;
    public $account_type;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Register user
    public function register($first_name, $last_name, $email, $password) {
        // Combine first and last name
        $name = $first_name . ' ' . $last_name;
        
        // Check if email exists
        if ($this->emailExists($email)) {
            return false;
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $default_profile_pic = 'images/default-profile.png';
        
        // Insert query
        $query = "INSERT INTO " . $this->table . " 
                  (name, email, password, profile_pic) 
                  VALUES (:name, :email, :password, :profile_pic)";
                  
        try {
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':profile_pic', $default_profile_pic);
            
            // Execute query
            if ($stmt->execute()) {
                return true;
            }
            return false;
        } catch (PDOException $e) {
            echo 'Registration Error: ' . $e->getMessage();
            return false;
        }
    }
    
    // Login user
    public function login($email, $password) {
        // Query to check if email exists
        $query = "SELECT id, name, email, password, profile_pic, account_type 
                  FROM " . $this->table . " 
                  WHERE email = :email";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            // Bind parameter
            $stmt->bindParam(':email', $email);
            
            // Execute query
            $stmt->execute();
            
            // Check if email exists
            if ($stmt->rowCount() === 1) {
                $row = $stmt->fetch();
                
                // Verify password
                if (password_verify($password, $row['password'])) {
                    // Set properties
                    $this->id = $row['id'];
                    $this->name = $row['name'];
                    $this->email = $row['email'];
                    $this->profile_pic = $row['profile_pic'] ?: 'images/default-profile.png';
                    $this->account_type = $row['account_type'];
                    
                    return true;
                }
            }
            
            return false;
        } catch (PDOException $e) {
            echo 'Login Error: ' . $e->getMessage();
            return false;
        }
    }
    
    // Check if email exists
    public function emailExists($email) {
        // Query to check if email exists
        $query = "SELECT id FROM " . $this->table . " WHERE email = :email";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
}
