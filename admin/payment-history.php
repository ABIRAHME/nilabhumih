<?php
session_start();
require_once 'db-parameters.php';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get bookings from both tables
    $sql_normal = "SELECT b.*, CONCAT(b.first_name, ' ', b.last_name) as name, p.title as package_name, 
                  COALESCE(b.payment_date, b.booking_date) as sort_date 
                  FROM bookings b 
                  LEFT JOIN tour_packages p ON b.package_id = p.id 
                  WHERE 1=1";
    $stmt_normal = $conn->prepare($sql_normal);
    $stmt_normal->execute();
    $normal_bookings = $stmt_normal->fetchAll(PDO::FETCH_ASSOC);

    $sql_special = "SELECT b.*, b.institute_name as name, p.title as package_name, 
                    COALESCE(b.payment_date, b.created_at) as sort_date 
                    FROM booking_sch_cor b 
                    LEFT JOIN tour_packages p ON b.package_id = p.id 
                    WHERE 1=1";
    $stmt_special = $conn->prepare($sql_special);
    $stmt_special->execute();
    $special_bookings = $stmt_special->fetchAll(PDO::FETCH_ASSOC);

    // Combine and sort all bookings by date
    $all_bookings = array_merge($normal_bookings, $special_bookings);
    usort($all_bookings, function($a, $b) {
        return strtotime($b['sort_date']) - strtotime($a['sort_date']);
    });

    // Add search functionality
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = strtolower($_GET['search']);
        $all_bookings = array_filter($all_bookings, function($booking) use ($search) {
            return strpos(strtolower($booking['name']), $search) !== false || 
                   strpos(strtolower($booking['transaction_id'] ?? ''), $search) !== false;
        });
    }

} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $error_message = "Database error occurred. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History - Admin Dashboard</title>
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
<body class="bg-gray-50">
    <?php include 'partials/navigation.php'; ?>

    <div class="md:ml-64 pt-16 min-h-screen">
        <div class="container mx-auto px-4 py-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-4 md:mb-0">Payment History</h1>
                
                <!-- Search Form -->
                <form action="" method="GET" class="w-full md:w-auto">
                    <div class="flex flex-col md:flex-row gap-2">
                        <input type="text" name="search" placeholder="Search by name or transaction ID" 
                               class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary transition-colors">
                            Search
                        </button>
                    </div>
                </form>
            </div>

            <!-- Responsive Table/Card Layout -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <!-- Desktop Table (hidden on small screens) -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer/Institute</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Package</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Travelers</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment Method</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Transaction ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($all_bookings as $booking): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($booking['name']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($booking['email']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?php echo htmlspecialchars($booking['package_name']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?php echo htmlspecialchars($booking['travelers']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?php 
                                    if (!empty($booking['partial_payment']) && $booking['partial_payment'] > 0) {
                                        echo 'Total: Tk ' . number_format($booking['total_amount'], 2) . '<br>';
                                        echo 'Paid: Tk ' . number_format($booking['payment_amount'], 2) . '<br>';
                                        echo 'Due: Tk ' . number_format($booking['total_amount'] - $booking['partial_payment'], 2);
                                    } else {
                                        echo 'Tk ' . number_format($booking['total_amount'], 2);
                                    }
                                    ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?php echo htmlspecialchars($booking['payment_method'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?php echo htmlspecialchars($booking['transaction_id'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $booking['payment_status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                        ($booking['payment_status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                        'bg-red-100 text-red-800'); ?>">
                                        <?php echo ucfirst($booking['payment_status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards (visible only on small screens) -->
                <div class="md:hidden">
                    <?php foreach ($all_bookings as $booking): ?>
                    <div class="p-4 border-b">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h3 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($booking['name']); ?></h3>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($booking['email']); ?></p>
                            </div>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php echo $booking['payment_status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                ($booking['payment_status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                'bg-red-100 text-red-800'); ?>">
                                <?php echo ucfirst($booking['payment_status']); ?>
                            </span>
                        </div>
                        <div class="space-y-1">
                            <div class="flex justify-between">
                                <span class="text-xs text-gray-500">Package:</span>
                                <span class="text-xs font-medium"><?php echo htmlspecialchars($booking['package_name']); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-xs text-gray-500">Travelers:</span>
                                <span class="text-xs font-medium"><?php echo htmlspecialchars($booking['travelers']); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-xs text-gray-500">Amount:</span>
                                <span class="text-xs font-medium">₹<?php echo number_format($booking['total_amount'], 2); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-xs text-gray-500">Transaction ID:</span>
                                <span class="text-xs font-medium"><?php echo htmlspecialchars($booking['transaction_id'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-xs text-gray-500">Payment Date:</span>
                                <span class="text-xs font-medium"><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add any additional JavaScript functionality here
    </script>
</body>
</html>