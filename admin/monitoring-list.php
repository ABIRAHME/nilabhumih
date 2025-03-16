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
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 12;
$offset = ($page - 1) * $items_per_page;

// Connect to database
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
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
               (SELECT COUNT(*) FROM bookings WHERE package_id = b.package_id AND payment_status = 'completed') as total_customers,
               (SELECT tm.status FROM tour_monitoring tm WHERE tm.package_id = b.package_id ORDER BY tm.id DESC LIMIT 1) as tour_status
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
               (SELECT COUNT(*) FROM bookings WHERE package_id = b.package_id AND payment_status = 'completed') as total_customers,
               (SELECT tm.status FROM tour_monitoring tm WHERE tm.package_id = b.package_id ORDER BY tm.id DESC LIMIT 1) as tour_status
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
    
    // Calculate total pages
    $total_pages = ceil($total_records / $items_per_page);
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tour Monitoring - Admin Dashboard</title>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'partials/navigation.php'; ?>
    
    <div class="md:ml-64 pt-16 min-h-screen">
        <div class="container mx-auto px-4 py-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Tour Monitoring</h1>
                <div class="flex space-x-4">
                    <form action="" method="GET" class="flex items-center">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search packages..." class="rounded-l-lg px-4 py-2 border-t border-l border-b border-gray-300 focus:outline-none focus:border-primary">
                        <button type="submit" class="rounded-r-lg px-4 py-2 border border-primary bg-primary text-white hover:bg-secondary transition-colors">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Bookings Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Package Details</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Travel Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Customers</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($bookings as $booking): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($booking['title']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($booking['duration']); ?> days</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($booking['travel_date'])); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo $booking['total_customers']; ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($booking['tour_status'] === 'completed'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i> Completed
                                    </span>
                                    <?php elseif ($booking['tour_status'] === 'active'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        <i class="fas fa-clock mr-1"></i> Active
                                    </span>
                                    <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                        <i class="fas fa-minus-circle mr-1"></i> Not Started
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-4">
                                        <a href="monitoring-details.php?package_id=<?php echo $booking['package_id']; ?>" 
                                           class="inline-flex items-center px-3 py-1 border border-primary text-primary text-sm font-medium rounded hover:bg-primary hover:text-white transition-colors">
                                            <i class="fas fa-chart-line mr-1"></i> Monitor
                                        </a>
                                        <?php if ($booking['tour_status'] !== 'completed'): ?>
                                        <button onclick="markAsCompleted(<?php echo $booking['package_id']; ?>)" 
                                                class="inline-flex items-center px-3 py-1 border border-green-600 text-green-600 text-sm font-medium rounded hover:bg-green-600 hover:text-white transition-colors">
                                            <i class="fas fa-check-circle mr-1"></i> Mark Completed
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="mt-6 flex justify-center">
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?php echo ($page - 1); ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Previous</span>
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $page === $i ? 'text-primary bg-primary bg-opacity-10' : 'text-gray-700 hover:bg-gray-50'; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo ($page + 1); ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Next</span>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        function markAsCompleted(packageId) {
            if (confirm('Are you sure you want to mark this tour as completed?')) {
                // Create form data
                const formData = new FormData();
                formData.append('package_id', packageId);
                formData.append('customer_id', 0);
                formData.append('action', 'complete_tour');
                formData.append('value', 1);
                
                // Send AJAX request
                fetch('ajax/update-attendance.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        alert('Tour marked as completed successfully!');
                        // Reload the page to reflect changes
                        window.location.reload();
                    } else {
                        alert('Failed to mark tour as completed: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while processing your request.');
                });
            }
        }
    </script>
</body>
</html>