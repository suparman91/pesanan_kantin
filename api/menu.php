<?php
require_once __DIR__ . '/../config.php';
if (session_status() == PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'unauth']); exit; }

// create table if missing
$conn->query("CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(191) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) DEFAULT 0,
    available_date DATE DEFAULT NULL,
    supplier_id INT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
)");

$res = $conn->query('SELECT m.*, s.name AS supplier_name FROM menu_items m LEFT JOIN suppliers s ON m.supplier_id = s.id ORDER BY m.created_at DESC');
if ($res === false) { http_response_code(500); echo json_encode(['error'=>'db','msg'=>$conn->error]); exit; }
$out = [];
while ($r = $res->fetch_assoc()) $out[] = $r;
echo json_encode($out);
exit;
