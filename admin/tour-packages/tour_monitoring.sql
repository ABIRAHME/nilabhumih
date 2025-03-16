CREATE TABLE IF NOT EXISTS tour_monitoring (
  id INT AUTO_INCREMENT PRIMARY KEY,
  package_id INT NOT NULL,
  monitoring_date DATE NOT NULL,
  status ENUM('active', 'completed') NOT NULL DEFAULT 'active',
  total_meals INT NOT NULL DEFAULT 0,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (package_id),
  INDEX (status)
);