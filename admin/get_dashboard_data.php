<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['account_type']) || $_SESSION['user']['account_type'] != 1) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

require_once '../includes/db_connection.php';

// Initialize response array
$response = [];

// Get total bookings
$bookingsQuery = "SELECT 
                    COUNT(*) as total_bookings,
                    SUM(CASE WHEN DATE(created_at) >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY) THEN 1 ELSE 0 END) as weekly_bookings,
                    SUM(CASE WHEN DATE(created_at) >= DATE_SUB(CURRENT_DATE, INTERVAL 14 DAY) AND DATE(created_at) < DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY) THEN 1 ELSE 0 END) as previous_week_bookings
                  FROM appointments";
$bookingsResult = $conn->query($bookingsQuery);
$bookingsData = $bookingsResult->fetch_assoc();

$response['bookings'] = [
    'total' => (int)$bookingsData['total_bookings'],
    'weekly' => (int)$bookingsData['weekly_bookings'],
    'previous_week' => (int)$bookingsData['previous_week_bookings'],
    'percentage_change' => $bookingsData['previous_week_bookings'] > 0 ? 
        round((($bookingsData['weekly_bookings'] - $bookingsData['previous_week_bookings']) / $bookingsData['previous_week_bookings']) * 100, 1) : 0
];

// Get revenue
$revenueQuery = "SELECT 
                    SUM(total_amount) as total_revenue,
                    SUM(CASE WHEN DATE(created_at) >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY) THEN total_amount ELSE 0 END) as monthly_revenue,
                    SUM(CASE WHEN DATE(created_at) >= DATE_SUB(CURRENT_DATE, INTERVAL 60 DAY) AND DATE(created_at) < DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY) THEN total_amount ELSE 0 END) as previous_month_revenue
                 FROM orders 
                 WHERE status != 'cancelled'";
$revenueResult = $conn->query($revenueQuery);
$revenueData = $revenueResult->fetch_assoc();

$response['revenue'] = [
    'total' => (float)$revenueData['total_revenue'],
    'monthly' => (float)$revenueData['monthly_revenue'],
    'previous_month' => (float)$revenueData['previous_month_revenue'],
    'percentage_change' => $revenueData['previous_month_revenue'] > 0 ? 
        round((($revenueData['monthly_revenue'] - $revenueData['previous_month_revenue']) / $revenueData['previous_month_revenue']) * 100, 1) : 0
];

// Get products sold
$productsQuery = "SELECT 
                    SUM(oi.quantity) as total_products,
                    SUM(CASE WHEN DATE(o.created_at) >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY) THEN oi.quantity ELSE 0 END) as weekly_products,
                    SUM(CASE WHEN DATE(o.created_at) >= DATE_SUB(CURRENT_DATE, INTERVAL 14 DAY) AND DATE(o.created_at) < DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY) THEN oi.quantity ELSE 0 END) as previous_week_products
                  FROM order_items oi
                  JOIN orders o ON oi.order_id = o.id
                  WHERE o.status != 'cancelled'";
$productsResult = $conn->query($productsQuery);
$productsData = $productsResult->fetch_assoc();

$response['products'] = [
    'total' => (int)$productsData['total_products'],
    'weekly' => (int)$productsData['weekly_products'],
    'previous_week' => (int)$productsData['previous_week_products'],
    'percentage_change' => $productsData['previous_week_products'] > 0 ? 
        round((($productsData['weekly_products'] - $productsData['previous_week_products']) / $productsData['previous_week_products']) * 100, 1) : 0
];

// Get new clients
$clientsQuery = "SELECT 
                    COUNT(*) as total_clients,
                    SUM(CASE WHEN DATE(created_at) >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY) THEN 1 ELSE 0 END) as monthly_clients,
                    SUM(CASE WHEN DATE(created_at) >= DATE_SUB(CURRENT_DATE, INTERVAL 60 DAY) AND DATE(created_at) < DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY) THEN 1 ELSE 0 END) as previous_month_clients
                 FROM users
                 WHERE account_type != '1' OR account_type IS NULL";
$clientsResult = $conn->query($clientsQuery);
$clientsData = $clientsResult->fetch_assoc();

$response['clients'] = [
    'total' => (int)$clientsData['total_clients'],
    'monthly' => (int)$clientsData['monthly_clients'],
    'previous_month' => (int)$clientsData['previous_month_clients'],
    'percentage_change' => $clientsData['previous_month_clients'] > 0 ? 
        round((($clientsData['monthly_clients'] - $clientsData['previous_month_clients']) / $clientsData['previous_month_clients']) * 100, 1) : 0
];

// Get weekly revenue data for chart
$weeklyRevenueQuery = "SELECT 
                        DAYNAME(created_at) as day_name, 
                        SUM(total_amount) as daily_revenue
                      FROM orders
                      WHERE DATE(created_at) >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY) 
                      AND status != 'cancelled'
                      GROUP BY DAYNAME(created_at)
                      ORDER BY DAYOFWEEK(created_at)";
$weeklyRevenueResult = $conn->query($weeklyRevenueQuery);

$days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
$weeklyRevenue = array_fill_keys($days, 0);

while ($row = $weeklyRevenueResult->fetch_assoc()) {
    $weeklyRevenue[$row['day_name']] = (float)$row['daily_revenue'];
}

$response['weekly_revenue'] = [
    'labels' => array_keys($weeklyRevenue),
    'data' => array_values($weeklyRevenue)
];

// Get service breakdown data for pie chart
$serviceQuery = "SELECT 
                    service, 
                    COUNT(*) as service_count 
                 FROM appointments 
                 GROUP BY service 
                 ORDER BY service_count DESC";
$serviceResult = $conn->query($serviceQuery);

$serviceLabels = [];
$serviceData = [];

while ($row = $serviceResult->fetch_assoc()) {
    $serviceLabels[] = $row['service'];
    $serviceData[] = (int)$row['service_count'];
}

$response['service_breakdown'] = [
    'labels' => $serviceLabels,
    'data' => $serviceData
];

// Get product inventory status
$inventoryQuery = "SELECT 
                    name, 
                    stock 
                  FROM products 
                  ORDER BY stock DESC";
$inventoryResult = $conn->query($inventoryQuery);

$productLabels = [];
$stockData = [];

// Define threshold for reorder level (30% of current stock as an example)
$reorderLevels = [];

while ($row = $inventoryResult->fetch_assoc()) {
    $productLabels[] = $row['name'];
    $stockData[] = (int)$row['stock'];
    // Example reorder level - adjust as needed
    $reorderLevels[] = max(5, round((int)$row['stock'] * 0.3));
}

$response['inventory'] = [
    'labels' => $productLabels,
    'stock' => $stockData,
    'reorder_levels' => $reorderLevels
];

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
