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

    <?php
    $packages = [
        [
            'title' => 'Enchanting Kerala',
            'duration' => '5 Days / 4 Nights',
            'price' => '₹24,999',
            'image' => 'images/demo.jpeg',
            'description' => 'Experience the serene backwaters, lush tea plantations, and rich culture of God\'s Own Country.',
            'highlights' => ['Alleppey Houseboat Stay', 'Munnar Tea Gardens', 'Kovalam Beach', 'Ayurvedic Spa'],
            'itinerary' => [
                'Day 1' => 'Arrival in Kochi - Transfer to Munnar',
                'Day 2' => 'Munnar Tea Gardens and Sightseeing',
                'Day 3' => 'Transfer to Alleppey - Houseboat Check-in',
                'Day 4' => 'Kovalam Beach Transfer and Activities',
                'Day 5' => 'Departure with Ayurvedic Spa Experience'
            ]
        ],
        // ... Add other packages similarly
    ];

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $package = isset($packages[$id]) ? $packages[$id] : $packages[0];
    ?>

    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <img src="<?php echo $package['image']; ?>" alt="<?php echo $package['title']; ?>" class="w-full h-96 object-cover">
            
            <div class="p-8">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800 mb-2"><?php echo $package['title']; ?></h1>
                        <p class="text-gray-600"><?php echo $package['description']; ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold text-primary"><?php echo $package['price']; ?></p>
                        <p class="text-gray-500"><?php echo $package['duration']; ?></p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Highlights</h2>
                        <ul class="list-disc list-inside space-y-2">
                            <?php foreach ($package['highlights'] as $highlight): ?>
                                <li class="text-gray-600"><?php echo $highlight; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Itinerary</h2>
                        <div class="space-y-4">
                            <?php foreach ($package['itinerary'] as $day => $activity): ?>
                                <div class="border-l-4 border-primary pl-4">
                                    <h3 class="font-bold text-gray-800"><?php echo $day; ?></h3>
                                    <p class="text-gray-600"><?php echo $activity; ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="tour-packages.php" class="px-6 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition duration-300">Back to Packages</a>
                    <a href="special-checkout.php?package=<?php echo $id; ?>" class="px-6 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition duration-300">Book Now</a>
                </div>
            </div>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>
</body>
</html>