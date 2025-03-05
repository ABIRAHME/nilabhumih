<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Nilabhoomi Tours and Travels</title>
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
        ],
        // ... Add other packages similarly
    ];

    $package_id = isset($_GET['package']) ? (int)$_GET['package'] : 0;
    $package = isset($packages[$package_id]) ? $packages[$package_id] : $packages[0];
    ?>

    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Booking Form -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Booking Information</h2>
                <form action="process_booking.php" method="POST" class="space-y-4">
                    <input type="hidden" name="package_id" value="<?php echo $package_id; ?>">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 mb-2">First Name</label>
                            <input type="text" name="first_name" required class="w-full px-4 py-2 border rounded focus:outline-none focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Last Name</label>
                            <input type="text" name="last_name" required class="w-full px-4 py-2 border rounded focus:outline-none focus:border-primary">
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Email</label>
                        <input type="email" name="email" required class="w-full px-4 py-2 border rounded focus:outline-none focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Phone</label>
                        <input type="tel" name="phone" required class="w-full px-4 py-2 border rounded focus:outline-none focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Travel Date</label>
                        <input type="date" name="travel_date" required class="w-full px-4 py-2 border rounded focus:outline-none focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Number of Travelers</label>
                        <input type="number" name="travelers" min="1" required class="w-full px-4 py-2 border rounded focus:outline-none focus:border-primary">
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Special Requirements</label>
                        <textarea name="special_requirements" rows="3" class="w-full px-4 py-2 border rounded focus:outline-none focus:border-primary"></textarea>
                    </div>

                    <button type="submit" class="w-full bg-primary text-white py-3 rounded hover:bg-secondary transition duration-300">
                        Proceed to Payment
                    </button>
                </form>
            </div>

            <!-- Order Summary -->
            <div class="bg-white rounded-lg shadow-lg p-6 h-fit">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Order Summary</h2>
                <div class="space-y-4">
                    <div class="flex items-center space-x-4">
                        <img src="<?php echo $package['image']; ?>" alt="<?php echo $package['title']; ?>" class="w-24 h-24 object-cover rounded">
                        <div>
                            <h3 class="font-bold text-gray-800"><?php echo $package['title']; ?></h3>
                            <p class="text-gray-600"><?php echo $package['duration']; ?></p>
                        </div>
                    </div>
                    <div class="border-t pt-4">
                        <div class="flex justify-between mb-2">
                            <span class="text-gray-600">Package Price</span>
                            <span class="font-bold"><?php echo $package['price']; ?></span>
                        </div>
                        <div class="flex justify-between mb-2">
                            <span class="text-gray-600">Taxes & Fees</span>
                            <span class="font-bold">₹2,999</span>
                        </div>
                        <div class="border-t pt-2 mt-2">
                            <div class="flex justify-between">
                                <span class="font-bold">Total</span>
                                <span class="font-bold text-primary">₹27,998</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>
</body>
</html>