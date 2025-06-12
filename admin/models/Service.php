<?php
class Service {
    private $conn;
    public $service_id, $name, $description, $duration, $price, $image, $active;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getById($service_id) {
        $stmt = $this->conn->prepare("SELECT * FROM services WHERE service_id = ?");
        $stmt->bind_param("i", $service_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // ...other methods as needed...
}
