<?php
session_start();
require_once 'db-parameters.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Reset booking counters if requested
if (isset($_GET['reset_counters']) && $_GET['reset_counters'] == 1) {
    $_SESSION['reset_booking_counters'] = true;
}

// Initialize variables
$booking_type = isset($_GET['type']) ? $_GET['type'] : 'normal';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 12;
$offset = ($page - 1) * $items_per_page;

// Connect to database
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Prepare query based on booking type and search query
    if ($booking_type === 'normal') {
        // Check if bookings table exists, if not create it
        $stmt = $conn->prepare("SHOW TABLES LIKE 'bookings'");
        $stmt->execute();
        if ($stmt->rowCount() == 0) {
            // Create the bookings table
            $sql = "CREATE TABLE IF NOT EXISTS bookings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                package_id INT NOT NULL,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                email VARCHAR(255) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                travel_date DATE NOT NULL,
                travelers INT NOT NULL,
                special_requirements TEXT,
                package_price DECIMAL(10, 2) NOT NULL,
                taxes_fees DECIMAL(10, 2) NOT NULL,
                total_amount DECIMAL(10, 2) NOT NULL,
                payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
                booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                transaction_id VARCHAR(255),
                payment_method VARCHAR(50),
                payment_date TIMESTAMP NULL,
                INDEX (package_id),
                INDEX (email),
                INDEX (payment_status)
            );";
            $conn->exec($sql);
        }
       
        // Count total records for pagination - count distinct packages for normal bookings
        if (!empty($search_query)) {
            $count_sql = "SELECT COUNT(DISTINCT b.package_id) FROM bookings b 
                         JOIN tour_packages p ON b.package_id = p.id 
                         WHERE b.first_name LIKE :search 
                         OR b.last_name LIKE :search 
                         OR b.email LIKE :search 
                         OR p.title LIKE :search";
            $count_stmt = $conn->prepare($count_sql);
            $search_param = "%$search_query%";
            $count_stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
        } else {
            $count_sql = "SELECT COUNT(DISTINCT package_id) FROM bookings";
            $count_stmt = $conn->prepare($count_sql);
        }
        $count_stmt->execute();
        $total_records = $count_stmt->fetchColumn();
        
        // Get bookings with pagination - for normal bookings, group by package_id to avoid duplicates
        if (!empty($search_query)) {
            $sql = "SELECT b.id, b.package_id, b.package_price, b.travel_date, b.payment_status, p.title, p.duration,
                   (SELECT COUNT(*) FROM bookings WHERE package_id = b.package_id) as total_customers 
                   FROM bookings b 
                   JOIN tour_packages p ON b.package_id = p.id 
                   WHERE b.first_name LIKE :search 
                   OR b.last_name LIKE :search 
                   OR b.email LIKE :search 
                   OR p.title LIKE :search 
                   GROUP BY b.package_id 
                   ORDER BY MAX(b.booking_date) DESC 
                   LIMIT :offset, :limit";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
        } else {
            $sql = "SELECT b.id, b.package_id, b.package_price, b.travel_date, b.payment_status, p.title, p.duration,
                   (SELECT COUNT(*) FROM bookings WHERE package_id = b.package_id) as total_customers 
                   FROM bookings b 
                   JOIN tour_packages p ON b.package_id = p.id 
                   GROUP BY b.package_id 
                   ORDER BY MAX(b.booking_date) DESC 
                   LIMIT :offset, :limit";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
        }
        $stmt->execute();
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // School or Corporate bookings
        // Check if booking_sch_cor table exists, if not create it
        $stmt = $conn->prepare("SHOW TABLES LIKE 'booking_sch_cor'");
        $stmt->execute();
        if ($stmt->rowCount() == 0) {
            // Create the booking_sch_cor table
            $sql = "CREATE TABLE booking_sch_cor (
                id INT(11) NOT NULL AUTO_INCREMENT,
                package_id INT(11) NOT NULL,
                package_type VARCHAR(50) NOT NULL,
                institute_name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                phone VARCHAR(50) NOT NULL,
                travel_date DATE NOT NULL,
                travelers INT(11) NOT NULL,
                special_requirements TEXT,
                package_price DECIMAL(10,2) NOT NULL,
                subtotal DECIMAL(10,2) NOT NULL,
                discount DECIMAL(10,2) NOT NULL DEFAULT 0,
                taxes_fees DECIMAL(10,2) NOT NULL,
                total_amount DECIMAL(10,2) NOT NULL,
                payment_amount DECIMAL(10,2) NOT NULL,
                partial_payment TINYINT(1) NOT NULL DEFAULT 0,
                payment_status VARCHAR(50) NOT NULL DEFAULT 'pending',
                transaction_id VARCHAR(255),
                payment_method VARCHAR(100),
                payment_date DATETIME,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            );";
            $conn->exec($sql);
        }
        
        // Check if partial payment filter is applied
        $partial_filter = isset($_GET['partial']) && $_GET['partial'] == 1;
        
        // Count total records for pagination
        if (!empty($search_query)) {
            $count_sql = "SELECT COUNT(*) FROM booking_sch_cor b 
                         JOIN tour_packages p ON b.package_id = p.id 
                         WHERE b.package_type = :type 
                         AND (b.institute_name LIKE :search 
                         OR b.email LIKE :search 
                         OR p.title LIKE :search)";
            if ($partial_filter) {
                $count_sql .= " AND b.partial_payment = 1";
            }
            $count_stmt = $conn->prepare($count_sql);
            $count_stmt->bindParam(':type', $booking_type, PDO::PARAM_STR);
            $search_param = "%$search_query%";
            $count_stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
        } else {
            $count_sql = "SELECT COUNT(*) FROM booking_sch_cor WHERE package_type = :type";
            if ($partial_filter) {
                $count_sql .= " AND partial_payment = 1";
            }
            $count_stmt = $conn->prepare($count_sql);
            $count_stmt->bindParam(':type', $booking_type, PDO::PARAM_STR);
        }
        $count_stmt->execute();
        $total_records = $count_stmt->fetchColumn();
        
        // Get bookings with pagination
        if (!empty($search_query)) {
            $sql = "SELECT b.*, p.title, p.duration FROM booking_sch_cor b 
                   JOIN tour_packages p ON b.package_id = p.id 
                   WHERE b.package_type = :type 
                   AND (b.institute_name LIKE :search 
                   OR b.email LIKE :search 
                   OR p.title LIKE :search)";
            if ($partial_filter) {
                $sql .= " AND b.partial_payment = 1";
            }
            $sql .= " ORDER BY b.created_at DESC 
                   LIMIT :offset, :limit";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':type', $booking_type, PDO::PARAM_STR);
            $stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
        } else {
            $sql = "SELECT b.*, p.title, p.duration FROM booking_sch_cor b 
                   JOIN tour_packages p ON b.package_id = p.id 
                   WHERE b.package_type = :type";
            if ($partial_filter) {
                $sql .= " AND b.partial_payment = 1";
            }
            $sql .= " ORDER BY b.created_at DESC 
                   LIMIT :offset, :limit";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':type', $booking_type, PDO::PARAM_STR);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
        }
        $stmt->execute();
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Calculate total pages for pagination
    $total_pages = ceil($total_records / $items_per_page);
    
} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings List - Admin Dashboard</title>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include 'partials/navigation.php'; ?>
    
    <div class="md:ml-64 pt-16 min-h-screen">
        <div class="container mx-auto px-4 py-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-4 md:mb-0">Bookings Management</h1>
                
                <!-- Search Form -->
                <form action="" method="GET" class="w-full md:w-auto">
                    <input type="hidden" name="type" value="<?php echo $booking_type; ?>">
                    <div class="flex flex-col md:flex-row gap-2">
                        <div class="relative">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" 
                                placeholder="Search bookings..." 
                                class="w-full md:w-64 px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                            <button type="submit" class="absolute right-2 top-2 text-gray-500">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <?php if (!empty($search_query)): ?>
                            <a href="?type=<?php echo $booking_type; ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors">
                                Clear
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Booking Type Tabs -->
            <div class="flex flex-wrap mb-6 border-b border-gray-200">
                <a href="?type=normal<?php echo !empty($search_query) ? '&search='.urlencode($search_query) : ''; ?>" 
                   class="px-4 py-2 font-medium text-sm <?php echo $booking_type === 'normal' ? 'text-primary border-b-2 border-primary' : 'text-gray-500 hover:text-gray-700'; ?>">
                    Normal Bookings
                </a>
                <a href="?type=educational<?php echo !empty($search_query) ? '&search='.urlencode($search_query) : ''; ?>" 
                   class="px-4 py-2 font-medium text-sm <?php echo $booking_type === 'educational' ? 'text-primary border-b-2 border-primary' : 'text-gray-500 hover:text-gray-700'; ?>">
                    School Bookings
                </a>
                <a href="?type=corporate<?php echo !empty($search_query) ? '&search='.urlencode($search_query) : ''; ?>" 
                   class="px-4 py-2 font-medium text-sm <?php echo $booking_type === 'corporate' ? 'text-primary border-b-2 border-primary' : 'text-gray-500 hover:text-gray-700'; ?>">
                    Corporate Bookings
                </a>
                <?php if ($booking_type === 'educational' || $booking_type === 'corporate'): ?>
                <a href="?type=<?php echo $booking_type; ?>&partial=1<?php echo !empty($search_query) ? '&search='.urlencode($search_query) : ''; ?>" 
                   class="px-4 py-2 font-medium text-sm ml-auto <?php echo isset($_GET['partial']) && $_GET['partial'] == 1 ? 'text-primary border-b-2 border-primary' : 'text-gray-500 hover:text-gray-700'; ?>">
                    Partial Payment Only
                </a>
                <?php endif; ?>
            </div>
            
            <?php if (isset($error_message)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p><?php echo $error_message; ?></p>
                </div>
            <?php endif; ?>
            
            <!-- Bookings Grid -->
            <?php if (empty($bookings)): ?>
                <div class="bg-white rounded-lg shadow-md p-6 text-center">
                    <p class="text-gray-500">No bookings found.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($bookings as $booking): ?>
                        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                            <div class="bg-primary text-white px-4 py-2">
                                <h3 class="font-bold truncate"><?php echo htmlspecialchars($booking['title']); ?></h3>
                            </div>
                            <div class="p-4">
                                <div class="mb-4">
                                    <?php if ($booking_type === 'normal'): ?>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Duration:</span>
                                            <span class="font-medium"><?php echo htmlspecialchars($booking['duration']); ?></span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Travel Date:</span>
                                            <span class="font-medium"><?php echo date('M d, Y', strtotime($booking['travel_date'])); ?></span>
                                        </div>
                                        
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Package Price:</span>
                                            <span class="font-medium">৳<?php echo number_format($booking['package_price'], 2); ?></span>
                                        </div>
                                        
                                        <div class="flex justify-between text-1xl mt-2">
                                            <span class="text-gray-600">Total Customer:</span>
                                            <span class="font-medium"><?php echo number_format($booking['total_customers']); ?></span>

                                           
                                        </div>
                                        <div class="mt-2">
                                            <a href="package-customers.php?package_id=<?php echo $booking['package_id']; ?>" class="text-1xl text-primary hover:text-secondary flex items-center">
                                                <i class="fas fa-users mr-1"></i> View all customers
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Duration:</span>
                                            <span class="font-medium"><?php echo htmlspecialchars($booking['duration']); ?></span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Institute:</span>
                                            <span class="font-medium"><?php echo htmlspecialchars($booking['institute_name']); ?></span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Travel Date:</span>
                                            <span class="font-medium"><?php echo date('M d, Y', strtotime($booking['travel_date'])); ?></span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Travelers:</span>
                                            <span class="font-medium"><?php echo number_format($booking['travelers']); ?></span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Total Amount:</span>
                                            <span class="font-medium">৳<?php echo number_format($booking['total_amount'], 2); ?></span>
                                        </div>
                                        <?php if ($booking['partial_payment']): ?>
                                        <div class="flex justify-between text-sm font-medium text-primary">
                                            <span>Payment Amount (30%):</span>
                                            <span>৳<?php echo number_format($booking['payment_amount'], 2); ?></span>
                                        </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="border-t border-gray-200 px-4 py-3">
                                    <div class="flex justify-between items-center">


                                       
                                          <!-- Add View All Customers button -->
                                       
                                    <?php if ($booking_type !== 'normal'): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php 
                                            $status = $booking_type === 'normal' ? $booking['payment_status'] : $booking['payment_status'];
                                            switch($status) {
                                                case 'completed':
                                                    echo 'bg-green-100 text-green-800';
                                                    break;
                                                case 'pending':
                                                    echo 'bg-yellow-100 text-yellow-800';
                                                    break;
                                                case 'failed':
                                                    echo 'bg-red-100 text-red-800';
                                                    break;
                                                case 'refunded':
                                                    echo 'bg-purple-100 text-purple-800';
                                                    break;
                                                default:
                                                    echo 'bg-gray-100 text-gray-800';
                                            }
                                        ?>">
                                            <?php echo ucfirst($status); ?>
                                        </span>
                                        <?php endif; ?>


                                        <?php if ($booking_type !== 'normal'): ?>
                                        <a href="booking-details.php?id=<?php echo $booking['id']; ?>&type=<?php echo $booking_type; ?>" 
                                           class="inline-flex items-center px-3 py-1 border border-primary text-primary text-sm font-medium rounded hover:bg-primary hover:text-white transition-colors">
                                            View Details
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="mt-8 flex justify-center">
                        <nav class="inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <?php if ($page > 1): ?>
                                <a href="?type=<?php echo $booking_type; ?>&page=<?php echo ($page - 1); ?><?php echo !empty($search_query) ? '&search='.urlencode($search_query) : ''; ?>" 
                                   class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Previous</span>
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php
                            // Calculate range of page numbers to display
                            $range = 2; // Display 2 pages before and after current page
                            $start_page = max(1, $page - $range);
                            $end_page = min($total_pages, $page + $range);
                            
                            // Always show first page
                            if ($start_page > 1) {
                                echo '<a href="?type=' . $booking_type . '&page=1' . (!empty($search_query) ? '&search='.urlencode($search_query) : '') . '" 
                                      class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                                
                                if ($start_page > 2) {
                                    echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                }
                            }
                            
                            // Display page numbers
                            for ($i = $start_page; $i <= $end_page; $i++) {
                                echo '<a href="?type=' . $booking_type . '&page=' . $i . (!empty($search_query) ? '&search='.urlencode($search_query) : '') . '" 
                                      class="relative inline-flex items-center px-4 py-2 border ' . ($i == $page ? 'border-primary bg-primary text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50') . ' text-sm font-medium">' . $i . '</a>';
                            }
                            
                            // Always show last page
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                }
                                
                                echo '<a href="?type=' . $booking_type . '&page=' . $total_pages . (!empty($search_query) ? '&search='.urlencode($search_query) : '') . '" 
                                      class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">' . $total_pages . '</a>';
                            }
                            ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?type=<?php echo $booking_type; ?>&page=<?php echo ($page + 1); ?><?php echo !empty($search_query) ? '&search='.urlencode($search_query) : ''; ?>" 
                                   class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Next</span>
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>