<?php
// config.php - koneksi database (mysqli)
if (session_status() == PHP_SESSION_NONE) session_start();

$DB_HOST = '192.168.1.223';
$DB_USER = 'nas_it';
$DB_PASS = 'Nasityc@2025';
$DB_NAME = 'pesanan_kantin';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

function esc($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
