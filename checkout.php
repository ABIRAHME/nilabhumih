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
                        brand: {
                            blue: '#008DDA',
                            teal: '#41C9E2',
                            light: '#ACE2E1',
                            cream: '#F7EEDD',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    boxShadow: {
                        'custom': '0 4px 20px rgba(0, 141, 218, 0.1)',
                    },
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gradient-to-r from-brand-cream to-brand-light min-h-screen">
    <?php include 'partials/navigation.php'; ?>

    <?php
    // Include database connection
    require_once 'includes/db-connection.php';
    
   
    
    // Get package ID from URL
    $package_id = isset($_GET['package']) ? (int)$_GET['package'] : 0;
    
    // If package ID is provided, fetch from database
    if ($package_id > 0) {
        try {
            $conn = getDbConnection();
            if ($conn) {
                $stmt = $conn->prepare("SELECT * FROM tour_packages WHERE id = :id");
                $stmt->bindParam(':id', $package_id, PDO::PARAM_INT);
                $stmt->execute();
                
                if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $package = [
                        'title' => $row['title'],
                        'duration' => $row['duration'],
                        'price' => $row['price'],
                        'date' => $row['tour_date'],
                        'image' => $row['image'] ?: 'images/demo.jpeg',
                    ];
                }
            }
        } catch (PDOException $e) {
            // Log error but continue with default package
            error_log("Error fetching package: " . $e->getMessage());
        }
    }
    
    // Calculate taxes and total
    $base_price = $package['price'];
    $travelers = isset($_POST['travelers']) ? (int)$_POST['travelers'] : 1;
    $subtotal = $base_price * $travelers;
    $taxes_fees = round($subtotal * 0.05, 2); // 5% taxes and fees
    $total_amount = $subtotal + $taxes_fees;
    ?>

    <div class="container mx-auto px-4 py-12">
        <h1 class="text-3xl md:text-4xl font-bold text-brand-blue text-center mb-10">Complete Your Booking</h1>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Order Summary (moved to left on desktop) -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-custom p-6 sticky top-6">
                    <h2 class="text-2xl font-bold text-brand-blue mb-6 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        Trip Summary
                    </h2>
                    <div class="space-y-6">
                        <div class="bg-brand-cream rounded-xl p-4">
                            <div class="flex flex-col space-y-4">
                                <img src="<?php echo $package['image']; ?>" alt="<?php echo $package['title']; ?>" class="w-full h-48 object-cover rounded-lg">
                                <div>
                                    <h3 class="font-bold text-xl text-brand-blue"><?php echo $package['title']; ?></h3>
                                    <div class="flex items-center text-gray-600 mt-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-brand-teal" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <p><?php echo $package['date']; ?></p>
                                    </div>
                                    <div class="flex items-center text-gray-600 mt-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-brand-teal" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <p><?php echo $package['duration']; ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="border-t border-dashed border-brand-light pt-4">
                            <div class="flex justify-between mb-3">
                                <span class="text-gray-600">Package Price (<span id="travelerCount">1</span> traveler<span id="travelerPlural"></span>)</span>
                                <span class="font-medium">Tk <span id="subtotalDisplay"><?php echo number_format($subtotal, 2); ?></span></span>
                            </div>
                            <div class="flex justify-between mb-3">
                                <span class="text-gray-600">Taxes & Fees (5%)</span>
                                <span class="font-medium">Tk <span id="taxesDisplay"><?php echo number_format($taxes_fees, 2); ?></span></span>
                            </div>
                            <div class="border-t border-brand-light pt-3 mt-3">
                                <div class="flex justify-between">
                                    <span class="font-bold text-lg">Total</span>
                                    <span class="font-bold text-lg text-brand-blue">Tk <span id="totalDisplay"><?php echo number_format($total_amount, 2); ?></span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Booking Form (moved to right on desktop) -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-custom p-6">
                    <h2 class="text-2xl font-bold text-brand-blue mb-6 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Your Information
                    </h2>
                    <form action="process_payment.php" method="POST" class="space-y-6" id="bookingForm">
                        <input type="hidden" name="package_id" value="<?php echo $package_id; ?>">
                        <input type="hidden" name="package_price" value="<?php echo $base_price; ?>" id="basePrice">
                        <input type="hidden" name="taxes_fees" value="<?php echo $taxes_fees; ?>" id="taxesFees">
                        <input type="hidden" name="total_amount" value="<?php echo $total_amount; ?>" id="totalAmount">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-gray-700 text-sm font-medium mb-2">First Name</label>
                                <input type="text" name="first_name" required class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-teal focus:border-transparent transition">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-medium mb-2">Last Name</label>
                                <input type="text" name="last_name" required class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-teal focus:border-transparent transition">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-gray-700 text-sm font-medium mb-2">Email</label>
                                <input type="email" name="email" required class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-teal focus:border-transparent transition">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-medium mb-2">Phone</label>
                                <input type="tel" name="phone" required class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-teal focus:border-transparent transition">
                            </div>
                        </div>

                        <input type="date" name="travel_date" value="<?php echo $package['date'];?>" required class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-teal focus:border-transparent transition" hidden>

                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Number of Travelers</label>
                            <div class="flex items-center">
                                <button type="button" id="decreaseTravelers" class="bg-brand-cream text-brand-blue rounded-l-lg px-4 py-3 hover:bg-brand-light transition">-</button>
                                <input type="number" name="travelers" id="travelers" min="1" max="5" value="1" required class="w-16 text-center py-3 border-t border-b border-gray-200 focus:outline-none">
                                <button type="button" id="increaseTravelers" class="bg-brand-cream text-brand-blue rounded-r-lg px-4 py-3 hover:bg-brand-light transition">+</button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Special Requirements</label>
                            <textarea name="special_requirements" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-teal focus:border-transparent transition"></textarea>
                        </div>

                        <button type="submit" class="w-full bg-brand-blue text-white font-medium py-4 rounded-lg hover:bg-brand-teal transition duration-300 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                            Proceed to Payment
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Get DOM elements
        const travelersInput = document.getElementById('travelers');
        const decreaseTravelers = document.getElementById('decreaseTravelers');
        const increaseTravelers = document.getElementById('increaseTravelers');
        const basePrice = parseFloat(document.getElementById('basePrice').value);
        const taxesFeesInput = document.getElementById('taxesFees');
        const totalAmountInput = document.getElementById('totalAmount');
        
        // Display elements
        const travelerCount = document.getElementById('travelerCount');
        const travelerPlural = document.getElementById('travelerPlural');
        const subtotalDisplay = document.getElementById('subtotalDisplay');
        const taxesDisplay = document.getElementById('taxesDisplay');
        const totalDisplay = document.getElementById('totalDisplay');
        
        // Update prices when number of travelers changes
        travelersInput.addEventListener('change', updatePrices);
        
        // Add event listeners for + and - buttons
        decreaseTravelers.addEventListener('click', () => {
            if (travelersInput.value > 1) {
                travelersInput.value = parseInt(travelersInput.value) - 1;
                updatePrices();
            }
        });
        
        increaseTravelers.addEventListener('click', () => {
            if (travelersInput.value < 5) {
                travelersInput.value = parseInt(travelersInput.value) + 1;
                updatePrices();
            }
        });
        
        function updatePrices() {
            const travelers = parseInt(travelersInput.value) || 1;
            
            // Update traveler count display
            travelerCount.textContent = travelers;
            travelerPlural.textContent = travelers > 1 ? 's' : '';
            
            // Calculate new prices
            const subtotal = basePrice * travelers;
            const taxesFees = subtotal * 0.05; // 5% taxes and fees
            const totalAmount = subtotal + taxesFees;
            
            // Update hidden form fields
            taxesFeesInput.value = taxesFees.toFixed(2);
            totalAmountInput.value = totalAmount.toFixed(2);
            
            // Update display
            subtotalDisplay.textContent = subtotal.toFixed(2);
            taxesDisplay.textContent = taxesFees.toFixed(2);
            totalDisplay.textContent = totalAmount.toFixed(2);
        }
        
        // Initialize prices
        updatePrices();
    </script>

    <?php include 'partials/footer.php'; ?>
</body>
</html>