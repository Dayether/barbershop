<?php
// Include Dashboard model
require_once 'models/Dashboard.php';

// Database connection
$db = new PDO('mysql:host=localhost;dbname=barbershop', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Initialize Dashboard object
$dashboard = new Dashboard($db);

// Get all the necessary data
$appointmentStats = $dashboard->getAppointmentStats();
$cashFlowData = $dashboard->getCashFlowData();
$profitLossData = $dashboard->getProfitLossData();
$completionRate = $dashboard->getCompletionRate();

// Replace this line:
// $latestAppointments = $dashboard->getLatestAppointments();

// With this direct query:
$latestAppointments = [];
$stmt = $db->prepare("
    SELECT 
        a.appointment_id,
        a.booking_reference,
        a.client_name,
        a.client_email,
        a.appointment_date,
        a.appointment_time,
        a.status,
        s.name AS service_name,
        a.service_id
    FROM appointments a
    LEFT JOIN services s ON a.service_id = s.service_id
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 5
");
$stmt->execute();
$latestAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$volumeStats = $dashboard->getMonthlyVolume();
$monthlyRevenue = $dashboard->getMonthlyRevenue();

// Format some values for display
$cashFlowTotal = number_format($cashFlowData['total'], 2);
$profitLossTotal = number_format($profitLossData['total'], 2);
$volumeTotal = number_format($volumeStats['current']);
$volumeChange = $volumeStats['change'];
$volumeChangeClass = ($volumeChange >= 0) ? 'positive' : 'negative';
$volumeChangeIcon = ($volumeChange >= 0) ? 'fa-arrow-up' : 'fa-arrow-down';

// Convert the data arrays to JSON for use in charts
$cashFlowDates = json_encode($cashFlowData['dates']);
$cashFlowAmounts = json_encode($cashFlowData['amounts']);
$profitLossMonths = json_encode($profitLossData['months']);
$profitLossRevenues = json_encode($profitLossData['revenues']);
?>

<div class="dashboard-welcome">
    <div class="welcome-text">
        <h1>Welcome to Tipuno Barbershop Dashboard</h1>
        <p>Here's what's happening with your business today</p>
    </div>
    <div class="dashboard-date">
        <?php echo date('l, F j, Y'); ?>
    </div>
</div>

<!-- Metrics Overview -->
<div class="dashboard-metrics">
    <div class="metric-card cash-flow">
        <div class="metric-header">
            <h3>Cash Flow</h3>
            <span class="metric-badge">+0.3%</span>
        </div>
        <div class="metric-value">
            $ <?php echo $cashFlowTotal; ?>
        </div>
        <div class="metric-period">
            1 Feb - Today
        </div>
        <div class="metric-chart">
            <canvas id="cashFlowChart"></canvas>
        </div>
    </div>
    
    <div class="metric-card profit-loss">
        <div class="metric-header">
            <h3>Profit & Loss</h3>
        </div>
        <div class="metric-value">
            $ <?php echo $profitLossTotal; ?>
        </div>
        <div class="metric-period">
            1 Feb - Today
        </div>
        <div class="metric-chart">
            <canvas id="profitLossChart"></canvas>
        </div>
    </div>
    
    <div class="metric-card volume">
        <div class="metric-header">
            <h3>Net Volume</h3>
            <span class="metric-badge <?php echo $volumeChangeClass; ?>">
                <i class="fas <?php echo $volumeChangeIcon; ?>"></i> <?php echo abs($volumeChange); ?>%
            </span>
        </div>
        <div class="metric-value">
            $ <?php echo $monthlyRevenue; ?>
        </div>
        <div class="metric-period">
            1 Feb - Today
        </div>
        <div class="metric-chart">
            <canvas id="volumeChart"></canvas>
        </div>
    </div>
</div>

<!-- Reports Completion & Cash Flow Details -->
<div class="dashboard-charts">
    <div class="chart-card completion-rate">
        <div class="chart-header">
            <h3>Reports Completion</h3>
        </div>
        <div class="chart-content">
            <div class="completion-gauge">
                <div class="gauge-value"><?php echo $completionRate['rate']; ?>%</div>
                <div class="gauge-circle">
                    <div class="gauge-fill" style="--percentage: <?php echo $completionRate['rate']; ?>"></div>
                </div>
            </div>
            <div class="completion-stats">
                <p>Reports completion rate for the given period is solid (<?php echo $completionRate['completed']; ?> out of <?php echo $completionRate['total']; ?> hrs logged)</p>
                <div class="missing-reports">
                    <span><?php echo $completionRate['missing']; ?> hrs missing</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="chart-card cash-flow-details">
        <div class="chart-header">
            <h3>Cash Flow</h3>
        </div>
        <div class="chart-content">
            <canvas id="cashFlowDetailChart"></canvas>
        </div>
    </div>
</div>

<!-- Latest Appointments -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3>Latest Appointments</h3>
        <div class="actions">
            <a href="admin_index.php?page=appointments" class="btn btn-outline btn-sm">View All</a>
        </div>
    </div>
    <div class="admin-card-body">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Client</th>
                        <th>Service</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Only show "No recent appointments found" if the array is empty or contains only empty/invalid rows
                    $hasAppointments = false;
                    if (!empty($latestAppointments) && is_array($latestAppointments)) {
                        foreach ($latestAppointments as $appointment) {
                            if (!empty($appointment['appointment_id'])) {
                                $hasAppointments = true;
                    ?>
                            <tr>
                                <td>
                                    <div class="booking-reference"><?php echo htmlspecialchars($appointment['booking_reference']); ?></div>
                                </td>
                                <td>
                                    <div class="client-info">
                                        <strong><?php echo htmlspecialchars($appointment['client_name']); ?></strong>
                                        <span class="email"><?php echo htmlspecialchars($appointment['client_email']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    // Display service name if available, fallback to service_id, fallback to empty string
                                    if (!empty($appointment['service_name'])) {
                                        echo htmlspecialchars($appointment['service_name']);
                                    } elseif (!empty($appointment['service_id'])) {
                                        // Try to fetch the service name from the database if not present in the row
                                        try {
                                            $db = new PDO('mysql:host=localhost;dbname=barbershop', 'root', '');
                                            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                                            $stmt = $db->prepare("SELECT name FROM services WHERE service_id = ?");
                                            $stmt->execute([$appointment['service_id']]);
                                            $service = $stmt->fetch(PDO::FETCH_ASSOC);
                                            echo $service ? htmlspecialchars($service['name']) : htmlspecialchars($appointment['service_id']);
                                        } catch (Exception $e) {
                                            echo htmlspecialchars($appointment['service_id']);
                                        }
                                    } else {
                                        echo '';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?> at
                                    <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </td>
                            </tr>
                    <?php
                            }
                        }
                    }
                    if (!$hasAppointments): ?>
                        <tr>
                            <td colspan="5" class="text-center">No recent appointments found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set common chart options for light theme
    Chart.defaults.color = '#5a5755';
    Chart.defaults.font.family = "'Montserrat', 'Segoe UI', sans-serif";
    
    // Cash Flow Mini Chart
    const cashFlowCtx = document.getElementById('cashFlowChart').getContext('2d');
    new Chart(cashFlowCtx, {
        type: 'bar',
        data: {
            labels: <?php echo $cashFlowDates; ?>,
            datasets: [{
                label: 'Revenue',
                data: <?php echo $cashFlowAmounts; ?>,
                backgroundColor: 'rgba(200, 166, 86, 0.4)',
                borderColor: 'rgba(200, 166, 86, 1)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    display: false
                },
                y: {
                    display: false,
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(29, 26, 22, 0.8)',
                    titleFont: {
                        family: "'Montserrat', sans-serif",
                        weight: 'bold'
                    },
                    bodyFont: {
                        family: "'Montserrat', sans-serif"
                    },
                    callbacks: {
                        title: function(tooltipItem) {
                            return tooltipItem[0].label;
                        },
                        label: function(context) {
                            return '$ ' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            },
            elements: {
                line: {
                    tension: 0.4
                }
            }
        }
    });
    
    // Profit & Loss Chart
    const profitLossCtx = document.getElementById('profitLossChart').getContext('2d');
    new Chart(profitLossCtx, {
        type: 'bar',
        data: {
            labels: <?php echo $profitLossMonths; ?>,
            datasets: [{
                label: 'Revenue',
                data: <?php echo $profitLossRevenues; ?>,
                backgroundColor: 'rgba(114, 47, 55, 0.4)',
                borderColor: 'rgba(114, 47, 55, 1)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    display: false
                },
                y: {
                    display: false,
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(29, 26, 22, 0.8)',
                    callbacks: {
                        label: function(context) {
                            return '$ ' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            }
        }
    });
    
    // Volume Chart
    const volumeCtx = document.getElementById('volumeChart').getContext('2d');
    new Chart(volumeCtx, {
        type: 'line',
        data: {
            labels: ['Nov 1', '', '', '', '', '', '', '', '', '', '', '', '', '', 'Nov 30'],
            datasets: [{
                label: 'Volume',
                data: [0, 1000, 1200, 1300, 1400, 1500, 1600, 1700, 2000, 2200, 2400, 2500, 2600, 3000, 4900],
                backgroundColor: 'transparent',
                borderColor: '#2196F3',
                borderWidth: 2,
                pointBackgroundColor: '#2196F3',
                pointRadius: 0,
                pointHoverRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    display: false
                },
                y: {
                    display: false,
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(29, 26, 22, 0.8)',
                    callbacks: {
                        label: function(context) {
                            return '$ ' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            },
            elements: {
                line: {
                    tension: 0.4
                }
            }
        }
    });
    
    // Cash Flow Detailed Chart
    const cashFlowDetailCtx = document.getElementById('cashFlowDetailChart').getContext('2d');
    new Chart(cashFlowDetailCtx, {
        type: 'bar',
        data: {
            labels: ['Nov 1', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'Today'],
            datasets: [{
                label: 'Income',
                data: [400, 520, 380, 450, 600, 450, 380, 400, 520, 380, 450, 600, 450, 380, 450, 600, 450, 380, 450, 600, 450, 380, 450, 600, 450, 380, 450, 600, 450, 380],
                backgroundColor: '#2196F3',
                borderColor: '#2196F3',
                borderWidth: 1
            }, {
                label: 'Expenses',
                data: [-300, -400, -300, -350, -500, -400, -300, -300, -400, -300, -350, -500, -400, -300, -350, -500, -400, -300, -350, -500, -400, -300, -350, -500, -400, -300, -350, -500, -400, -300],
                backgroundColor: '#F44336',
                borderColor: '#F44336',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    grid: {
                        color: 'rgba(200, 200, 200, 0.2)'
                    },
                    ticks: {
                        callback: function(value) {
                            if (value === 0) return '0';
                            return value > 0 ? '+' + value : value;
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    backgroundColor: 'rgba(29, 26, 22, 0.8)',
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': $' + context.parsed.y;
                        }
                    }
                }
            }
        }
    });
});
</script>

<!-- Add CSS for the dashboard -->
<style>
/* Dashboard Styles */
.dashboard-welcome {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.welcome-text h1 {
    font-size: 1.8rem;
    margin-bottom: 5px;
    color: var(--secondary-color);
}

.welcome-text p {
    color: var(--text-medium);
    font-size: 1rem;
}

.dashboard-date {
    font-size: 1rem;
    color: var(--text-muted);
    background: var(--light-bg);
    padding: 8px 16px;
    border-radius: 50px;
}

/* Metrics Grid */
.dashboard-metrics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    margin-bottom: 25px;
}

.metric-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    position: relative;
    overflow: hidden;
}

.metric-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.metric-header h3 {
    margin: 0;
    font-size: 1.1rem;
    color: var(--text-dark);
}

.metric-badge {
    font-size: 0.8rem;
    padding: 3px 10px;
    border-radius: 50px;
    font-weight: 600;
}

.metric-badge.positive {
    background-color: rgba(76, 175, 80, 0.1);
    color: #4CAF50;
}

.metric-badge.negative {
    background-color: rgba(244, 67, 54, 0.1);
    color: #F44336;
}

.metric-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--secondary-color);
    margin-bottom: 5px;
}

.metric-period {
    font-size: 0.8rem;
    color: var(--text-muted);
    margin-bottom: 15px;
}

.metric-chart {
    height: 70px;
    position: relative;
}

/* Chart cards */
.dashboard-charts {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    margin-bottom: 25px;
}

.chart-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
}

.chart-header {
    margin-bottom: 20px;
}

.chart-header h3 {
    margin: 0;
    font-size: 1.1rem;
    color: var(--text-dark);
}

.chart-content {
    position: relative;
    min-height: 200px;
}

/* Completion gauge */
.completion-gauge {
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
    width: 120px;
    height: 120px;
    margin: 0 auto 20px;
}

.gauge-value {
    position: absolute;
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--secondary-color);
}

.gauge-circle {
    position: relative;
    width: 100%;
    height: 100%;
}

.gauge-fill {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: conic-gradient(
        var(--primary-color) calc(var(--percentage) * 1%),
        #e9ecef calc(var(--percentage) * 1%)
    );
    mask: radial-gradient(transparent 55%, black 56%);
    -webkit-mask: radial-gradient(transparent 55%, black 56%);
}

.completion-stats {
    text-align: center;
}

.completion-stats p {
    font-size: 0.9rem;
    color: var(--text-medium);
    margin-bottom: 10px;
    padding: 0 20px;
}

.missing-reports {
    background-color: rgba(244, 67, 54, 0.1);
    color: #F44336;
    font-size: 0.85rem;
    padding: 5px 10px;
    border-radius: 50px;
    display: inline-block;
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .dashboard-metrics,
    .dashboard-charts {
        grid-template-columns: 1fr;
    }
    
    .welcome-text h1 {
        font-size: 1.5rem;
    }
}

@media (max-width: 768px) {
    .dashboard-welcome {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
}
</style>
