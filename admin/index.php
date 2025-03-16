<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Initialize variables with default values
$total_bookings = ['completed' => 0, 'pending' => 0, 'failed' => 0];
$normal_bookings = ['completed' => 0, 'pending' => 0, 'failed' => 0];
$edu_cor_bookings = ['completed' => 0, 'pending' => 0, 'failed' => 0];
$packages = ['normal' => 0, 'educational' => 0, 'corporate' => 0];
$enquiries = ['new' => 0, 'read' => 0, 'replied' => 0];

// Include database connection parameters
require_once 'db-parameters.php';

try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Check if bookings table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'bookings'");
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        // Get normal bookings statistics
        $stmt = $conn->prepare("SELECT 
            COALESCE(SUM(CASE WHEN payment_status = 'completed' THEN 1 ELSE 0 END), 0) as completed,
            COALESCE(SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END), 0) as pending,
            COALESCE(SUM(CASE WHEN payment_status IN ('failed', 'refunded') THEN 1 ELSE 0 END), 0) as failed
            FROM bookings");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $normal_bookings = $result;
        }
    }
    
    // Check if booking_sch_cor table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'booking_sch_cor'");
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        // Get school/corporate bookings statistics
        $stmt = $conn->prepare("SELECT 
            COALESCE(SUM(CASE WHEN payment_status = 'completed' THEN 1 ELSE 0 END), 0) as completed,
            COALESCE(SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END), 0) as pending,
            COALESCE(SUM(CASE WHEN payment_status IN ('failed', 'refunded') THEN 1 ELSE 0 END), 0) as failed
            FROM booking_sch_cor");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $edu_cor_bookings = $result;
        }
    }
    
    // Calculate total bookings
    $total_bookings = [
        'completed' => (int)$normal_bookings['completed'] + (int)$edu_cor_bookings['completed'],
        'pending' => (int)$normal_bookings['pending'] + (int)$edu_cor_bookings['pending'],
        'failed' => (int)$normal_bookings['failed'] + (int)$edu_cor_bookings['failed']
    ];
    
    // Check if tour_packages table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'tour_packages'");
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        // Get package counts
        $stmt = $conn->prepare("SELECT 
            COALESCE(SUM(CASE WHEN package_type = 'normal' AND is_published = 1 THEN 1 ELSE 0 END), 0) as normal,
            COALESCE(SUM(CASE WHEN package_type = 'educational' AND is_published = 1 THEN 1 ELSE 0 END), 0) as educational,
            COALESCE(SUM(CASE WHEN package_type = 'corporate' AND is_published = 1 THEN 1 ELSE 0 END), 0) as corporate
            FROM tour_packages");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $packages = $result;
        }
    }
    
    // Check if enquiries table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'enquiries'");
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        // Get enquiries statistics
        $stmt = $conn->prepare("SELECT 
            COALESCE(SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END), 0) as new,
            COALESCE(SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END), 0) as `read`,
            COALESCE(SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END), 0) as replied
            FROM enquiries");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $enquiries = $result;
        }
    }
    
    // Check if tour_monitoring table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'tour_monitoring'");
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        // Get monitoring statistics grouped by package_id
        $stmt = $conn->prepare("SELECT 
            COUNT(DISTINCT CASE WHEN status = 'completed' THEN package_id END) as completed,
            COUNT(DISTINCT CASE WHEN status = 'active' THEN package_id END) as active
            FROM tour_monitoring");
        $stmt->execute();
        $monitoring = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get total number of unique packages
        $stmt = $conn->prepare("SELECT COUNT(DISTINCT id) as total FROM tour_packages WHERE is_published = 1");
        $stmt->execute();
        $total_packages = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Calculate not started tours (packages that are neither completed nor active)
    } else {
        $monitoring = ['completed' => 0, 'active' => 0, 'not_started' => 0];
    }
} catch(PDOException $e) {
    // Keep the default values initialized at the start
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Nilabhoomi Tours and Travels</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
    </script>
</head>
<body class="bg-gradient-to-r from-blue-50 to-indigo-50 min-h-screen">
 <?php include('partials/navigation.php'); ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 py-8 md:ml-64">
        <!-- Statistics Section -->
        <section class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Dashboard Overview</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Statistics Cards -->
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow duration-300">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Total Bookings</h3>
                    <p class="text-3xl font-bold text-green-400">Completed: <?php echo $total_bookings['completed']; ?></p>
                    <p class="text-3xl font-bold text-amber-400">Pending: <?php echo $total_bookings['pending']; ?></p>
                    <p class="text-3xl font-bold text-red-400">Failed: <?php echo $total_bookings['failed']; ?></p>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow duration-300">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Total Normal Bookings</h3>
                    <p class="text-3xl font-bold text-green-400">Completed: <?php echo $normal_bookings['completed']; ?></p>
                    <p class="text-3xl font-bold text-amber-400">Pending: <?php echo $normal_bookings['pending']; ?></p>
                    <p class="text-3xl font-bold text-red-400">Failed: <?php echo $normal_bookings['failed']; ?></p>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow duration-300">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Total Edu/Cor Bookings</h3>
                    <p class="text-3xl font-bold text-green-400">Completed: <?php echo $edu_cor_bookings['completed']; ?></p>
                    <p class="text-3xl font-bold text-amber-400">Pending: <?php echo $edu_cor_bookings['pending']; ?></p>
                    <p class="text-3xl font-bold text-red-400">Failed: <?php echo $edu_cor_bookings['failed']; ?></p>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow duration-300">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Total Packages</h3>
                    <p class="text-3xl font-bold text-fuchsia-400">Nor Packages: <?php echo $packages['normal']; ?></p>
                    <p class="text-3xl font-bold text-teal-400">Edu Packages: <?php echo $packages['educational']; ?></p>
                    <p class="text-3xl font-bold text-green-400">Cor Packages: <?php echo $packages['corporate']; ?></p>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow duration-300">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Total Enquiries</h3>
                    <p class="text-3xl font-bold text-fuchsia-400">New: <?php echo $enquiries['new']; ?></p>
                    <p class="text-3xl font-bold text-teal-400">Read: <?php echo $enquiries['read']; ?></p>
                    <p class="text-3xl font-bold text-green-400">Replied: <?php echo $enquiries['replied']; ?></p>
                </div> 

                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow duration-300">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Total Monitoring</h3>
                    <p class="text-3xl font-bold text-fuchsia-400">Completed: <?php echo $monitoring['completed']; ?></p>
                    <p class="text-3xl font-bold text-teal-400">Active: <?php echo $monitoring['active']; ?></p>
                </div> 
                
            </div>
        </section>

        <!-- Quick Actions Section -->
        <section class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Quick Actions</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <a href="tour-packages-list.php" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Manage Packages</h3>
                    <p class="text-gray-600">Add, edit, or remove tour packages</p>
                </a>

                <a href="bookings-list.php" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">View Bookings</h3>
                    <p class="text-gray-600">Manage customer bookings</p>
                </a>

                <a href="monitoring-list.php" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Tour Monitoring</h3>
                    <p class="text-gray-600">Monitor ongoing tours</p>
                </a>

                <a href="payment-history.php" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Payment History</h3>
                    <p class="text-gray-600">View payment transactions</p>
                </a>

                <a href="admin_users.php" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Manage Users</h3>
                    <p class="text-gray-600">Manage admin users</p>
                </a>

                <a href="enquiries-list.php" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Contact Enquiries</h3>
                    <p class="text-gray-600">View customer enquiries</p>
                </a>
            </div>
        </section>
    </div>
</body>
</html>