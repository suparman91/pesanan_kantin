<?php
require_once __DIR__ . '/../config.php';
session_start();
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) {
    http_response_code(401); echo json_encode(['error'=>'unauth']); exit;
}

$role = $_SESSION['role'] ?? '';
// suppliers see pending orders, others see all
if ($role === 'supplier') {
    $q = $conn->prepare("SELECT o.*, u.name AS user_name, s.name AS supplier_name FROM orders o LEFT JOIN users u ON o.user_id=u.id LEFT JOIN suppliers s ON o.supplier_id=s.id WHERE o.status='pending' ORDER BY o.created_at DESC");
    $q->execute();
    $res = $q->get_result();
} else {
    $res = $conn->query("SELECT o.*, u.name AS user_name, s.name AS supplier_name FROM orders o LEFT JOIN users u ON o.user_id=u.id LEFT JOIN suppliers s ON o.supplier_id=s.id ORDER BY o.created_at DESC");
}

$out = [];
while ($r = $res->fetch_assoc()) {
    $out[] = $r;
}
echo json_encode($out);
