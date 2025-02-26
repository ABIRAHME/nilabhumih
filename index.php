<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nilabhoomi Tours and Travels</title>
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
    <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 to-indigo-600 h-[600px]">
        <div class="container mx-auto px-4 h-full flex items-center">
            <div class="text-white max-w-2xl">
                <h1 class="text-5xl font-bold mb-6">Discover the World with Nilabhoomi</h1>
                <p class="text-xl mb-8">Experience unforgettable journeys and create lasting memories with our expertly curated travel packages.</p>
                <a href="tour-packages.php" class="bg-white text-indigo-600 px-8 py-3 rounded-full font-semibold hover:bg-indigo-50 transition duration-300">Explore Packages</a>
            </div>
        </div>
        <div class="absolute bottom-0 left-0 right-0 h-32 bg-gradient-to-t from-blue-50 to-transparent"></div>
    </div>

    <!-- Featured Destinations -->
    <div class="container mx-auto px-4 py-16">
        <h2 class="text-3xl font-bold text-center mb-12 text-gray-800">Popular Destinations</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Destination Cards will be added dynamically -->
        </div>
    </div>

    <!-- Why Choose Us -->
    <div class="bg-gradient-to-r from-indigo-600 to-blue-600 py-16">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12 text-white">Why Choose Nilabhoomi</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white/10 backdrop-blur-md p-6 rounded-lg text-white">
                    <h3 class="text-xl font-semibold mb-4">Expert Guides</h3>
                    <p>Professional and experienced guides to enhance your travel experience.</p>
                </div>
                <div class="bg-white/10 backdrop-blur-md p-6 rounded-lg text-white">
                    <h3 class="text-xl font-semibold mb-4">Best Prices</h3>
                    <p>Competitive prices and exclusive deals for unforgettable journeys.</p>
                </div>
                <div class="bg-white/10 backdrop-blur-md p-6 rounded-lg text-white">
                    <h3 class="text-xl font-semibold mb-4">24/7 Support</h3>
                    <p>Round-the-clock customer support for peace of mind during your travels.</p>
                </div>
            </div>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
</body>
</html>