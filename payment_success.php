<?php
// Include database connection and SSLCOMMERZ configuration
require_once 'includes/db-connection.php';
require_once 'includes/sslcommerz-config.php';

// Initialize variables
$booking = null;
$error_message = '';
$success = false;

// Check if payment data is received from SSLCOMMERZ
if (isset($_POST['val_id']) && !empty($_POST['val_id'])) {
    $validation_id = $_POST['val_id'];
    // Fix the variable assignment - use the imported variables correctly
    $store_id_value = $store_id;
    $store_passwd_value = $store_password;
    
    // Validate the transaction with SSLCOMMERZ
    $validation_url_value = $validation_url;
    $requested_url = $validation_url_value . "?val_id=" . $validation_id . "&store_id=" . $store_id_value . "&store_passwd=" . $store_passwd_value . "&v=1&format=json";
    
    $handle = curl_init();
    curl_setopt($handle, CURLOPT_URL, $requested_url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($handle);
    
    if ($result) {
        $result = json_decode($result);
        
        // Check if the transaction is valid
        if (isset($result->status) && $result->status == 'VALID') {
            $tran_id = $result->tran_id;
            $amount = $result->amount;
            $card_type = $result->card_type;
            
            try {
                // Get database connection
                $conn = getDbConnection();
                
                if ($conn) {
                    // Check if it's a school/corporate booking (SCH_ prefix) or regular booking
                    $booking_parts = explode('_', $tran_id);
                    $is_school_booking = false;
                    
                    if (isset($booking_parts[0]) && $booking_parts[0] === 'SCH') {
                        // School/Corporate booking
                        $is_school_booking = true;
                        $booking_id = $booking_parts[1];
                        
                        // Update school/corporate booking payment status
                        $stmt = $conn->prepare("UPDATE booking_sch_cor 
                                              SET payment_status = 'completed', 
                                                  transaction_id = :transaction_id,
                                                  payment_method = :payment_method,
                                                  payment_date = NOW() 
                                              WHERE id = :booking_id");
                        
                        $stmt->bindParam(':transaction_id', $tran_id, PDO::PARAM_STR);
                        $stmt->bindParam(':payment_method', $card_type, PDO::PARAM_STR);
                        $stmt->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
                        $stmt->execute();
                        
                        // Fetch school/corporate booking details
                        $stmt = $conn->prepare("SELECT b.*, p.title as package_title, p.duration as package_duration, p.image as image_path 
                                              FROM booking_sch_cor b 
                                              LEFT JOIN tour_packages p ON b.package_id = p.id 
                                              WHERE b.id = :booking_id");
                        $stmt->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
                        $stmt->execute();
                    } else {
                        // Regular booking
                        $booking_id = $booking_parts[0];
                        
                        // Update regular booking payment status
                        $stmt = $conn->prepare("UPDATE bookings 
                                              SET payment_status = 'completed', 
                                                  transaction_id = :transaction_id,
                                                  payment_method = :payment_method,
                                                  payment_date = NOW() 
                                              WHERE id = :booking_id");
                        
                        $stmt->bindParam(':transaction_id', $tran_id, PDO::PARAM_STR);
                        $stmt->bindParam(':payment_method', $card_type, PDO::PARAM_STR);
                        $stmt->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
                        $stmt->execute();
                        
                        // Fetch regular booking details
                        $stmt = $conn->prepare("SELECT b.*, p.title as package_title, p.duration as package_duration, p.image as image_path 
                                              FROM bookings b 
                                              LEFT JOIN tour_packages p ON b.package_id = p.id 
                                              WHERE b.id = :booking_id");
                        $stmt->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
                        $stmt->execute();
                    }
                    
                    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $booking = $row;
                        $booking['is_school_booking'] = $is_school_booking;
                        // Set default image if not available
                        if (empty($booking['image_path'])) {
                            $booking['image_path'] = 'images/demo.jpeg';
                        }
                        $success = true;
                    } else {
                        $error_message = 'Booking not found';
                    }
                } else {
                    $error_message = 'Database connection failed';
                }
            } catch (PDOException $e) {
                $error_message = 'Error processing payment: ' . $e->getMessage();
                error_log($error_message);
            }
        } else {
            $error_message = 'Invalid transaction';
        }
    } else {
        $error_message = 'Error validating transaction';
    }
    
    curl_close($handle);
} else if (isset($_GET['booking_id']) && !empty($_GET['booking_id'])) {
    // If redirected from booking success page
    $booking_id = (int)$_GET['booking_id'];
    $is_school_booking = isset($_GET['type']) && $_GET['type'] === 'school';
    
    try {
        // Get database connection
        $conn = getDbConnection();
        
        if ($conn) {
            if ($is_school_booking) {
                // Fetch school/corporate booking details
                $stmt = $conn->prepare("SELECT b.*, p.title as package_title, p.duration as package_duration, p.image as image_path 
                                      FROM booking_sch_cor b 
                                      LEFT JOIN tour_packages p ON b.package_id = p.id 
                                      WHERE b.id = :booking_id");
                $stmt->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
                $stmt->execute();
            } else {
                // Fetch regular booking details
                $stmt = $conn->prepare("SELECT b.*, p.title as package_title, p.duration as package_duration, p.image as image_path 
                                      FROM bookings b 
                                      LEFT JOIN tour_packages p ON b.package_id = p.id 
                                      WHERE b.id = :booking_id");
                $stmt->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
                $stmt->execute();
            }
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $booking = $row;
                $booking['is_school_booking'] = $is_school_booking;
                // Set default image if not available
                if (empty($booking['image_path'])) {
                    $booking['image_path'] = 'images/demo.jpeg';
                }
                $success = true;
            } else {
                $error_message = 'Booking not found';
            }
        } else {
            $error_message = 'Database connection failed';
        }
    } catch (PDOException $e) {
        $error_message = 'Error retrieving booking: ' . $e->getMessage();
        error_log($error_message);
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
    <title>Payment Successful - Nilabhoomi Tours and Travels</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#008DDA',
                        secondary: '#41C9E2',
                        accent: '#ACE2E1',
                        light: '#F7EEDD'
                    }
                }
            }
        }
    </script>
     <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    
    <style>
        .bg-wave-pattern {
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='20' viewBox='0 0 100 20' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M21.184 20c.357-.13.72-.264 1.088-.402l1.768-.661C33.64 15.347 39.647 14 50 14c10.271 0 15.362 1.222 24.629 4.928.955.383 1.869.74 2.75 1.072h6.225c-2.51-.73-5.139-1.691-8.233-2.928C65.888 13.278 60.562 12 50 12c-10.626 0-16.855 1.397-26.66 5.063l-1.767.662c-2.475.923-4.66 1.674-6.724 2.275h6.335zm0-20C13.258 2.892 8.077 4 0 4V2c5.744 0 9.951-.574 14.85-2h6.334zM77.38 0C85.239 2.966 90.502 4 100 4V2c-6.842 0-11.386-.542-16.396-2h-6.225zM0 14c8.44 0 13.718-1.21 22.272-4.402l1.768-.661C33.64 5.347 39.647 4 50 4c10.271 0 15.362 1.222 24.629 4.928C84.112 12.722 89.438 14 100 14v-2c-10.271 0-15.362-1.222-24.629-4.928C65.888 3.278 60.562 2 50 2 39.374 2 33.145 3.397 23.34 7.063l-1.767.662C13.223 10.84 8.163 12 0 12v2z' fill='%23ACE2E1' fill-opacity='0.2' fill-rule='evenodd'/%3E%3C/svg%3E");
        }
    </style>
</head>
<body class="bg-light bg-wave-pattern min-h-screen">
    <?php include 'partials/navigation.php'; ?>

    <div class="container mx-auto px-4 py-16">
        <div class="max-w-4xl mx-auto bg-white rounded-2xl shadow-xl overflow-hidden transform transition-all hover:shadow-2xl">
            <?php if ($success && $booking): ?>
                <div class="bg-gradient-to-r from-primary to-secondary h-2"></div>
                <div class="p-8">
                    <div class="flex items-center justify-center mb-8">
                        <div class="bg-accent rounded-full p-4 shadow-md">
                            <svg class="w-16 h-16 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </div>
                    
                    <h1 class="text-4xl font-bold text-center text-primary mb-6">Payment Successful!</h1>
                    <p class="text-center text-gray-600 mb-8 max-w-2xl mx-auto">Your booking has been confirmed and your payment has been processed successfully. Please bring this receipt with you on your travel date.</p>
                    
                    <div class="border-t border-b border-gray-200 py-8 mb-8">
                        <div class="flex items-center mb-6">
                            <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-semibold text-primary">Booking Details</h2>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-accent bg-opacity-20 p-6 rounded-lg">
                            <div>
                                <p class="text-gray-700 mb-3"><span class="font-medium text-primary">Booking ID:</span> #<?php echo $booking['id']; ?></p>
                                <p class="text-gray-700 mb-3"><span class="font-medium text-primary">Package:</span> <?php echo $booking['package_title']; ?></p>
                                <p class="text-gray-700 mb-3"><span class="font-medium text-primary">Duration:</span> <?php echo $booking['package_duration']; ?></p>
                                <p class="text-gray-700 mb-3"><span class="font-medium text-primary">Travel Date:</span> <?php echo date('F j, Y', strtotime($booking['travel_date'])); ?></p>
                                <p class="text-gray-700 mb-3"><span class="font-medium text-primary">Travelers:</span> <?php echo $booking['travelers']; ?></p>
                            </div>
                            <div>
                                <?php if (isset($booking['is_school_booking']) && $booking['is_school_booking']): ?>
                                    <p class="text-gray-700 mb-3"><span class="font-medium text-primary">Institute:</span> <?php echo $booking['institute_name']; ?></p>
                                <?php else: ?>
                                    <p class="text-gray-700 mb-3"><span class="font-medium text-primary">Name:</span> <?php echo $booking['first_name'] . ' ' . $booking['last_name']; ?></p>
                                <?php endif; ?>
                                <p class="text-gray-700 mb-3"><span class="font-medium text-primary">Email:</span> <?php echo $booking['email']; ?></p>
                                <p class="text-gray-700 mb-3"><span class="font-medium text-primary">Phone:</span> <?php echo $booking['phone']; ?></p>
                                <p class="text-gray-700 mb-3"><span class="font-medium text-primary">Transaction ID:</span> <?php echo $booking['transaction_id']; ?></p>
                                <p class="text-gray-700 mb-3"><span class="font-medium text-primary">Payment Method:</span> <?php echo $booking['payment_method']; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="border-b border-gray-200 py-8 mb-8">
                        <div class="flex items-center mb-6">
                            <div class="w-10 h-10 rounded-full bg-secondary flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-semibold text-secondary">Payment Summary</h2>
                        </div>
                        <div class="bg-white p-6 rounded-lg shadow-md">
                            <div class="flex justify-between mb-3">
                                <span class="text-gray-600">Package Price:</span>
                                <span class="text-gray-800 font-medium">Tk <?php echo number_format($booking['package_price'], 2); ?></span>
                            </div>
                            
                            <?php if (isset($booking['is_school_booking']) && $booking['is_school_booking']): ?>
                                <div class="flex justify-between mb-3">
                                    <span class="text-gray-600">Subtotal (<?php echo $booking['travelers']; ?> travelers):</span>
                                    <span class="text-gray-800 font-medium">Tk <?php echo number_format($booking['subtotal'], 2); ?></span>
                                </div>
                                
                                <?php if ($booking['discount'] > 0): ?>
                                <div class="flex justify-between mb-3">
                                    <span class="text-gray-600">Discount:</span>
                                    <span class="text-green-600 font-medium">-Tk<?php echo number_format($booking['discount'], 2); ?></span>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <div class="flex justify-between mb-3">
                                <span class="text-gray-600">Taxes & Fees:</span>
                                <span class="text-gray-800 font-medium">Tk <?php echo number_format($booking['taxes_fees'], 2); ?></span>
                            </div>
                            
                            <div class="border-t border-gray-200 my-4"></div>
                            
                            <?php if (isset($booking['is_school_booking']) && $booking['is_school_booking'] && $booking['partial_payment']): ?>
                            <div class="flex justify-between mb-3">
                                <span class="text-gray-600">Total Amount:</span>
                                <span class="text-gray-800 font-medium">Tk <?php echo number_format($booking['total_amount'], 2); ?></span>
                            </div>
                            <div class="flex justify-between font-bold text-lg">
                                <span class="text-primary">Partial Payment (30%):</span>
                                <span class="text-primary">Tk <?php echo number_format($booking['payment_amount'], 2); ?></span>
                            </div>
                            <?php else: ?>
                            <div class="flex justify-between font-bold text-lg">
                                <span class="text-primary">Total Paid:</span>
                                <span class="text-primary">Tk <?php echo number_format($booking['total_amount'], 2); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <p class="text-gray-600 mb-8">A confirmation email has been sent to <span class="font-medium"><?php echo $booking['email']; ?></span></p>
                        <div class="flex flex-col sm:flex-row justify-center gap-6 mb-6">
                            <a href="generate_payment_pdf.php?booking_id=<?php echo $booking['id']; ?><?php echo isset($booking['is_school_booking']) && $booking['is_school_booking'] ? '&type=school' : ''; ?>" class="inline-block px-8 py-4 bg-secondary text-white rounded-full hover:bg-primary transition-colors duration-300 flex items-center justify-center shadow-lg transform hover:scale-105">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Download Receipt
                            </a>
                            <a href="index.php" class="inline-block px-8 py-4 bg-primary text-white rounded-full hover:bg-secondary transition-colors duration-300 shadow-lg transform hover:scale-105">
                                Return to Home
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-gradient-to-r from-red-500 to-orange-500 h-2"></div>
                <div class="p-8 text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-red-100 mb-6">
                        <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold text-red-600 mb-4">Payment Error</h2>
                    <p class="text-gray-600 mb-8 max-w-md mx-auto"><?php echo $error_message ?: 'There was an error processing your payment. Please try again.'; ?></p>
                    <a href="index.php" class="inline-block px-8 py-4 bg-primary text-white rounded-full hover:bg-secondary transition-colors duration-300 shadow-lg">Return to Home</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Customer Support Section -->
        <div class="max-w-4xl mx-auto mt-12 bg-white rounded-2xl shadow-lg p-8 text-center">
            <h3 class="text-2xl font-semibold text-primary mb-4">Need assistance?</h3>
            <p class="text-gray-600 mb-6">Our customer support team is available 24/7 to help with any questions.</p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="tel:+8801234567890" class="flex items-center justify-center px-6 py-3 bg-accent text-primary rounded-full hover:bg-primary hover:text-white transition-colors duration-300">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    </svg>
                    Call Support
                </a>
                <a href="mailto:support@nilabhoomi.com" class="flex items-center justify-center px-6 py-3 bg-accent text-primary rounded-full hover:bg-primary hover:text-white transition-colors duration-300">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    Email Support
                </a>
            </div>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>
</body>
</html>