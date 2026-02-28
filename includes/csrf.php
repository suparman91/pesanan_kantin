<?php
// includes/csrf.php - simple CSRF utilities
// Ensure a session is started; use session_status() guard to avoid warnings
if (session_status() == PHP_SESSION_NONE) session_start();

if (!function_exists('csrf_token')) {
    function csrf_token() {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    function csrf_input() {
        $t = csrf_token();
        return '<input type="hidden" name="_csrf" value="' . $t . '">';
    }

    function csrf_verify($token) {
        if (empty($_SESSION['_csrf_token'])) return false;
        return hash_equals($_SESSION['_csrf_token'], $token);
    }
}
