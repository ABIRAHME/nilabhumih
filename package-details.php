<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Package Details - Nilabhoomi Tours and Travels</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        deepBlue: '#008DDA',
                        teal: '#41C9E2',
                        lightTeal: '#ACE2E1',
                        cream: '#F7EEDD'
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gradient-to-r from-lightTeal to-cream min-h-screen">
    <?php include 'partials/navigation.php'; ?>

    <?php
    // Include database connection
    require_once 'includes/db-connection.php';
    
    // Initialize package variable
    $package = null;
    $error_message = '';
    
    // Get package ID from URL
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    try {
        // Get database connection
        $conn = getDbConnection();
        
        if ($conn) {
            // Fetch the specific package by ID
            $stmt = $conn->prepare("SELECT * FROM tour_packages WHERE id = :id AND package_type = 'normal' AND is_published = 1");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $tour_package = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($tour_package) {
                // Fetch highlights for this package
                $highlights_stmt = $conn->prepare("SELECT highlight FROM package_highlights WHERE package_id = :package_id");
                $highlights_stmt->bindParam(':package_id', $id);
                $highlights_stmt->execute();
                $highlights = $highlights_stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Fetch itinerary for this package
                $itinerary_stmt = $conn->prepare("SELECT day, activity FROM package_itinerary WHERE package_id = :package_id ORDER BY id ASC");
                $itinerary_stmt->bindParam(':package_id', $id);
                $itinerary_stmt->execute();
                $itinerary_items = $itinerary_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Convert itinerary items to associative array
                $itinerary = [];
                foreach ($itinerary_items as $item) {
                    $itinerary[$item['day']] = $item['activity'];
                }
                
                // Create package array with all details
                $package = [
                    'id' => $tour_package['id'],
                    'title' => $tour_package['title'],
                    'duration' => $tour_package['duration'],
                    'price' => $tour_package['price'],
                    'date' => $tour_package['tour_date'],
                    'image' => $tour_package['image'],
                    'description' => $tour_package['description'],
                    'highlights' => $highlights,
                    'itinerary' => $itinerary
                ];
            } else {
                // If package not found, redirect to tour packages page
                header('Location: tour-packages.php');
                exit;
            }
        } else {
            // If database connection fails, set error message
            $error_message = 'Unable to connect to the database. Please try again later.';
        }
    } catch (PDOException $e) {
        // Log error and set error message
        error_log("Database error: " . $e->getMessage());
        $error_message = 'An error occurred while fetching package details. Please try again later.';
    }
    
    // If no package found or error occurred, show error message
    if (!$package && empty($error_message)) {
        $error_message = 'Package not found. Please select another package.';
    }
    ?>

    <div class="container mx-auto px-4 py-12 max-w-6xl">
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                <p><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>

        <?php if ($package): ?>
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <div class="relative">
                    <img src="<?php echo $package['image']; ?>" alt="<?php echo $package['title']; ?>" class="w-full h-96 object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent flex items-end">
                        <div class="p-8 w-full">
                            <h1 class="text-4xl font-bold text-white mb-2"><?php echo $package['title']; ?></h1>
                        </div>
                    </div>
                </div>
                
                <div class="p-8">
                    <div class="flex flex-col md:flex-row md:justify-between gap-6 mb-10">
                        <div class="md:w-2/3">
                            <p class="text-gray-700 leading-relaxed mb-6"><?php echo $package['description']; ?></p>
                        </div>
                        
                        <div class="md:w-1/3 bg-deepBlue/5 p-6 rounded-xl">
                            <div class="flex flex-col space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 font-medium"><i class="fas fa-coins mr-2"></i>Price:</span>
                                    <span class="text-2xl font-bold text-deepBlue"><?php echo $package['price']; ?></span>
                                </div>
                                
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 font-medium"><i class="far fa-calendar-alt mr-2"></i>Date:</span>
                                    <span class="text-gray-800 font-medium"><?php echo $package['date']; ?></span>
                                </div>
                                
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 font-medium"><i class="far fa-clock mr-2"></i>Duration:</span>
                                    <span class="text-gray-800 font-medium"><?php echo $package['duration']; ?></span>
                                </div>
                                
                                <a href="checkout.php?package=<?php echo $id; ?>" class="mt-4 w-full bg-deepBlue hover:bg-teal text-white font-medium py-3 px-6 rounded-lg transition duration-300 flex justify-center items-center">
                                    <i class="fas fa-ticket-alt mr-2"></i> Book Now
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 mb-10">
                        <div>
                            <h2 class="text-2xl font-bold text-deepBlue mb-6 flex items-center">
                                <i class="fas fa-star text-teal mr-2"></i> Highlights
                            </h2>
                            <ul class="space-y-3">
                                <?php foreach ($package['highlights'] as $highlight): ?>
                                    <li class="flex items-start">
                                        <span class="text-teal mr-2 mt-1"><i class="fas fa-check-circle"></i></span>
                                        <span class="text-gray-700"><?php echo $highlight; ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <div>
                            <h2 class="text-2xl font-bold text-deepBlue mb-6 flex items-center">
                                <i class="fas fa-map-marked-alt text-teal mr-2"></i> Itinerary
                            </h2>
                            <div class="space-y-6">
                                <?php foreach ($package['itinerary'] as $day => $activity): ?>
                                    <div class="border-l-4 border-teal pl-4 py-1">
                                        <h3 class="font-bold text-gray-800 mb-1"><?php echo $day; ?></h3>
                                        <p class="text-gray-600"><?php echo $activity; ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-between items-center bg-cream/50 p-6 rounded-xl">
                        <div class="mb-4 md:mb-0">
                            <h3 class="text-lg font-medium text-deepBlue mb-1">Ready for an unforgettable adventure?</h3>
                            <p class="text-gray-600">Book this tour and create memories that will last a lifetime.</p>
                        </div>
                        <div class="flex gap-4">
                            <a href="tour-packages.php" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition duration-300 flex items-center">
                                <i class="fas fa-arrow-left mr-2"></i> Back to Packages
                            </a>
                            <a href="checkout.php?package=<?php echo $id; ?>" class="px-6 py-3 bg-deepBlue text-white rounded-lg hover:bg-teal transition duration-300 flex items-center">
                                <i class="fas fa-shopping-cart mr-2"></i> Book Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'partials/footer.php'; ?>
</body>
</html>