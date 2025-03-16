<?php
// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
if (!headers_sent()) {
    header('Location: login.php');
} else {
    echo '<script>window.location.href = "login.php";</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=login.php"></noscript>';
}
    exit();
}

// Check if we need to reset the booking counters
if (isset($_GET['reset_counters']) && $_GET['reset_counters'] == 1) {
    $_SESSION['reset_booking_counters'] = true;
}

// Get count of bookings by status
$new_bookings_count = 0;
$completed_bookings_count = 0;
$failed_bookings_count = 0;

// Only count bookings if we're not resetting counters
if (!isset($_SESSION['reset_booking_counters']) || $_SESSION['reset_booking_counters'] !== true) {
    try {
        // Use the same database connection parameters as in bookings-list.php
        require_once dirname(__DIR__) . '/db-parameters.php';
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Count normal bookings with different statuses
        $stmt = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE payment_status = 'pending'");
        $stmt->execute();
        $new_bookings_count = $stmt->fetchColumn();
        
        $stmt = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE payment_status = 'completed'");
        $stmt->execute();
        $completed_bookings_count = $stmt->fetchColumn();
        
        $stmt = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE payment_status = 'failed'");
        $stmt->execute();
        $failed_bookings_count = $stmt->fetchColumn();
        
        // Also count school/corporate bookings with different statuses if the table exists
        $stmt = $conn->prepare("SHOW TABLES LIKE 'booking_sch_cor'");
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM booking_sch_cor WHERE payment_status = 'pending'");
            $stmt->execute();
            $new_bookings_count += $stmt->fetchColumn();
            
            $stmt = $conn->prepare("SELECT COUNT(*) FROM booking_sch_cor WHERE payment_status = 'completed'");
            $stmt->execute();
            $completed_bookings_count += $stmt->fetchColumn();
            
            $stmt = $conn->prepare("SELECT COUNT(*) FROM booking_sch_cor WHERE payment_status = 'failed'");
            $stmt->execute();
            $failed_bookings_count += $stmt->fetchColumn();
        }
    } catch(PDOException $e) {
        // Silently handle error
        error_log("Error counting bookings: " . $e->getMessage());
    }
}
?>
<!-- Top Navigation -->
<nav class="bg-white shadow-lg fixed w-full z-10">
    <div class="max-w-full mx-auto px-4">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <button id="sidebarToggle" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <h1 class="text-xl font-bold text-gray-800 ml-4">Admin Dashboard</h1>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-gray-600">Welcome, <?php echo htmlspecialchars($_SESSION['admin_fullname']); ?></span>
                <a href="logout.php" class="text-red-600 hover:text-red-800">Logout</a>
            </div>
        </div>
    </div>
</nav>

<!-- Sidebar -->
<aside id="sidebar" class="bg-white w-64 min-h-screen fixed left-0 z-0 transform -translate-x-full md:translate-x-0 transition-transform duration-200 ease-in-out shadow-lg">
    <div class="py-4 px-3">
        <div class="space-y-4">
            <!-- Dashboard -->
           

            <!-- Tour Packages -->
            <div class="space-y-2">

            <div class="px-4 py-2 mt-16 text-gray-500 uppercase text-xs font-semibold">
            <a href="index.php" class="flex items-center px-4 py-2 text-gray-700 hover:bg-primary hover:text-white rounded-lg transition-colors">
                <svg class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span>Dashboard</span>
            </a>
            </div>
                <div class="px-4 py-2 text-gray-500 uppercase text-xs font-semibold">Tour Packages</div>
                
                
                
                <a href="tour-packages-add.php" class="flex items-center px-4 py-2 text-gray-700 hover:bg-primary hover:text-white rounded-lg transition-colors">
                    <svg class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    <span>Add Package</span>
                </a>
                <a href="tour-packages-list.php" class="flex items-center px-4 py-2 text-gray-700 hover:bg-primary hover:text-white rounded-lg transition-colors">
                    <svg class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    <span>Package List</span>
                </a>
            </div>

            <!-- Bookings -->
            <div class="space-y-2">
                <div class="px-4 py-2 text-gray-500 uppercase text-xs font-semibold">Bookings</div>
                <a href="bookings-list.php" id="viewBookingsLink" class="flex items-center px-4 py-2 text-gray-700 hover:bg-primary hover:text-white rounded-lg transition-colors relative">
                    <svg class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 4v12l-4-2-4 2V4M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>View Bookings</span>
                    
                    <!-- Improved booking counters with better visual representation -->
                    <div class="ml-2 flex items-center" id="bookingCounters">
                        <?php if ($new_bookings_count > 0 || $completed_bookings_count > 0 || $failed_bookings_count > 0): ?>
                        <div class="flex items-center ml-2 px-2 py-1 bg-gray-100 rounded-md shadow-sm">
                            <?php if ($new_bookings_count > 0): ?>
                            <div class="flex items-center mr-2" title="Pending Bookings">
                                <span class="inline-block w-3 h-3 rounded-full bg-red-500 mr-1"></span>
                                <span class="text-xs font-medium text-gray-700"><?php echo $new_bookings_count; ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($completed_bookings_count > 0): ?>
                            <div class="flex items-center mr-2" title="Completed Bookings">
                                <span class="inline-block w-3 h-3 rounded-full bg-green-500 mr-1"></span>
                                <span class="text-xs font-medium text-gray-700"><?php echo $completed_bookings_count; ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($failed_bookings_count > 0): ?>
                            <div class="flex items-center" title="Failed Bookings">
                                <span class="inline-block w-3 h-3 rounded-full bg-gray-500 mr-1"></span>
                                <span class="text-xs font-medium text-gray-700"><?php echo $failed_bookings_count; ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
             <!-- Booking Monitoring -->
             <div class="space-y-2">
                <div class="px-4 py-2 text-gray-500 uppercase text-xs font-semibold">Monitoring</div>
                <a href="monitoring-list.php" class="flex items-center px-4 py-2 text-gray-700 hover:bg-primary hover:text-white rounded-lg transition-colors">
                    <svg class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                    <span>Tour Monitoring</span>
                </a>
            </div>

            <!-- Enquiries -->
            <div class="space-y-2">
                <div class="px-4 py-2 text-gray-500 uppercase text-xs font-semibold">Enquiries</div>
                <a href="enquiries-list.php" class="flex items-center px-4 py-2 text-gray-700 hover:bg-primary hover:text-white rounded-lg transition-colors">
                    <svg class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                    <span>Booking Enquiries</span>
                </a>
            </div>
            

            <!-- Settings -->
            <div class="space-y-2">
                <div class="px-4 py-2 text-gray-500 uppercase text-xs font-semibold">Settings</div>

                <a href="payment-statistics.php" class="flex items-center px-4 py-2 text-gray-700 hover:bg-primary hover:text-white rounded-lg transition-colors">
                    <svg class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <span>Payment Statistics</span>
                </a>

                <a href="payment-history.php" class="flex items-center px-4 py-2 text-gray-700 hover:bg-primary hover:text-white rounded-lg transition-colors">
                    <svg class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    <span>Payment History</span>
                </a>
                
            </div>
            
        </div>
    </div>
</aside>

<!-- Sidebar Toggle Script -->
<script>
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('-translate-x-full');
    });
    
    // Add event listener to the View Bookings link to reset counters when clicked
    document.addEventListener('DOMContentLoaded', function() {
        const bookingsLink = document.getElementById('viewBookingsLink');
        if (bookingsLink) {
            bookingsLink.addEventListener('click', function(e) {
                // Prevent the default link behavior
                e.preventDefault();
                
                // Hide the booking counters immediately for visual feedback
                const counterContainer = document.getElementById('bookingCounters');
                if (counterContainer) {
                    counterContainer.style.display = 'none';
                }
                
                // Redirect to bookings-list.php with reset_counters parameter
                window.location.href = 'bookings-list.php?reset_counters=1';
            });
        }
    });
</script>
