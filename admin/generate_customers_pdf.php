<?php
session_start();
require_once 'db-parameters.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Check if package ID is provided
if (!isset($_GET['package_id']) || empty($_GET['package_id'])) {
    die('Package ID is required');
}

$package_id = (int)$_GET['package_id'];
$package_info = null;
$customers = [];

try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get package information
    $stmt = $conn->prepare("SELECT id, title, duration FROM tour_packages WHERE id = :id");
    $stmt->bindParam(':id', $package_id, PDO::PARAM_INT);
    $stmt->execute();
    $package_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($package_info) {
        // Count total customers for this package
        $count_sql = "SELECT COUNT(*) FROM bookings WHERE package_id = :package_id AND payment_status = 'completed'";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bindParam(':package_id', $package_id, PDO::PARAM_INT);
        $count_stmt->execute();
        $total_completed = $count_stmt->fetchColumn();
        
        // Get customers with completed status
        $sql = "SELECT id, first_name, last_name, email, phone, travel_date, travelers, 
               total_amount, payment_status, booking_date 
               FROM bookings 
               WHERE package_id = :package_id AND payment_status = 'completed' 
               ORDER BY travel_date ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':package_id', $package_id, PDO::PARAM_INT);
        $stmt->execute();
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        die('Package not found');
    }
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// If we have the package data and customers, generate PDF
if ($package_info && !empty($customers)) {
    // Check if TCPDF is installed via Composer
    $composer_tcpdf = __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';
    // Check if TCPDF is in a local directory
    $local_tcpdf = __DIR__ . '/../includes/tcpdf/tcpdf.php';
    
    if (file_exists($composer_tcpdf)) {
        require_once($composer_tcpdf);
    } else if (file_exists($local_tcpdf)) {
        require_once($local_tcpdf);
    } else {
        die('TCPDF library not found. Please install TCPDF via Composer or download it locally.');
    }
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Nilabhoomi Tours and Travels');
    $pdf->SetAuthor('Nilabhoomi Tours and Travels');
    $pdf->SetTitle('Package Customers - ' . $package_info['title']);
    $pdf->SetSubject('Package Customers List');
    
    // Remove header and footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 15);
    
    // Set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 12);
    
    // Company logo and info
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->Cell(0, 10, 'Nilabhoomi Tours and Travels', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 5, 'Package Customers List', 0, 1, 'C');
    $pdf->Ln(10);
    
    // Package Info Header
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetFillColor(230, 247, 255);
    $pdf->Cell(0, 10, $package_info['title'], 0, 1, 'C', 1);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Ln(5);
    
    // Package Details
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(60, 7, 'Duration:', 0, 0);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 7, $package_info['duration'], 0, 1);
    
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(60, 7, 'Travel Date:', 0, 0);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 7, date('F j, Y', strtotime($customers[0]['travel_date'])), 0, 1);
    
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(60, 7, 'Total Completed Bookings:', 0, 0);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 7, $total_completed, 0, 1);
    
    $pdf->Ln(10);
    
    // Customers Table Header
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(60, 10, 'Customer', 1, 0, 'C', 1);
    $pdf->Cell(70, 10, 'Contact', 1, 0, 'C', 1);
    $pdf->Cell(30, 10, 'Travelers', 1, 0, 'C', 1);
    $pdf->Cell(25, 10, 'Amount', 1, 1, 'C', 1);
    
    // Customers Table Content
    $pdf->SetFont('helvetica', '', 10);
    foreach ($customers as $customer) {
        // Customer name
        $pdf->Cell(60, 10, $customer['first_name'] . ' ' . $customer['last_name'], 1, 0, 'L');
        
        // Contact info (email and phone)
        $contact_info = $customer['email'] . "\n" . $customer['phone'];
        $pdf->MultiCell(70, 10, $contact_info, 1, 'L', 0, 0);
        
        // Travelers
        $pdf->Cell(30, 10, $customer['travelers'], 1, 0, 'C');
        
        // Amount
        $pdf->Cell(25, 10, 'Tk ' . number_format($customer['total_amount'], 2), 1, 1, 'R');
    }
    
    $pdf->Ln(10);
    
    // Footer note
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(0, 10, 'Generated on: ' . date('F j, Y'), 0, 1, 'L');
    $pdf->Cell(0, 5, 'This is a computer-generated document and requires no signature.', 0, 1, 'C');
    
    // Output the PDF
    $pdf_filename = 'package_customers_' . $package_id . '.pdf';
    $pdf->Output($pdf_filename, 'D'); // 'D' means download
    exit;
} else {
    echo 'No completed bookings found for this package.';
}