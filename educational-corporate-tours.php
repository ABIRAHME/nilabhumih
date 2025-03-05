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
            <h1 class="text-4xl font-bold text-white mb-4">Educational & Corporate Tours</h1>
            <p class="text-xl text-white/80">Specialized tours for schools and corporate groups</p>
        </div>
    </div>

    <!-- Filter Buttons -->
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-center space-x-4 mb-8">
            <button onclick="filterTours('all')" class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-secondary transition duration-300 filter-btn active">All Tours</button>
            <button onclick="filterTours('school')" class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-secondary transition duration-300 filter-btn">School Tours</button>
            <button onclick="filterTours('corporate')" class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-secondary transition duration-300 filter-btn">Corporate Tours</button>
        </div>
    </div>

    <!-- Tour Packages Grid -->
    <div class="container mx-auto px-4 pb-16">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php
            $packages = [
                [
                    'type' => 'school',
                    'title' => 'Historical Heritage Tour',
                    'duration' => '3 Days / 2 Nights',
                    'price' => '₹8,999',
                    'image' => 'images/demo.jpeg',
                    'description' => 'Educational tour covering historical monuments and cultural heritage sites.',
                    'highlights' => ['Interactive History Sessions', 'Museum Visits', 'Cultural Workshops', 'Educational Activities'],
                    'itinerary' => [
                        'Day 1' => 'Monument Visit & Historical Overview',
                        'Day 2' => 'Museum Tour & Cultural Activities',
                        'Day 3' => 'Workshop & Learning Assessment'
                    ]
                ],
                [
                    'type' => 'school',
                    'title' => 'Science & Technology Tour',
                    'duration' => '4 Days / 3 Nights',
                    'price' => '₹12,999',
                    'image' => 'images/demo.jpeg',
                    'description' => 'Educational tour focusing on science, technology, and innovation centers.',
                    'highlights' => ['Science Center Visits', 'Interactive Workshops', 'Tech Labs', 'Hands-on Experiments'],
                    'itinerary' => [
                        'Day 1' => 'Science Center Exploration',
                        'Day 2' => 'Tech Lab Activities',
                        'Day 3' => 'Innovation Workshop',
                        'Day 4' => 'Project Presentation'
                    ]
                ],
                [
                    'type' => 'corporate',
                    'title' => 'Team Building Retreat',
                    'duration' => '3 Days / 2 Nights',
                    'price' => '₹15,999',
                    'image' => 'images/demo.jpeg',
                    'description' => 'Corporate retreat focused on team building and leadership development.',
                    'highlights' => ['Team Building Activities', 'Leadership Workshops', 'Adventure Sports', 'Strategy Sessions'],
                    'itinerary' => [
                        'Day 1' => 'Ice Breaking & Team Activities',
                        'Day 2' => 'Leadership Workshop & Adventure',
                        'Day 3' => 'Strategy Planning & Closure'
                    ]
                ],
                [
                    'type' => 'corporate',
                    'title' => 'Corporate Wellness Retreat',
                    'duration' => '4 Days / 3 Nights',
                    'price' => '₹18,999',
                    'image' => 'images/demo.jpeg',
                    'description' => 'Focus on employee wellness, stress management, and team bonding.',
                    'highlights' => ['Wellness Sessions', 'Yoga & Meditation', 'Team Activities', 'Stress Management'],
                    'itinerary' => [
                        'Day 1' => 'Wellness Introduction',
                        'Day 2' => 'Team Building & Activities',
                        'Day 3' => 'Stress Management Workshop',
                        'Day 4' => 'Reflection & Action Planning'
                    ]
                ]
            ];

            foreach ($packages as $index => $package) {
                echo <<<HTML
                <div class="tour-card {$package['type']} bg-white rounded-lg shadow-lg overflow-hidden transition-transform hover:scale-105 duration-300">
                    <img src="{$package['image']}" alt="{$package['title']}" class="w-full h-48 object-cover">
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-xl font-bold text-gray-800">{$package['title']}</h3>
                            <span class="px-3 py-1 bg-primary text-white text-sm rounded-full">{$package['type']}</span>
                        </div>
                        <p class="text-gray-600 mb-4">{$package['description']}</p>
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-sm text-gray-500">{$package['duration']}</span>
                            <span class="text-lg font-bold text-primary">{$package['price']}/person</span>
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
                            <a href="special-package-details.php?id={$index}&type=special" class="flex-1 bg-primary text-white text-center py-2 rounded hover:bg-secondary transition duration-300">View Details</a>
                            
                            <a href="special-checkout.php?package={$index}&type=special" class="flex-1 bg-green-600 text-white text-center py-2 rounded hover:bg-green-700 transition duration-300">Book Now</a>
                        
                        </div>
                    </div>
                </div>
HTML;
            }
            ?>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>

    <script>
        function filterTours(type) {
            // Update active button state
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active', 'bg-secondary');
            });
            event.target.classList.add('active', 'bg-secondary');

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