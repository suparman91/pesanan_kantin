<?php
// SSE endpoint for real-time notifications
require_once __DIR__ . '/../config.php';
if (session_status() == PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) { http_response_code(401); echo 'Unauthorized'; exit; }
$uid = (int)$_SESSION['user_id'];
// allow script to continue even if client disconnects and run indefinitely
ignore_user_abort(true);
set_time_limit(0);
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');

$lastId = 0;
if (!empty($_SERVER['HTTP_LAST_EVENT_ID'])) $lastId = (int)$_SERVER['HTTP_LAST_EVENT_ID'];

function sendEvent($id, $data) {
    echo "id: {$id}\n";
    echo "data: " . json_encode($data) . "\n\n";
    @ob_flush(); @flush();
}

// initial heartbeat
echo "retry: 2000\n\n"; @ob_flush(); @flush();

// release session lock so other requests can run while this long-polling SSE runs
if (function_exists('session_write_close')) session_write_close();
while (!connection_aborted()) {
    // fetch notifications newer than lastId for this user or broadcasts
    $sql = "SELECT id,order_id,message,created_by,target_user,is_read,created_at FROM notifications WHERE id>? AND (target_user IS NULL OR target_user=?) ORDER BY id ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $lastId, $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $found = false;
    while ($row = $res->fetch_assoc()) {
        $found = true;
        $lastId = (int)$row['id'];
        sendEvent($lastId, $row);
    }
    // heartbeat to keep connection alive
    if (!$found) {
        echo "data: {\"heartbeat\":1}\n\n";
        @ob_flush(); @flush();
    }
    // sleep a short while
    sleep(2);
}
