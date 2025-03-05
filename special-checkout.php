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
            'type' => 'school',
            'title' => 'Historical Heritage Tour',
            'duration' => '3 Days / 2 Nights',
            'price' => '100',
            'image' => 'images/demo.jpeg',
        ],
        // Add other packages here
    ];

    $package_id = isset($_GET['package']) ? (int)$_GET['package'] : 0;
    $package = isset($packages[$package_id]) ? $packages[$package_id] : $packages[0];
    ?>

    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Booking Form -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Booking Information</h2>
                <form action="process_booking.php" method="POST" class="space-y-4" id="bookingForm">
                    <input type="hidden" name="package_id" value="<?php echo $package_id; ?>">
                    <input type="hidden" name="package_type" value="<?php echo $package['type']; ?>">
                    
                    <div>
                        <label class="block text-gray-700 mb-2">Institute/Company Name</label>
                        <input type="text" name="institute_name" required class="w-full px-4 py-2 border rounded focus:outline-none focus:border-primary">
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
                        <input type="number" name="travelers" min="1" required 
                               class="w-full px-4 py-2 border rounded focus:outline-none focus:border-primary"
                               id="travelersInput">
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Special Requirements</label>
                        <textarea name="special_requirements" rows="3" class="w-full px-4 py-2 border rounded focus:outline-none focus:border-primary"></textarea>
                    </div>

                    <div class="flex items-center space-x-2">
                        <input type="checkbox" id="partialPayment" name="partial_payment" class="w-4 h-4 text-primary">
                        <label for="partialPayment" class="text-gray-700">Pay 30% Advance</label>
                    </div>

                    <button type="submit" class="w-full bg-primary text-white py-3 rounded hover:bg-secondary transition duration-300">
                        <span id="paymentText">Proceed to Full Payment</span>
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
                            <p class="text-gray-600">Type: <?php echo ucfirst($package['type']); ?></p>
                        </div>
                    </div>
                    <div class="border-t pt-4" id="orderSummary">
                        <div class="flex justify-between mb-2">
                            <span class="text-gray-600">Base Price (per person)</span>
                            <span class="font-bold">₹<?php echo $package['price']; ?></span>
                        </div>
                        <div class="flex justify-between mb-2">
                            <span class="text-gray-600">Number of Travelers</span>
                            <span class="font-bold" id="travelerCount">1</span>
                        </div>
                        <div class="flex justify-between mb-2">
                            <span class="text-gray-600">Subtotal</span>
                            <span class="font-bold" id="subtotal">₹<?php echo $package['price']; ?></span>
                        </div>
                        <div class="flex justify-between mb-2" id="discountRow" style="display: none;">
                            <span class="text-gray-600">Group Discount (7%)</span>
                            <span class="font-bold text-green-600" id="discount">-₹0</span>
                        </div>
                        <div class="flex justify-between mb-2">
                            <span class="text-gray-600">Taxes & Fees (5%)</span>
                            <span class="font-bold" id="taxes">₹0</span>
                        </div>
                        <div class="border-t pt-2 mt-2">
                            <div class="flex justify-between">
                                <span class="font-bold">Total Amount</span>
                                <span class="font-bold text-primary" id="totalAmount">₹<?php echo $package['price']; ?></span>
                            </div>
                            <div class="flex justify-between mt-2" id="partialPaymentRow" style="display: none;">
                                <span class="font-bold">Advance Payment (30%)</span>
                                <span class="font-bold text-secondary" id="partialAmount">₹0</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>

    <script>
        const travelersInput = document.getElementById('travelersInput');
        const partialPayment = document.getElementById('partialPayment');
        const basePrice = <?php echo $package['price']; ?>;

        function updateOrderSummary() {
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

            document.getElementById('travelerCount').textContent = travelers;
            document.getElementById('subtotal').textContent = `₹${subtotal.toFixed(2)}`;
            document.getElementById('discount').textContent = `-₹${discount.toFixed(2)}`;
            document.getElementById('taxes').textContent = `₹${taxes.toFixed(2)}`;
            document.getElementById('totalAmount').textContent = `₹${total.toFixed(2)}`;
            document.getElementById('partialAmount').textContent = `₹${partialAmount.toFixed(2)}`;
        }

        function updatePaymentText() {
            const paymentText = document.getElementById('paymentText');
            const partialPaymentRow = document.getElementById('partialPaymentRow');

            if (partialPayment.checked) {
                paymentText.textContent = 'Proceed with 30% Payment';
                partialPaymentRow.style.display = 'flex';
            } else {
                paymentText.textContent = 'Proceed to Full Payment';
                partialPaymentRow.style.display = 'none';
            }
        }

        travelersInput.addEventListener('input', updateOrderSummary);
        partialPayment.addEventListener('change', updatePaymentText);

        // Initial calculations
        updateOrderSummary();
        updatePaymentText();
    </script>
</body>
</html>