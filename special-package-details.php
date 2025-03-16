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
                        primary: '#008DDA',
                        secondary: '#41C9E2',
                        accent: '#ACE2E1',
                        light: '#F7EEDD'
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
</head>
<body class="bg-gradient-to-r from-accent/30 to-light">
    <?php 
    include 'partials/navigation.php';
    require_once 'includes/db-connection.php';
    
    // Initialize package data
    $package = [
        'title' => 'Package Not Found',
        'duration' => '',
        'price' => '',
        'image' => 'images/demo.jpeg',
        'description' => 'The requested package could not be found.',
        'highlights' => [],
        'itinerary' => []
    ];
    
    // Check if ID is provided
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $package_id = (int)$_GET['id'];
        
        // Get database connection
        $conn = getDbConnection();
        
        if ($conn) {
            try {
                // Fetch package details
                $stmt = $conn->prepare("SELECT * FROM tour_packages WHERE id = :id AND is_published = 1");
                $stmt->bindParam(':id', $package_id);
                $stmt->execute();
                
                $packageData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($packageData) {
                    // Set basic package data
                    $package['title'] = $packageData['title'];
                    $package['duration'] = $packageData['duration'];
                    $package['price'] = $packageData['price'];
                    $package['description'] = $packageData['description'];
                    
                    // Set image path with fallback
                    if (!empty($packageData['image'])) {
                        $package['image'] = $packageData['image'];
                        if (strpos($package['image'], 'images/') !== 0) {
                            $package['image'] = 'images/' . $package['image'];
                        }
                    }
                    
                    // Fetch highlights
                    $stmtHighlights = $conn->prepare("SELECT highlight FROM package_highlights WHERE package_id = :package_id");
                    $stmtHighlights->bindParam(':package_id', $package_id);
                    $stmtHighlights->execute();
                    $package['highlights'] = $stmtHighlights->fetchAll(PDO::FETCH_COLUMN);
                    
                    // Fetch itinerary
                    $stmtItinerary = $conn->prepare("SELECT day, activity FROM package_itinerary WHERE package_id = :package_id ORDER BY day");
                    $stmtItinerary->bindParam(':package_id', $package_id);
                    $stmtItinerary->execute();
                    
                    $itineraryItems = $stmtItinerary->fetchAll(PDO::FETCH_ASSOC);
                    $package['itinerary'] = [];
                    
                    foreach ($itineraryItems as $item) {
                        $package['itinerary'][$item['day']] = $item['activity'];
                    }
                }
            } catch (PDOException $e) {
                // Log error
                error_log("Error fetching package details: " . $e->getMessage());
            }
        }
    }
    ?>

    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Package Header -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-8">
            <div class="relative">
                <img src="<?php echo $package['image']; ?>" alt="<?php echo $package['title']; ?>" class="w-full h-80 md:h-96 object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                <div class="absolute bottom-0 left-0 p-6 w-full">
                    <div class="flex flex-col md:flex-row md:justify-between md:items-end">
                        <h1 class="text-3xl md:text-4xl font-bold text-white mb-2"><?php echo $package['title']; ?></h1>
                        <div class="bg-primary text-white px-6 py-3 rounded-full font-bold text-xl shadow-lg">
                            <?php echo $package['price']; ?>
                        </div>
                    </div>
                    <p class="text-white/90 text-lg"><?php echo $package['duration']; ?></p>
                </div>
            </div>
        </div>
        
        <!-- Package Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column -->
            <div class="lg:col-span-2">
                <!-- Description -->
                <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
                    <h2 class="text-2xl font-bold text-primary mb-4">About This Package</h2>
                    <p class="text-gray-700 leading-relaxed"><?php echo $package['description']; ?></p>
                </div>
                
                <!-- Itinerary -->
                <div class="bg-white rounded-2xl shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-primary mb-6 flex items-center">
                        <i class="fas fa-route mr-3 text-secondary"></i> Itinerary
                    </h2>
                    <div class="space-y-6">
                        <?php foreach ($package['itinerary'] as $day => $activity): ?>
                            <div class="border-l-4 border-secondary pl-4 py-2 hover:bg-light transition duration-300 rounded">
                                <h3 class="font-bold text-primary text-lg"><?php echo $day; ?></h3>
                                <p class="text-gray-700"><?php echo $activity; ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Right Column -->
            <div>
                <!-- Highlights Card -->
                <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 sticky top-8">
                    <h2 class="text-2xl font-bold text-primary mb-6 flex items-center">
                        <i class="fas fa-star mr-3 text-secondary"></i> Highlights
                    </h2>
                    <ul class="space-y-4">
                        <?php foreach ($package['highlights'] as $highlight): ?>
                            <li class="flex items-start">
                                <div class="text-accent mr-3 mt-1"><i class="fas fa-check-circle"></i></div>
                                <p class="text-gray-700"><?php echo $highlight; ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <!-- Booking Area -->
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <a href="special-checkout.php?package=<?php echo $package_id; ?>" class="block w-full bg-primary hover:bg-primary/90 text-white text-center py-4 rounded-xl font-bold text-lg transition duration-300 shadow-lg">
                            <i class="fas fa-calendar-check mr-2"></i> Book Now
                        </a>
                        <a href="educational-corporate-tours.php" class="block w-full mt-4 bg-light hover:bg-light/80 text-primary text-center py-3 rounded-xl font-medium transition duration-300">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Packages
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>
</body>
</html>