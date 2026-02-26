<?php
// Simple router for friendly URLs and protected routes
session_start();

$scriptName = dirname($_SERVER['SCRIPT_NAME']);
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$relative = '/';
if (strlen($path) > strlen($scriptName)) {
    $relative = substr($path, strlen($scriptName));
}
$relative = '/' . trim($relative, '/');
if ($relative === '/.') $relative = '/';

$routes = [
    '/' => null, // homepage handled inline
    '/login_karyawan' => 'login.php',
    '/login.php' => 'login.php',
    '/logout' => 'logout.php',
    '/dashboard' => 'dashboard.php',
    '/orders' => 'orders.php',
    '/suppliers' => 'suppliers.php',
    '/users' => 'users.php',
    '/api/orders' => 'api/orders.php',
    '/api/suppliers' => 'api/suppliers.php',
];

$protected = [
    '/dashboard','/orders','/suppliers','/users','/api/orders','/api/suppliers'
];

if (!array_key_exists($relative, $routes)) {
    http_response_code(404);
    echo "<h1>404 Not Found</h1><p>Route $relative tidak ditemukan.</p>";
    exit;
}

// If route is protected and user not logged in, redirect to friendly login
if (in_array($relative, $protected) && empty($_SESSION['user_id'])) {
    $base = $scriptName === '/' ? '' : $scriptName;
    header('Location: ' . $base . '/login_karyawan');
    exit;
}

$target = $routes[$relative];
if ($target === null) {
    // Redirect root to login if not authenticated, otherwise to dashboard
    $base = $scriptName === '/' ? '' : $scriptName;
    if (!empty($_SESSION['user_id'])) {
        header('Location: ' . $base . '/dashboard');
    } else {
        header('Location: ' . $base . '/login_karyawan');
    }
    exit;
}

// include target file
$file = __DIR__ . '/' . $target;
if (file_exists($file)) {
    require $file;
} else {
    http_response_code(500);
    echo "File target tidak ditemukan: $target";
}
