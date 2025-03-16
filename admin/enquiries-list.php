<?php
session_start();
require_once 'db-parameters.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Initialize variables
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Connect to database
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if enquiries table exists, if not create it
    $stmt = $conn->prepare("SHOW TABLES LIKE 'enquiries'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        // Create the enquiries table
        $sql = "CREATE TABLE IF NOT EXISTS enquiries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('new', 'read', 'replied') DEFAULT 'new',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
            INDEX (email),
            INDEX (status)
        );";
        $conn->exec($sql);
        
        // Insert some sample data for testing
        $sample_data = [
            ['John Doe', 'john@example.com', '+91 9876543210', 'I am interested in the Darjeeling tour package. Can you provide more details?', 'new'],
            ['Jane Smith', 'jane@example.com', '+91 8765432109', 'Looking for a family package to Sikkim for 5 people in December.', 'new'],
            ['Amit Kumar', 'amit@example.com', '+91 7654321098', 'Do you offer customized tour packages for corporate groups?', 'new']
        ];
        
        $insert_sql = "INSERT INTO enquiries (name, email, phone, message, status) VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        
        foreach ($sample_data as $data) {
            $insert_stmt->execute($data);
        }
    }
    
    // Count total records for pagination
    if (!empty($search_query)) {
        $count_sql = "SELECT COUNT(*) FROM enquiries 
                     WHERE name LIKE :search 
                     OR email LIKE :search 
                     OR phone LIKE :search 
                     OR message LIKE :search";
        $count_stmt = $conn->prepare($count_sql);
        $search_param = "%$search_query%";
        $count_stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
    } else {
        $count_sql = "SELECT COUNT(*) FROM enquiries";
        $count_stmt = $conn->prepare($count_sql);
    }
    $count_stmt->execute();
    $total_records = $count_stmt->fetchColumn();
    
    // Get enquiries with pagination
    if (!empty($search_query)) {
        $sql = "SELECT * FROM enquiries 
               WHERE name LIKE :search 
               OR email LIKE :search 
               OR phone LIKE :search 
               OR message LIKE :search 
               ORDER BY created_at DESC 
               LIMIT :offset, :limit";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
    } else {
        $sql = "SELECT * FROM enquiries 
               ORDER BY created_at DESC 
               LIMIT :offset, :limit";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
    }
    $stmt->execute();
    $enquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total pages for pagination
    $total_pages = ceil($total_records / $items_per_page);
    
} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
    $enquiries = [];
    $total_pages = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enquiries List - Nilabhoomi Tours and Travels</title>
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
            <h1 class="text-2xl font-bold text-gray-800">Booking Enquiries</h1>
            
            <!-- Search Form -->
            <form action="" method="GET" class="flex">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" 
                       placeholder="Search enquiries..." 
                       class="px-4 py-2 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-r-md hover:bg-blue-700 transition duration-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </form>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>

        <!-- Enquiries Table -->
        <div class="bg-white shadow-md rounded-lg overflow-hidden mb-6">
            <?php if (empty($enquiries)): ?>
                <div class="p-6 text-center text-gray-500">
                    <?php if (!empty($search_query)): ?>
                        No enquiries found matching "<?php echo htmlspecialchars($search_query); ?>". 
                        <a href="enquiries-list.php" class="text-blue-600 hover:underline">View all enquiries</a>
                    <?php else: ?>
                        No enquiries found.
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($enquiries as $enquiry): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($enquiry['name']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($enquiry['email']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($enquiry['phone']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 max-w-xs truncate"><?php echo htmlspecialchars($enquiry['message']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
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
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_color; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M d, Y', strtotime($enquiry['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="enquiry-details.php?id=<?php echo $enquiry['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                                    <a href="#" class="text-green-600 hover:text-green-900 mark-as-read" data-id="<?php echo $enquiry['id']; ?>" data-status="read">Mark as Read</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="flex justify-center mt-6">
                <nav class="inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo ($page - 1); ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" 
                           class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <span class="sr-only">Previous</span>
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    <?php endif; ?>
                    
                    <?php 
                    // Calculate range of page numbers to display
                    $range = 2; // Display 2 pages before and after current page
                    $start_page = max(1, $page - $range);
                    $end_page = min($total_pages, $page + $range);
                    
                    // Always show first page
                    if ($start_page > 1) {
                        echo '<a href="?page=1' . (!empty($search_query) ? '&search=' . urlencode($search_query) : '') . '" 
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                        if ($start_page > 2) {
                            echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                        }
                    }
                    
                    // Display page numbers
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        $active_class = $i === $page ? 'bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50';
                        echo '<a href="?page=' . $i . (!empty($search_query) ? '&search=' . urlencode($search_query) : '') . '" 
                               class="relative inline-flex items-center px-4 py-2 border ' . $active_class . ' text-sm font-medium">' . $i . '</a>';
                    }
                    
                    // Always show last page
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                        }
                        echo '<a href="?page=' . $total_pages . (!empty($search_query) ? '&search=' . urlencode($search_query) : '') . '" 
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">' . $total_pages . '</a>';
                    }
                    ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo ($page + 1); ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" 
                           class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <span class="sr-only">Next</span>
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
    </div>

    <!-- View Enquiry Modal -->
    <div id="enquiryModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">Enquiry Details</h3>
                <button id="closeModal" class="text-gray-400 hover:text-gray-500">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="px-6 py-4" id="enquiryDetails">
                <div class="animate-pulse">
                    <div class="h-4 bg-gray-200 rounded w-3/4 mb-4"></div>
                    <div class="h-4 bg-gray-200 rounded w-1/2 mb-4"></div>
                    <div class="h-4 bg-gray-200 rounded w-5/6 mb-4"></div>
                    <div class="h-20 bg-gray-200 rounded w-full"></div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
                <button id="markAsReadBtn" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition duration-300 mr-2">Mark as Read</button>
                <button id="closeModalBtn" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition duration-300">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Handle view enquiry click
        document.addEventListener('DOMContentLoaded', function() {
            const viewLinks = document.querySelectorAll('.view-enquiry');
            const modal = document.getElementById('enquiryModal');
            const closeModal = document.getElementById('closeModal');
            const closeModalBtn = document.getElementById('closeModalBtn');
            const enquiryDetails = document.getElementById('enquiryDetails');
            const markAsReadBtn = document.getElementById('markAsReadBtn');
            
            // Show modal when view link is clicked
            viewLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const enquiryId = this.getAttribute('data-id');
                    
                    // Show loading state
                    enquiryDetails.innerHTML = `
                        <div class="animate-pulse">
                            <div class="h-4 bg-gray-200 rounded w-3/4 mb-4"></div>
                            <div class="h-4 bg-gray-200 rounded w-1/2 mb-4"></div>
                            <div class="h-4 bg-gray-200 rounded w-5/6 mb-4"></div>
                            <div class="h-20 bg-gray-200 rounded w-full"></div>
                        </div>
                    `;
                    
                    modal.classList.remove('hidden');
                    
                    // Fetch enquiry details via AJAX
                    fetch('ajax/get-enquiry.php?id=' + enquiryId)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const enquiry = data.enquiry;
                                let statusBadge = '';
                                
                                switch(enquiry.status) {
                                    case 'new':
                                        statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">New</span>';
                                        break;
                                    case 'read':
                                        statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Read</span>';
                                        break;
                                    case 'replied':
                                        statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Replied</span>';
                                        break;
                                }
                                
                                enquiryDetails.innerHTML = `
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                        <div>
                                            <p class="text-sm text-gray-500">Name</p>
                                            <p class="text-md font-medium">${enquiry.name}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500">Status</p>
                                            <p class="text-md font-medium">${statusBadge}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500">Email</p>
                                            <p class="text-md font-medium">${enquiry.email}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500">Phone</p>
                                            <p class="text-md font-medium">${enquiry.phone}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500">Date</p>
                                            <p class="text-md font-medium">${new Date(enquiry.created_at).toLocaleDateString()}</p>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <p class="text-sm text-gray-500">Message</p>
                                        <p class="text-md mt-1 p-3 bg-gray-50 rounded">${enquiry.message}</p>
                                    </div>
                                `;
                                
                                // Update mark as read button text based on current status
                                if (enquiry.status === 'new') {
                                    markAsReadBtn.textContent = 'Mark as Read';
                                    markAsReadBtn.setAttribute('data-status', 'read');
                                } else if (enquiry.status === 'read') {
                                    markAsReadBtn.textContent = 'Mark as Replied';
                                    markAsReadBtn.setAttribute('data-status', 'replied');
                                } else {
                                    markAsReadBtn.textContent = 'Mark as New';
                                    markAsReadBtn.setAttribute('data-status', 'new');
                                }
                                
                                markAsReadBtn.setAttribute('data-id', enquiry.id);
                            } else {
                                enquiryDetails.innerHTML = `<p class="text-red-500">${data.message || 'Error loading enquiry details'}</p>`;
                            }
                        })
                        .catch(error => {
                            enquiryDetails.innerHTML = `<p class="text-red-500">Error: ${error.message}</p>`;
                        });
                });
            });
            
            // Close modal when close button is clicked
            closeModal.addEventListener('click', function() {
                modal.classList.add('hidden');
            });
            
            closeModalBtn.addEventListener('click', function() {
                modal.classList.add('hidden');
            });
            
            // Close modal when clicking outside
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                }
            });
            
            // Handle mark as read button in modal
            markAsReadBtn.addEventListener('click', function() {
                const enquiryId = this.getAttribute('data-id');
                const newStatus = this.getAttribute('data-status');
                
                fetch('ajax/update-enquiry-status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${enquiryId}&status=${newStatus}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload the page to reflect changes
                        window.location.reload();
                    } else {
                        alert(data.message || 'Error updating status');
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                });
            });
            
            // Handle mark as read links in the table
            const markAsReadLinks = document.querySelectorAll('.mark-as-read');
            markAsReadLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const enquiryId = this.getAttribute('data-id');
                    const newStatus = this.getAttribute('data-status');
                    
                    fetch('ajax/update-enquiry-status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id=${enquiryId}&status=${newStatus}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Reload the page to reflect changes
                            window.location.reload();
                        } else {
                            alert(data.message || 'Error updating status');
                        }
                    })
                    .catch(error => {
                        alert('Error: ' + error.message);
                    });
                });
            });
        });
    </script>
</body>
</html>