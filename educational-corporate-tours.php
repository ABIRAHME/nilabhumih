<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Educational & Corporate Tours - Nilabhoomi Tours and Travels</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#008DDA',
                        secondary: '#41C9E2',
                        tertiary: '#ACE2E1',
                        neutral: '#F7EEDD'
                    },
                   
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
</head>
<body class="bg-neutral min-h-screen">

    <?php 
   
    require_once 'includes/db-connection.php';
    
    // Function to limit text to a specific word count
    function limitWords($text, $limit = 50) {
        $words = explode(' ', $text);
        if (count($words) > $limit) {
            return implode(' ', array_slice($words, 0, $limit)) . '...';
        }
        return $text;
    }
    ?>

<?php include 'partials/navigation.php'; ?>
    <!-- Hero Section -->
    <div class="relative bg-gradient-to-r from-primary to-secondary py-24 overflow-hidden">
        <div class="absolute inset-0">
            <svg class="absolute bottom-0 left-0 w-full h-48 text-neutral opacity-10" viewBox="0 0 1440 320" fill="currentColor" preserveAspectRatio="none">
                <path d="M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,213.3C672,224,768,224,864,213.3C960,203,1056,181,1152,181.3C1248,181,1344,203,1392,213.3L1440,224L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
            </svg>
        </div>
        <div class="container mx-auto px-4 text-center relative z-10">
            <span class="inline-block px-4 py-1 bg-white/20 text-white rounded-full text-sm font-medium mb-5">Explore Our Special Tours</span>
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6">Educational & Corporate Tours</h1>
            <p class="text-xl text-white/90 max-w-2xl mx-auto">Specialized curated experiences designed for schools and corporate groups to learn, bond, and grow together</p>
        </div>
    </div>

    <!-- Filter Buttons -->
    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-wrap justify-center gap-4 mb-12 max-w-3xl mx-auto">
            <button onclick="filterTours('all')" class="px-8 py-3 bg-primary text-white rounded-full hover:bg-secondary transition duration-300 shadow-md filter-btn active-filter font-medium">
                All Tours
            </button>
            <button onclick="filterTours('school')" class="px-8 py-3 bg-primary text-white rounded-full hover:bg-secondary transition duration-300 shadow-md filter-btn font-medium">
                School Tours
            </button>
            <button onclick="filterTours('corporate')" class="px-8 py-3 bg-primary text-white rounded-full hover:bg-secondary transition duration-300 shadow-md filter-btn font-medium">
                Corporate Tours
            </button>
        </div>
    </div>

    <!-- Tour Packages Grid -->
    <div class="container mx-auto px-4 pb-24">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php
            // Get database connection
            $conn = getDbConnection();
            $packages = [];
            
            if ($conn) {
                try {
                    // Fetch educational and corporate tour packages
                    $stmt = $conn->prepare("SELECT * FROM tour_packages WHERE package_type IN ('educational', 'corporate') AND is_published = 1 ORDER BY created_at DESC");
                    $stmt->execute();
                    $tourPackages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Process each package
                    foreach ($tourPackages as $package) {
                        $packageId = $package['id'];
                        
                        // Fetch highlights for this package
                        $stmtHighlights = $conn->prepare("SELECT highlight FROM package_highlights WHERE package_id = :package_id");
                        $stmtHighlights->bindParam(':package_id', $packageId);
                        $stmtHighlights->execute();
                        $highlights = $stmtHighlights->fetchAll(PDO::FETCH_COLUMN);
                        
                        // Add highlights to package data
                        $package['highlights'] = $highlights;
                        
                        // Add to packages array
                        $packages[] = $package;
                    }
                } catch (PDOException $e) {
                    echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded'>Error fetching packages: " . $e->getMessage() . "</div>";
                }
            } else {
                echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded'>Database connection failed</div>";
            }
            
            // Display packages
            if (!empty($packages)) {
                foreach ($packages as $package) {
                    // Determine the tour type class (for filtering)
                    $typeClass = $package['package_type'] === 'educational' ? 'school' : 'corporate';
                    $typeLabel = $package['package_type'] === 'educational' ? 'Educational' : 'Corporate';
                    
                    // Set image path with fallback
                    $imagePath = !empty($package['image']) ? $package['image'] : 'images/demo.jpeg';
                    if (strpos($imagePath, 'images/') !== 0) {
                        $imagePath = 'images/' . $imagePath;
                    }
                    
                    // Limit description to 50 words
                    $limitedDescription = limitWords($package['description'], 50);
                    
                    echo <<<HTML
                    <div class="tour-card {$typeClass} bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="relative">
                            <img src="{$imagePath}" alt="{$package['title']}" class="w-full h-56 object-cover">
                            <div class="absolute top-4 right-4">
                                <span class="px-4 py-1 bg-secondary text-white text-sm rounded-full font-medium">{$typeLabel}</span>
                            </div>
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-3">{$package['title']}</h3>
                            <p class="text-gray-600 mb-5">{$limitedDescription}</p>
                            
                            <div class="flex items-center justify-between mb-5 pb-5 border-b border-gray-100">
                                <div class="flex items-center space-x-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-sm text-gray-500">{$package['duration']}</span>
                                </div>
                                <span class="text-lg font-bold text-primary">{$package['price']}<span class="text-sm font-normal">/person</span></span>
                            </div>
                            
                            <div class="space-y-3 mb-6">
                                <h4 class="font-semibold text-gray-800 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Highlights
                                </h4>
                                <ul class="space-y-2 text-gray-600 text-sm">
    HTML;
                    
                    // Display highlights
                    if (!empty($package['highlights'])) {
                        foreach ($package['highlights'] as $highlight) {
                            echo "<li class='flex items-start'>
                                <span class='text-tertiary mr-2'>•</span>
                                {$highlight}
                            </li>";
                        }
                    } else {
                        echo "<li class='flex items-start'>
                            <span class='text-tertiary mr-2'>•</span>
                            Package details available on request
                        </li>";
                    }
                    
                    echo <<<HTML
                                </ul>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-3">
                                <a href="special-package-details.php?id={$package['id']}" class="w-full bg-primary hover:bg-secondary text-white font-medium text-center py-3 px-4 rounded-full transition duration-300">View Details</a>
                                
                                <a href="special-checkout.php?package={$package['id']}" class="w-full bg-tertiary hover:bg-secondary text-primary hover:text-white font-medium text-center py-3 px-4 rounded-full transition duration-300">Book Now</a>
                            </div>
                        </div>
                    </div>
    HTML;
                }
            } else {
                echo "<div class='col-span-3 text-center py-16 bg-white rounded-2xl shadow-md'>
                    <svg xmlns='http://www.w3.org/2000/svg' class='h-16 w-16 mx-auto text-primary/30 mb-4' fill='none' viewBox='0 0 24 24' stroke='currentColor'>
                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' />
                    </svg>
                    <p class='text-xl text-gray-600'>No tour packages found at the moment.</p>
                    <p class='text-gray-500 mt-2'>Please check back later or contact us for custom tour options.</p>
                </div>";
            }
            ?>
        </div>
    </div>

    <!-- Newsletter Section -->
    <div class="bg-tertiary py-16">
        <div class="container mx-auto px-4">
            <div class="max-w-3xl mx-auto text-center">
                <h2 class="text-3xl font-bold text-primary mb-4">Stay Updated with New Tours</h2>
                <p class="text-gray-600 mb-8">Subscribe to our newsletter and be the first to know about new educational and corporate tour packages.</p>
                <form class="flex flex-col sm:flex-row gap-3 max-w-lg mx-auto">
                    <input type="email" placeholder="Your email address" class="flex-1 px-5 py-3 rounded-full focus:outline-none focus:ring-2 focus:ring-primary">
                    <button type="submit" class="bg-primary hover:bg-secondary text-white px-8 py-3 rounded-full font-medium transition duration-300">Subscribe</button>
                </form>
            </div>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>

    <script>
        function filterTours(type) {
            // Update active button state
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active-filter');
            });
            event.target.classList.add('active-filter');

            // Filter tours
            document.querySelectorAll('.tour-card').forEach(card => {
                if (type === 'all' || card.classList.contains(type)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>