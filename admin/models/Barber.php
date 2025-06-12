<?php
class Barber {
    // Database connection and table name
    private $conn;
    private $table = 'barbers';

    // Object properties
    public $id;
    public $name;
    public $bio;
    public $image;
    public $active;

    // Constructor with DB connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Read all barbers with pagination
    public function read($page = 1, $per_page = 10) {
        // Calculate offset for pagination
        $offset = ($page - 1) * $per_page;
        
        // Create query
        $query = "SELECT * FROM " . $this->table . "
                  ORDER BY barber_id DESC
                  LIMIT :limit OFFSET :offset";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind values
        $stmt->bindParam(':limit', $per_page, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        
        // Execute query
        $stmt->execute();
        
        return $stmt;
    }

    // Count total barbers
    public function count() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }

    // Read single barber
    public function readSingle() {
        // Create query
        $query = "SELECT * FROM " . $this->table . " WHERE barber_id = :id";
        
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
            $this->bio = $row['bio'];
            $this->image = $row['image'];
            $this->active = $row['active'];
            
            return true;
        }
        
        return false;
    }

    // Create barber
    public function create() {
        // Create query
        $query = "INSERT INTO " . $this->table . " 
                  SET name = :name, 
                      bio = :bio, 
                      image = :image, 
                      active = :active";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->bio = htmlspecialchars(strip_tags($this->bio));
        $this->image = htmlspecialchars(strip_tags($this->image));
        $this->active = htmlspecialchars(strip_tags($this->active));
        
        // Bind data
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':bio', $this->bio);
        $stmt->bindParam(':image', $this->image);
        $stmt->bindParam(':active', $this->active);
        
        // Execute query
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        // Print error if something goes wrong
        printf("Error: %s.\n", $stmt->error);
        
        return false;
    }

    // Update barber
    public function update() {
        // Create query
        $query = "UPDATE " . $this->table . "
                  SET name = :name, 
                      bio = :bio, 
                      active = :active";
        
        // Add image to update if not empty
        if(!empty($this->image)) {
            $query .= ", image = :image";
        }
        
        $query .= " WHERE barber_id = :id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->bio = htmlspecialchars(strip_tags($this->bio));
        $this->active = htmlspecialchars(strip_tags($this->active));
        
        // Bind data
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':bio', $this->bio);
        $stmt->bindParam(':active', $this->active);
        
        // Bind image if not empty
        if(!empty($this->image)) {
            $this->image = htmlspecialchars(strip_tags($this->image));
            $stmt->bindParam(':image', $this->image);
        }
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        // Print error if something goes wrong
        printf("Error: %s.\n", $stmt->error);
        
        return false;
    }

    // Delete barber
    public function delete() {
        // Get barber image before deleting
        $this->readSingle();
        $image_path = $this->image;
        
        // Create query
        $query = "DELETE FROM " . $this->table . " WHERE barber_id = :id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // Bind data
        $stmt->bindParam(':id', $this->id);
        
        // Execute query
        if($stmt->execute()) {
            // Delete image file if exists
            if($image_path && file_exists("../$image_path")) {
                unlink("../$image_path");
            }
            return true;
        }
        
        // Print error if something goes wrong
        printf("Error: %s.\n", $stmt->error);
        
        return false;
    }

    // Toggle active status
    public function toggleActive() {
        // Read current status
        $this->readSingle();
        
        // Toggle status
        $this->active = $this->active ? 0 : 1;
        
        // Create query
        $query = "UPDATE " . $this->table . " SET active = :active WHERE barber_id = :id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind data
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':active', $this->active);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    // Get all active barbers (for dropdowns)
    public static function getActiveBarbers($db) {
        $query = "SELECT barber_id, name FROM barbers WHERE active = 1 ORDER BY name ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
}
