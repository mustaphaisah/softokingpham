-- Pharmacy Inventory and Sales Management System SQL Setup
CREATE DATABASE IF NOT EXISTS pharmacy_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pharmacy_system;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'cashier') NOT NULL DEFAULT 'cashier',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS suppliers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  contact VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS medicines (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  category VARCHAR(150) NOT NULL,
  quantity INT NOT NULL DEFAULT 0,
  cost_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  expiry_date DATE NOT NULL,
  min_stock INT NOT NULL DEFAULT 0,
  supplier_id INT,
  FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total_profit DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  payment_method VARCHAR(50) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sale_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sale_id INT NOT NULL,
  medicine_id INT NOT NULL,
  quantity INT NOT NULL,
  cost_price DECIMAL(10,2) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
  FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS stock_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  medicine_id INT NOT NULL,
  change_type ENUM('IN', 'OUT') NOT NULL,
  quantity INT NOT NULL,
  date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO users (username, password, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('cashier', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cashier');

INSERT INTO suppliers (name, contact) VALUES
('PharmaCorp', 'contact@pharmacorp.com'),
('MediSupply', 'info@medisupply.com');

INSERT INTO medicines (name, category, quantity, cost_price, price, expiry_date, min_stock, supplier_id) VALUES
('Paracetamol', 'Pain Relief', 120, 1.50, 2.50, DATE_ADD(CURDATE(), INTERVAL 120 DAY), 20, 1),
('Azithromycin', 'Antibiotic', 40, 4.00, 7.20, DATE_ADD(CURDATE(), INTERVAL 25 DAY), 15, 1),
('Vitamin C', 'Supplements', 80, 3.00, 5.99, DATE_ADD(CURDATE(), INTERVAL 180 DAY), 20, 2),
('Cough Syrup', 'Respiratory', 25, 4.00, 6.75, DATE_ADD(CURDATE(), INTERVAL 14 DAY), 10, 2),
('Insulin', 'Diabetes', 10, 15.00, 25.00, DATE_ADD(CURDATE(), INTERVAL 60 DAY), 5, 1);
