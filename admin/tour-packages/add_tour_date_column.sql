-- Add tour_date column to tour_packages table
ALTER TABLE tour_packages ADD COLUMN tour_date DATE NULL AFTER price;

-- Update the SQL creation script to include the new column for future reference
-- This is the updated CREATE TABLE statement that includes the tour_date column
/*
CREATE TABLE IF NOT EXISTS tour_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    package_type ENUM('normal', 'educational', 'corporate') NOT NULL,
    title VARCHAR(255) NOT NULL,
    duration VARCHAR(100) NOT NULL,
    price VARCHAR(100) NOT NULL,
    tour_date DATE NULL,
    image VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    is_published TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_package_type (package_type),
    INDEX idx_is_published (is_published)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
*/