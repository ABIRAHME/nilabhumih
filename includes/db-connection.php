<?php
// Database connection parameters
include 'admin/db-parameters.php';
// Function to create a database connection
function getDbConnection() {
    global $host, $dbname, $username, $password;
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        // Log error and return null
        error_log("Database connection failed: " . $e->getMessage());
        return null;
    }
}