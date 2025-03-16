<?php
// SSLCOMMERZ Configuration

// Test Mode Configuration
$sslcommerz_mode = 'sandbox'; // 'sandbox' for testing, 'securepay' for production

// Store Credentials
if ($sslcommerz_mode === 'sandbox') {
    // Sandbox/Test credentials
    $store_id = 'urexa676d6fd94eef8'; // Replace with your test store ID
    $store_password = 'urexa676d6fd94eef8@ssl'; // Replace with your test store password
    $sslcommerz_url = 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php';
    $validation_url = 'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php';
} else {
    // Live credentials
    $store_id = 'urexa676d6fd94eef8'; // Replace with your live store ID
    $store_password = 'urexa676d6fd94eef8@ssl'; // Replace with your live store password
    $sslcommerz_url = 'https://securepay.sslcommerz.com/gwprocess/v4/api.php';
    $validation_url = 'https://securepay.sslcommerz.com/validator/api/validationserverAPI.php';
}

// Success, Fail, Cancel, IPN URLs
$base_url = 'http://localhost/nilabhumih';

$success_url = $base_url . '/payment_success.php';
$fail_url = $base_url . '/payment_fail.php';
$cancel_url = $base_url . '/payment_cancel.php';
$ipn_url = $base_url . '/payment_ipn.php';