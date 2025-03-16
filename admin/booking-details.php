<?php
session_start();
require_once 'db-parameters.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Initialize variables
$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$booking_type = isset($_GET['type']) ? $_GET['type'] : 'normal';
$booking = null;
$error_message = '';

// Connect to database
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get booking details based on type
    if ($booking_type === 'normal') {
        $sql = "SELECT b.*, p.title, p.duration, p.description 
               FROM bookings b 
               JOIN tour_packages p ON b.package_id = p.id 
               WHERE b.id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $booking_id, PDO::PARAM_INT);
        $stmt->execute();
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        // School or Corporate booking
        $sql = "SELECT b.*, p.title, p.duration, p.description 
               FROM booking_sch_cor b 
               JOIN tour_packages p ON b.package_id = p.id 
               WHERE b.id = :id AND b.package_type = :type";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $booking_id, PDO::PARAM_INT);
        $stmt->bindParam(':type', $booking_type, PDO::PARAM_STR);
        $stmt->execute();
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$booking) {
        $error_message = 'Booking not found';
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

$due_amount = $booking['total_amount'] - $booking['payment_amount'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - Admin Dashboard</title>
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
                <a href="bookings-list.php?type=<?php echo $booking_type; ?>" class="text-primary hover:text-secondary mr-2">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="text-2xl font-bold text-gray-800">Booking Details</h1>
            </div>
            
            <?php if (isset($error_message) && !empty($error_message)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p><?php echo $error_message; ?></p>
                </div>
            <?php elseif ($booking): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <!-- Header with package title -->
                    <div class="bg-primary text-white px-6 py-4">
                        <h2 class="text-xl font-bold"><?php echo htmlspecialchars($booking['title']); ?></h2>
                        <p class="text-sm opacity-80">
                            <?php if ($booking_type === 'normal'): ?>
                                Booking #<?php echo $booking['id']; ?> - <?php echo date('F d, Y', strtotime($booking_type === 'normal' ? $booking['booking_date'] : $booking['created_at'])); ?>
                            <?php else: ?>
                                <?php echo ucfirst($booking_type); ?> Booking #<?php echo $booking['id']; ?> - <?php echo date('F d, Y', strtotime($booking['created_at'])); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <!-- Booking details -->
                    <div class="p-6">
                        <!-- Status badge -->
                        <div class="mb-6 flex justify-between items-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo getStatusBadgeClass($booking['payment_status']); ?>">
                                <?php echo ucfirst($booking['payment_status']); ?>
                            </span>
                            
                           
                        </div>
                        
                        <!-- Two column layout for details -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Left column: Customer/Institute info -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                                    <?php echo $booking_type === 'normal' ? 'Customer Information' : 'Institute Information'; ?>
                                </h3>
                                <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                                    <?php if ($booking_type === 'normal'): ?>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Name:</span>
                                            <span class="font-medium"><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Institute Name:</span>
                                            <span class="font-medium"><?php echo htmlspecialchars($booking['institute_name']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Email:</span>
                                        <span class="font-medium"><?php echo htmlspecialchars($booking['email']); ?></span>
                                    </div>
                                    
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Phone:</span>
                                        <span class="font-medium"><?php echo htmlspecialchars($booking['phone']); ?></span>
                                    </div>
                                    
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Travel Date:</span>
                                        <span class="font-medium"><?php echo date('F d, Y', strtotime($booking['travel_date'])); ?></span>
                                    </div>
                                    
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Number of Travelers:</span>
                                        <span class="font-medium"><?php echo number_format($booking['travelers']); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Right column: Payment details -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 mb-4">Payment Information</h3>
                                <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                                    <?php if ($booking_type === 'normal'): ?>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Package Price:</span>
                                            <span class="font-medium">Tk <?php echo number_format($booking['package_price'], 2); ?></span>
                                        </div>
                                        
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Taxes & Fees:</span>
                                            <span class="font-medium">Tk <?php echo number_format($booking['taxes_fees'], 2); ?></span>
                                        </div>
                                        
                                        <div class="flex justify-between font-bold">
                                            <span>Total Amount:</span>
                                            <span>Tk <?php echo number_format($booking['total_amount'], 2); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Package Price (per person):</span>
                                            <span class="font-medium">Tk <?php echo number_format($booking['package_price'], 2); ?></span>
                                        </div>
                                        
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Subtotal:</span>
                                            <span class="font-medium">Tk <?php echo number_format($booking['subtotal'], 2); ?></span>
                                        </div>
                                        
                                        <?php if ($booking['discount'] > 0): ?>
                                            <div class="flex justify-between text-green-600">
                                                <span>Discount:</span>
                                                <span>-Tk <?php echo number_format($booking['discount'], 2); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Taxes & Fees:</span>
                                            <span class="font-medium">Tk <?php echo number_format($booking['taxes_fees'], 2); ?></span>
                                        </div>
                                        
                                        <div class="flex justify-between font-bold">
                                            <span>Total Amount:</span>
                                            <span>Tk <?php echo number_format($booking['total_amount'], 2); ?></span>
                                        </div>
                                        
                                        <?php if ($booking['partial_payment']): ?>
                                            <div class="flex justify-between text-primary">
                                                <span>Payment Amount (30%):</span>
                                                <span>Tk <?php echo number_format($booking['payment_amount'], 2); ?></span>
                                            </div>
                                            <div class="flex justify-between font-bold">
                                            <span>Due Amount:</span>
                                            <span>Tk <?php echo number_format($due_amount, 2); ?></span>
                                        </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($booking['transaction_id'])): ?>
                                        <div class="pt-2 border-t border-gray-200">
                                            <div class="flex justify-between">
                                                <span class="text-gray-600">Transaction ID:</span>
                                                <span class="font-medium"><?php echo htmlspecialchars($booking['transaction_id']); ?></span>
                                            </div>
                                            
                                            <?php if (!empty($booking['payment_method'])): ?>
                                                <div class="flex justify-between">
                                                    <span class="text-gray-600">Payment Method:</span>
                                                    <span class="font-medium"><?php echo htmlspecialchars($booking['payment_method']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($booking['payment_date'])): ?>
                                                <div class="flex justify-between">
                                                    <span class="text-gray-600">Payment Date:</span>
                                                    <span class="font-medium"><?php echo date('F d, Y H:i', strtotime($booking['payment_date'])); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Special Requirements -->
                        <?php if (!empty($booking['special_requirements'])): ?>
                            <div class="mt-6">
                                <h3 class="text-lg font-semibold text-gray-800 mb-2">Special Requirements</h3>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($booking['special_requirements'])); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Package Details -->
                        <div class="mt-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Package Details</h3>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex justify-between mb-2">
                                    <span class="text-gray-600">Package Name:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($booking['title']); ?></span>
                                </div>
                                <div class="flex justify-between mb-2">
                                    <span class="text-gray-600">Duration:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($booking['duration']); ?></span>
                                </div>
                                <?php if (!empty($booking['description'])): ?>
                                    <div class="mt-2">
                                        <span class="text-gray-600 block mb-1">Description:</span>
                                        <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($booking['description'])); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="mt-8 flex flex-wrap gap-3 justify-end">
                            <a href="bookings-list.php?type=<?php echo $booking_type; ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors">
                                Back to List
                            </a>
                            
                          
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
   
</body>
</html>