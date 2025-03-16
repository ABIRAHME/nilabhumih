<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Special Tour Checkout - Nilabhoomi Tours and Travels</title>
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
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-light to-accent min-h-screen">
    <?php include 'partials/navigation.php'; ?>

    <?php
    // Include database connection
    require_once 'includes/db-connection.php';
    
    // Initialize package variable
    $package = [
        'title' => 'Package Not Found',
        'duration' => '',
        'price' => '',
        'image' => 'images/demo.jpeg',
        'description' => 'The requested package could not be found.',
        'type' => 'educational',
        'package_type' => 'educational'
    ];

    // Get package ID from URL
    $package_id = isset($_GET['package']) ? (int)$_GET['package'] : 0;
    
    // Get database connection
    $conn = getDbConnection();
    
    if ($conn && $package_id > 0) {
        try {
            // Fetch package details
            $stmt = $conn->prepare("SELECT * FROM tour_packages WHERE id = :id AND is_published = 1");
            $stmt->bindParam(':id', $package_id);
            $stmt->execute();
            
            $packageData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($packageData) {
                // Set basic package data
                $package['title'] = $packageData['title'];
                $package['duration'] = $packageData['duration'];
                $package['price'] = $packageData['price'];
                $package['description'] = $packageData['description'];
                $package['type'] = $packageData['package_type'];
                $package['package_type'] = $packageData['package_type'];
                
                // Set image path with fallback
                if (!empty($packageData['image'])) {
                    $package['image'] = $packageData['image'];
                    if (strpos($package['image'], 'images/') !== 0) {
                        $package['image'] = 'images/' . $package['image'];
                    }
                }
            }
        } catch (PDOException $e) {
            // Log error
            error_log("Error fetching package details: " . $e->getMessage());
        }
    }
    ?>

    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <h1 class="text-3xl font-bold text-primary mb-8 text-center">Complete Your Booking</h1>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Order Summary -->
            <div class="lg:col-span-1 order-2 lg:order-1">
                <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-4 transform transition-all duration-300 hover:shadow-xl">
                    <h2 class="text-2xl font-bold text-primary mb-6 flex items-center">
                        <i class="fas fa-receipt mr-2"></i> Order Summary
                    </h2>
                    
                    <div class="space-y-6">
                        <div class="flex items-center space-x-4 bg-light rounded-lg p-3">
                            <img src="<?php echo $package['image']; ?>" alt="<?php echo $package['title']; ?>" class="w-24 h-24 object-cover rounded-lg shadow">
                            <div>
                                <h3 class="font-bold text-gray-800 text-lg"><?php echo $package['title']; ?></h3>
                                <p class="text-gray-600">
                                    <i class="far fa-clock mr-1"></i>
                                    <?php echo $package['duration']; ?>
                                </p>
                                <p class="text-gray-600">
                                    <i class="far fa-bookmark mr-1"></i>
                                    Type: <?php echo ucfirst($package['type']); ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="border-t border-gray-200 pt-4" id="orderSummary">
                            <div class="flex justify-between mb-3">
                                <span class="text-gray-600">Base Price (per person)</span>
                                <span class="font-bold"><?php echo $package['price']; ?></span>
                            </div>
                            <div class="flex justify-between mb-3">
                                <span class="text-gray-600">Number of Travelers</span>
                                <span class="font-bold" id="travelerCount">1</span>
                            </div>
                            <div class="flex justify-between mb-3">
                                <span class="text-gray-600">Subtotal</span>
                                <span class="font-bold" id="subtotal"><?php echo $package['price']; ?></span>
                            </div>
                            <div class="flex justify-between mb-3 text-green-600" id="discountRow" style="display: none;">
                                <span class="flex items-center">
                                    <i class="fas fa-tag mr-1"></i> Group Discount (7%)
                                </span>
                                <span class="font-bold" id="discount">-0</span>
                            </div>
                            <div class="flex justify-between mb-3">
                                <span class="text-gray-600">Taxes & Fees (5%)</span>
                                <span class="font-bold" id="taxes">0</span>
                            </div>
                            
                            <div class="border-t border-gray-200 pt-4 mt-4">
                                <div class="flex justify-between">
                                    <span class="font-bold text-lg">Total Amount</span>
                                    <span class="font-bold text-lg text-primary" id="totalAmount"><?php echo $package['price']; ?></span>
                                </div>
                                <div class="flex justify-between mt-3 p-3 bg-secondary bg-opacity-10 rounded-lg" id="partialPaymentRow" style="display: none;">
                                    <span class="font-bold">Advance Payment (30%)</span>
                                    <span class="font-bold text-secondary" id="partialAmount">0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking Form -->
            <div class="lg:col-span-2 order-1 lg:order-2">
                <div class="bg-white rounded-2xl shadow-lg p-6 transform transition-all duration-300 hover:shadow-xl">
                    <h2 class="text-2xl font-bold text-primary mb-6 flex items-center">
                        <i class="fas fa-clipboard-list mr-2"></i> Booking Information
                    </h2>
                    
                    <form action="process_booking.php" method="POST" class="space-y-5" id="bookingForm">
                        <input type="hidden" name="package_id" value="<?php echo $package_id; ?>">
                        <input type="hidden" name="package_type" value="<?php echo $package['type']; ?>">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-gray-700 mb-2 font-medium">
                                    <i class="fas fa-building mr-1"></i> Institute/Company Name
                                </label>
                                <input type="text" name="institute_name" required 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                            </div>

                            <div>
                                <label class="block text-gray-700 mb-2 font-medium">
                                    <i class="fas fa-envelope mr-1"></i> Email
                                </label>
                                <input type="email" name="email" required 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-gray-700 mb-2 font-medium">
                                    <i class="fas fa-phone mr-1"></i> Phone
                                </label>
                                <input type="tel" name="phone" required 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                            </div>

                            <div>
                                <label class="block text-gray-700 mb-2 font-medium">
                                    <i class="fas fa-calendar mr-1"></i> Travel Date
                                </label>
                                <input type="date" name="travel_date" required 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                            </div>
                        </div>

                        <div>
                            <label class="block text-gray-700 mb-2 font-medium">
                                <i class="fas fa-users mr-1"></i> Number of Travelers
                            </label>
                            <input type="number" name="travelers" min="1" max="2000" required 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                   id="travelersInput">
                        </div>

                        <div>
                            <label class="block text-gray-700 mb-2 font-medium">
                                <i class="fas fa-comment-dots mr-1"></i> Special Requirements
                            </label>
                            <textarea name="special_requirements" rows="3" 
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"></textarea>
                        </div>

                        <div class="p-4 bg-light rounded-lg">
                            <div class="flex items-center space-x-3">
                                <input type="checkbox" id="partialPayment" name="partial_payment" 
                                       class="w-5 h-5 text-primary rounded focus:ring-primary">
                                <label for="partialPayment" class="text-gray-700 font-medium">
                                    Pay 30% Advance Now
                                </label>
                            </div>
                            <p class="text-sm text-gray-500 mt-2 ml-8">
                                Pay just 30% to reserve your spot and the rest later
                            </p>
                        </div>

                        <button type="submit" 
                                class="w-full bg-primary text-white py-4 rounded-lg hover:bg-secondary transition duration-300 font-bold text-lg shadow-lg flex items-center justify-center">
                            <span id="paymentText">Proceed to Full Payment</span>
                            <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const travelersInput = document.getElementById('travelersInput');
            const partialPayment = document.getElementById('partialPayment');
            
            // Safely parse the price value, ensuring it's a valid number
            let basePrice = 0;
            try {
                // Get the price string and clean it (remove any non-numeric characters except decimal point)
                const priceStr = '<?php echo $package["price"]; ?>'.trim();
                // Extract only numbers and decimal point
                const numericPrice = priceStr.replace(/[^0-9.]/g, '');
                basePrice = parseFloat(numericPrice) || 0;
            } catch (e) {
                console.error('Error parsing price:', e);
                basePrice = 0;
            }

            function updateOrderSummary() {
                // Ensure travelers is a valid number
                const travelers = parseInt(travelersInput.value) || 1;
                const subtotal = basePrice * travelers;
                let discount = 0;

                // Apply 7% discount for groups of 100 or more
                if (travelers >= 100) {
                    discount = subtotal * 0.07;
                    document.getElementById('discountRow').style.display = 'flex';
                } else {
                    document.getElementById('discountRow').style.display = 'none';
                }

                const subtotalAfterDiscount = subtotal - discount;
                const taxes = subtotalAfterDiscount * 0.05;
                const total = subtotalAfterDiscount + taxes;
                const partialAmount = total * 0.3;

                // Update the DOM elements with formatted values
                document.getElementById('travelerCount').textContent = travelers;
                document.getElementById('subtotal').textContent = `Tk ${subtotal.toFixed(2)}`;
                document.getElementById('discount').textContent = `-Tk ${discount.toFixed(2)}`;
                document.getElementById('taxes').textContent = `Tk ${taxes.toFixed(2)}`;
                document.getElementById('totalAmount').textContent = `Tk ${total.toFixed(2)}`;
                document.getElementById('partialAmount').textContent = `Tk ${partialAmount.toFixed(2)}`;
            }

            function updatePaymentText() {
                const paymentText = document.getElementById('paymentText');
                const partialPaymentRow = document.getElementById('partialPaymentRow');

                if (partialPayment && partialPayment.checked) {
                    paymentText.textContent = 'Proceed with 30% Payment';
                    partialPaymentRow.style.display = 'flex';
                } else {
                    paymentText.textContent = 'Proceed to Full Payment';
                    partialPaymentRow.style.display = 'none';
                }
            }

            // Add event listeners only if elements exist
            if (travelersInput) {
                travelersInput.addEventListener('input', updateOrderSummary);
            }
            
            if (partialPayment) {
                partialPayment.addEventListener('change', updatePaymentText);
            }

            // Initial calculations
            updateOrderSummary();
            updatePaymentText();
        });
    </script>
</body>
</html>