<?php
class Dashboard {
    // Database connection
    private $conn;
    
    // Constructor with DB connection
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Get appointment statistics
    public function getAppointmentStats() {
        try {
            $query = "SELECT 
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                        COUNT(*) as total
                      FROM appointments";
                      
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [
                'pending' => 0,
                'confirmed' => 0,
                'completed' => 0,
                'cancelled' => 0,
                'total' => 0
            ];
        }
    }
    
    // Get cash flow data for chart
    public function getCashFlowData() {
        try {
            $query = "SELECT 
                        DATE(created_at) as date,
                        SUM(total_amount) as amount
                      FROM orders
                      WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                      AND status != 'cancelled'
                      GROUP BY DATE(created_at)
                      ORDER BY date";
                      
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format the data for the chart
            $dates = [];
            $amounts = [];
            
            foreach ($results as $row) {
                $dates[] = date('M d', strtotime($row['date']));
                $amounts[] = (float) $row['amount'];
            }
            
            return [
                'dates' => $dates,
                'amounts' => $amounts,
                'total' => array_sum($amounts)
            ];
        } catch (PDOException $e) {
            return [
                'dates' => [],
                'amounts' => [],
                'total' => 0
            ];
        }
    }
    
    // Get profit/loss data
    public function getProfitLossData() {
        try {
            $query = "SELECT 
                        DATE_FORMAT(created_at, '%b') as month,
                        SUM(total_amount) as revenue
                      FROM orders
                      WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                      AND status != 'cancelled'
                      GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                      ORDER BY created_at";
                      
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format the data for the chart
            $months = [];
            $revenues = [];
            
            foreach ($results as $row) {
                $months[] = $row['month'];
                $revenues[] = (float) $row['revenue'];
            }
            
            return [
                'months' => $months,
                'revenues' => $revenues,
                'total' => array_sum($revenues)
            ];
        } catch (PDOException $e) {
            return [
                'months' => [],
                'revenues' => [],
                'total' => 0
            ];
        }
    }
    
    // Get completion rate data
    public function getCompletionRate() {
        try {
            $query = "SELECT 
                        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                        COUNT(*) as total
                      FROM appointments";
                      
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $completionRate = ($result['total'] > 0) ? 
                  round(($result['completed'] / $result['total']) * 100) : 0;
                  
            return [
                'rate' => $completionRate,
                'completed' => $result['completed'],
                'total' => $result['total'],
                'missing' => $result['total'] - $result['completed']
            ];
        } catch (PDOException $e) {
            return [
                'rate' => 0,
                'completed' => 0,
                'total' => 0,
                'missing' => 0
            ];
        }
    }
    
    // Get latest appointments
    public function getLatestAppointments($limit = 5) {
        try {
            $query = "SELECT a.*, u.name as user_name 
                      FROM appointments a
                      LEFT JOIN users u ON a.user_id = u.id
                      ORDER BY a.created_at DESC
                      LIMIT :limit";
                      
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // Get monthly volume stats
    public function getMonthlyVolume() {
        try {
            $query = "SELECT 
                        COUNT(*) as count
                      FROM appointments
                      WHERE MONTH(appointment_date) = MONTH(CURRENT_DATE())
                      AND YEAR(appointment_date) = YEAR(CURRENT_DATE())";
                      
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $currentMonth = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?: 0;
            
            $query = "SELECT 
                        COUNT(*) as count
                      FROM appointments
                      WHERE MONTH(appointment_date) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
                      AND YEAR(appointment_date) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))";
                      
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $lastMonth = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?: 0;
            
            $percentChange = 0;
            if ($lastMonth > 0) {
                $percentChange = round((($currentMonth - $lastMonth) / $lastMonth) * 100);
            }
            
            return [
                'current' => $currentMonth,
                'previous' => $lastMonth,
                'change' => $percentChange
            ];
        } catch (PDOException $e) {
            return [
                'current' => 0,
                'previous' => 0,
                'change' => 0
            ];
        }
    }
    
    // Get monthly revenue
    public function getMonthlyRevenue() {
        try {
            $query = "SELECT 
                        SUM(total_amount) as total
                      FROM orders
                      WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
                      AND YEAR(created_at) = YEAR(CURRENT_DATE())
                      AND status != 'cancelled'";
                      
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['total'] ?: 0;
        } catch (PDOException $e) {
            return 0;
        }
    }
}
