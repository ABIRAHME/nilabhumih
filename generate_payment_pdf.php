<?php
// Include database connection and SSLCOMMERZ configuration
require_once 'includes/db-connection.php';
require_once 'includes/sslcommerz-config.php';

// Check if booking ID is provided
if (!isset($_GET['booking_id']) || empty($_GET['booking_id'])) {
    die('Booking ID is required');
}

$booking_id = (int)$_GET['booking_id'];
$is_school_booking = isset($_GET['type']) && $_GET['type'] === 'school';
$booking = null;

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
        } else {
            die('Booking not found');
        }
    } else {
        die('Database connection failed');
    }
} catch (PDOException $e) {
    die('Error retrieving booking: ' . $e->getMessage());
}

// If we have the booking data, generate PDF
if ($booking) {
    // Check if TCPDF is installed via Composer
    $composer_tcpdf = __DIR__ . '/vendor/tecnickcom/tcpdf/tcpdf.php';
    // Check if TCPDF is in a local directory
    $local_tcpdf = __DIR__ . '/includes/tcpdf/tcpdf.php';
    
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
    $pdf->SetTitle('Payment Receipt - Booking #' . $booking['id']);
    $pdf->SetSubject('Payment Receipt');
    
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
    $pdf->Cell(0, 5, 'Payment Receipt', 0, 1, 'C');
    $pdf->Ln(10);
    
    // Payment Success Message
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetFillColor(230, 247, 255);
    $pdf->Cell(0, 10, 'Payment Successful!', 0, 1, 'C', 1);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Ln(5);
    $pdf->MultiCell(0, 10, 'Your booking has been confirmed and your payment has been processed successfully. Please bring this PDF with you on your travel date.', 0, 'C');
    $pdf->Ln(5);
    
    // Booking Details
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Booking Details', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Ln(2);
    
    // Create a single-column layout for booking details
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(50, 7, 'Booking ID:', 0, 0);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 7, '#' . $booking['id'], 0, 1);
    
    $pdf->SetFont('helvetica', 'B', 12);
    if (isset($booking['is_school_booking']) && $booking['is_school_booking']) {
        $pdf->Cell(50, 7, 'Institute:', 0, 0);
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 7, $booking['institute_name'], 0, 1);
    } else {
        $pdf->Cell(50, 7, 'Name:', 0, 0);
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 7, $booking['first_name'] . ' ' . $booking['last_name'], 0, 1);
    }
    
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(50, 7, 'Package:', 0, 0);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 7, $booking['package_title'], 0, 1);
    
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(50, 7, 'Email:', 0, 0);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 7, $booking['email'], 0, 1);
    
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(50, 7, 'Duration:', 0, 0);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 7, $booking['package_duration'], 0, 1);
    
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(50, 7, 'Phone:', 0, 0);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 7, $booking['phone'], 0, 1);
    
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(50, 7, 'Travel Date:', 0, 0);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 7, date('F j, Y', strtotime($booking['travel_date'])), 0, 1);
    
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(50, 7, 'Transaction ID:', 0, 0);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 7, $booking['transaction_id'], 0, 1);
    
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(50, 7, 'Travelers:', 0, 0);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 7, $booking['travelers'], 0, 1);
    
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(50, 7, 'Payment Method:', 0, 0);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 7, $booking['payment_method'], 0, 1);
    
    $pdf->Ln(10);
    
    // Payment Summary
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Payment Summary', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Ln(2);
    
    $pdf->Cell(120, 7, 'Package Price:', 0, 0);
    $pdf->Cell(0, 7, 'Tk ' . number_format($booking['package_price'], 2), 0, 1, 'R');
    
    if (isset($booking['is_school_booking']) && $booking['is_school_booking']) {
        $pdf->Cell(120, 7, 'Subtotal (' . $booking['travelers'] . ' travelers):', 0, 0);
        $pdf->Cell(0, 7, 'Tk ' . number_format($booking['subtotal'], 2), 0, 1, 'R');
        
        if ($booking['discount'] > 0) {
            $pdf->Cell(120, 7, 'Discount:', 0, 0);
            $pdf->SetTextColor(0, 128, 0); // Green color for discount
            $pdf->Cell(0, 7, '-Tk ' . number_format($booking['discount'], 2), 0, 1, 'R');
            $pdf->SetTextColor(0, 0, 0); // Reset to black
        }
    }
    
    $pdf->Cell(120, 7, 'Taxes & Fees:', 0, 0);
    $pdf->Cell(0, 7, 'Tk ' . number_format($booking['taxes_fees'], 2), 0, 1, 'R');
    
    if (isset($booking['is_school_booking']) && $booking['is_school_booking'] && isset($booking['partial_payment']) && $booking['partial_payment']) {
        $pdf->Cell(120, 7, 'Total Amount:', 0, 0);
        $pdf->Cell(0, 7, 'Tk ' . number_format($booking['total_amount'], 2), 0, 1, 'R');
        
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(120, 7, 'Partial Payment (30%):', 0, 0);
        $pdf->Cell(0, 7, 'Tk ' . number_format($booking['payment_amount'], 2), 0, 1, 'R');
    } else {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(120, 7, 'Total Paid:', 0, 0);
        $pdf->Cell(0, 7, 'Tk ' . number_format($booking['total_amount'], 2), 0, 1, 'R');
    }
    
    $pdf->Ln(15);
    
    // Footer note
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(0, 10, 'Thank you for choosing Nilabhoomi Tours and Travels!', 0, 1, 'C');
    $pdf->Cell(0, 5, 'This is a computer-generated document and requires no signature.', 0, 1, 'C');
    
    // Output the PDF
    $booking_type = isset($booking['is_school_booking']) && $booking['is_school_booking'] ? 'school' : 'regular';
    $pdf_filename = 'payment_receipt_' . $booking_type . '_' . $booking['id'] . '.pdf';
    $pdf->Output($pdf_filename, 'D'); // 'D' means download
    exit;
} else {
    echo 'Unable to generate PDF. Booking information not available.';
}