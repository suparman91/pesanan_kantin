<?php
require_once __DIR__ . '/../config.php';
session_start();
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'unauth']); exit; }
$role = $_SESSION['role'] ?? '';
if ($role === 'supplier') {
    $q = $conn->prepare("SELECT COUNT(*) as c FROM orders WHERE status='pending'");
    $q->execute(); $res = $q->get_result()->fetch_assoc();
    echo json_encode(['pending' => (int)$res['c']]);
} else {
    // admin/hrd see pending and total
    $res1 = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status='pending'");
    $pending = $res1->fetch_assoc()['c'] ?? 0;
    echo json_encode(['pending' => (int)$pending]);
}
