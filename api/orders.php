<?php
require_once __DIR__ . '/../config.php';
// config.php may already start the session; guard to avoid notices
if (session_status() == PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) {
    http_response_code(401); echo json_encode(['error'=>'unauth']); exit;
}

 $role = $_SESSION['role'] ?? '';
// support optional filters: status/filter and supplier_id
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : null;
$filter = isset($_GET['filter']) ? trim($_GET['filter']) : null; // alias
$supplierId = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : null;

// normalize status filter: allow legacy strings or numeric codes
$status = null;
if ($statusFilter) $status = $statusFilter;
elseif ($filter) $status = $filter;
if ($status === 'all') $status = null;

// base query
$base = "SELECT o.*, u.name AS user_name, s.name AS supplier_name FROM orders o LEFT JOIN users u ON o.user_id=u.id LEFT JOIN suppliers s ON o.supplier_id=s.id";

// build where clauses
$where = [];
$params = [];
$types = '';

// role-specific defaults and filters
if ($status) {
    // handle legacy string statuses or numeric codes
    if (is_numeric($status)) {
        $code = intval($status);
        $where[] = 'o.status_code = ?'; $params[] = $code; $types .= 'i';
        if ($role === 'supplier' && in_array($code, [ORDER_STATUS_CONFIRMED])) {
            // when supplier requests confirmed/accepted, only show those claimed by them
            $where[] = 'o.claimed_by = ?'; $params[] = (int)$_SESSION['user_id']; $types .= 'i';
        }
    } else {
        $s = strtolower($status);
        if ($s === 'claimed') {
            if ($role === 'supplier') {
                $where[] = 'o.claimed_by = ?'; $params[] = (int)$_SESSION['user_id']; $types .= 'i';
            } else {
                $where[] = 'o.claimed_by IS NOT NULL';
            }
        } elseif (in_array($s, ['pending','open'])) {
            $openCode = defined('ORDER_STATUS_OPEN') ? ORDER_STATUS_OPEN : 0;
            $where[] = 'o.status_code = ?'; $params[] = $openCode; $types .= 'i';
        } elseif (in_array($s, ['accepted','confirmed'])) {
            $confirmedCode = defined('ORDER_STATUS_CONFIRMED') ? ORDER_STATUS_CONFIRMED : 1;
            $where[] = 'o.status_code = ?'; $params[] = $confirmedCode; $types .= 'i';
            if ($role === 'supplier') { $where[] = 'o.claimed_by = ?'; $params[] = (int)$_SESSION['user_id']; $types .= 'i'; }
        } else {
            http_response_code(400); echo json_encode(['error'=>'invalid_status']); exit;
        }
    }
} else {
    // no explicit status filter
    if ($role === 'supplier') {
        // suppliers see open orders plus any orders they've claimed
        $openCode = defined('ORDER_STATUS_OPEN') ? ORDER_STATUS_OPEN : 0;
        $where[] = '(o.status_code = ? OR o.claimed_by = ?)'; $params[] = $openCode; $params[] = (int)$_SESSION['user_id']; $types .= 'ii';
    }
}

if ($supplierId) {
    $where[] = 'o.supplier_id = ?'; $params[] = $supplierId; $types .= 'i';
}

// assemble query
if (count($where)) {
    $sql = $base . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY o.created_at DESC';
    $stmt = $conn->prepare($sql);
    if ($stmt === false) { http_response_code(500); echo json_encode(['error'=>'db','msg'=>$conn->error]); exit; }
    if (count($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query($base . ' ORDER BY o.created_at DESC');
}

$out = [];
if ($res === false) {
    http_response_code(500);
    echo json_encode(['error'=>'db','msg'=>$conn->error ?? 'query_failed']);
    exit;
}

while ($r = $res->fetch_assoc()) {
    // normalize status label for frontend convenience
    $legacy = $r['status'] ?? null;
    $code = isset($r['status_code']) ? intval($r['status_code']) : order_status_code_from_legacy($legacy);
    $r['status_code'] = $code;
    $r['status_label'] = order_status_label($code);
    $out[] = $r;
}
echo json_encode($out);

    // ensure script ends without accidental output
    exit;
