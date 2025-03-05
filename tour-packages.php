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
                        primary: '#1e40af',
                        secondary: '#4f46e5'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-r from-blue-50 to-indigo-50">
    <?php include 'partials/navigation.php'; ?>

    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 py-20">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-4xl font-bold text-white mb-4">Tour Packages</h1>
            <p class="text-xl text-white/80">Discover our handpicked destinations</p>
        </div>
    </div>

    <!-- Tour Packages Grid -->
    <div class="container mx-auto px-4 py-16">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php
            $packages = [
                [
                    'title' => 'Enchanting Kerala',
                    'duration' => '5 Days / 4 Nights',
                    'price' => '₹24,999',
                    'image' => 'images/demo.jpeg',
                    'description' => 'Experience the serene backwaters, lush tea plantations, and rich culture of God\'s Own Country.',
                    'highlights' => ['Alleppey Houseboat Stay', 'Munnar Tea Gardens', 'Kovalam Beach', 'Ayurvedic Spa']
                ],
                [
                    'title' => 'Royal Rajasthan',
                    'duration' => '7 Days / 6 Nights',
                    'price' => '₹34,999',
                    'image' => 'images/demo.jpeg',
                    'description' => 'Journey through the land of kings, exploring majestic forts, palaces, and desert landscapes.',
                    'highlights' => ['Amber Fort', 'Desert Safari', 'Lake Palace', 'Local Cuisine']
                ],
                [
                    'title' => 'Himalayan Adventure',
                    'duration' => '6 Days / 5 Nights',
                    'price' => '₹29,999',
                    'image' => 'images/demo.jpeg',
                    'description' => 'Embark on a thrilling journey through the mighty Himalayas with breathtaking views.',
                    'highlights' => ['Trekking', 'River Rafting', 'Camping', 'Local Monasteries']
                ],
                [
                    'title' => 'Goa Beach Escape',
                    'duration' => '4 Days / 3 Nights',
                    'price' => '₹19,999',
                    'image' => 'images/demo.jpeg',
                    'description' => 'Relax on pristine beaches, enjoy water sports, and experience vibrant nightlife.',
                    'highlights' => ['Beach Activities', 'Water Sports', 'Nightlife', 'Portuguese Heritage']
                ],
                [
                    'title' => 'Northeast Explorer',
                    'duration' => '8 Days / 7 Nights',
                    'price' => '₹39,999',
                    'image' => 'images/demo.jpeg',
                    'description' => 'Discover the untouched beauty of Northeast India with its unique culture and landscapes.',
                    'highlights' => ['Living Root Bridges', 'Tea Gardens', 'Wildlife Safari', 'Local Festivals']
                ],
                [
                    'title' => 'Golden Triangle',
                    'duration' => '6 Days / 5 Nights',
                    'price' => '₹27,999',
                    'image' => 'images/demo.jpeg',
                    'description' => 'Experience the perfect introduction to India\'s rich history and culture.',
                    'highlights' => ['Taj Mahal', 'Red Fort', 'City Palace', 'Local Markets']
                ]
            ];

            foreach ($packages as $index => $package) {
                echo <<<HTML
                <div class="bg-white rounded-lg shadow-lg overflow-hidden transition-transform hover:scale-105 duration-300">
                    <img src="{$package['image']}" alt="{$package['title']}" class="w-full h-48 object-cover">
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-2">{$package['title']}</h3>
                        <p class="text-gray-600 mb-4">{$package['description']}</p>
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-sm text-gray-500">{$package['duration']}</span>
                            <span class="text-lg font-bold text-primary">{$package['price']}</span>
                        </div>
                        <div class="space-y-2 mb-4">
                            <h4 class="font-semibold text-gray-800">Highlights:</h4>
                            <ul class="list-disc list-inside text-gray-600 text-sm">
HTML;
                foreach ($package['highlights'] as $highlight) {
                    echo "<li>{$highlight}</li>";
                }
                echo <<<HTML
                            </ul>
                        </div>
                        <div class="flex space-x-2">
                            <a href="package-details.php?id={$index}" class="flex-1 bg-primary text-white text-center py-2 rounded hover:bg-secondary transition duration-300">View Details</a>
                            <a href="checkout.php?package={$index}" class="flex-1 bg-green-600 text-white text-center py-2 rounded hover:bg-green-700 transition duration-300">Book Now</a>
                        </div>
                    </div>
                </div>
                HTML;
            }

            function renderHighlights($highlights) {
                return implode('', array_map(function($highlight) {
                    return "<li>{$highlight}</li>";
                }, $highlights));
            }
            ?>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
</body>
</html>