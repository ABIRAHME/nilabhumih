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
$monitoring = null;

try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get package information
    $stmt = $conn->prepare("SELECT id, title, duration FROM tour_packages WHERE id = :id");
    $stmt->bindParam(':id', $package_id, PDO::PARAM_INT);
    $stmt->execute();
    $package_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get active monitoring session if exists
    $sql = "SELECT * FROM tour_monitoring WHERE package_id = :package_id AND status = 'active' ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':package_id', $package_id, PDO::PARAM_INT);
    $stmt->execute();
    $monitoring = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If no active monitoring exists, set total_meals to 0
    $total_meals = $monitoring ? $monitoring['total_meals'] : 0;
    $monitoring_id = $monitoring ? $monitoring['id'] : 0;
    
    if ($package_info) {
        // Count total customers for this package
        $count_sql = "SELECT COUNT(*) FROM bookings WHERE package_id = :package_id AND payment_status = 'completed'";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bindParam(':package_id', $package_id, PDO::PARAM_INT);
        $count_stmt->execute();
        $total_completed = $count_stmt->fetchColumn();
        
        // Get customers with completed status and attendance information
        $sql = "SELECT b.*, 
               IFNULL(ca.attended, 0) as has_attended,
               IFNULL(ca.meals_taken, 0) as has_meals
               FROM bookings b
               LEFT JOIN customer_attendance ca ON b.id = ca.customer_id AND ca.monitoring_id = :monitoring_id
               WHERE b.package_id = :package_id AND b.payment_status = 'completed' 
               ORDER BY b.travel_date ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':package_id', $package_id, PDO::PARAM_INT);
        $stmt->bindParam(':monitoring_id', $monitoring_id, PDO::PARAM_INT);
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
    $pdf->SetTitle('Tour Monitoring - ' . $package_info['title']);
    $pdf->SetSubject('Tour Monitoring Report');
    
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
    $pdf->Cell(0, 5, 'Tour Monitoring Report', 0, 1, 'C');
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
    
    if (!empty($customers)) {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(60, 7, 'Travel Date:', 0, 0);
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 7, date('F j, Y', strtotime($customers[0]['travel_date'])), 0, 1);
    }
    
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(60, 7, 'Total Completed Bookings:', 0, 0);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 7, $total_completed, 0, 1);
    
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(60, 7, 'Total Meals Per Person:', 0, 0);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 7, $total_meals, 0, 1);
    
    $pdf->Ln(10);
    
    // Card Format Header
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(60, 10, 'customer name', 1, 0, 'C', 1);
    $pdf->Cell(40, 10, 'Travelers number', 1, 0, 'C', 1);
    $pdf->Cell(40, 10, 'Present check box', 1, 0, 'C', 1);
    $pdf->Cell(40, 10, 'Meals check boxes', 1, 1, 'C', 1);
    
    // Customers Card Content
    $pdf->SetFont('helvetica', '', 10);
    
    // Set card dimensions and spacing
    $card_height = 15;
    $card_margin = 5;
    $cards_per_row = 1;
    $current_card = 0;
    $cards_per_page = 5; // Minimum 5 cards on first page
    $cards_on_current_page = 0;
    $is_first_page = true;
    
    foreach ($customers as $customer) {
        // Check if we need to add a new page
        if ($cards_on_current_page >= ($is_first_page ? $cards_per_page : 10)) {
            $pdf->AddPage();
            
            // Add header on new page
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->SetFillColor(240, 240, 240);
            $pdf->Cell(60, 10, 'customer name', 1, 0, 'C', 1);
            $pdf->Cell(40, 10, 'Travelers number', 1, 0, 'C', 1);
            $pdf->Cell(40, 10, 'Present check box', 1, 0, 'C', 1);
            $pdf->Cell(40, 10, 'Meals check boxes', 1, 1, 'C', 1);
            $pdf->SetFont('helvetica', '', 10);
            
            // Reset position for the first card on the new page
            // Get the current Y position after the header and set it explicitly
            $header_end_y = $pdf->GetY();
            $pdf->SetY($header_end_y);
            
            $cards_on_current_page = 0;
            $is_first_page = false;
        }
        
        // Start a new row if needed - only if we're not at the beginning of a new page
        if ($current_card % $cards_per_row == 0 && $current_card > 0 && $cards_on_current_page > 0) {
            $pdf->Ln($card_height + $card_margin);
        }
        
        // Calculate positions
        $start_x = $pdf->GetX();
        $start_y = $pdf->GetY();
        
        // Draw card border
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->Rect($start_x, $start_y, 180, $card_height);
        
        // Customer name
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY($start_x, $start_y);
        $pdf->Cell(60, $card_height, $customer['first_name'] . ' ' . $customer['last_name'], 1, 0, 'L');
        
        // Travelers
        $pdf->SetXY($start_x + 60, $start_y);
        $pdf->Cell(40, $card_height, $customer['travelers'], 1, 0, 'C');
        
        // Present checkbox
        $pdf->SetXY($start_x + 100, $start_y);
        $pdf->Cell(40, $card_height, '', 1, 0, 'C');
        
        // Draw checkbox for attendance
        $checkbox_x = $start_x + 120; // Center in the cell
        $checkbox_y = $start_y + ($card_height / 2) - 2;
        $pdf->Rect($checkbox_x, $checkbox_y, 4, 4, 'D');
        
        // If attended, fill with X
        if ($customer['has_attended']) {
            $pdf->SetFont('', 'B', 8);
            $pdf->Text($checkbox_x + 0.5, $checkbox_y + 3, 'X');
            $pdf->SetFont('', '', 10);
        }
        
        // Meals checkboxes
        $pdf->SetXY($start_x + 140, $start_y);
        $pdf->Cell(40, $card_height, '', 1, 0, 'C');
        
        // Calculate total meal checkboxes needed (total_meals × travelers)
        $total_meal_checkboxes = $total_meals * $customer['travelers'];
        
        // Support up to 40 meal checkboxes per customer
        $max_checkboxes = 40;
        $total_meal_checkboxes = min($total_meal_checkboxes, $max_checkboxes);
        
        // Calculate spacing and layout for checkboxes
        $checkbox_width = 2.5; // Smaller checkboxes
        $checkbox_height = 2.5;
        $spacing_x = 4; // Closer horizontal spacing
        $spacing_y = 4; // Vertical spacing
        
        // Calculate how many rows and columns we need
        $checkboxes_per_row = 10; // 10 checkboxes per row
        $rows_needed = ceil($total_meal_checkboxes / $checkboxes_per_row);
        
        // Starting position
        $start_meals_x = $start_x + 142;
        $start_meals_y = $start_y + 2;
        
        // Draw checkboxes for meals in a grid pattern
        for ($i = 0; $i < $total_meal_checkboxes; $i++) {
            $row = floor($i / $checkboxes_per_row);
            $col = $i % $checkboxes_per_row;
            
            $meals_x = $start_meals_x + ($col * $spacing_x);
            $meals_y = $start_meals_y + ($row * $spacing_y);
            
            $pdf->Rect($meals_x, $meals_y, $checkbox_width, $checkbox_height, 'D');
            
            // Mark checkbox if meals taken
            if ($i < $customer['has_meals']) {
                $pdf->SetFont('', 'B', 6); // Smaller font for X
                $pdf->Text($meals_x + 0.5, $meals_y + 2, 'X');
                $pdf->SetFont('', '', 10);
            }
        }
        
        // Show a note with the total count
        $pdf->SetFont('', '', 6);
        $pdf->Text($start_x + 142, $start_y + $card_height - 2, $customer['has_meals'] . '/' . $total_meal_checkboxes . ' meals');
        $pdf->SetFont('', '', 10);
        
        // Move to next card position
        $current_card++;
        $cards_on_current_page++;
        $pdf->Ln($card_height);
    }
    
    $pdf->Ln(10);
    
    // Footer note
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(0, 10, 'Generated on: ' . date('F j, Y'), 0, 1, 'L');
    $pdf->Cell(0, 5, 'This is a computer-generated document and requires no signature.', 0, 1, 'C');
    
    // Output the PDF
    $pdf_filename = 'tour_monitoring_' . $package_id . '.pdf';
    $pdf->Output($pdf_filename, 'D'); // 'D' means download
    exit;
} else {
    echo 'No completed bookings found for this package.';
}