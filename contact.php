<?php
session_start();
require_once 'includes/db-connection.php';

// Initialize database connection
$conn = getDbConnection();
if (!$conn) {
    die("Database connection failed. Please try again later.");
}

// Initialize variables for form data and error messages
$name = $email = $phone = $message = '';
$errors = [];
$success_message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    if (empty($_POST['name'])) {
        $errors[] = 'Name is required';
    } else {
        $name = htmlspecialchars(trim($_POST['name']));
    }
    
    if (empty($_POST['email'])) {
        $errors[] = 'Email is required';
    } else if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    } else {
        $email = htmlspecialchars(trim($_POST['email']));
    }
    
    if (empty($_POST['phone'])) {
        $errors[] = 'Phone number is required';
    } else {
        $phone = htmlspecialchars(trim($_POST['phone']));
    }
    
    if (empty($_POST['message'])) {
        $errors[] = 'Message is required';
    } else {
        $message = htmlspecialchars(trim($_POST['message']));
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        try {
            // Insert into enquiries table
            $sql = "INSERT INTO enquiries (name, email, phone, message, status) VALUES (?, ?, ?, ?, 'new')";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$name, $email, $phone, $message]);
            
            // Clear form data after successful submission
            $name = $email = $phone = $message = '';
            $success_message = 'Thank you for your message! We will get back to you soon.';
            
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Nilabhoomi Tours and Travels</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#008DDA',
                        secondary: '#41C9E2',
                        tertiary: '#ACE2E1',
                        cream: '#F7EEDD'
                    },
                   
                    boxShadow: {
                        'custom': '0 10px 25px -5px rgba(0, 141, 218, 0.1), 0 8px 10px -6px rgba(0, 141, 218, 0.1)'
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
   
</head>
<body class="bg-cream min-h-screen">
    <?php include 'partials/navigation.php'; ?>

    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-primary to-secondary py-16 md:py-24">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">Contact Us</h1>
            <p class="text-xl text-white/90 max-w-2xl mx-auto">Get in touch with our travel experts and start planning your dream vacation today</p>
        </div>
    </div>

    <!-- Contact Content -->
    <div class="container mx-auto px-4 py-12 md:py-20 -mt-10">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
            <!-- Contact Form -->
            <div class="lg:col-span-3 bg-white p-6 md:p-10 rounded-2xl shadow-custom">
                <h2 class="text-2xl md:text-3xl font-bold text-primary mb-6">Send us a Message</h2>
                
                <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r-md" role="alert">
                    <p class="font-bold">Please fix the following errors:</p>
                    <ul class="list-disc pl-5">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r-md" role="alert">
                    <p><?php echo $success_message; ?></p>
                </div>
                <?php endif; ?>
                
                <form class="space-y-6" method="POST" action="">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition duration-200" required>
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition duration-200" required>
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition duration-200" required>
                    </div>
                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                        <textarea id="message" name="message" rows="5" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition duration-200" required><?php echo htmlspecialchars($message); ?></textarea>
                    </div>
                    <button type="submit" class="w-full bg-primary text-white py-3 px-6 rounded-lg hover:bg-secondary transition duration-300 font-medium text-lg shadow-md hover:shadow-lg">Send Message</button>
                </form>
            </div>

            <!-- Contact Information -->
            <div class="lg:col-span-2 space-y-8">
                <div class="bg-white p-6 md:p-8 rounded-2xl shadow-custom">
                    <h2 class="text-2xl font-bold text-primary mb-6">Contact Information</h2>
                    <div class="space-y-6">
                        <div class="flex items-start space-x-4">
                            <div class="bg-tertiary p-3 rounded-full">
                                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">Address</h3>
                                <p class="text-gray-600">123 Travel Street<br>Kolkata, West Bengal</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-4">
                            <div class="bg-tertiary p-3 rounded-full">
                                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">Phone</h3>
                                <p class="text-gray-600">+91 123 456 7890</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-4">
                            <div class="bg-tertiary p-3 rounded-full">
                                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">Email</h3>
                                <p class="text-gray-600">info@nilabhoomi.com</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 md:p-8 rounded-2xl shadow-custom">
                    <h2 class="text-2xl font-bold text-primary mb-6">Business Hours</h2>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center pb-2 border-b border-tertiary">
                            <span class="text-gray-600">Monday - Friday</span>
                            <span class="text-gray-800 font-medium bg-tertiary/50 px-3 py-1 rounded-full">9:00 AM - 6:00 PM</span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b border-tertiary">
                            <span class="text-gray-600">Saturday</span>
                            <span class="text-gray-800 font-medium bg-tertiary/50 px-3 py-1 rounded-full">10:00 AM - 4:00 PM</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Sunday</span>
                            <span class="text-gray-800 font-medium bg-red-100 px-3 py-1 rounded-full">Closed</span>
                        </div>
                    </div>
                </div>
                
                
            </div>
        </div>
        
        <!-- Location Map Section -->
        <div class="mt-12 md:mt-16 bg-white p-6 md:p-8 rounded-2xl shadow-custom">
            <h2 class="text-2xl md:text-3xl font-bold text-primary mb-6">Find Us</h2>
            <div class="aspect-video bg-tertiary/30 rounded-lg flex items-center justify-center">
                <p class="text-gray-600 text-center">Map placeholder - Insert your Google Maps or other map integration here</p>
            </div>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
</body>
</html>