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
    
    try {
        // Get database connection
        $conn = getDbConnection();
        
        if ($conn) {
            // Extract booking ID from transaction ID (assuming tran_id format: BOOKING_ID_TIMESTAMP)
            $booking_id = explode('_', $tran_id)[0];
            
            // Update booking payment status to canceled
            $stmt = $conn->prepare("UPDATE bookings 
                                  SET payment_status = 'canceled' 
                                  WHERE id = :booking_id");
            
            $stmt->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Fetch booking details
            $stmt = $conn->prepare("SELECT b.*, p.title as package_title 
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
} else {
    $error_message = 'No payment information received';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Canceled - Nilabhoomi Tours and Travels</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            blue: '#008DDA',
                            teal: '#41C9E2',
                            light: '#ACE2E1',
                            cream: '#F7EEDD'
                        }
                    },
                    boxShadow: {
                        'custom': '0 10px 25px -5px rgba(0, 141, 218, 0.1), 0 8px 10px -6px rgba(0, 141, 218, 0.1)',
                    }
                }
            }
        }
    </script>
     <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
</head>
<body class="bg-gradient-to-br from-brand-cream to-white min-h-screen">
    <?php include 'partials/navigation.php'; ?>

    <div class="container mx-auto px-4 py-12 md:py-20">
        <div class="max-w-xl mx-auto bg-white rounded-2xl shadow-custom p-6 md:p-10 animate__animated animate__fadeIn">
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-brand-light mb-6 animate__animated animate__pulse animate__infinite animate__slow">
                    <svg class="w-10 h-10 text-brand-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-brand-blue mb-4">Payment Canceled</h2>
                <div class="h-1 w-20 bg-brand-teal mx-auto mb-6 rounded-full"></div>
                <p class="text-gray-600 mb-8 text-lg">Your payment process has been canceled. Your booking is still saved but not confirmed.</p>
                
                
                
                <a href="index.php" class="inline-block px-6 py-3 bg-brand-cream text-gray-700 rounded-lg hover:bg-opacity-80 transition-all duration-300 border border-brand-light">
                    <div class="flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        Return to Home
                    </div>
                </a>
            </div>
            
            <?php if ($error_message): ?>
                <div class="mt-8 p-4 bg-red-50 text-red-700 rounded-lg">
                    <div class="flex">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span><?php echo htmlspecialchars($error_message); ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="mt-8 text-center text-gray-500 text-sm">
            <p>Having trouble? <a href="contact.php" class="text-brand-blue hover:underline">Contact our support team</a></p>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>
    
    <script>
        // Add smooth page transitions
        document.addEventListener('DOMContentLoaded', function() {
            const links = document.querySelectorAll('a');
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    if (this.hostname === window.location.hostname) {
                        const card = document.querySelector('.max-w-xl');
                        card.classList.remove('animate__fadeIn');
                        card.classList.add('animate__fadeOut');
                    }
                });
            });
        });
    </script>
</body>
</html>