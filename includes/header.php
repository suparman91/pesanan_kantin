<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/csrf.php';
if (empty($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}
// load site logo if exists
$site_logo = null;
$logo_path = __DIR__ . '/../uploads/site_logo.png';
if (file_exists($logo_path)) {
  $site_logo = 'uploads/site_logo.png';
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pesanan Kantin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
</head>
<body>
<div class="d-flex" id="wrapper">
  <style>
    .site-logo{max-height:40px;max-width:180px;object-fit:contain}
    /* sidebar responsive overlay */
    #sidebar-wrapper { transition: transform .25s ease; }
    @media (max-width: 767px) {
      #sidebar-wrapper { position: fixed; z-index:1045; height:100%; left:0; top:0; transform: translateX(-100%); }
      #sidebar-wrapper.show { transform: translateX(0); }
      #page-content-wrapper { padding-top:56px; }
    }
  </style>
