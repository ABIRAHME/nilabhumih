<?php
session_start();
require_once 'db-parameters.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: tour-packages-list.php');
    exit();
}

$package_id = (int)$_GET['id'];

// Initialize variables for form data
$package_type = $title = $duration = $price = $description = '';
$tour_date = '';
$highlights = [];
$itinerary = [];
$is_published = 1;
$days = 1;
$current_image = '';
$success_message = $error_message = '';

// Fetch package data
try {
    // Create database connection
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch package details
    $stmt = $conn->prepare("SELECT * FROM tour_packages WHERE id = :id");
    $stmt->bindParam(':id', $package_id);
    $stmt->execute();
    
    $package = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$package) {
        header('Location: tour-packages-list.php');
        exit();
    }
    
    // Set form data from package
    $package_type = $package['package_type'];
    $title = $package['title'];
    $duration = $package['duration'];
    $price = $package['price'];
    $tour_date = $package['tour_date'] ?? '';
    $description = $package['description'];
    $is_published = $package['is_published'];
    $current_image = $package['image'];
    
    // Extract days from duration (e.g., "5 Days / 4 Nights" -> 5)
    $days_pattern = '/^(\d+)\s*Days/i';
    if (preg_match($days_pattern, $duration, $matches)) {
        $days = (int)$matches[1];
    }
    
    // Fetch highlights
    $stmt = $conn->prepare("SELECT highlight FROM package_highlights WHERE package_id = :package_id");
    $stmt->bindParam(':package_id', $package_id);
    $stmt->execute();
    $highlights = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Fetch itinerary
    $stmt = $conn->prepare("SELECT day, activity FROM package_itinerary WHERE package_id = :package_id ORDER BY day");
    $stmt->bindParam(':package_id', $package_id);
    $stmt->execute();
    $itinerary_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($itinerary_data as $item) {
        $itinerary[$item['day']] = $item['activity'];
    }
    
    // Debug itinerary data
    error_log("Itinerary data for package ID $package_id: " . print_r($itinerary, true));
    
} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $package_type = $_POST['package_type'] ?? '';
    $title = $_POST['title'] ?? '';
    $duration = $_POST['duration'] ?? '';
    $price = $_POST['price'] ?? '';
    $tour_date = $package_type === 'normal' ? $_POST['tour_date'] ?? '' : null;
    $description = $_POST['description'] ?? '';
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    
    // Extract days from duration (e.g., "5 Days / 4 Nights" -> 5)
    $days_pattern = '/^(\d+)\s*Days/i';
    if (preg_match($days_pattern, $duration, $matches)) {
        $days = (int)$matches[1];
    }
    
    // Process highlights (comma-separated)
    if (!empty($_POST['highlights'])) {
        $highlights = array_map('trim', explode(',', $_POST['highlights']));
    } else {
        $highlights = [];
    }
    
    // Process itinerary
    $itinerary = [];
    foreach ($_POST as $key => $value) {
        // Check if the key matches the pattern itinerary_day{number}
        if (preg_match('/^itinerary_day(\d+)$/', $key, $matches)) {
            $dayNum = $matches[1];
            // Only add non-empty values to the itinerary
            if (!empty(trim($value))) {
                $itinerary["Day {$dayNum}"] = $value;
            }
        }
    }
    
    // Validate form data
    $errors = [];
    if (empty($package_type)) $errors[] = "Package type is required";
    if (empty($title)) $errors[] = "Title is required";
    if (empty($duration)) $errors[] = "Duration is required";
    if (empty($price)) $errors[] = "Price is required";
    if (empty($description)) $errors[] = "Description is required";
    if (empty($highlights)) $errors[] = "At least one highlight is required";
    
    // Check if at least one itinerary day is filled
    if (empty($itinerary)) $errors[] = "At least one day's itinerary is required";
    
    // Handle image upload
    $image_path = $current_image; // Default to current image
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../images/';
        $temp_name = $_FILES['image']['tmp_name'];
        $original_name = $_FILES['image']['name'];
        $extension = pathinfo($original_name, PATHINFO_EXTENSION);
        $file_name = uniqid('package_') . '.' . $extension;
        $upload_path = $upload_dir . $file_name;
        
        // Check if directory exists, if not create it
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                $errors[] = "Failed to create upload directory";
            }
        }
        
        // Ensure directory is writable
        if (file_exists($upload_dir) && !is_writable($upload_dir)) {
            // Try to make the directory writable
            chmod($upload_dir, 0777);
            if (!is_writable($upload_dir)) {
                $errors[] = "Upload directory is not writable. Please check permissions.";
            }
        }
        
        // Only attempt to upload if no errors so far
        if (empty($errors)) {
            if (move_uploaded_file($temp_name, $upload_path)) {
                $image_path = 'images/' . $file_name;
            } else {
                $upload_error = error_get_last();
                $errors[] = "Failed to upload image: " . ($upload_error ? $upload_error['message'] : 'Unknown error');
            }
        }
    } else if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Map error codes to meaningful messages
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the upload_max_filesize directive in php.ini",
            UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form",
            UPLOAD_ERR_PARTIAL => "The uploaded file was only partially uploaded",
            UPLOAD_ERR_NO_FILE => "No file was uploaded",
            UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
            UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload"
        ];
        
        $error_code = $_FILES['image']['error'];
        $error_message = isset($error_messages[$error_code]) ? $error_messages[$error_code] : "Unknown upload error";
        $errors[] = "Image upload error: " . $error_message;
    }
    
    // If no errors, update database
    if (empty($errors)) {
        try {
            // Create database connection
            $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Begin transaction
            $conn->beginTransaction();
            
            // Update tour_packages table
            $stmt = $conn->prepare("UPDATE tour_packages SET 
                                  package_type = :package_type, 
                                  title = :title, 
                                  duration = :duration, 
                                  price = :price, 
                                  tour_date = :tour_date, 
                                  image = :image, 
                                  description = :description, 
                                  is_published = :is_published, 
                                  updated_at = NOW() 
                                  WHERE id = :id");
            $stmt->bindParam(':package_type', $package_type);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':duration', $duration);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':tour_date', $tour_date);
            $stmt->bindParam(':image', $image_path);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':is_published', $is_published);
            $stmt->bindParam(':id', $package_id);
            $stmt->execute();
            
            // Delete existing highlights
            $stmt = $conn->prepare("DELETE FROM package_highlights WHERE package_id = :package_id");
            $stmt->bindParam(':package_id', $package_id);
            $stmt->execute();
            
            // Insert new highlights
            foreach ($highlights as $highlight) {
                $stmt = $conn->prepare("INSERT INTO package_highlights (package_id, highlight) VALUES (:package_id, :highlight)");
                $stmt->bindParam(':package_id', $package_id);
                $stmt->bindParam(':highlight', $highlight);
                $stmt->execute();
            }
            
            // Delete existing itinerary
            $stmt = $conn->prepare("DELETE FROM package_itinerary WHERE package_id = :package_id");
            $stmt->bindParam(':package_id', $package_id);
            $stmt->execute();
            
            // Insert new itinerary
            foreach ($itinerary as $day => $activity) {
                $stmt = $conn->prepare("INSERT INTO package_itinerary (package_id, day, activity) VALUES (:package_id, :day, :activity)");
                $stmt->bindParam(':package_id', $package_id);
                $stmt->bindParam(':day', $day);
                $stmt->bindParam(':activity', $activity);
                $stmt->execute();
            }
            
            // Commit transaction
            $conn->commit();
            
            $success_message = "Package updated successfully!";
            
        } catch(PDOException $e) {
            // Rollback transaction on error
            $conn->rollBack();
            $error_message = "Database error: " . $e->getMessage();
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Tour Package - Admin Dashboard</title>
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
<body class="bg-gray-100">
    <?php include 'partials/navigation.php'; ?>
    
    <div class="md:ml-64 pt-16 min-h-screen">
        <div class="container mx-auto px-4 py-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">Edit Tour Package</h1>
                    <a href="tour-packages-list.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i> Back to List
                    </a>
                </div>
                
                <?php if (!empty($success_message)): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                    <p><?php echo $success_message; ?></p>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p><?php echo $error_message; ?></p>
                </div>
                <?php endif; ?>
                
                <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <!-- Package Type -->
                    <div>
                        <label for="package_type" class="block text-sm font-medium text-gray-700 mb-1">Package Type</label>
                        <select id="package_type" name="package_type" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">Select Package Type</option>
                            <option value="normal" <?php echo $package_type === 'normal' ? 'selected' : ''; ?>>Normal Tour</option>
                            <option value="educational" <?php echo $package_type === 'educational' ? 'selected' : ''; ?>>Educational Tour</option>
                            <option value="corporate" <?php echo $package_type === 'corporate' ? 'selected' : ''; ?>>Corporate Tour</option>
                        </select>
                    </div>
                    
                    <!-- Title -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary" placeholder="e.g. Enchanting Kerala">
                    </div>
                    
                    <!-- Duration -->
                    <div>
                        <label for="duration" class="block text-sm font-medium text-gray-700 mb-1">Duration</label>
                        <input type="text" id="duration" name="duration" value="<?php echo htmlspecialchars($duration); ?>" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary" placeholder="e.g. 5 Days / 4 Nights">
                    </div>
                    
                    <!-- Price -->
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price</label>
                        <input type="text" id="price" name="price" value="<?php echo htmlspecialchars($price); ?>" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary" placeholder="e.g. 24,999Tk">
                    </div>
                    
                    <!-- Tour Date (only for normal tours) -->
                    <div id="tour-date-container" style="<?php echo $package_type !== 'normal' ? 'display: none;' : ''; ?>">
                        <label for="tour_date" class="block text-sm font-medium text-gray-700 mb-1">Tour Date</label>
                        <input type="date" id="tour_date" name="tour_date" value="<?php echo htmlspecialchars($tour_date); ?>" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        <p class="text-xs text-gray-500 mt-1">Select the starting date of the tour.</p>
                    </div>
                    
                    <!-- Current Image -->
                    <?php if (!empty($current_image)): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Current Image</label>
                        <div class="mt-1">
                            <img src="../<?php echo $current_image; ?>" alt="Current Package Image" class="w-32 h-24 object-cover rounded">
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Image Upload -->
                    <div>
                        <label for="image" class="block text-sm font-medium text-gray-700 mb-1">Update Package Image</label>
                        <input type="file" id="image" name="image" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary" accept="image/*">
                        <p class="text-xs text-gray-500 mt-1">Leave empty to keep the current image. Upload a new high-quality image (JPEG, PNG) to replace it.</p>
                    </div>
                    
                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea id="description" name="description" rows="4" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Detailed description of the package"><?php echo htmlspecialchars($description); ?></textarea>
                    </div>
                    
                    <!-- Highlights -->
                    <div>
                        <label for="highlights" class="block text-sm font-medium text-gray-700 mb-1">Highlights</label>
                        <textarea id="highlights" name="highlights" rows="3" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter highlights separated by commas (e.g. Alleppey Houseboat Stay, Munnar Tea Gardens)"><?php echo htmlspecialchars(implode(', ', $highlights)); ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">Enter key highlights of the package, separated by commas.</p>
                    </div>
                    
                    <!-- Itinerary (Dynamic based on duration) -->
                    <div id="itinerary-container" class="space-y-4">
                        <label class="block text-sm font-medium text-gray-700">Itinerary</label>
                        <p class="text-xs text-gray-500 mb-2">Enter the itinerary for each day of the tour. The number of days will be automatically calculated from the duration field.</p>
                        
                        <div id="itinerary-fields" class="space-y-3">
                            <!-- Itinerary fields will be added dynamically via JavaScript -->
                             
                            <?php 
                            // Get all unique day numbers from the itinerary array
                            $itinerary_days = array_keys($itinerary);
                            
                            // Make sure we have at least the number of days from the duration
                            $max_day_num = $days;
                            
                            // Check if we have itinerary items with day numbers higher than the calculated days
                            foreach ($itinerary_days as $day_key) {
                                // Extract the day number from the key (e.g., "Day 5" -> 5)
                                if (preg_match('/Day\s+(\d+)/i', $day_key, $matches)) {
                                    $day_num = (int)$matches[1];
                                    $max_day_num = max($max_day_num, $day_num);
                                }
                            }
                            
                            // Display fields for all days
                            for ($i = 1; $i <= $max_day_num; $i++): 
                            ?>
                                <div class="flex items-center space-x-2 mb-2">
                                    <label for="itinerary_day<?php echo $i; ?>" class="w-24 text-sm font-medium text-gray-700">Day <?php echo $i; ?>:</label>
                                    <input type="text" id="itinerary_day<?php echo $i; ?>" name="itinerary_day<?php echo $i; ?>" value="<?php echo isset($itinerary["Day {$i}"]) ? htmlspecialchars($itinerary["Day {$i}"]) : ''; ?>" class="flex-1 px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Activity for Day <?php echo $i; ?>">
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <!-- Is Published Toggle -->
                    <div class="flex items-center">
                        <input type="checkbox" id="is_published" name="is_published" <?php echo $is_published ? 'checked' : ''; ?> class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                        <label for="is_published" class="ml-2 block text-sm text-gray-700">Publish this package</label>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="flex justify-end space-x-3">
                        <a href="tour-packages-list.php" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors">Cancel</a>
                        <button type="submit" class="px-6 py-2 bg-primary text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                            Update Package
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Function to toggle tour date field based on package type
        function toggleTourDateField() {
            const packageTypeSelect = document.getElementById('package_type');
            const tourDateContainer = document.getElementById('tour-date-container');
            
            if (packageTypeSelect && tourDateContainer) {
                if (packageTypeSelect.value === 'normal') {
                    tourDateContainer.style.display = 'block';
                } else {
                    tourDateContainer.style.display = 'none';
                }
            }
        }
        
        // Function to update itinerary fields based on duration
        function updateItineraryFields() {
            const durationInput = document.getElementById('duration');
            const itineraryFields = document.getElementById('itinerary-fields');
            
            if (!durationInput || !itineraryFields) {
                console.error('Required elements not found');
                return;
            }
            
            // Extract number of days from duration input
            const durationText = durationInput.value;
            const daysMatch = durationText.match(/^(\d+)\s*Days?/i);
            
            let days = 1; // Default to 1 day
            if (daysMatch && daysMatch[1]) {
                days = parseInt(daysMatch[1]);
                if (isNaN(days) || days < 1) days = 1;
                if (days > 30) days = 30; // Reasonable upper limit
            }
            
            // Store current values
            const currentValues = {};
            const inputs = itineraryFields.querySelectorAll('input[id^="itinerary_day"]');
            inputs.forEach(input => {
                const dayNum = input.id.replace('itinerary_day', '');
                currentValues[dayNum] = input.value;
            });
            
            // Find the maximum day number from both the current form and stored itinerary data
            let maxDayNum = days;
            
            // Check current form fields
            inputs.forEach(input => {
                const dayNum = parseInt(input.id.replace('itinerary_day', ''));
                if (!isNaN(dayNum) && dayNum > maxDayNum) {
                    maxDayNum = dayNum;
                }
            });
            
            // Check stored itinerary data
            if (window.itineraryData) {
                Object.keys(window.itineraryData).forEach(key => {
                    const dayMatch = key.match(/Day\s+(\d+)/i);
                    if (dayMatch && dayMatch[1]) {
                        const dayNum = parseInt(dayMatch[1]);
                        if (!isNaN(dayNum) && dayNum > maxDayNum) {
                            maxDayNum = dayNum;
                        }
                    }
                });
            }
            
            // Clear existing fields
            itineraryFields.innerHTML = '';
            
            // Add fields for each day up to the maximum day number
            for (let i = 1; i <= maxDayNum; i++) {
                const dayField = document.createElement('div');
                dayField.className = 'flex items-center space-x-2 mb-2';
                
                // Check for itinerary value in PHP format "Day {i}"
                const phpDayKey = `Day ${i}`;
                const dayValue = window.itineraryData && window.itineraryData[phpDayKey] ? 
                                window.itineraryData[phpDayKey] : 
                                (currentValues[i] || '');
                
                dayField.innerHTML = `
                    <label for="itinerary_day${i}" class="w-24 text-sm font-medium text-gray-700">Day ${i}:</label>
                    <input type="text" id="itinerary_day${i}" name="itinerary_day${i}" value="${dayValue}" class="flex-1 px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Activity for Day ${i}">
                `;
                itineraryFields.appendChild(dayField);
            }
        }
        
        // Initialize when DOM is fully loaded
        window.addEventListener('DOMContentLoaded', function() {
            // Create a global variable to store PHP itinerary data
            window.itineraryData = <?php echo json_encode($itinerary); ?>;
            console.log('Initialized itinerary data:', window.itineraryData);
            
            // Collect initial PHP-rendered itinerary values as backup
            const initialInputs = document.querySelectorAll('input[id^="itinerary_day"]');
            initialInputs.forEach(input => {
                const dayNum = parseInt(input.id.replace('itinerary_day', ''));
                if (!isNaN(dayNum) && input.value) {
                    // Only override if not already set
                    if (!window.itineraryData[`Day ${dayNum}`]) {
                        window.itineraryData[`Day ${dayNum}`] = input.value;
                    }
                }
            });
            
            const durationInput = document.getElementById('duration');
            
            if (durationInput) {
                // Update fields when duration changes
                durationInput.addEventListener('input', updateItineraryFields);
                durationInput.addEventListener('change', updateItineraryFields);
                durationInput.addEventListener('blur', updateItineraryFields);
            }
            
            // Add event listener to package type select for toggling tour date field
            const packageTypeSelect = document.getElementById('package_type');
            if (packageTypeSelect) {
                packageTypeSelect.addEventListener('change', toggleTourDateField);
                // Initialize tour date visibility
                toggleTourDateField();
            }
        });
    </script>
</body>
</html>