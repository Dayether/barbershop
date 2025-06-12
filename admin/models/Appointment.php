<?php
class Appointment {
    // Database connection and table name
    private $conn;
    private $table = 'appointments';

    // Object properties
    public $id;
    public $booking_reference;
    public $user_id;
    public $service_id;
    public $appointment_date;
    public $appointment_time;
    public $barber_id;
    public $client_name;
    public $client_email;
    public $client_phone;
    public $notes;
    public $status;
    public $created_at;

    // Constructor with DB connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Create appointment
    public function create() {
        // Create query
        $query = "INSERT INTO " . $this->table . " 
                  SET booking_reference = :booking_reference, 
                      user_id = :user_id,
                      service_id = :service_id, 
                      appointment_date = :appointment_date,
                      appointment_time = :appointment_time,
                      barber_id = :barber_id,
                      client_name = :client_name,
                      client_email = :client_email,
                      client_phone = :client_phone,
                      notes = :notes,
                      status = :status";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $this->booking_reference = htmlspecialchars(strip_tags($this->booking_reference));
        $this->user_id = $this->user_id ?? null;
        $this->service_id = htmlspecialchars(strip_tags($this->service_id));
        $this->appointment_date = htmlspecialchars(strip_tags($this->appointment_date));
        $this->appointment_time = htmlspecialchars(strip_tags($this->appointment_time));
        $this->barber_id = htmlspecialchars(strip_tags($this->barber_id));
        $this->client_name = htmlspecialchars(strip_tags($this->client_name));
        $this->client_email = htmlspecialchars(strip_tags($this->client_email));
        $this->client_phone = htmlspecialchars(strip_tags($this->client_phone));
        $this->notes = htmlspecialchars(strip_tags($this->notes));
        $this->status = htmlspecialchars(strip_tags($this->status));
        
        // Generate booking reference if not provided
        if(empty($this->booking_reference)) {
            $this->booking_reference = 'TIP' . strtoupper(substr(md5(uniqid()), 0, 8));
        }
        
        // Bind data
        $stmt->bindParam(':booking_reference', $this->booking_reference);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':service_id', $this->service_id);
        $stmt->bindParam(':appointment_date', $this->appointment_date);
        $stmt->bindParam(':appointment_time', $this->appointment_time);
        $stmt->bindParam(':barber_id', $this->barber_id);
        $stmt->bindParam(':client_name', $this->client_name);
        $stmt->bindParam(':client_email', $this->client_email);
        $stmt->bindParam(':client_phone', $this->client_phone);
        $stmt->bindParam(':notes', $this->notes);
        $stmt->bindParam(':status', $this->status);
        
        // Execute query
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        // Print error if something goes wrong
        printf("Error: %s.\n", $stmt->error);
        
        return false;
    }

    // Read single appointment by ID
    public function readSingle() {
        // FIX: Use appointment_id instead of id
        $query = "SELECT a.*, s.name AS service_name, b.name AS barber_name
                  FROM appointments a
                  LEFT JOIN services s ON a.service_id = s.service_id
                  LEFT JOIN barbers b ON a.barber_id = b.barber_id
                  WHERE a.appointment_id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return false;
        }

        // Set properties (add more as needed)
        $this->id = $row['appointment_id'];
        $this->booking_reference = $row['booking_reference'];
        $this->user_id = $row['user_id'];
        $this->service = $row['service_id'];
        $this->appointment_date = $row['appointment_date'];
        $this->appointment_time = $row['appointment_time'];
        $this->barber = $row['barber_id'];
        $this->client_name = $row['client_name'];
        $this->client_email = $row['client_email'];
        $this->client_phone = $row['client_phone'];
        $this->notes = $row['notes'];
        $this->status = $row['status'];
        $this->created_at = $row['created_at'];
        // Optionally set service_name and barber_name
        $this->service_name = $row['service_name'] ?? null;
        $this->barber_name = $row['barber_name'] ?? null;

        return true;
    }

    // Update appointment
    public function update() {
        $query = "UPDATE appointments SET 
            service_id = :service_id,
            appointment_date = :appointment_date,
            appointment_time = :appointment_time,
            barber_id = :barber_id,
            client_name = :client_name,
            client_email = :client_email,
            client_phone = :client_phone,
            notes = :notes,
            status = :status
            WHERE appointment_id = :appointment_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':service_id', $this->service_id);
        $stmt->bindParam(':appointment_date', $this->appointment_date);
        $stmt->bindParam(':appointment_time', $this->appointment_time);
        $stmt->bindParam(':barber_id', $this->barber_id);
        $stmt->bindParam(':client_name', $this->client_name);
        $stmt->bindParam(':client_email', $this->client_email);
        $stmt->bindParam(':client_phone', $this->client_phone);
        $stmt->bindParam(':notes', $this->notes);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':appointment_id', $this->id);
        return $stmt->execute();
    }

    // Delete appointment
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

    // Update status
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

    // Add to appointment history
    public function addHistory($action, $notes, $user_id = null, $staff_id = null) {
        $query = "INSERT INTO appointment_history 
                  SET appointment_id = :appointment_id, 
                      action = :action,
                      notes = :notes,
                      user_id = :user_id,
                      staff_id = :staff_id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':appointment_id', $this->id);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':staff_id', $staff_id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    // Get appointment history
    public function getHistory() {
        $query = "SELECT h.*, u.name as user_name, s.name as staff_name
                 FROM appointment_history h
                 LEFT JOIN users u ON h.user_id = u.id
                 LEFT JOIN users s ON h.staff_id = s.id
                 WHERE h.appointment_id = :appointment_id
                 ORDER BY h.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':appointment_id', $this->id);
        $stmt->execute();
        
        return $stmt;
    }

    // Dashboard stats: Appointments by status
    public static function getAppointmentStats($db) {
        $query = "SELECT 
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                    COUNT(*) as total
                  FROM appointments";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Dashboard stats: Upcoming appointments
    public static function getUpcomingAppointments($db, $limit = 5) {
        $query = "SELECT a.*, u.name as user_name 
                  FROM appointments a
                  LEFT JOIN users u ON a.user_id = u.id
                  WHERE a.appointment_date >= CURDATE()
                  AND a.status IN ('pending', 'confirmed')
                  ORDER BY a.appointment_date ASC, a.appointment_time ASC
                  LIMIT :limit";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }
}
?>
