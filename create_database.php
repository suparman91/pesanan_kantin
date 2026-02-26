<?php
// create_database.php
// Membaca konfigurasi dari config.php tanpa mengeksekusinya, lalu membuat database jika belum ada.
$cfg = @file_get_contents(__DIR__ . '/config.php');
if ($cfg === false) {
    die('Tidak dapat membaca config.php - pastikan file ada.');
}

function extract_var($txt, $name, $default=''){
    if (preg_match('/\$'.preg_quote($name,'/')."\s*=\s*'([^']*)'/", $txt, $m)) return $m[1];
    if (preg_match('/\$'.preg_quote($name,'/')."\s*=\s*\"([^\"]*)\"/", $txt, $m)) return $m[1];
    return $default;
}

$DB_HOST = extract_var($cfg, 'DB_HOST', '192.168.1.223');
$DB_USER = extract_var($cfg, 'DB_USER', 'nas_it');
$DB_PASS = extract_var($cfg, 'DB_PASS', 'Nasityc@2025');
$DB_NAME = extract_var($cfg, 'DB_NAME', 'pesanan_kantin');

$mysqli = @new mysqli($DB_HOST, $DB_USER, $DB_PASS);
if ($mysqli->connect_error) {
    die('Koneksi ke MySQL gagal: ' . $mysqli->connect_error);
}

$sql = "CREATE DATABASE IF NOT EXISTS `" . $mysqli->real_escape_string($DB_NAME) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($mysqli->query($sql) === TRUE) {
    echo "Database <strong>$DB_NAME</strong> berhasil dibuat atau sudah ada.<br>";
    echo "Selanjutnya jalankan: <a href=\"setup.php\">setup.php</a> untuk membuat tabel dan akun admin default.<br>";
} else {
    echo 'Gagal membuat database: ' . $mysqli->error;
}

echo '<p><a href="login.php">Kembali ke Login</a></p>';
