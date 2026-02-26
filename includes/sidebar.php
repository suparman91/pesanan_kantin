<?php
// sidebar.php - gunakan role untuk menampilkan menu
$role = $_SESSION['role'] ?? '';
?>
<div class="bg-light border-end" id="sidebar-wrapper" style="width:250px;">
  <div class="sidebar-heading px-3 py-2">
    <?php if (!empty($site_logo)): ?>
      <img src="<?= $site_logo ?>" alt="Logo" class="site-logo mb-1">
    <?php else: ?>
      Kantin Pabrik
    <?php endif; ?>
  </div>
  <div class="list-group list-group-flush">
    <a href="dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>
    <a href="orders.php" class="list-group-item list-group-item-action">Pemesanan</a>
    <a href="suppliers.php" class="list-group-item list-group-item-action">Supplier</a>
    <?php if ($role === 'admin' || $role === 'hrd'): ?>
      <a href="users.php" class="list-group-item list-group-item-action">Kelola Pengguna</a>
    <?php endif; ?>
    <a href="logout.php" class="list-group-item list-group-item-action text-danger">Logout</a>
  </div>
</div>

<div id="page-content-wrapper" class="w-100">
  <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-3">
    <div class="container-fluid">
      <button class="btn btn-outline-secondary me-2" id="menu-toggle"><span class="navbar-toggler-icon"></span></button>
      <a class="navbar-brand me-auto" href="dashboard.php"><?php if (!empty($site_logo)): ?><img src="<?= $site_logo ?>" class="site-logo" alt="Logo"><?php else: ?>Kantin Pabrik<?php endif; ?></a>

      <form class="d-none d-md-flex mx-3" role="search">
        <input class="form-control form-control-sm" type="search" placeholder="Cari..." aria-label="Search">
      </form>

      <ul class="navbar-nav ms-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle position-relative" href="#" id="notifDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-bell"></i>
            <span id="notifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none;">0</span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notifDropdown">
            <li><span class="dropdown-item-text">Tidak ada notifikasi</span></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <?= esc($_SESSION['name']) ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
            <li><a class="dropdown-item" href="users.php">Profil</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </nav>
  <div class="container-fluid mt-3">

<script>
  (function(){
    var btn = document.getElementById('menu-toggle');
    btn.addEventListener('click', function(e){
      e.preventDefault();
      var sb = document.getElementById('sidebar-wrapper');
      if (window.innerWidth <= 767) {
        // overlay mode
        sb.classList.toggle('show');
        if (sb.classList.contains('show')) {
          var ov = document.createElement('div'); ov.id='sidebar-overlay'; ov.style.cssText='position:fixed;inset:0;background:rgba(0,0,0,0.3);z-index:1040;'; document.body.appendChild(ov);
          ov.addEventListener('click', function(){ sb.classList.remove('show'); ov.remove(); });
        } else {
          var ex = document.getElementById('sidebar-overlay'); if (ex) ex.remove();
        }
      } else {
        sb.classList.toggle('d-none');
      }
    });
    // active link highlighting
    var path = window.location.pathname.toLowerCase();
    document.querySelectorAll('#sidebar-wrapper .list-group a').forEach(function(a){
      var href = a.getAttribute('href');
      if (!href) return;
      if (path.indexOf(href.toLowerCase()) !== -1 || path.endsWith('/' + href.toLowerCase())) {
        a.classList.add('active');
      }
    });

    // notifications polling
    function pollNotifs(){
      fetch('api/notifications.php').then(r=>r.json()).then(function(j){
        if (j && j.pending) {
          var b = document.getElementById('notifBadge');
          b.textContent = j.pending;
          b.style.display = j.pending>0? 'inline-block':'none';
        }
      }).catch(()=>{});
    }
    pollNotifs();
    setInterval(pollNotifs, 15000);
  })();
</script>
