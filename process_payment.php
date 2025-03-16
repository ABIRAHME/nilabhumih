<?php
// Include database connection
require_once 'includes/db-connection.php';

// Initialize response variables
$success = false;
$message = '';
$redirect_url = '';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $required_fields = ['package_id', 'first_name', 'last_name', 'email', 'phone', 'travel_date', 'travelers', 'package_price', 'taxes_fees', 'total_amount'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        $message = 'Missing required fields: ' . implode(', ', $missing_fields);
    } else {
        // Sanitize and prepare data
        $package_id = (int)$_POST['package_id'];
        $first_name = htmlspecialchars(trim($_POST['first_name']));
        $last_name = htmlspecialchars(trim($_POST['last_name']));
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $phone = htmlspecialchars(trim($_POST['phone']));
        $travel_date = htmlspecialchars(trim($_POST['travel_date']));
        $travelers = (int)$_POST['travelers'];
        $special_requirements = isset($_POST['special_requirements']) ? htmlspecialchars(trim($_POST['special_requirements'])) : '';
        $package_price = (float)$_POST['package_price'];
        $taxes_fees = (float)$_POST['taxes_fees'];
        $total_amount = (float)$_POST['total_amount'];
        $payment_status = 'pending'; // Default status
        
        try {
            // Get database connection
            $conn = getDbConnection();
            
            if ($conn) {
                // Begin transaction
                $conn->beginTransaction();
                
                // Insert booking record
                $stmt = $conn->prepare("INSERT INTO bookings 
                    (package_id, first_name, last_name, email, phone, travel_date, travelers, 
                    special_requirements, package_price, taxes_fees, total_amount, payment_status) 
                    VALUES (:package_id, :first_name, :last_name, :email, :phone, :travel_date, 
                    :travelers, :special_requirements, :package_price, :taxes_fees, :total_amount, :payment_status)");
                
                $stmt->bindParam(':package_id', $package_id, PDO::PARAM_INT);
                $stmt->bindParam(':first_name', $first_name, PDO::PARAM_STR);
                $stmt->bindParam(':last_name', $last_name, PDO::PARAM_STR);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
                $stmt->bindParam(':travel_date', $travel_date, PDO::PARAM_STR);
                $stmt->bindParam(':travelers', $travelers, PDO::PARAM_INT);
                $stmt->bindParam(':special_requirements', $special_requirements, PDO::PARAM_STR);
                $stmt->bindParam(':package_price', $package_price, PDO::PARAM_STR);
                $stmt->bindParam(':taxes_fees', $taxes_fees, PDO::PARAM_STR);
                $stmt->bindParam(':total_amount', $total_amount, PDO::PARAM_STR);
                $stmt->bindParam(':payment_status', $payment_status, PDO::PARAM_STR);
                
                $stmt->execute();
                $booking_id = $conn->lastInsertId();
                
                // Commit transaction
                $conn->commit();
                
                $success = true;
                $message = 'Booking created successfully!';
                
                // Initialize SSLCOMMERZ payment gateway
                require_once 'includes/sslcommerz-config.php';
                
                // Create unique transaction ID using booking ID and timestamp
                $tran_id = $booking_id . '_' . time();
                
                // Prepare customer info
                $cus_name = $first_name . ' ' . $last_name;
                $cus_email = $email;
                $cus_phone = $phone;
                
                // Prepare product info
                $stmt = $conn->prepare("SELECT title FROM tour_packages WHERE id = :package_id");
                $stmt->bindParam(':package_id', $package_id, PDO::PARAM_INT);
                $stmt->execute();
                $package_title = $stmt->fetchColumn() ?: 'Tour Package';
                
                // Prepare SSLCOMMERZ post data
                $post_data = array(
                    'store_id' => $store_id,
                    'store_passwd' => $store_password,
                    'total_amount' => $total_amount,
                    'currency' => 'BDT',
                    'tran_id' => $tran_id,
                    'success_url' => $success_url,
                    'fail_url' => $fail_url,
                    'cancel_url' => $cancel_url,
                    'ipn_url' => $ipn_url,
                    'cus_name' => $cus_name,
                    'cus_email' => $cus_email,
                    'cus_phone' => $cus_phone,
                    'cus_add1' => 'N/A', // Adding required customer address field
                    'cus_city' => 'N/A',
                    'cus_state' => 'N/A',
                    'cus_postcode' => 'N/A',
                    'cus_country' => 'Bangladesh',
                    'product_name' => $package_title,
                    'product_category' => 'Travel',
                    'product_profile' => 'general',
                    'shipping_method' => 'NO', // Adding required shipping method parameter
                    'ship_name' => $cus_name,
                    'ship_add1' => 'N/A',
                    'ship_city' => 'N/A',
                    'ship_state' => 'N/A',
                    'ship_postcode' => 'N/A',
                    'ship_country' => 'Bangladesh',
                );
                
                // Update booking with transaction ID
                $stmt = $conn->prepare("UPDATE bookings SET transaction_id = :transaction_id WHERE id = :booking_id");
                $stmt->bindParam(':transaction_id', $tran_id, PDO::PARAM_STR);
                $stmt->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
                $stmt->execute();
                
                // Initialize CURL
                $handle = curl_init();
                curl_setopt($handle, CURLOPT_URL, $sslcommerz_url);
                curl_setopt($handle, CURLOPT_POST, 1);
                curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);
                curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
                
                $content = curl_exec($handle);
                
                if ($content) {
                    $response = json_decode($content, true);
                    
                    // Log the full response for debugging
                    error_log('SSLCOMMERZ Response: ' . json_encode($response));
                    
                    // Check if the API request was successful
                    if (isset($response['status']) && $response['status'] == 'SUCCESS') {
                        // Redirect to the payment gateway URL
                        $redirect_url = $response['GatewayPageURL'];
                    } else {
                        // If API request failed, redirect to payment failure page with detailed error
                        $error_message = isset($response['failedreason']) ? $response['failedreason'] : 'Payment gateway initialization failed';
                        error_log('SSLCOMMERZ Error: ' . $error_message);
                        $redirect_url = 'payment_fail.php?booking_id=' . $booking_id . '&error=' . urlencode($error_message);
                    }
                } else {
                    // If CURL request failed
                    $error_message = 'Failed to connect to payment gateway: ' . curl_error($handle);
                    error_log('SSLCOMMERZ Error: ' . $error_message . ' - ' . curl_error($handle));
                    $redirect_url = 'payment_fail.php?booking_id=' . $booking_id;
                }
                
                curl_close($handle);
            } else {
                $message = 'Database connection failed';
            }
        } catch (PDOException $e) {
            // Rollback transaction on error
            if ($conn) {
                $conn->rollBack();
            }
            $message = 'Error processing booking: ' . $e->getMessage();
            error_log($message);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $success ? 'Payment Processing' : 'Booking Status'; ?> - Nilabhoomi Tours and Travels</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#008DDA',
                        secondary: '#41C9E2',
                        tertiary: '#ACE2E1',
                        accent: '#F7EEDD'
                    }
                }
            }
        }
    </script>
     <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    
    <style>
        .wave-pattern {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 320'%3E%3Cpath fill='%23ACE2E1' fill-opacity='0.4' d='M0,128L48,117.3C96,107,192,85,288,90.7C384,96,480,128,576,133.3C672,139,768,117,864,128C960,139,1056,181,1152,176C1248,171,1344,117,1392,90.7L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z'%3E%3C/path%3E%3C/svg%3E");
            background-size: cover;
            background-position: bottom;
        }
    </style>
</head>
<body class="bg-accent min-h-screen flex flex-col">
    <?php include 'partials/navigation.php'; ?>

    <div class="wave-pattern flex-grow flex items-center justify-center py-12 px-4">
        <div class="max-w-md w-full bg-white rounded-xl shadow-xl overflow-hidden">
            <?php if ($success): ?>
                <div class="py-8 px-6 md:p-10">
                    <div class="flex flex-col items-center justify-center">
                        <div class="w-20 h-20 rounded-full bg-tertiary flex items-center justify-center mb-6">
                            <svg class="w-10 h-10 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-primary mb-3">Booking Confirmed!</h2>
                        <div class="w-16 h-1 bg-secondary rounded mb-6"></div>
                        <p class="text-gray-600 text-center mb-6">Your booking has been successfully created. You will now be redirected to the payment page.</p>
                        
                        <div class="relative my-6">
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="h-12 w-12 rounded-full border-t-4 border-b-4 border-primary animate-spin"></div>
                            </div>
                            <div class="h-12"></div>
                        </div>
                        
                        <p class="text-sm text-gray-500 mt-2 mb-4 text-center">If you are not redirected automatically, please click the button below.</p>
                        
                        <a href="<?php echo $redirect_url; ?>" class="w-full px-6 py-3 bg-primary text-white text-center rounded-lg hover:bg-secondary transition-colors duration-300 transform hover:-translate-y-1 shadow-md">
                            Proceed to Payment
                        </a>
                    </div>
                </div>
                <script>
                    // Redirect after 3 seconds
                    setTimeout(function() {
                        window.location.href = "<?php echo $redirect_url; ?>";
                    }, 3000);
                </script>
            <?php else: ?>
                <div class="py-8 px-6 md:p-10">
                    <div class="flex flex-col items-center justify-center">
                        <div class="w-20 h-20 rounded-full bg-red-100 flex items-center justify-center mb-6">
                            <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-3">Booking Error</h2>
                        <div class="w-16 h-1 bg-red-400 rounded mb-6"></div>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 text-sm text-center text-gray-700">
                            <?php echo $message ?: 'There was an error processing your booking. Please try again.'; ?>
                        </div>
                        
                        <a href="javascript:history.back()" class="w-full px-6 py-3 bg-primary text-white text-center rounded-lg hover:bg-secondary transition-colors duration-300 transform hover:-translate-y-1 shadow-md flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Go Back
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="bg-primary text-white py-6">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <p class="text-sm">&copy; <?php echo date('Y'); ?> Nilabhoomi Tours and Travels. All rights reserved.</p>
                </div>
                <div class="flex space-x-4">
                    <a href="#" class="text-white hover:text-accent transition-colors">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M22.675 0h-21.35c-.732 0-1.325.593-1.325 1.325v21.351c0 .731.593 1.324 1.325 1.324h11.495v-9.294h-3.128v-3.622h3.128v-2.671c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.795.715-1.795 1.763v2.313h3.587l-.467 3.622h-3.12v9.293h6.116c.73 0 1.323-.593 1.323-1.325v-21.35c0-.732-.593-1.325-1.325-1.325z"/>
                        </svg>
                    </a>
                    <a href="#" class="text-white hover:text-accent transition-colors">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                        </svg>
                    </a>
                    <a href="#" class="text-white hover:text-accent transition-colors">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>