<?php
session_start();
require_once 'db-parameters.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Get package ID from URL
$package_id = isset($_GET['package_id']) ? (int)$_GET['package_id'] : 0;

// Initialize variables
$package = null;
$customers = [];

// Connect to database
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get package details
    $sql = "SELECT p.*, 
           (SELECT COUNT(*) FROM bookings WHERE package_id = p.id) as total_customers
           FROM tour_packages p 
           WHERE p.id = :package_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':package_id', $package_id, PDO::PARAM_INT);
    $stmt->execute();
    $package = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get active monitoring session if exists
    $monitoring = null;
    $sql = "SELECT * FROM tour_monitoring WHERE package_id = :package_id AND status = 'active' ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':package_id', $package_id, PDO::PARAM_INT);
    $stmt->execute();
    $monitoring = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If no active monitoring exists, set total_meals to 0
    $total_meals = $monitoring ? $monitoring['total_meals'] : 0;
    $monitoring_id = $monitoring ? $monitoring['id'] : 0;
    
    // Get customer details
    $sql = "SELECT b.*, 
           IFNULL(ca.attended, 0) as has_attended,
           IFNULL(ca.meals_taken, 0) as has_meals
           FROM bookings b
           LEFT JOIN customer_attendance ca ON b.id = ca.customer_id AND ca.monitoring_id = :monitoring_id
           WHERE b.package_id = :package_id AND b.payment_status = 'completed' 
           ORDER BY b.booking_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':package_id', $package_id, PDO::PARAM_INT);
    $stmt->bindParam(':monitoring_id', $monitoring_id, PDO::PARAM_INT);
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tour Monitoring Details - Admin Dashboard</title>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'partials/navigation.php'; ?>
    
    <div class="md:ml-64 pt-16 min-h-screen">
        <div class="container mx-auto px-4 py-8">
            <?php if ($package): ?>
            <!-- Package Details Section -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($package['title']); ?></h1>
                        <p class="text-gray-600"><?php echo htmlspecialchars($package['duration']); ?> days</p>
                    </div>
                    <div class="mt-4 md:mt-0 flex flex-col sm:flex-row gap-4">
                        <div class="flex items-center">
                            <label class="mr-2 text-gray-700">Total Meals:</label>
                            <input type="number" id="totalMeals" min="1" max="4" value="<?php echo $total_meals; ?>" 
                                   class="border rounded px-3 py-1 w-20 text-center" min="0">
                        </div>
                        <a href="generate_monitoring_pdf.php?package_id=<?php echo $package_id; ?>" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors">
                            <i class="fas fa-file-pdf mr-2"></i>Download as PDF
                        </a>
                    </div>
                </div>
                
                <!-- Customer Details Section -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Travelers</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Attendance</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Meals</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($customers as $customer): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($customer['email']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($customer['phone']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo $customer['travelers']; ?></div>
                                </td>
                               
                                <td class="px-6 py-4">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $customer['payment_status'] === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo ucfirst($customer['payment_status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" class="attendance-checkbox form-checkbox h-5 w-5 text-primary rounded border-gray-300"
                                               data-customer-id="<?php echo $customer['id']; ?>" <?php echo $customer['has_attended'] ? 'checked' : ''; ?>>
                                    </label>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="meals-checkbox-container <?php echo ($customer['has_attended'] && $total_meals > 0) ? '' : 'hidden'; ?>" data-meals-taken="<?php echo $customer['has_meals']; ?>">
                                        <?php 
                                        // Calculate total meal checkboxes based on number of travelers
                                        $total_meal_checkboxes = $total_meals * $customer['travelers'];
                                        for ($i = 1; $i <= $total_meal_checkboxes; $i++): 
                                            // Calculate which meal number this is (1 to total_meals)
                                            $meal_number = (($i - 1) % $total_meals) + 1;
                                            // Calculate which traveler this is (1 to travelers)
                                            $traveler_number = ceil($i / $total_meals);
                                        ?>
                                            <label class="inline-flex items-center mr-2 mb-1">
                                                <input type="checkbox" class="meals-checkbox form-checkbox h-5 w-5 text-secondary rounded border-gray-300"
                                                       data-customer-id="<?php echo $customer['id']; ?>"
                                                       data-meal-number="<?php echo $i; ?>"
                                                       <?php echo ($customer['has_meals'] >= $i) ? 'checked' : ''; ?>>
                                                <span class="ml-1 text-xs">T<?php echo $traveler_number; ?>-M<?php echo $meal_number; ?></span>
                                            </label>
                                        <?php endfor; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <p class="text-gray-600">Package not found.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const packageId = <?php echo $package_id; ?>;
        
        // Handle attendance checkboxes
        const attendanceCheckboxes = document.querySelectorAll('.attendance-checkbox');
        attendanceCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const customerId = this.dataset.customerId;
                const mealsContainer = this.closest('tr').querySelector('.meals-checkbox-container');
                const value = this.checked ? 1 : 0;
                const totalMeals = parseInt(document.getElementById('totalMeals').value) || 0;
                
                // Update UI - only show meals checkbox if attendance is checked AND total meals > 0
                if (this.checked && totalMeals > 0) {
                    mealsContainer.classList.remove('hidden');
                } else {
                    mealsContainer.classList.add('hidden');
                    const mealsCheckbox = mealsContainer.querySelector('.meals-checkbox');
                    if (mealsCheckbox) mealsCheckbox.checked = false;
                }
                
                // Send AJAX request
                updateAttendance(packageId, customerId, 'attendance', value);
            });
        });
        
        // Handle meals checkboxes
        const mealsCheckboxes = document.querySelectorAll('.meals-checkbox');
        mealsCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const customerId = this.dataset.customerId;
                const mealNumber = parseInt(this.dataset.mealNumber);
                
                // Count how many meals are checked for this customer
                const customerRow = this.closest('tr');
                const checkedMeals = customerRow.querySelectorAll('.meals-checkbox:checked').length;
                
                // Send AJAX request with the total number of meals taken
                updateAttendance(packageId, customerId, 'meals', checkedMeals);
            });
        });

        // Handle complete tour button
        const completeTourBtn = document.getElementById('completeTour');
        if (completeTourBtn) {
            completeTourBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to mark this tour as complete?')) {
                    // Send AJAX request to complete tour
                    updateAttendance(packageId, 0, 'complete_tour', 1)
                        .then(response => {
                            if (response.success) {
                                // Create success message element
                                const successMsg = document.createElement('div');
                                successMsg.className = 'fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded shadow-md';
                                successMsg.innerHTML = `
                                    <div class="flex items-center">
                                        <i class="fas fa-check-circle mr-2"></i>
                                        <span><strong>Success!</strong> Tour completed successfully.</span>
                                    </div>
                                `;
                                document.body.appendChild(successMsg);
                                
                                // Update UI to show completed status
                                const packageSection = document.querySelector('.bg-white.rounded-lg.shadow-md.p-6.mb-6');
                                if (packageSection) {
                                    const statusBadge = document.createElement('div');
                                    statusBadge.className = 'mt-2 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800';
                                    statusBadge.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Tour completed successfully';
                                    packageSection.querySelector('h1').parentNode.appendChild(statusBadge);
                                }
                                
                                // Disable the complete button
                                completeTourBtn.disabled = true;
                                completeTourBtn.classList.add('opacity-50', 'cursor-not-allowed');
                                
                                // Redirect after 3 seconds
                                setTimeout(() => {
                                    window.location.href = 'monitoring-list.php';
                                }, 3000);
                            } else {
                                alert('Failed to complete tour: ' + response.message);
                            }
                        });
                }
            });
        }

        // Handle total meals input
        const totalMealsInput = document.getElementById('totalMeals');
        if (totalMealsInput) {
            totalMealsInput.addEventListener('change', function() {
                const value = parseInt(this.value) || 0;
                
                // Update UI - regenerate meal checkboxes for each customer based on new total meals value
                const attendanceCheckboxes = document.querySelectorAll('.attendance-checkbox');
                attendanceCheckboxes.forEach(checkbox => {
                    const customerId = checkbox.dataset.customerId;
                    const mealsContainer = checkbox.closest('tr').querySelector('.meals-checkbox-container');
                    const hasAttended = checkbox.checked;
                    
                    // Clear existing meal checkboxes
                    mealsContainer.innerHTML = '';
                    
                    // Generate new meal checkboxes based on total meals value
                    if (hasAttended && value > 0) {
                        mealsContainer.classList.remove('hidden');
                        
                        // Get current meals taken for this customer
                        const currentMealsTaken = parseInt(mealsContainer.dataset.mealsTaken) || 0;
                        
                        // Get number of travelers for this customer
                        const travelers = parseInt(checkbox.closest('tr').querySelector('td:nth-child(3) .text-sm').textContent) || 1;
                        
                        // Calculate total meal checkboxes based on number of travelers
                        const totalMealCheckboxes = value * travelers;
                        
                        // Create new meal checkboxes
                        for (let i = 1; i <= totalMealCheckboxes; i++) {
                            const label = document.createElement('label');
                            label.className = 'inline-flex items-center mr-2 mb-1';
                            
                            // Calculate which meal number this is (1 to total_meals)
                            const mealNumber = ((i - 1) % value) + 1;
                            // Calculate which traveler this is (1 to travelers)
                            const travelerNumber = Math.ceil(i / value);
                            
                            const input = document.createElement('input');
                            input.type = 'checkbox';
                            input.className = 'meals-checkbox form-checkbox h-5 w-5 text-secondary rounded border-gray-300';
                            input.dataset.customerId = customerId;
                            input.dataset.mealNumber = i;
                            input.checked = i <= currentMealsTaken;
                            
                            // Add event listener to new checkbox
                            input.addEventListener('change', function() {
                                const customerId = this.dataset.customerId;
                                const customerRow = this.closest('tr');
                                const checkedMeals = customerRow.querySelectorAll('.meals-checkbox:checked').length;
                                updateAttendance(packageId, customerId, 'meals', checkedMeals);
                            });
                            
                            const span = document.createElement('span');
                            span.className = 'ml-1 text-xs';
                            span.textContent = `T${travelerNumber}-M${mealNumber}`;
                            
                            label.appendChild(input);
                            label.appendChild(span);
                            mealsContainer.appendChild(label);
                        }
                    } else {
                        mealsContainer.classList.add('hidden');
                    }
                });
                
                // Send AJAX request to update total meals
                updateAttendance(packageId, 0, 'update_total_meals', value)
                    .then(response => {
                        if (!response.success) {
                            alert('Failed to update total meals: ' + response.message);
                        }
                    });
            });
        }
        
        // Function to update attendance/meals
        function updateAttendance(packageId, customerId, action, value) {
            return fetch('ajax/update-attendance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `package_id=${packageId}&customer_id=${customerId}&action=${action}&value=${value}`
            })
            .then(response => response.json())
            .catch(error => {
                console.error('Error:', error);
                return { success: false, message: 'Network error' };
            });
        }
    });
    </script>
</body>
</html>