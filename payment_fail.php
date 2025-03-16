<?php
// Include database connection and SSLCOMMERZ configuration
require_once 'includes/db-connection.php';
require_once 'includes/sslcommerz-config.php';

// Initialize variables
$error_message = '';
$booking_id = null;

// Check if payment data is received from SSLCOMMERZ
if (isset($_POST['tran_id']) && !empty($_POST['tran_id'])) {
    $tran_id = $_POST['tran_id'];
    // Fix the variable assignment - use the imported variables correctly
    $store_id_value = $store_id;
    $store_passwd_value = $store_password;
    
    try {
        // Get database connection
        $conn = getDbConnection();
        
        if ($conn) {
            // Extract booking ID from transaction ID (assuming tran_id format: BOOKING_ID_TIMESTAMP)
            $booking_id = explode('_', $tran_id)[0];
            
            // Update booking payment status to failed
            $stmt = $conn->prepare("UPDATE bookings 
                                  SET payment_status = 'failed' 
                                  WHERE id = :booking_id");
            
            $stmt->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Fetch booking details
            $stmt = $conn->prepare("SELECT b.*, p.title as package_title, p.image as image_path 
                                  FROM bookings b 
                                  LEFT JOIN tour_packages p ON b.package_id = p.id 
                                  WHERE b.id = :booking_id");
            $stmt->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
            $stmt->execute();
            
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                $error_message = 'Booking not found';
            }
        } else {
            $error_message = 'Database connection failed';
        }
    } catch (PDOException $e) {
        $error_message = 'Error processing payment: ' . $e->getMessage();
        error_log($error_message);
    }
} else if (isset($_GET['booking_id']) && !empty($_GET['booking_id'])) {
    // If redirected with booking ID
    $booking_id = (int)$_GET['booking_id'];
    
    // Check if error message is provided in URL
    if (isset($_GET['error']) && !empty($_GET['error'])) {
        $error_message = urldecode($_GET['error']);
    }
} else {
    $error_message = 'No payment information received';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed - Nilabhoomi Tours and Travels</title>
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
     <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    
</head>
<body class="bg-gradient-to-r from-blue-50 to-indigo-50">
    <?php include 'partials/navigation.php'; ?>

    <div class="container mx-auto px-4 py-16">
        <div class="max-w-lg mx-auto bg-white rounded-lg shadow-lg p-8">
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-100 mb-6">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Payment Failed</h2>
                <p class="text-gray-600 mb-6"><?php echo $error_message ?: 'Your payment could not be processed. Please try again or contact customer support.'; ?></p>
                
                <?php if ($booking_id): ?>
                    <div class="space-y-4 mb-6">
                        <a href="index.php" class="block w-full px-6 py-3 bg-primary text-white rounded-md hover:bg-secondary transition-colors">
                            Return to Packages
                        </a>
                    </div>
                <?php endif; ?>
                
                <a href="index.php" class="inline-block px-6 py-3 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 transition-colors">
                    Return to Home
                </a>
            </div>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>
</body>
</html>