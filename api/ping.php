<?php
// Simple health check returning JSON
header('Content-Type: application/json');
http_response_code(200);
echo json_encode(['ok' => true]);
exit;
