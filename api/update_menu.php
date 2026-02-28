<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/csrf.php';
if (session_status() == PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin','hrd','supplier'])) { http_response_code(403); echo json_encode(['error'=>'forbidden']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'method']); exit; }
if (empty($_POST['_csrf']) || !csrf_verify($_POST['_csrf'])) { http_response_code(400); echo json_encode(['error'=>'csrf']); exit; }

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) { http_response_code(400); echo json_encode(['error'=>'invalid']); exit; }

// check allowed update days
$weekday = (int)date('N');
$allowed = $MENU_UPDATE_DAYS ?? [1,2,3,4,5,6,7];
$role = $_SESSION['role'] ?? '';
if ($role === 'supplier' && !in_array($weekday, $allowed)) {
    http_response_code(403); echo json_encode(['error'=>'not_allowed_today']); exit;
}

$name = trim($_POST['name'] ?? '');
$desc = trim($_POST['description'] ?? '');
$price = max(0, (float)($_POST['price'] ?? 0));
$available_date = !empty($_POST['available_date']) ? trim($_POST['available_date']) : null;
$supplier_id = isset($_POST['supplier_id']) && is_numeric($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
$updated_by = (int)$_SESSION['user_id'];

if ($name === '') { http_response_code(400); echo json_encode(['error'=>'name_required']); exit; }

// ensure available_date column exists
$colRes = $conn->query("SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='menu_items' AND COLUMN_NAME='available_date'");
if ($colRes) {
    $row = $colRes->fetch_assoc();
    if (empty($row['c'])) {
        $conn->query("ALTER TABLE menu_items ADD COLUMN available_date DATE DEFAULT NULL");
    }
}

$stmt = $conn->prepare('UPDATE menu_items SET name=?,description=?,price=?,available_date=?,supplier_id=?,updated_by=? WHERE id=?');
if (! $stmt) { http_response_code(500); echo json_encode(['error'=>'db','msg'=>$conn->error]); exit; }
$stmt->bind_param('ssdsiii', $name, $desc, $price, $available_date, $supplier_id, $updated_by, $id);
if ($stmt->execute()) echo json_encode(['ok'=>true]); else { http_response_code(500); echo json_encode(['error'=>'db','msg'=>$stmt->error ?: $conn->error]); }
exit;
