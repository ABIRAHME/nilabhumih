<?php
session_start();
require_once 'db-parameters.php';

// Initialize variables
$success_message = $error_message = '';
$packages = [];

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $package_id = (int)$_GET['id'];
    
    try {
        // Create database connection
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Begin transaction
        $conn->beginTransaction();
        
        // First, get the image path before deleting the package
        $stmt = $conn->prepare("SELECT image FROM tour_packages WHERE id = :package_id");
        $stmt->bindParam(':package_id', $package_id);
        $stmt->execute();
        $package = $stmt->fetch(PDO::FETCH_ASSOC);
        $image_path = $package['image'] ?? '';
        
        // Delete from package_highlights table
        $stmt = $conn->prepare("DELETE FROM package_highlights WHERE package_id = :package_id");
        $stmt->bindParam(':package_id', $package_id);
        $stmt->execute();
        
        // Delete from package_itinerary table
        $stmt = $conn->prepare("DELETE FROM package_itinerary WHERE package_id = :package_id");
        $stmt->bindParam(':package_id', $package_id);
        $stmt->execute();
        
        // Delete from tour_packages table
        $stmt = $conn->prepare("DELETE FROM tour_packages WHERE id = :package_id");
        $stmt->bindParam(':package_id', $package_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        // Delete the image file if it exists
        if (!empty($image_path)) {
            // Construct the correct path to the image file
            $full_path = dirname(dirname(__FILE__)) . '/' . $image_path;
            
            // Debug information
            error_log("Attempting to delete image: " . $full_path);
            
            if (file_exists($full_path)) {
                if (unlink($full_path)) {
                    // Image deleted successfully
                    error_log("Image deleted successfully: " . $full_path);
                } else {
                    // Failed to delete image
                    $error_message = "Warning: Could not delete image file. Please check file permissions.";
                    error_log("Failed to delete image: " . $full_path . " - Error: " . error_get_last()['message']);
                }
            } else {
                error_log("Image file not found: " . $full_path);
            }
        }
        
        $success_message = "Package deleted successfully!";
    } catch(PDOException $e) {
        // Rollback transaction on error
        if (isset($conn)) {
            $conn->rollBack();
        }
        $error_message = "Database error: " . $e->getMessage();
    }
}

// Fetch all packages
try {
    // Create database connection
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch packages with pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10; // Items per page
    $offset = ($page - 1) * $limit;
    
    // Process search and filter parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $package_type = isset($_GET['package_type']) ? trim($_GET['package_type']) : '';
    
    // Build the query based on search parameters
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(title LIKE :search OR description LIKE :search OR duration LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if (!empty($package_type)) {
        $where_conditions[] = "package_type = :package_type";
        $params[':package_type'] = $package_type;
    }
    
    // Construct the WHERE clause
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    // Get total count for pagination with filters
    $count_sql = "SELECT COUNT(*) FROM tour_packages $where_clause";
    $count_stmt = $conn->prepare($count_sql);
    foreach ($params as $key => $value) {
        $count_stmt->bindValue($key, $value);
    }
    $count_stmt->execute();
    $total_packages = $count_stmt->fetchColumn();
    $total_pages = ceil($total_packages / $limit);
    
    // Fetch packages for current page with filters
    $sql = "SELECT * FROM tour_packages $where_clause ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tour Packages - Admin Dashboard</title>
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
    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'partials/navigation.php'; ?>
    
    <div class="md:ml-64 pt-16 min-h-screen">
        <div class="container mx-auto px-4 py-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">Tour Packages</h1>
                    <a href="tour-packages-add.php" class="px-4 py-2 bg-primary text-white rounded-md hover:bg-blue-700 transition-colors flex items-center">
                        <i class="fas fa-plus mr-2"></i> Add New Package
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
                
                <!-- Search and Filter -->
                <div class="mb-6">
                    <form action="" method="GET" class="flex flex-col md:flex-row gap-4">
                        <div class="flex-grow">
                            <input type="text" name="search" placeholder="Search packages..." class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div class="w-full md:w-48">
                            <select name="package_type" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="">All Types</option>
                                <option value="normal">Normal Tour</option>
                                <option value="educational">Educational Tour</option>
                                <option value="corporate">Corporate Tour</option>
                            </select>
                        </div>
                        <button type="submit" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors">
                            <i class="fas fa-search mr-2"></i> Search
                        </button>
                    </form>
                </div>
                
                <!-- Packages Table (Desktop) -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full bg-white rounded-lg overflow-hidden">
                        <thead class="bg-gray-100 text-gray-700">
                            <tr>
                                <th class="py-3 px-4 text-left">ID</th>
                                <th class="py-3 px-4 text-left">Image</th>
                                <th class="py-3 px-4 text-left">Title</th>
                                <th class="py-3 px-4 text-left">Type</th>
                                <th class="py-3 px-4 text-left">Duration</th>
                                <th class="py-3 px-4 text-left">Price</th>
                                <th class="py-3 px-4 text-left">Status</th>
                                <th class="py-3 px-4 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (count($packages) > 0): ?>
                                <?php foreach ($packages as $package): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-3 px-4"><?php echo $package['id']; ?></td>
                                        <td class="py-3 px-4">
                                            <?php if (!empty($package['image'])): ?>
                                                <img src="../<?php echo $package['image']; ?>" alt="<?php echo htmlspecialchars($package['title']); ?>" class="w-16 h-12 object-cover rounded">
                                            <?php else: ?>
                                                <div class="w-16 h-12 bg-gray-200 rounded flex items-center justify-center text-gray-500">No Image</div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($package['title']); ?></td>
                                        <td class="py-3 px-4">
                                            <?php 
                                            $type_labels = [
                                                'normal' => 'Normal Tour',
                                                'educational' => 'Educational Tour',
                                                'corporate' => 'Corporate Tour'
                                            ];
                                            echo $type_labels[$package['package_type']] ?? $package['package_type']; 
                                            ?>
                                        </td>
                                        <td class="py-3 px-4"><?php echo htmlspecialchars($package['duration']); ?></td>
                                        <td class="py-3 px-4"><?php echo htmlspecialchars($package['price']); ?></td>
                                        <td class="py-3 px-4">
                                            <?php if ($package['is_published']): ?>
                                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Published</span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs">Draft</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex space-x-2">
                                                <a href="tour-packages-edit.php?id=<?php echo $package['id']; ?>" class="text-blue-600 hover:text-blue-800" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="#" onclick="confirmDelete(<?php echo $package['id']; ?>, '<?php echo addslashes(htmlspecialchars($package['title'])); ?>')" class="text-red-600 hover:text-red-800" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="py-6 px-4 text-center text-gray-500">No packages found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Packages Cards (Mobile) -->
                <div class="md:hidden space-y-4">
                    <?php if (count($packages) > 0): ?>
                        <?php foreach ($packages as $package): ?>
                            <div class="bg-white rounded-lg shadow p-4 border-l-4 border-primary">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center space-x-3">
                                        <?php if (!empty($package['image'])): ?>
                                            <img src="../<?php echo $package['image']; ?>" alt="<?php echo htmlspecialchars($package['title']); ?>" class="w-16 h-12 object-cover rounded">
                                        <?php else: ?>
                                            <div class="w-16 h-12 bg-gray-200 rounded flex items-center justify-center text-gray-500">No Image</div>
                                        <?php endif; ?>
                                        <div>
                                            <h3 class="font-medium"><?php echo htmlspecialchars($package['title']); ?></h3>
                                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($package['duration']); ?></p>
                                        </div>
                                    </div>
                                    <div>
                                        <?php if ($package['is_published']): ?>
                                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Published</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs">Draft</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="mt-3 flex justify-between items-center">
                                    <div>
                                        <p class="text-sm"><span class="text-gray-500">Type:</span> <?php echo $type_labels[$package['package_type']] ?? $package['package_type']; ?></p>
                                        <p class="text-sm"><span class="text-gray-500">Price:</span> <?php echo htmlspecialchars($package['price']); ?></p>
                                    </div>
                                    <div class="flex space-x-3">
                                        <a href="tour-packages-edit.php?id=<?php echo $package['id']; ?>" class="p-2 bg-blue-100 text-blue-600 rounded-full hover:bg-blue-200" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="#" onclick="confirmDelete(<?php echo $package['id']; ?>, '<?php echo addslashes(htmlspecialchars($package['title'])); ?>')" class="p-2 bg-red-100 text-red-600 rounded-full hover:bg-red-200" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="py-6 px-4 text-center text-gray-500 bg-white rounded-lg shadow">
                            No packages found
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="mt-6">
                    <nav class="flex justify-center">
                        <ul class="flex space-x-2">
                            <?php if ($page > 1): ?>
                                <li>
                                    <a href="?page=<?php echo $page - 1; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['package_type']) ? '&package_type=' . urlencode($_GET['package_type']) : ''; ?>" class="px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition-colors">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li>
                                    <a href="?page=<?php echo $i; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['package_type']) ? '&package_type=' . urlencode($_GET['package_type']) : ''; ?>" class="px-3 py-1 <?php echo $i === $page ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> rounded transition-colors">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li>
                                    <a href="?page=<?php echo $page + 1; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['package_type']) ? '&package_type=' . urlencode($_GET['package_type']) : ''; ?>" class="px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition-colors">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-md mx-auto">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Confirm Delete</h3>
            <p class="text-gray-600 mb-6">Are you sure you want to delete the package <span id="packageTitle" class="font-semibold"></span>? This action cannot be undone.</p>
            <div class="flex justify-end space-x-3">
                <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition-colors">
                    Cancel
                </button>
                <a id="confirmDeleteBtn" href="#" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors">
                    Delete
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Delete confirmation modal
        function confirmDelete(id, title) {
            document.getElementById('packageTitle').textContent = title;
            document.getElementById('confirmDeleteBtn').href = 'tour-packages-list.php?action=delete&id=' + id;
            document.getElementById('deleteModal').classList.remove('hidden');
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
        
        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>