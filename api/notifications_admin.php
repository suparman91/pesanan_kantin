<?php
require_once __DIR__ . '/../config.php';
if (session_status() == PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'unauth']); exit; }
$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['admin','hrd'])) { http_response_code(403); echo json_encode(['error'=>'forbidden']); exit; }

$res = $conn->query("SELECT n.id,n.order_id,n.message,n.created_by,n.target_user,n.is_read,n.created_at,
  cu.name AS created_by_name, tu.name AS target_user_name
  FROM notifications n
  LEFT JOIN users cu ON n.created_by = cu.id
  LEFT JOIN users tu ON n.target_user = tu.id
  ORDER BY n.created_at DESC");
// Ensure notifications table exists to avoid DB errors
$conn->query("CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    message VARCHAR(255),
    created_by INT,
    target_user INT DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$res = $conn->query("SELECT n.id,n.order_id,n.message,n.created_by,n.target_user,n.is_read,n.created_at,
  cu.name AS created_by_name, tu.name AS target_user_name
  FROM notifications n
  LEFT JOIN users cu ON n.created_by = cu.id
  LEFT JOIN users tu ON n.target_user = tu.id
  ORDER BY n.created_at DESC");
if ($res === false) {
    http_response_code(500);
    echo json_encode(['error'=>'db','msg'=>$conn->error ?? 'query_failed']);
    exit;
}

$out = [];
while ($r = $res->fetch_assoc()) $out[] = $r;
echo json_encode($out);
exit;
