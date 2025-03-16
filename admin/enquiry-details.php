<?php
session_start();
require_once 'db-parameters.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Check if enquiry ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: enquiries-list.php');
    exit();
}

$enquiry_id = (int)$_GET['id'];
$enquiry = null;
$error_message = null;
$success_message = null;

// Process status update if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $new_status = $_POST['status'];
    
    // Validate status
    if (!in_array($new_status, ['new', 'read', 'replied'])) {
        $error_message = 'Invalid status';
    } else {
        try {
            $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Update enquiry status
            $sql = "UPDATE enquiries SET status = :status, updated_at = NOW() WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':status', $new_status, PDO::PARAM_STR);
            $stmt->bindParam(':id', $enquiry_id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $success_message = 'Status updated successfully';
            } else {
                $error_message = 'Enquiry not found or status not changed';
            }
        } catch(PDOException $e) {
            $error_message = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get enquiry details
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql = "SELECT * FROM enquiries WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $enquiry_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $enquiry = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$enquiry) {
        $error_message = 'Enquiry not found';
    } else {
        // If the enquiry is new, automatically mark it as read
        if ($enquiry['status'] === 'new') {
            $update_sql = "UPDATE enquiries SET status = 'read', updated_at = NOW() WHERE id = :id";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bindParam(':id', $enquiry_id, PDO::PARAM_INT);
            $update_stmt->execute();
            
            // Update the status in the current data
            $enquiry['status'] = 'read';
            $success_message = 'Enquiry marked as read';
        }
    }
} catch(PDOException $e) {
    $error_message = 'Database error: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enquiry Details - Nilabhoomi Tours and Travels</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1e40af',
                        secondary: '#4f46e5'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-r from-blue-50 to-indigo-50 min-h-screen">
    <?php include('partials/navigation.php'); ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 py-8 md:ml-64 pt-20">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Enquiry Details</h1>
            <a href="enquiries-list.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition duration-300 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Enquiries
            </a>
        </div>

        <?php if ($error_message): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p><?php echo $success_message; ?></p>
            </div>
        <?php endif; ?>

        <?php if ($enquiry): ?>
            <div class="bg-white shadow-md rounded-lg overflow-hidden mb-6">
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800 mb-2">Contact Information</h2>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="mb-3">
                                    <p class="text-sm text-gray-500">Name</p>
                                    <p class="text-md font-medium"><?php echo htmlspecialchars($enquiry['name']); ?></p>
                                </div>
                                <div class="mb-3">
                                    <p class="text-sm text-gray-500">Email</p>
                                    <p class="text-md font-medium"><?php echo htmlspecialchars($enquiry['email']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Phone</p>
                                    <p class="text-md font-medium"><?php echo htmlspecialchars($enquiry['phone']); ?></p>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800 mb-2">Enquiry Details</h2>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="mb-3">
                                    <p class="text-sm text-gray-500">Status</p>
                                    <div class="mt-1">
                                        <?php 
                                        $status_color = '';
                                        $status_text = '';
                                        
                                        switch($enquiry['status']) {
                                            case 'new':
                                                $status_color = 'bg-red-100 text-red-800';
                                                $status_text = 'New';
                                                break;
                                            case 'read':
                                                $status_color = 'bg-yellow-100 text-yellow-800';
                                                $status_text = 'Read';
                                                break;
                                            case 'replied':
                                                $status_color = 'bg-green-100 text-green-800';
                                                $status_text = 'Replied';
                                                break;
                                        }
                                        ?>
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_color; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <p class="text-sm text-gray-500">Date Submitted</p>
                                    <p class="text-md font-medium"><?php echo date('F j, Y, g:i a', strtotime($enquiry['created_at'])); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Last Updated</p>
                                    <p class="text-md font-medium"><?php echo $enquiry['updated_at'] ? date('F j, Y, g:i a', strtotime($enquiry['updated_at'])) : 'Not updated'; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-2">Message</h2>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="whitespace-pre-line"><?php echo htmlspecialchars($enquiry['message']); ?></p>
                        </div>
                    </div>
                    
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 mb-2">Update Status</h2>
                        <form method="POST" action="" class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex flex-wrap gap-4">
                                <div>
                                    <input type="radio" id="status_new" name="status" value="new" <?php echo $enquiry['status'] === 'new' ? 'checked' : ''; ?> class="hidden peer">
                                    <label for="status_new" class="inline-flex items-center justify-center px-4 py-2 border rounded-md cursor-pointer peer-checked:bg-red-100 peer-checked:text-red-800 peer-checked:border-red-300 hover:bg-gray-100">
                                        New
                                    </label>
                                </div>
                                <div>
                                    <input type="radio" id="status_read" name="status" value="read" <?php echo $enquiry['status'] === 'read' ? 'checked' : ''; ?> class="hidden peer">
                                    <label for="status_read" class="inline-flex items-center justify-center px-4 py-2 border rounded-md cursor-pointer peer-checked:bg-yellow-100 peer-checked:text-yellow-800 peer-checked:border-yellow-300 hover:bg-gray-100">
                                        Read
                                    </label>
                                </div>
                                <div>
                                    <input type="radio" id="status_replied" name="status" value="replied" <?php echo $enquiry['status'] === 'replied' ? 'checked' : ''; ?> class="hidden peer">
                                    <label for="status_replied" class="inline-flex items-center justify-center px-4 py-2 border rounded-md cursor-pointer peer-checked:bg-green-100 peer-checked:text-green-800 peer-checked:border-green-300 hover:bg-gray-100">
                                        Replied
                                    </label>
                                </div>
                                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition duration-300 ml-auto">
                                    Update Status
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-white shadow-md rounded-lg overflow-hidden mb-6">
                <div class="p-6 text-center text-gray-500">
                    Enquiry not found. <a href="enquiries-list.php" class="text-blue-600 hover:underline">Return to enquiries list</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide success message after 3 seconds
            const successMessage = document.querySelector('.bg-green-100');
            if (successMessage) {
                setTimeout(function() {
                    successMessage.style.display = 'none';
                }, 3000);
            }
        });
    </script>
</body>
</html>