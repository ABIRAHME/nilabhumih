<?php
session_start();
require_once 'db-parameters.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Initialize variables with default values
$total_revenue = ['normal' => 0, 'educational' => 0, 'corporate' => 0];
$monthly_revenue = [];
$monthly_bookings = [];

try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Get revenue from normal bookings
    $stmt = $conn->prepare("SELECT 
        SUM(total_amount) as total_amount,
        SUM(total_amount) as total_paid
        FROM bookings WHERE payment_status = 'completed'");
    $stmt->execute();
    $result = $stmt->fetch();
    $total_revenue['normal'] = $result['total_amount'] ?? 0;
    
    // Get revenue from educational/corporate bookings
    $stmt = $conn->prepare("SELECT 
        SUM(CASE WHEN LOWER(package_type) = 'educational' THEN total_amount ELSE 0 END) as educational_total,
        SUM(CASE WHEN LOWER(package_type) = 'educational' THEN payment_amount ELSE 0 END) as educational_paid,
        SUM(CASE WHEN LOWER(package_type) = 'corporate' THEN total_amount ELSE 0 END) as corporate_total,
        SUM(CASE WHEN LOWER(package_type) = 'corporate' THEN payment_amount ELSE 0 END) as corporate_paid,
        SUM(CASE WHEN partial_payment = 1 THEN payment_amount ELSE 0 END) as total_partial_revenue
        FROM booking_sch_cor WHERE LOWER(payment_status) = 'completed'");
    $stmt->execute();
    $result = $stmt->fetch();
    $total_revenue['educational'] = $result['educational_total'] ?? 0;
    $total_revenue['corporate'] = $result['corporate_total'] ?? 0;
    $total_paid_revenue['educational'] = $result['educational_paid'] ?? 0;
    $total_paid_revenue['corporate'] = $result['corporate_paid'] ?? 0;
    $total_partial_revenue = $result['total_partial_revenue'] ?? 0;
    
    // Calculate total and due revenue
    $total_all_revenue = $total_revenue['normal'] + $total_revenue['educational'] + $total_revenue['corporate'];
    $total_paid_revenue = $total_revenue['normal'] + $total_paid_revenue['educational'] + $total_paid_revenue['corporate'];
    $total_due_revenue = $total_all_revenue - $total_paid_revenue;
    
    // Get monthly revenue data for the past 12 months
    $stmt = $conn->prepare("SELECT 
        DATE_FORMAT(payment_date, '%Y-%m') as month,
        SUM(total_amount) as revenue,
        COUNT(*) as bookings
        FROM bookings 
        WHERE payment_status = 'completed'
        AND payment_date IS NOT NULL
        AND payment_date >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
        ORDER BY month ASC");
    $stmt->execute();
    $normal_monthly = $stmt->fetchAll();
    
    // Get monthly revenue data for educational/corporate bookings
    $stmt = $conn->prepare("SELECT 
        DATE_FORMAT(payment_date, '%Y-%m') as month,
        SUM(total_amount) as revenue,
        COUNT(*) as bookings
        FROM booking_sch_cor 
        WHERE payment_status = 'completed'
        AND payment_date IS NOT NULL
        AND payment_date >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
        ORDER BY month ASC");
    $stmt->execute();
    $edu_cor_monthly = $stmt->fetchAll();
    
    // Combine and process monthly data
    $months = [];
    foreach (array_merge($normal_monthly, $edu_cor_monthly) as $data) {
        if (!empty($data['month'])) {
            $month = $data['month'];
            if (!isset($months[$month])) {
                $months[$month] = ['revenue' => 0, 'bookings' => 0];
            }
            $months[$month]['revenue'] += (float)$data['revenue'];
            $months[$month]['bookings'] += (int)$data['bookings'];
        }
    }
    ksort($months);
    
    // Format data for monthly revenue chart
    $monthly_revenue = [];
    foreach ($months as $month => $data) {
        $timestamp = strtotime($month . '-01') * 1000; // Convert to JavaScript timestamp
        $monthly_revenue[] = [
            'x' => $timestamp,
            'y' => (float)$data['revenue']
        ];
    }
    
    // Format data for monthly bookings chart
    $monthly_bookings = [];
    foreach ($months as $month => $data) {
        $timestamp = strtotime($month . '-01') * 1000; // Convert to JavaScript timestamp
        $monthly_bookings[] = [
            'x' => $timestamp,
            'y' => (int)$data['bookings']
        ];
    }
    
    
    
} catch(PDOException $e) {
    $error_message = 'Database error: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Statistics - Nilabhoomi Tours and Travels</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1e40af',
                        secondary: '#4f46e5'
                    }
                }
            }
        }
        
        window.onload = function() {
            var revenueChart = new CanvasJS.Chart("revenueChartContainer", {
                animationEnabled: true,
                theme: "light2",
                title: {
                    text: "Revenue Distribution"
                },
                axisY: {
                    title: "Revenue (BDT)",
                    includeZero: true
                },
                data: [{
                    type: "column",
                    dataPoints: [
                        { label: "Normal Tours", y: <?php echo $total_revenue['normal']; ?> },
                        { label: "Educational Tours", y: <?php echo $total_revenue['educational']; ?> },
                        { label: "Corporate Tours", y: <?php echo $total_revenue['corporate']; ?> }
                    ]
                }]
            });
            revenueChart.render();
            
            var monthlyChart = new CanvasJS.Chart("monthlyChartContainer", {
                animationEnabled: true,
                theme: "light2",
                title: {
                    text: "Monthly Revenue Trend"
                },
                axisX: {
                    type: "datetime",
                    valueFormatString: "MMM YYYY"
                },
                axisY: {
                    title: "Revenue (BDT)",
                    includeZero: true
                },
                data: [{
                    type: "spline",
                    xValueType: "dateTime",
                    dataPoints: <?php echo json_encode($monthly_revenue); ?>
                }]
            });
            monthlyChart.render();

            var revenuePieChart = new CanvasJS.Chart("revenuePieChartContainer", {
                animationEnabled: true,
                theme: "light2",
                title: {
                    text: "Revenue Distribution by Type"
                },
                data: [{
                    type: "pie",
                    showInLegend: true,
                    indexLabel: "{label}: {y}",
                    indexLabelPlacement: "inside",
                    dataPoints: [
                        { label: "Total Paid Revenue", y: <?php echo $total_paid_revenue; ?> },
                        { label: "Total Due Revenue", y: <?php echo $total_due_revenue; ?> },
                        { label: "Total Partial Revenue", y: <?php echo $total_partial_revenue;?>}
                    ]
                }]
            });
            revenuePieChart.render();

            var toursPieChart = new CanvasJS.Chart("toursPieChartContainer", {
                animationEnabled: true,
                theme: "light2",
                title: {
                    text: "Revenue by Tour Category"
                },
                data: [{
                    type: "pie",
                    showInLegend: true,
                    indexLabel: "{label}: {y}",
                    indexLabelPlacement: "inside",
                    dataPoints: [
                        { label: "Normal Tours", y: <?php echo $total_revenue['normal']; ?> },
                        { label: "Educational Tours", y: <?php echo $total_revenue['educational']; ?> },
                        { label: "Corporate Tours", y: <?php echo $total_revenue['corporate']; ?> }
                    ]
                }]
            });
            toursPieChart.render();
        }
    </script>
</head>
<body class="bg-gradient-to-r from-blue-50 to-indigo-50 min-h-screen">
    <?php include('partials/navigation.php'); ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 py-8 md:ml-64">
        <!-- Statistics Section -->
        <section class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Payment Statistics</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Statistics Cards -->
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow duration-300">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Total Revenue</h3>
                    <p class="text-3xl font-bold text-green-400">BDT <?php echo number_format($total_all_revenue, 2); ?> Tk</p>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow duration-300">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Total Partial Revenue</h3>
                    <p class="text-3xl font-bold text-amber-400">BDT <?php echo number_format($total_partial_revenue, 2); ?> Tk</p>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow duration-300">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Total Due Revenue</h3>
                    <p class="text-3xl font-bold text-blue-400">BDT <?php echo number_format($total_due_revenue, 2); ?> Tk</p>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow duration-300">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Normal Tours Revenue</h3>
                    <p class="text-3xl font-bold text-green-400">BDT <?php echo number_format($total_revenue['normal'], 2); ?> Tk</p>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow duration-300">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Educational Tours Revenue</h3>
                    <p class="text-3xl font-bold text-amber-400">BDT <?php echo number_format($total_revenue['educational'], 2); ?> TK</p>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow duration-300">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Corporate Tours Revenue</h3>
                    <p class="text-3xl font-bold text-blue-400">BDT <?php echo number_format($total_revenue['corporate'], 2); ?> Tk</p>
                </div>
            </div>
        </section>

        <!-- Charts Section -->
        <section class="mb-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Revenue Distribution Chart -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div id="revenueChartContainer" style="height: 370px; width: 100%;"></div>
                </div>

                <!-- Monthly Revenue Trend Chart -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div id="monthlyChartContainer" style="height: 370px; width: 100%;"></div>
                </div>

                <!-- Revenue Type Pie Chart -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div id="revenuePieChartContainer" style="height: 370px; width: 100%;"></div>
                </div>

                <!-- Tours Revenue Pie Chart -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div id="toursPieChartContainer" style="height: 370px; width: 100%;"></div>
                </div>
            </div>
        </section>
    </div>
</body>
</html>