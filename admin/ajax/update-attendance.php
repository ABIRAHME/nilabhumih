<?php
session_start();
require_once '../db-parameters.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if required parameters are provided
if (!isset($_POST['package_id']) || !isset($_POST['customer_id']) || !isset($_POST['action'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$package_id = (int)$_POST['package_id'];
$customer_id = (int)$_POST['customer_id'];
$action = $_POST['action'];
$value = isset($_POST['value']) ? (int)$_POST['value'] : 0;

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get or create monitoring record for this package
    $monitoring_id = 0;
    
    // Check if there's an active monitoring for this package
    $stmt = $conn->prepare("SELECT id FROM tour_monitoring WHERE package_id = :package_id AND status = 'active' ORDER BY id DESC LIMIT 1");
    $stmt->bindParam(':package_id', $package_id, PDO::PARAM_INT);
    $stmt->execute();
    $monitoring = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($monitoring) {
        $monitoring_id = $monitoring['id'];
    } else {
        // Create new monitoring record
        $stmt = $conn->prepare("INSERT INTO tour_monitoring (package_id, monitoring_date) VALUES (:package_id, CURDATE())");
        $stmt->bindParam(':package_id', $package_id, PDO::PARAM_INT);
        $stmt->execute();
        $monitoring_id = $conn->lastInsertId();
    }
    
    // Check if attendance record exists
    $stmt = $conn->prepare("SELECT id, attended, meals_taken FROM customer_attendance WHERE monitoring_id = :monitoring_id AND customer_id = :customer_id");
    $stmt->bindParam(':monitoring_id', $monitoring_id, PDO::PARAM_INT);
    $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
    $stmt->execute();
    $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($action === 'attendance') {
        if ($attendance) {
            // Update existing record
            $stmt = $conn->prepare("UPDATE customer_attendance SET attended = :value WHERE id = :id");
            $stmt->bindParam(':value', $value, PDO::PARAM_INT);
            $stmt->bindParam(':id', $attendance['id'], PDO::PARAM_INT);
            $stmt->execute();
            
            // If attendance is set to 0, also set meals to 0
            if ($value == 0) {
                $stmt = $conn->prepare("UPDATE customer_attendance SET meals_taken = 0 WHERE id = :id");
                $stmt->bindParam(':id', $attendance['id'], PDO::PARAM_INT);
                $stmt->execute();
            }
        } else {
            // Create new record
            $stmt = $conn->prepare("INSERT INTO customer_attendance (monitoring_id, customer_id, attended) VALUES (:monitoring_id, :customer_id, :value)");
            $stmt->bindParam(':monitoring_id', $monitoring_id, PDO::PARAM_INT);
            $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
            $stmt->bindParam(':value', $value, PDO::PARAM_INT);
            $stmt->execute();
        }
    } elseif ($action === 'meals') {
        if ($attendance) {
            // Update existing record
            $stmt = $conn->prepare("UPDATE customer_attendance SET meals_taken = :value WHERE id = :id");
            $stmt->bindParam(':value', $value, PDO::PARAM_INT);
            $stmt->bindParam(':id', $attendance['id'], PDO::PARAM_INT);
            $stmt->execute();
        } else {
            // Create new record with attendance also set to true
            $attended = 1; // If setting meals, customer must be present
            $stmt = $conn->prepare("INSERT INTO customer_attendance (monitoring_id, customer_id, attended, meals_taken) VALUES (:monitoring_id, :customer_id, :attended, :value)");
            $stmt->bindParam(':monitoring_id', $monitoring_id, PDO::PARAM_INT);
            $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
            $stmt->bindParam(':attended', $attended, PDO::PARAM_INT);
            $stmt->bindParam(':value', $value, PDO::PARAM_INT);
            $stmt->execute();
        }
    } elseif ($action === 'update_total_meals') {
        // Update total meals for the monitoring
        $stmt = $conn->prepare("UPDATE tour_monitoring SET total_meals = :value WHERE id = :id");
        $stmt->bindParam(':value', $value, PDO::PARAM_INT);
        $stmt->bindParam(':id', $monitoring_id, PDO::PARAM_INT);
        $stmt->execute();
    } elseif ($action === 'complete_tour') {
        // Mark tour as complete
        $stmt = $conn->prepare("UPDATE tour_monitoring SET status = 'completed' WHERE id = :id");
        $stmt->bindParam(':id', $monitoring_id, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'monitoring_id' => $monitoring_id]);
    
} catch(PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}