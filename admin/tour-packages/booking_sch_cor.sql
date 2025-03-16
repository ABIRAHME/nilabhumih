-- Create booking_sch_cor table for educational and corporate bookings
CREATE TABLE IF NOT EXISTS booking_sch_cor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    package_id INT NOT NULL,
    institution_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    travel_date DATE NOT NULL,
    participants INT NOT NULL,
    special_requirements TEXT,
    package_price DECIMAL(10, 2) NOT NULL,
    taxes_fees DECIMAL(10, 2) NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    transaction_id VARCHAR(255),
    payment_method VARCHAR(50),
    payment_date TIMESTAMP NULL,
    INDEX (package_id),
    INDEX (email),
    INDEX (payment_status),
    FOREIGN KEY (package_id) REFERENCES tour_packages(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;