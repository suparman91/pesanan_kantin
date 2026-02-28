<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/csrf.php';
if (session_status() == PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'unauth']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'method']); exit; }
if (empty($_POST['_csrf']) || !csrf_verify($_POST['_csrf'])) { http_response_code(400); echo json_encode(['error'=>'csrf']); exit; }

// ensure orders table has order_date column
$col = $conn->query("SHOW COLUMNS FROM orders LIKE 'order_date'");
if (!$col || $col->num_rows === 0) {
    $conn->query("ALTER TABLE orders ADD COLUMN order_date DATE DEFAULT NULL");
}
// ensure orders table has status_code column (numeric status mapping)
$col = $conn->query("SHOW COLUMNS FROM orders LIKE 'status_code'");
if (!$col || $col->num_rows === 0) {
    $conn->query("ALTER TABLE orders ADD COLUMN status_code TINYINT DEFAULT 0");
}

$item = trim($_POST['item'] ?? '');
$qty = max(1, (int)($_POST['quantity'] ?? 1));
$price = max(0, (float)($_POST['total_price'] ?? 0));
$user_id = (int)$_SESSION['user_id'];

// accept menu_id (optional) to use menu item name/price
$menu_id = isset($_POST['menu_id']) && is_numeric($_POST['menu_id']) ? (int)$_POST['menu_id'] : 0;
if ($menu_id > 0) {
    $mq = $conn->prepare('SELECT name,price FROM menu_items WHERE id=?');
    $mq->bind_param('i',$menu_id); $mq->execute(); $mres = $mq->get_result(); if ($mres && ($mi = $mres->fetch_assoc())) { $item = $mi['name']; if (empty($price) || $price==0) $price = (float)$mi['price']; }
}

// order date validation: allow ordering from (today -1) to (today +5)
$order_date = $_POST['order_date'] ?? null;
if ($order_date) {
    $d = DateTime::createFromFormat('Y-m-d', $order_date);
    if (!$d) { http_response_code(400); echo json_encode(['error'=>'invalid_date']); exit; }
    $today = new DateTime('today');
    $min = (clone $today)->modify('-1 day');
    $max = (clone $today)->modify('+5 days');
    if ($d < $min || $d > $max) { http_response_code(400); echo json_encode(['error'=>'date_out_of_range','msg'=>'Order date must be between '.$min->format('Y-m-d').' and '.$max->format('Y-m-d')]); exit; }
} else {
    $order_date = (new DateTime('today'))->format('Y-m-d');
}

// use numeric status code (ORDER_STATUS_OPEN) for new orders
$statusCode = defined('ORDER_STATUS_OPEN') ? ORDER_STATUS_OPEN : 0;
$stmt = $conn->prepare('INSERT INTO orders (user_id,item,quantity,total_price,order_date,status_code) VALUES (?,?,?,?,?,?)');
$stmt->bind_param('isidsi',$user_id,$item,$qty,$price,$order_date,$statusCode);
if ($stmt->execute()) {
    $newId = $stmt->insert_id;
    // create notifications table if missing and target admin
    $conn->query("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT,
        message VARCHAR(255),
        created_by INT,
        target_user INT DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $col = $conn->query("SHOW COLUMNS FROM notifications LIKE 'target_user'");
    if (!$col || $col->num_rows === 0) {
        $conn->query("ALTER TABLE notifications ADD COLUMN target_user INT DEFAULT NULL");
    }
    // notify all admin/hrd users
    $admins = $conn->query("SELECT id,name FROM users WHERE role IN ('admin','hrd')");
    $uname = '';
    $uqq = $conn->prepare('SELECT name FROM users WHERE id=?'); $uqq->bind_param('i',$user_id); $uqq->execute(); $uqqr = $uqq->get_result(); if ($ur = $uqqr->fetch_assoc()) $uname = $ur['name'];
    $msg = $conn->real_escape_string("Pesanan baru #$newId dari $uname");
    if ($admins) {
        while ($a = $admins->fetch_assoc()) {
            $t = (int)$a['id'];
            $conn->query("INSERT INTO notifications (order_id,message,created_by,target_user,is_read) VALUES ($newId,'$msg',$user_id,$t,0)");
        }
    } else {
        $conn->query("INSERT INTO notifications (order_id,message,created_by,target_user,is_read) VALUES ($newId,'$msg',$user_id,NULL,0)");
    }
    echo json_encode(['ok'=>true,'id'=>$newId]);
} else {
    http_response_code(500); echo json_encode(['error'=>'db']);
}
