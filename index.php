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
                        primary: '#008DDA',
                        secondary: '#41C9E2',
                        accent: '#ACE2E1',
                        light: '#F7EEDD'
                    }
                }
            }
        }
    </script>
    <style>
        .hero-gradient {
            background: linear-gradient(to right, rgba(0, 141, 218, 0.85), rgba(65, 201, 226, 0.85));
        }
        .fade-in {
            animation: fadeIn 1.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    
</head>
<body class="bg-light">
    <!-- Navigation placeholder #008DDA
#41C9E2
#ACE2E1
#F7EEDD 

make this page modern use this page color palette "#008DDA
#41C9E2
#ACE2E1
#F7EEDD" use php and tailwind css make it responsive don't change php code -->
    <?php include 'partials/navigation.php'; ?>
    
    <!-- Hero Section -->
    <div class="relative h-[600px] bg-cover bg-center" style="background-image: url('/api/placeholder/1200/600');">
        <div class="absolute inset-0 hero-gradient"></div>
        <div class="container mx-auto px-4 h-full flex items-center relative z-10">
            <div class="text-white max-w-2xl fade-in">
                <h1 class="text-5xl font-bold mb-6 leading-tight">Discover the World with Nilabhoomi</h1>
                <p class="text-xl mb-8 text-white leading-relaxed">Experience unforgettable journeys and create lasting memories with our expertly curated travel packages.</p>
                <a href="tour-packages.php" class="inline-block bg-light text-primary px-8 py-3 rounded-full font-semibold hover:bg-white transition duration-300 transform hover:scale-105 shadow-lg">Explore Packages</a>
            </div>
        </div>
        <div class="absolute bottom-0 left-0 right-0 h-32 bg-gradient-to-t from-light to-transparent"></div>
    </div>
    
    <!-- Recent Tours -->
    <div class="container mx-auto px-4 py-16">
        <h2 class="text-3xl font-bold text-center mb-12 text-primary">Popular Destinations</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Tour Card 1 -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden transform transition duration-300 hover:scale-105 hover:shadow-xl">
                <img src="images/demo.jpeg" alt="Beach destination" class="w-full h-48 object-cover">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-3">
                        <span class="bg-accent text-primary text-sm px-3 py-1 rounded-full">Beach</span>
                        <span class="text-gray-600">7 days</span>
                    </div>
                    <h3 class="text-xl font-semibold mb-2 text-gray-800">Maldives Paradise</h3>
                    <p class="text-gray-600 mb-4">Experience the crystal clear waters and pristine beaches of the Maldives.</p>
                    <div class="flex justify-between items-center">
                        <span class="text-primary font-bold">1,299Tk</span>
                        <a href="#" class="text-secondary hover:underline">View Details</a>
                    </div>
                </div>
            </div>
            
            <!-- Tour Card 2 -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden transform transition duration-300 hover:scale-105 hover:shadow-xl">
                <img src="images/demo.jpeg" alt="Mountain destination" class="w-full h-48 object-cover">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-3">
                        <span class="bg-accent text-primary text-sm px-3 py-1 rounded-full">Mountain</span>
                        <span class="text-gray-600">5 days</span>
                    </div>
                    <h3 class="text-xl font-semibold mb-2 text-gray-800">Swiss Alps Adventure</h3>
                    <p class="text-gray-600 mb-4">Trek through breathtaking mountain ranges and experience alpine beauty.</p>
                    <div class="flex justify-between items-center">
                        <span class="text-primary font-bold">949Tk</span>
                        <a href="#" class="text-secondary hover:underline">View Details</a>
                    </div>
                </div>
            </div>
            
            <!-- Tour Card 3 -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden transform transition duration-300 hover:scale-105 hover:shadow-xl">
                <img src="images/demo.jpeg" alt="City destination" class="w-full h-48 object-cover">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-3">
                        <span class="bg-accent text-primary text-sm px-3 py-1 rounded-full">City</span>
                        <span class="text-gray-600">4 days</span>
                    </div>
                    <h3 class="text-xl font-semibold mb-2 text-gray-800">Tokyo Exploration</h3>
                    <p class="text-gray-600 mb-4">Immerse yourself in the vibrant culture and modern wonders of Tokyo.</p>
                    <div class="flex justify-between items-center">
                        <span class="text-primary font-bold">899Tk</span>
                        <a href="#" class="text-secondary hover:underline">View Details</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Why Choose Us -->
    <div class="bg-primary py-16">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12 text-light">Why Choose Nilabhoomi</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white/10 backdrop-blur-md p-6 rounded-lg shadow-lg text-light border border-accent/20">
                    <div class="flex items-center mb-4">
                        <div class="bg-accent/20 p-3 rounded-full mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-light" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold">Expert Guides</h3>
                    </div>
                    <p>Professional and experienced guides to enhance your travel experience with local knowledge and insights.</p>
                </div>
                
                <div class="bg-white/10 backdrop-blur-md p-6 rounded-lg shadow-lg text-light border border-accent/20">
                    <div class="flex items-center mb-4">
                        <div class="bg-accent/20 p-3 rounded-full mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-light" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold">Best Prices</h3>
                    </div>
                    <p>Competitive prices and exclusive deals for unforgettable journeys that provide excellent value for your travel budget.</p>
                </div>
                
                <div class="bg-white/10 backdrop-blur-md p-6 rounded-lg shadow-lg text-light border border-accent/20">
                    <div class="flex items-center mb-4">
                        <div class="bg-accent/20 p-3 rounded-full mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-light" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold">24/7 Support</h3>
                    </div>
                    <p>Round-the-clock customer support for peace of mind during your travels, ensuring assistance whenever you need it.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Testimonials -->
    <div class="container mx-auto px-4 py-16">
        <h2 class="text-3xl font-bold text-center mb-12 text-primary">What Our Travelers Say</h2>
        <div class="flex flex-wrap justify-center gap-6">
            <div class="bg-white p-6 rounded-xl shadow-lg max-w-md">
                <div class="flex items-center mb-4">
                 <!--   <img src="/api/placeholder/60/60" alt="Customer" class="w-12 h-12 rounded-full object-cover mr-4"> -->
                    <div>
                        <h4 class="font-semibold text-gray-800">Sarah Johnson</h4>
                        <div class="flex text-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </div>
                    </div>
                </div>
                <p class="text-gray-600 mb-2">"Our trip to Bali with Nilabhoomi was simply magical. The attention to detail and personalized service made all the difference. Will definitely book with them again!"</p>
                <p class="text-accent text-sm font-medium">Bali Adventure Tour, June 2024</p>
            </div>
            
            <div class="bg-white p-6 rounded-xl shadow-lg max-w-md">
                <div class="flex items-center mb-4">
                <!--    <img src="/api/placeholder/60/60" alt="Customer" class="w-12 h-12 rounded-full object-cover mr-4"> -->
                    <div>
                        <h4 class="font-semibold text-gray-800">Michael Chen</h4>
                        <div class="flex text-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </div>
                    </div>
                </div>
                <p class="text-gray-600 mb-2">"From the moment we booked until our return, everything was handled perfectly. Our guide was knowledgeable and the accommodations were superb. Highly recommend!"</p>
                <p class="text-accent text-sm font-medium">European Heritage Tour, August 2024</p>
            </div>
        </div>
    </div>
    
    <!-- Newsletter -->
    <div class="bg-accent/30 py-12">
        <div class="container mx-auto px-4">
            <div class="max-w-xl mx-auto text-center">
                <h3 class="text-2xl font-bold mb-3 text-primary">Get Travel Inspiration</h3>
                <p class="text-gray-700 mb-6">Subscribe to our newsletter and receive exclusive offers and travel tips.</p>
                <div class="flex flex-col sm:flex-row gap-2">
                    <input type="email" placeholder="Your email address" class="flex-1 px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                    <button class="bg-primary text-white px-6 py-3 rounded-lg font-medium hover:bg-secondary transition">Subscribe</button>
                </div>
            </div>
        </div>
    </div>
    
   
    <?php include 'partials/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
</body>
</html>