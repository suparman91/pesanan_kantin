<?php
// Load config first so session.save_path and error logging are configured
require_once __DIR__ . '/../config.php';
// config may or may not have started the session; guard to avoid notices
if (session_status() == PHP_SESSION_NONE) session_start();
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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
  <link href="assets/css/theme.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script>
    // Non-blocking toast helper for AJAX errors
    function showToastError(title, text) {
      if (window.Swal && typeof Swal.fire === 'function') {
        Swal.fire({toast:true,position:'top-end',icon:'error',title:title || 'Error',text:text || '',showConfirmButton:false,timer:3500});
      } else {
        console.error('Toast:', title, text);
      }
    }
    // Global AJAX prefilter: ensure JSON responses and show friendly errors
    (function($){
      $(function(){
        $.ajaxPrefilter(function(options, originalOptions, jqXHR){
          var _success = options.success;
          options.success = function(data, textStatus, xhr){
            // if server returned string/html instead of JSON, try to parse
            if (typeof data === 'string') {
              try {
                data = JSON.parse(data);
              } catch(e) {
                // call original error handler so callers (DataTables etc.) can handle failure
                if (typeof options.error === 'function') {
                  options.error.call(this, xhr, 'parsererror', e);
                } else if (typeof originalOptions.error === 'function') {
                  originalOptions.error.call(this, xhr, 'parsererror', e);
                } else {
                    showToastError('Server response error','Invalid JSON from server');
                  }
                return;
              }
            }
            if (data && data.error) {
              // prefer calling error handlers rather than swallowing the response
              if (typeof options.error === 'function') {
                options.error.call(this, xhr, 'error', data);
              } else if (typeof originalOptions.error === 'function') {
                originalOptions.error.call(this, xhr, 'error', data);
              } else {
                showToastError('API Error', data.error || data.msg || 'Unknown error');
              }
              return;
            }
            if (typeof _success === 'function') return _success.call(this, data, textStatus, xhr);
          };
        });
      });
    })(jQuery);
  </script>
</head>
<body>
<div class="d-flex" id="wrapper">
  <style>
    .site-logo{max-height:40px;max-width:180px;object-fit:contain}
    /* offcanvas sidebar sizing to match previous width */
    /* Sidebar visual tweaks */
    :root { --sidebar-width: 250px; --sidebar-active-bg: var(--primary); --sidebar-text-dark: #212529; }
    /* make offcanvas use the same width everywhere and occupy full viewport height */
    .offcanvas, .offcanvas-lg { width: var(--sidebar-width); height: 100vh; }
    /* ensure the offcanvas body is a column so menu can stretch and profile/footer can sit at bottom */
    .offcanvas .offcanvas-body { display: flex; flex-direction: column; padding: 0.5rem; }
    /* list-group item sizing and icon; make list-group stretch to fill available height and scroll when needed */
    .offcanvas .list-group { display: flex; flex-direction: column; flex: 1 1 auto; overflow: auto; margin: 0; }
    .offcanvas .list-group-item { border-radius: 0; padding: .65rem .9rem; color:var(--sidebar-text-dark); }
    .sidebar-category { text-transform: uppercase; letter-spacing: .03em; font-size: .725rem; padding-top: .5rem; padding-bottom: .35rem; }
    /* sidebar collapse toggle icon */
    .sidebar-toggle-icon { transition: transform .25s ease; }
    .sidebar-toggle-icon.rotate { transform: rotate(90deg); }
    /* make the dashboard link match navbar height and align vertically */
    .list-group-item.dashboard-link { height: 56px; display: flex; align-items: center; padding-left: .9rem; padding-right: .9rem; }
    /* ensure first item sits flush to the top (no extra margin) */
    .offcanvas .list-group { margin-top: 0 !important; }
    .offcanvas .list-group-item + .list-group-item { border-top-width: 1px; }
    .offcanvas .list-group-item .bi { font-size: 1.05rem; width: 1.3rem; text-align:center; color:var(--muted); margin-right:.6rem; }
    .offcanvas .list-group-item.active { background-color: var(--sidebar-active-bg); color: #fff; }
    .offcanvas .list-group-item.active .bi { color: #fff; }
    /* Ensure full-height layout so footer can stick to bottom when content is short */
    html, body { height: 100%; }
    /* Default: offcanvas behaves as overlay (mobile) and content is full-width */
    #wrapper { min-height: 100vh; }
    /* make page content a column so footer sits at bottom when space allows */
    /* keep no wrapper right padding so topbar can be flush; content margins remain via inner containers */
    #page-content-wrapper { margin-left: 0; display: flex; flex-direction: column; min-height: 100vh; padding-right: 0; }
    /* Desktop: render offcanvas as a static sidebar using CSS only */
    @media (min-width: 992px) {
      .offcanvas.offcanvas-start {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: var(--sidebar-width) !important;
        height: 100vh !important;
        transform: none !important;
        visibility: visible !important;
        z-index: 1040 !important;
        transition: transform .2s ease, visibility .2s ease;
      }
      /* 3D visual enhancements for sidebar: subtle gradient, border and shadow */
      .offcanvas.offcanvas-start {
        background: linear-gradient(180deg, #ffffff 0%, #f6f9fc 100%);
        box-shadow: 0 10px 24px rgba(22,28,36,0.06);
        border-right: 1px solid rgba(0,0,0,0.06);
      }
      .offcanvas.offcanvas-start .offcanvas-body { background: transparent; }
      .offcanvas .list-group-item { background: transparent; transition: background-color .15s ease, box-shadow .12s ease; }
      .offcanvas .list-group-item:hover { background-color: rgba(13,110,253,0.04); }
      .offcanvas .list-group-item.active {
        background: linear-gradient(90deg, var(--sidebar-active-bg), rgba(13,110,253,0.85));
        color: #fff;
        box-shadow: inset 0 -6px 18px rgba(0,0,0,0.06);
      }
      .offcanvas .list-group-item.active .bi { color: #fff; }
      /* make category buttons slightly elevated */
      .list-group-item.sidebar-category { background: transparent; }
      /* ensure the offcanvas body scrolls independently */
      .offcanvas.offcanvas-start .offcanvas-body { height: 100%; overflow-y: auto; }
      /* remove extra top spacing in the offcanvas header so menu aligns with navbar */
      .offcanvas.offcanvas-start .offcanvas-header {
        padding-top: 0.25rem !important;
        padding-bottom: 0.25rem !important;
        height: 56px !important;
        align-items: center !important;
        border-bottom: none !important;
      }
      .offcanvas.offcanvas-start .offcanvas-title { margin: 0; font-size: 1rem; }
      /* ensure the offcanvas body content starts at the top without extra padding */
      .offcanvas.offcanvas-start .offcanvas-body { padding-top: 0 !important; }
      .offcanvas.offcanvas-start .list-group { margin-top: 0 !important; padding-top: 0 !important; }
      /* hide any backdrop on large screens */
      .offcanvas-backdrop { display: none !important; }
      /* keep page content shifted to make room for static sidebar */
      #page-content-wrapper { margin-left: var(--sidebar-width); display:flex; flex-direction:column; min-height:100vh; padding-right: 0; }
      /* keep toggle visible on large screens so user can collapse the sidebar */
      .navbar .btn[data-bs-toggle="offcanvas"]{ display: inline-block !important; }
      /* make topbar right edge flush (remove extra right padding) */
      .navbar { padding-right: 0 !important; width: 100%; }
      .navbar .container-fluid { padding-right: 0 !important; margin-right: 0 !important; width:100%; }

      /* keep content cards from expanding to full width so navbar stays visually flush
        content will keep its side margins while navbar covers the full available area */
      #page-content-wrapper > .container-fluid,
      #page-content-wrapper .content-card { box-sizing: border-box; }
      /* limit content width by subtracting left+right margins (desktop) */
      #page-content-wrapper > .container-fluid,
      #page-content-wrapper .content-card { width: calc(100% - 2.5rem); }
      /* hide internal offcanvas close button on large screens */
      .offcanvas .btn-close { display: none !important; }

      /* collapsed state: hide sidebar and expand content */
      body.sidebar-collapsed #offcanvasSidebar { transform: translateX(-100%) !important; visibility: hidden !important; }
      body.sidebar-collapsed #page-content-wrapper { margin-left: 0 !important; }
      /* ensure the main content area grows so footer is pushed to bottom */
      #page-content-wrapper > .container-fluid, #page-content-wrapper .content-card { flex: 1 1 auto; }
    }
    @media (max-width: 767px) {
      #page-content-wrapper { padding-top:56px; }
    }
    /* Card view for main content: responsive margins, padding and subtle shadow
       Applies to the direct container inside #page-content-wrapper (common layout)
       If you prefer a manual wrapper, add class "content-card" to your page container. */
    #page-content-wrapper > .container-fluid,
    #page-content-wrapper .content-card {
      background: var(--bs-body-bg, #ffffff);
      border-radius: .5rem;
      box-shadow: 0 .5rem 1rem rgba(0,0,0,.08);
      padding: 1rem;
      margin: 1rem;
      margin-bottom: 2rem; /* space between content and footer */
    }
    @media (max-width: 767px) {
      #page-content-wrapper > .container-fluid,
      #page-content-wrapper .content-card { margin: .5rem; padding: .75rem; margin-bottom: 1.25rem; }
    }
    @media (min-width: 992px) {
      #page-content-wrapper > .container-fluid,
      #page-content-wrapper .content-card { margin: 1.25rem; padding: 1.25rem; margin-bottom: 2.5rem; }
    }
  </style>
