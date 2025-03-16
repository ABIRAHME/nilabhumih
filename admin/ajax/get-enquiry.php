<?php
session_start();
require_once '../db-parameters.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if enquiry ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Enquiry ID is required']);
    exit();
}

$enquiry_id = (int)$_GET['id'];

// Connect to database
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get enquiry details
    $sql = "SELECT * FROM enquiries WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $enquiry_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $enquiry = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($enquiry) {
        // If the enquiry is new, automatically mark it as read
        if ($enquiry['status'] === 'new') {
            $update_sql = "UPDATE enquiries SET status = 'read', updated_at = NOW() WHERE id = :id";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bindParam(':id', $enquiry_id, PDO::PARAM_INT);
            $update_stmt->execute();
            
            // Update the status in the response
            $enquiry['status'] = 'read';
        }
        
        echo json_encode(['success' => true, 'enquiry' => $enquiry]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Enquiry not found']);
    }
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}