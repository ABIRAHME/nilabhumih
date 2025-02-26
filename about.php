<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Nilabhoomi Tours and Travels</title>
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
            <h1 class="text-4xl font-bold text-white mb-4">About Nilabhoomi</h1>
            <p class="text-xl text-white/80">Your Journey Begins With Us</p>
        </div>
    </div>

    <!-- About Content -->
    <div class="container mx-auto px-4 py-16">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
            <div class="space-y-6">
                <h2 class="text-3xl font-bold text-gray-800">Our Story</h2>
                <p class="text-gray-600">Founded in 2010, Nilabhoomi Tours and Travels has been at the forefront of creating unforgettable travel experiences. We believe in turning your travel dreams into reality with our expertly curated packages and personalized service.</p>
                <p class="text-gray-600">Our team of experienced travel professionals works tirelessly to ensure that every journey with us is smooth, enjoyable, and memorable. From handpicking the best accommodations to designing perfect itineraries, we take care of every detail.</p>
            </div>
            <div class="bg-white p-8 rounded-lg shadow-lg">
                <h3 class="text-2xl font-bold text-gray-800 mb-6">Why Choose Us</h3>
                <ul class="space-y-4">
                    <li class="flex items-start space-x-3">
                        <svg class="w-6 h-6 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-gray-600">Expert travel guides with years of experience</span>
                    </li>
                    <li class="flex items-start space-x-3">
                        <svg class="w-6 h-6 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-gray-600">Customized travel packages for every budget</span>
                    </li>
                    <li class="flex items-start space-x-3">
                        <svg class="w-6 h-6 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-gray-600">24/7 customer support throughout your journey</span>
                    </li>
                    <li class="flex items-start space-x-3">
                        <svg class="w-6 h-6 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-gray-600">Best price guarantee on all packages</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Team Section -->
        <div class="mt-20">
            <h2 class="text-3xl font-bold text-center text-gray-800 mb-12">Meet Our Team</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white p-6 rounded-lg shadow-lg text-center">
                    <div class="w-32 h-32 mx-auto mb-4 overflow-hidden rounded-full">
                        <img src="https://randomuser.me/api/portraits/men/1.jpg" alt="Team Member" class="w-full h-full object-cover">
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800">John Doe</h3>
                    <p class="text-gray-600">Founder & CEO</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-lg text-center">
                    <div class="w-32 h-32 mx-auto mb-4 overflow-hidden rounded-full">
                        <img src="https://randomuser.me/api/portraits/women/1.jpg" alt="Team Member" class="w-full h-full object-cover">
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800">Jane Smith</h3>
                    <p class="text-gray-600">Travel Expert</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-lg text-center">
                    <div class="w-32 h-32 mx-auto mb-4 overflow-hidden rounded-full">
                        <img src="https://randomuser.me/api/portraits/men/2.jpg" alt="Team Member" class="w-full h-full object-cover">
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800">Mike Johnson</h3>
                    <p class="text-gray-600">Customer Relations</p>
                </div>
            </div>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
</body>
</html>