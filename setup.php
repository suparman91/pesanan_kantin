<?php
// setup.php - buat tabel awal dan admin default
require 'config.php';

$queries = [];
$queries[] = "CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(30) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$queries[] = "CREATE TABLE IF NOT EXISTS suppliers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  contact VARCHAR(150),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
";

$queries[] = "CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  supplier_id INT DEFAULT NULL,
  item VARCHAR(255) NOT NULL,
  quantity INT DEFAULT 1,
  total_price DECIMAL(10,2) DEFAULT 0,
  status VARCHAR(50) DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
)
";

foreach ($queries as $q) {
    if (!$conn->query($q)) {
        echo "Error: " . $conn->error . "\n";
    }
}

// insert default admin jika belum ada
$email = 'admin@kantin.local';
$check = $conn->prepare('SELECT id FROM users WHERE email=?');
$check->bind_param('s', $email);
$check->execute();
$check->store_result();
if ($check->num_rows === 0) {
    $pass = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)');
    $role = 'admin';
    $name = 'Administrator';
    $stmt->bind_param('ssss', $name, $email, $pass, $role);
    $stmt->execute();
    echo "Admin user created: $email / password: admin123\n";
} else {
    echo "Admin already exists.\n";
}

echo "Setup complete.\n";
