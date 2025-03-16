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
    $required_fields = ['package_id', 'institute_name', 'email', 'phone', 'travel_date', 'travelers'];
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
        $package_type = htmlspecialchars(trim($_POST['package_type']));
        $institute_name = htmlspecialchars(trim($_POST['institute_name']));
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $phone = htmlspecialchars(trim($_POST['phone']));
        $travel_date = htmlspecialchars(trim($_POST['travel_date']));
        $travelers = (int)$_POST['travelers'];
        $special_requirements = isset($_POST['special_requirements']) ? htmlspecialchars(trim($_POST['special_requirements'])) : '';
        $partial_payment = isset($_POST['partial_payment']) ? 1 : 0;
        $payment_status = 'pending'; // Default status
        
        try {
            // Get database connection
            $conn = getDbConnection();
            
            if ($conn) {
                // Begin transaction
                $conn->beginTransaction();
                
                // Get package details to calculate prices
                $stmt = $conn->prepare("SELECT price, title FROM tour_packages WHERE id = :id");
                $stmt->bindParam(':id', $package_id, PDO::PARAM_INT);
                $stmt->execute();
                $package = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$package) {
                    throw new Exception('Package not found');
                }
                
                // Calculate prices
                $package_price = floatval(preg_replace('/[^0-9.]/', '', $package['title']));
                if ($package_price <= 0) {
                    // If price extraction from title failed, try from price field
                    $package_price = floatval(preg_replace('/[^0-9.]/', '', $package['price']));
                }
                
                $subtotal = $package_price * $travelers;
                $discount = 0;
                
                // Apply 7% discount for groups of 100 or more
                if ($travelers >= 100) {
                    $discount = $subtotal * 0.07;
                }
                
                $subtotal_after_discount = $subtotal - $discount;
                $taxes_fees = $subtotal_after_discount * 0.05;
                $total_amount = $subtotal_after_discount + $taxes_fees;
                
                // If partial payment is selected, calculate 30% of total
                $payment_amount = $partial_payment ? ($total_amount * 0.3) : $total_amount;
                
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
                    )";
                    $conn->exec($sql);
                }
                
                // Insert booking record
                $stmt = $conn->prepare("INSERT INTO booking_sch_cor 
                    (package_id, package_type, institute_name, email, phone, travel_date, travelers, 
                    special_requirements, package_price, subtotal, discount, taxes_fees, total_amount, 
                    payment_amount, partial_payment, payment_status) 
                    VALUES (:package_id, :package_type, :institute_name, :email, :phone, :travel_date, 
                    :travelers, :special_requirements, :package_price, :subtotal, :discount, :taxes_fees, 
                    :total_amount, :payment_amount, :partial_payment, :payment_status)");
                
                $stmt->bindParam(':package_id', $package_id, PDO::PARAM_INT);
                $stmt->bindParam(':package_type', $package_type, PDO::PARAM_STR);
                $stmt->bindParam(':institute_name', $institute_name, PDO::PARAM_STR);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
                $stmt->bindParam(':travel_date', $travel_date, PDO::PARAM_STR);
                $stmt->bindParam(':travelers', $travelers, PDO::PARAM_INT);
                $stmt->bindParam(':special_requirements', $special_requirements, PDO::PARAM_STR);
                $stmt->bindParam(':package_price', $package_price, PDO::PARAM_STR);
                $stmt->bindParam(':subtotal', $subtotal, PDO::PARAM_STR);
                $stmt->bindParam(':discount', $discount, PDO::PARAM_STR);
                $stmt->bindParam(':taxes_fees', $taxes_fees, PDO::PARAM_STR);
                $stmt->bindParam(':total_amount', $total_amount, PDO::PARAM_STR);
                $stmt->bindParam(':payment_amount', $payment_amount, PDO::PARAM_STR);
                $stmt->bindParam(':partial_payment', $partial_payment, PDO::PARAM_INT);
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
                $tran_id = 'SCH_' . $booking_id . '_' . time();
                
                // Prepare customer info
                $cus_name = $institute_name;
                $cus_email = $email;
                $cus_phone = $phone;
                
                // Prepare product info
                $product_name = $package['title'] . ' (' . ucfirst($package_type) . ' Tour)';
                
                // Prepare SSLCOMMERZ post data
                $post_data = array(
                    'store_id' => $store_id,
                    'store_passwd' => $store_password,
                    'total_amount' => $payment_amount,
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
                    'product_name' => $product_name,
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
                $stmt = $conn->prepare("UPDATE booking_sch_cor SET transaction_id = :transaction_id WHERE id = :booking_id");
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
        } catch (Exception $e) {
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
                    },
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                    boxShadow: {
                        'custom': '0 10px 25px -5px rgba(0, 141, 218, 0.2), 0 10px 10px -5px rgba(0, 141, 218, 0.1)',
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #ACE2E1 0%, #F7EEDD 100%);
        }
        .loader {
            border-top-color: #008DDA;
            -webkit-animation: spinner 1.5s linear infinite;
            animation: spinner 1.5s linear infinite;
        }
        @-webkit-keyframes spinner {
            0% { -webkit-transform: rotate(0deg); }
            100% { -webkit-transform: rotate(360deg); }
        }
        @keyframes spinner {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
     <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    
</head>
<body class="min-h-screen">
    <?php include 'partials/navigation.php'; ?>

    <div class="container mx-auto px-4 py-8 md:py-16">
        <div class="max-w-md mx-auto bg-white rounded-2xl shadow-custom overflow-hidden">
            <?php if ($success): ?>
                <div class="bg-gradient-to-r from-primary to-secondary py-6 px-4 text-white text-center">
                    <h1 class="text-2xl font-bold">Payment Processing</h1>
                </div>
                <div class="p-6 md:p-8 text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 mb-6">
                        <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Booking Confirmed!</h2>
                    <p class="text-gray-600 mb-8">Your booking has been successfully created. You will now be redirected to the payment page.</p>
                    
                    <div class="flex justify-center mb-6">
                        <div class="loader ease-linear rounded-full border-4 border-t-4 border-gray-200 h-12 w-12"></div>
                    </div>
                    
                    <div class="bg-accent rounded-lg p-4 mb-6 text-sm text-gray-700">
                        <p>If you are not redirected automatically, please click the button below.</p>
                    </div>
                    
                    <a href="<?php echo $redirect_url; ?>" class="inline-block w-full md:w-auto px-8 py-4 bg-primary text-white font-semibold rounded-full hover:bg-secondary transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        Proceed to Payment
                    </a>
                </div>  
                <script>
                    // Redirect after 3 seconds
                    setTimeout(function() {
                        window.location.href = "<?php echo $redirect_url; ?>";
                    }, 3000);
                </script>
            <?php else: ?>
                <div class="bg-gradient-to-r from-red-500 to-red-600 py-6 px-4 text-white text-center">
                    <h1 class="text-2xl font-bold">Booking Status</h1>
                </div>
                <div class="p-6 md:p-8 text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-red-100 mb-6">
                        <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Booking Error</h2>
                    <div class="bg-red-50 rounded-lg p-4 mb-8 text-red-800">
                        <p><?php echo $message ?: 'There was an error processing your booking. Please try again.'; ?></p>
                    </div>
                    <a href="javascript:history.back()" class="inline-block w-full md:w-auto px-8 py-4 bg-primary text-white font-semibold rounded-full hover:bg-secondary transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        Go Back
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>
</body>
</html>