CREATE TABLE IF NOT EXISTS customer_attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  monitoring_id INT NOT NULL,
  customer_id INT NOT NULL,
  attended BOOLEAN NOT NULL DEFAULT 0,
  meals_taken INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (monitoring_id),
  INDEX (customer_id)
);