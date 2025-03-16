<?php
// Include database connection and SSLCOMMERZ configuration
require_once 'includes/db-connection.php';
require_once 'includes/sslcommerz-config.php';

// Initialize variables
$error_message = '';
$success = false;

// Check if IPN data is received from SSLCOMMERZ
if (isset($_POST['val_id']) && !empty($_POST['val_id'])) {
    $validation_id = $_POST['val_id'];
    $tran_id = isset($_POST['tran_id']) ? $_POST['tran_id'] : '';
    $amount = isset($_POST['amount']) ? $_POST['amount'] : 0;
    $card_type = isset($_POST['card_type']) ? $_POST['card_type'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    
    // Fix the variable assignment - use the imported variables correctly
    $store_id_value = $store_id;
    $store_passwd_value = $store_password;
    
    // Log IPN data for debugging
    error_log('SSLCOMMERZ IPN received: ' . json_encode($_POST));
    
    // Validate the transaction with SSLCOMMERZ
    if ($status == 'VALID') {
        try {
            // Get database connection
            $conn = getDbConnection();
            
            if ($conn && !empty($tran_id)) {
                // Extract booking ID from transaction ID (assuming tran_id format: BOOKING_ID_TIMESTAMP)
                $booking_id = explode('_', $tran_id)[0];
                
                // Update booking payment status
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
                
                $success = true;
                error_log('SSLCOMMERZ IPN: Payment updated successfully for booking ID ' . $booking_id);
            } else {
                $error_message = 'Database connection failed or transaction ID missing';
                error_log('SSLCOMMERZ IPN Error: ' . $error_message);
            }
        } catch (PDOException $e) {
            $error_message = 'Error processing IPN: ' . $e->getMessage();
            error_log('SSLCOMMERZ IPN Error: ' . $error_message);
        }
    } else {
        $error_message = 'Invalid transaction status: ' . $status;
        error_log('SSLCOMMERZ IPN Error: ' . $error_message);
    }
} else {
    $error_message = 'No IPN data received';
    error_log('SSLCOMMERZ IPN Error: ' . $error_message);
}

// Return response to SSLCOMMERZ
header('Content-Type: application/json');
if ($success) {
    echo json_encode(['status' => 'success', 'message' => 'IPN processed successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => $error_message]);
}