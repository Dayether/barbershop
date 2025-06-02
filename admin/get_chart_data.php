<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['account_type']) || $_SESSION['user']['account_type'] != 1) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

require_once '../includes/db_connection.php';

// Initialize response
$response = [];

// Get chart type and period
$chart = isset($_GET['chart']) ? $_GET['chart'] : '';
$period = isset($_GET['period']) ? $_GET['period'] : 'week';

// Process different chart types
switch ($chart) {
    case 'revenue':
        getRevenueData($conn, $period, $response);
        break;
    case 'services':
        getServicesData($conn, $response);
        break;
    case 'inventory':
        getInventoryData($conn, $response);
        break;
    default:
        $response = ['error' => 'Invalid chart type'];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;

/**
 * Get revenue data based on the selected period
 */
function getRevenueData($conn, $period, &$response) {
    switch($period) {
        case 'week':
            // Get daily revenue for the current week
            $sql = "SELECT 
                      DATE_FORMAT(created_at, '%W') AS day_name,
                      SUM(total_amount) AS revenue
                    FROM orders
                    WHERE 
                      YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1) 
                      AND status != 'cancelled'
                    GROUP BY day_name
                    ORDER BY FIELD(day_name, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
            break;
            
        case 'month':
            // Get weekly revenue for the current month
            $sql = "SELECT 
                      CONCAT('Week ', FLOOR((DAY(created_at)-1)/7)+1) AS week_label,
                      SUM(total_amount) AS revenue
                    FROM orders
                    WHERE 
                      YEAR(created_at) = YEAR(CURDATE())
                      AND MONTH(created_at) = MONTH(CURDATE())
                      AND status != 'cancelled'
                    GROUP BY week_label
                    ORDER BY MIN(created_at)";
            break;
            
        case 'year':
            // Get monthly revenue for the current year
            $sql = "SELECT 
                      DATE_FORMAT(created_at, '%b') AS month_name,
                      SUM(total_amount) AS revenue
                    FROM orders
                    WHERE 
                      YEAR(created_at) = YEAR(CURDATE())
                      AND status != 'cancelled'
                    GROUP BY MONTH(created_at), month_name
                    ORDER BY MONTH(created_at)";
            break;
            
        default:
            // Default to weekly data
            $sql = "SELECT 
                      DATE_FORMAT(created_at, '%W') AS day_name,
                      SUM(total_amount) AS revenue
                    FROM orders
                    WHERE 
                      YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1) 
                      AND status != 'cancelled'
                    GROUP BY day_name
                    ORDER BY FIELD(day_name, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
    }
    
    // Execute query
    $result = $conn->query($sql);
    
    if (!$result) {
        $response = ['error' => 'Database error: ' . $conn->error];
        return;
    }
    
    // Process results
    $labels = [];
    $data = [];
    
    // Define default labels based on period
    $defaultLabels = [];
    $defaultData = [];
    
    switch($period) {
        case 'week':
            $defaultLabels = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            $defaultData = array_fill(0, 7, 0);
            break;
        case 'month':
            // Determine number of weeks in current month
            $daysInMonth = date('t');
            $weeksInMonth = ceil($daysInMonth / 7);
            for ($i = 1; $i <= $weeksInMonth; $i++) {
                $defaultLabels[] = "Week " . $i;
            }
            $defaultData = array_fill(0, count($defaultLabels), 0);
            break;
        case 'year':
            $defaultLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $defaultData = array_fill(0, 12, 0);
            break;
    }
    
    // Fill with actual data where available
    while ($row = $result->fetch_assoc()) {
        $index = array_search($row['day_name'] ?? $row['week_label'] ?? $row['month_name'], $defaultLabels);
        if ($index !== false) {
            $defaultData[$index] = (float)$row['revenue'];
        }
    }
    
    $response = [
        'labels' => $defaultLabels,
        'data' => $defaultData
    ];
}

/**
 * Get services data for the pie/doughnut chart
 */
function getServicesData($conn, &$response) {
    $sql = "SELECT 
              service,
              COUNT(*) as count
            FROM appointments
            WHERE status != 'cancelled'
            GROUP BY service
            ORDER BY count DESC";
            
    $result = $conn->query($sql);
    
    if (!$result) {
        $response = ['error' => 'Database error: ' . $conn->error];
        return;
    }
    
    $labels = [];
    $data = [];
    
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['service'];
        $data[] = (int)$row['count'];
    }
    
    $response = [
        'labels' => $labels,
        'data' => $data
    ];
}

/**
 * Get inventory data for product stock levels
 */
function getInventoryData($conn, &$response) {
    $sql = "SELECT 
              name,
              stock
            FROM products
            ORDER BY name ASC";
            
    $result = $conn->query($sql);
    
    if (!$result) {
        $response = ['error' => 'Database error: ' . $conn->error];
        return;
    }
    
    $labels = [];
    $stock = [];
    $reorderLevels = [];
    
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['name'];
        $stock[] = (int)$row['stock'];
        // Calculate a simple reorder level (example: 20% of current stock)
        $reorderLevels[] = max(5, round($row['stock'] * 0.2));
    }
    
    $response = [
        'labels' => $labels,
        'stock' => $stock,
        'reorderLevels' => $reorderLevels
    ];
}
