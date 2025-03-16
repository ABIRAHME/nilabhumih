<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tour Packages - Nilabhoomi Tours and Travels</title>
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
</head>
<body class="bg-light min-h-screen">
    <?php include 'partials/navigation.php'; ?>

    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-primary to-secondary py-16 md:py-24 relative overflow-hidden">
        <div class="absolute inset-0 opacity-20">
            <svg class="h-full w-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                <path d="M0,0 L100,0 L100,100 Z" fill="white"></path>
            </svg>
        </div>
        <div class="container mx-auto px-4 text-center relative z-10">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">Tour Packages</h1>
            <p class="text-xl text-white/90 max-w-2xl mx-auto">Discover our handpicked destinations for unforgettable experiences</p>
        </div>
    </div>

    <!-- Tour Packages Grid -->
    <div class="container mx-auto px-4 py-12 md:py-16">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php
            // Include database connection
            require_once 'includes/db-connection.php';
            
            // Initialize packages array
            $packages = [];
            
            try {
                // Get database connection
                $conn = getDbConnection();
                
                if ($conn) {
                    // Fetch normal tour packages that are published
                    $stmt = $conn->prepare("SELECT * FROM tour_packages WHERE package_type = 'normal' AND is_published = 1 ORDER BY created_at DESC");
                    $stmt->execute();
                    $tour_packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // For each package, fetch its highlights
                    foreach ($tour_packages as $tour_package) {
                        $package_id = $tour_package['id'];
                        
                        // Fetch highlights for this package
                        $highlights_stmt = $conn->prepare("SELECT highlight FROM package_highlights WHERE package_id = :package_id");
                        $highlights_stmt->bindParam(':package_id', $package_id);
                        $highlights_stmt->execute();
                        $highlights = $highlights_stmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        // Add package with highlights to packages array
                        $packages[] = [
                            'id' => $package_id,
                            'title' => $tour_package['title'],
                            'duration' => $tour_package['duration'],
                            'price' => $tour_package['price'],
                            'date' => $tour_package['tour_date'],
                            'image' => $tour_package['image'],
                            'description' => $tour_package['description'],
                            'highlights' => $highlights
                        ];
                    }
                } else {
                    // If database connection fails, show error message
                    echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md" role="alert">
                            <p>Unable to connect to the database. Please try again later.</p>
                          </div>';
                }
            } catch (PDOException $e) {
                // Log error and show error message
                error_log("Database error: " . $e->getMessage());
                echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md" role="alert">
                        <p>An error occurred while fetching tour packages. Please try again later.</p>
                      </div>';
            }
            
            // If no packages found, display a message
            if (empty($packages)) {
                echo '<div class="col-span-full text-center py-12">
                        <div class="bg-accent/40 p-8 rounded-lg">
                            <p class="text-primary text-lg">No tour packages available at the moment. Please check back later.</p>
                        </div>
                      </div>';
            }

            // Function to limit description to max 50 words
            function limitWords($text, $maxWords = 40) {
                $words = explode(' ', $text);
                if (count($words) > $maxWords) {
                    return implode(' ', array_slice($words, 0, $maxWords)) . '...';
                }
                return $text;
            }

            foreach ($packages as $index => $package) {
                // Limit description to max 40 words
                $limited_description = limitWords($package['description'], 40);
                
                echo <<<HTML
                <div class="bg-white rounded-xl shadow-lg overflow-hidden transition-all duration-300 hover:shadow-xl group">
                    <div class="relative overflow-hidden">
                        <img src="{$package['image']}" alt="{$package['title']}" class="w-full h-56 object-cover transition-transform duration-500 group-hover:scale-110">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-primary mb-2">{$package['title']}</h3>
                        <p class="text-gray-600 mb-4 text-sm">{$limited_description}</p>
                        
                        <div class="flex flex-wrap items-center justify-between mb-4 gap-2">
                            <div class="flex items-center text-secondary font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                </svg>
                                <span>{$package['duration']}</span>
                            </div>
                            
                            <span class="text-lg font-bold text-primary">{$package['price']}</span>
                        </div>
                        
                        <div class="p-3 bg-accent/20 rounded-lg mb-4">
                            <div class="flex items-center text-secondary mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-sm">Date: {$package['date']}</span>
                            </div>
                           
                            <h4 class="font-semibold text-primary mb-1">Highlights:</h4>
                            <ul class="list-disc list-inside text-gray-600 text-sm space-y-1">
HTML;
                foreach ($package['highlights'] as $highlight) {
                    echo "<li>{$highlight}</li>";
                }
                echo <<<HTML
                            </ul>
                        </div>
                        <div class="flex space-x-2">
                            <a href="package-details.php?id={$package['id']}" class="flex-1 bg-primary text-white text-center py-2 rounded-lg hover:bg-primary/90 transition duration-300 flex items-center justify-center">
                                <span>View Details</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </a>
                            <a href="checkout.php?package={$package['id']}" class="flex-1 bg-secondary text-white text-center py-2 rounded-lg hover:bg-secondary/90 transition duration-300 flex items-center justify-center">
                                <span>Book Now</span>
                               
                            </a>
                        </div>
                    </div>
                </div>
                HTML;
            }
            ?>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
</body>
</html>