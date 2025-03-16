<?php
session_start();
require_once 'db-parameters.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Initialize variables
$package_id = isset($_GET['package_id']) ? (int)$_GET['package_id'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$items_per_page = 15;
$offset = ($page - 1) * $items_per_page;
$package_info = null;
$customers = [];
$error_message = '';

// Connect to database
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get package information
    if ($package_id > 0) {
        $stmt = $conn->prepare("SELECT id, title, duration FROM tour_packages WHERE id = :id");
        $stmt->bindParam(':id', $package_id, PDO::PARAM_INT);
        $stmt->execute();
        $package_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($package_info) {
            // Count total customers for this package
            $count_sql = "SELECT COUNT(*) FROM bookings WHERE package_id = :package_id";
            
            // Add search condition if search query is provided
            if (!empty($search_query)) {
                $count_sql .= " AND (first_name LIKE :search OR last_name LIKE :search OR email LIKE :search OR phone LIKE :search)";
            }
            
            $count_stmt = $conn->prepare($count_sql);
            $count_stmt->bindParam(':package_id', $package_id, PDO::PARAM_INT);
            
            if (!empty($search_query)) {
                $search_param = "%$search_query%";
                $count_stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
            }
            
            $count_stmt->execute();
            $total_records = $count_stmt->fetchColumn();
            
            // Get customers with pagination
            $sql = "SELECT id, first_name, last_name, email, phone, travel_date, travelers, 
                   total_amount, payment_status, booking_date 
                   FROM bookings 
                   WHERE package_id = :package_id";
            
            // Add search condition if search query is provided
            if (!empty($search_query)) {
                $sql .= " AND (first_name LIKE :search OR last_name LIKE :search OR email LIKE :search OR phone LIKE :search)";
            }
            
            $sql .= " ORDER BY booking_date DESC 
                   LIMIT :offset, :limit";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':package_id', $package_id, PDO::PARAM_INT);
            
            if (!empty($search_query)) {
                $search_param = "%$search_query%";
                $stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
            }
            
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
            $stmt->execute();
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Count completed bookings
            $completed_sql = "SELECT COUNT(*) FROM bookings WHERE package_id = :package_id AND payment_status = 'completed'";
            $completed_stmt = $conn->prepare($completed_sql);
            $completed_stmt->bindParam(':package_id', $package_id, PDO::PARAM_INT);
            $completed_stmt->execute();
            $completed_bookings = $completed_stmt->fetchColumn();
            
            // Calculate total pages for pagination
            $total_pages = ceil($total_records / $items_per_page);
        } else {
            $error_message = 'Package not found';
        }
    } else {
        $error_message = 'Invalid package ID';
    }
} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// Function to get status badge class
function getStatusBadgeClass($status) {
    switch($status) {
        case 'completed':
            return 'bg-green-100 text-green-800';
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'failed':
            return 'bg-red-100 text-red-800';
        case 'refunded':
            return 'bg-purple-100 text-purple-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Package Customers - Admin Dashboard</title>
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
            <div class="flex items-center mb-6">
                <a href="bookings-list.php?type=normal" class="text-primary hover:text-secondary mr-2">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="text-2xl font-bold text-gray-800">Package Customers</h1>
            </div>
            
            <?php if (isset($error_message) && !empty($error_message)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p><?php echo $error_message; ?></p>
                </div>
            <?php elseif ($package_info): ?>
                <!-- Package Info Header -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-xl font-bold text-primary mb-2"><?php echo htmlspecialchars($package_info['title']); ?></h2>
                            <p class="text-gray-600">Duration: <?php echo htmlspecialchars($package_info['duration']); ?></p>
                            <p class="text-gray-600 mt-2">Total Customers: <?php echo number_format($total_records); ?></p>
                            <?php if (isset($completed_bookings) && $completed_bookings > 0): ?>
                            <p class="text-gray-600">Completed Bookings: <?php echo number_format($completed_bookings); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php if (isset($completed_bookings) && $completed_bookings > 0): ?>
                        <div>
                            <a href="generate_customers_pdf.php?package_id=<?php echo $package_id; ?>" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-md">
                                <i class="fas fa-download mr-2"></i> Download Completed Customers PDF
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Search Form -->
                <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                    <form action="" method="GET" class="flex items-center">
                        <input type="hidden" name="package_id" value="<?php echo $package_id; ?>">
                        <div class="flex-grow">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search by name, email or phone..." class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <button type="submit" class="ml-2 px-4 py-2 bg-primary text-white rounded-md hover:bg-secondary">
                            <i class="fas fa-search mr-1"></i> Search
                        </button>
                        <?php if (!empty($search_query)): ?>
                        <a href="?package_id=<?php echo $package_id; ?>" class="ml-2 px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                            <i class="fas fa-times mr-1"></i> Clear
                        </a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <!-- Customers List -->
                <?php if (empty($customers)): ?>
                    <div class="bg-white rounded-lg shadow-md p-6 text-center">
                        <p class="text-gray-500">No customers found for this package.</p>
                    </div>
                <?php else: ?>
                    <!-- Responsive Table/Card Layout -->
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <!-- Desktop Table (hidden on small screens) -->
                        <div class="hidden md:block">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Travel Date</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Travelers</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking Date</th>
                                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($customers as $customer): ?>
                                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                                            <td class="px-4 py-3">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($customer['email']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($customer['phone']); ?></div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($customer['travel_date'])); ?></div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="text-sm text-gray-900"><?php echo number_format($customer['travelers']); ?></div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="text-sm text-gray-900">৳<?php echo number_format($customer['total_amount'], 2); ?></div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusBadgeClass($customer['payment_status']); ?>">
                                                    <?php echo ucfirst($customer['payment_status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($customer['booking_date'])); ?></div>
                                            </td>
                                            <td class="px-4 py-3 text-right text-sm font-medium">
                                                <a href="booking-details.php?id=<?php echo $customer['id']; ?>&type=normal" class="text-primary hover:text-secondary">View Details</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Mobile Card Layout (visible only on small screens) -->
                        <div class="md:hidden">
                            <div class="divide-y divide-gray-200">
                                <?php foreach ($customers as $customer): ?>
                                    <div class="p-4 hover:bg-gray-50 transition-colors duration-150">
                                        <div class="flex justify-between items-start mb-3">
                                            <div class="text-base font-medium text-gray-900">
                                                <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
                                            </div>
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusBadgeClass($customer['payment_status']); ?>">
                                                <?php echo ucfirst($customer['payment_status']); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="grid grid-cols-2 gap-2 mb-3 text-sm">
                                            <div>
                                                <div class="text-gray-500 font-medium">Contact:</div>
                                                <div class="text-gray-900"><?php echo htmlspecialchars($customer['email']); ?></div>
                                                <div class="text-gray-700"><?php echo htmlspecialchars($customer['phone']); ?></div>
                                            </div>
                                            <div>
                                                <div class="text-gray-500 font-medium">Travel Date:</div>
                                                <div class="text-gray-900"><?php echo date('M d, Y', strtotime($customer['travel_date'])); ?></div>
                                            </div>
                                        </div>
                                        
                                        <div class="grid grid-cols-2 gap-2 mb-3 text-sm">
                                            <div>
                                                <div class="text-gray-500 font-medium">Travelers:</div>
                                                <div class="text-gray-900"><?php echo number_format($customer['travelers']); ?></div>
                                            </div>
                                            <div>
                                                <div class="text-gray-500 font-medium">Amount:</div>
                                                <div class="text-gray-900">৳<?php echo number_format($customer['total_amount'], 2); ?></div>
                                            </div>
                                        </div>
                                        
                                        <div class="flex justify-between items-center text-sm">
                                            <div>
                                                <div class="text-gray-500 font-medium">Booking Date:</div>
                                                <div class="text-gray-900"><?php echo date('M d, Y', strtotime($customer['booking_date'])); ?></div>
                                            </div>
                                            <a href="booking-details.php?id=<?php echo $customer['id']; ?>&type=normal" class="inline-flex items-center px-3 py-2 border border-primary rounded-md text-sm font-medium text-primary hover:bg-primary hover:text-white transition-colors duration-150">
                                                <i class="fas fa-eye mr-1"></i> View
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="mt-8 flex justify-center">
                            <nav class="inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?package_id=<?php echo $package_id; ?>&page=<?php echo ($page - 1); ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" 
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
                                    echo '<a href="?package_id=' . $package_id . '&page=1' . (!empty($search_query) ? '&search=' . urlencode($search_query) : '') . '" 
                                          class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                                    
                                    if ($start_page > 2) {
                                        echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                    }
                                }
                                
                                // Display page numbers
                                for ($i = $start_page; $i <= $end_page; $i++) {
                                    echo '<a href="?package_id=' . $package_id . '&page=' . $i . (!empty($search_query) ? '&search=' . urlencode($search_query) : '') . '" 
                                          class="relative inline-flex items-center px-4 py-2 border ' . ($i == $page ? 'border-primary bg-primary text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50') . ' text-sm font-medium">' . $i . '</a>';
                                }
                                
                                // Always show last page
                                if ($end_page < $total_pages) {
                                    if ($end_page < $total_pages - 1) {
                                        echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                    }
                                    
                                    echo '<a href="?package_id=' . $package_id . '&page=' . $total_pages . (!empty($search_query) ? '&search=' . urlencode($search_query) : '') . '" 
                                          class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">' . $total_pages . '</a>';
                                }
                                ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?package_id=<?php echo $package_id; ?>&page=<?php echo ($page + 1); ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" 
                                       class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Next</span>
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>