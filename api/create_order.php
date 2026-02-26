<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/csrf.php';
session_start();
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'unauth']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'method']); exit; }
if (empty($_POST['_csrf']) || !csrf_verify($_POST['_csrf'])) { http_response_code(400); echo json_encode(['error'=>'csrf']); exit; }

$item = trim($_POST['item'] ?? '');
$qty = max(1, (int)($_POST['quantity'] ?? 1));
$price = max(0, (float)($_POST['total_price'] ?? 0));
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare('INSERT INTO orders (user_id,item,quantity,total_price) VALUES (?,?,?,?)');
$stmt->bind_param('isid',$user_id,$item,$qty,$price);
if ($stmt->execute()) {
    echo json_encode(['ok'=>true,'id'=>$stmt->insert_id]);
} else {
    http_response_code(500); echo json_encode(['error'=>'db']);
}
