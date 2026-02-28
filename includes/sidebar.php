<?php
// sidebar.php - gunakan role untuk menampilkan menu
$role = $_SESSION['role'] ?? '';
$initial = '';
if (!empty($_SESSION['name'])) {
  $initial = strtoupper(mb_substr($_SESSION['name'], 0, 1));
} else {
  $initial = 'U';
}
?>
<div class="offcanvas offcanvas-lg offcanvas-start bg-light" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="offcanvasSidebarLabel">
      <?php if (!empty($site_logo)): ?>
        <img src="<?= $site_logo ?>" alt="Logo" class="site-logo mb-1">
      <?php else: ?>
        Kantin Pabrik
      <?php endif; ?>
    </h5>
    <button type="button" class="btn-close text-reset d-lg-none" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body p-0">
    <div class="d-flex flex-column h-100">
      <div class="list-group list-group-flush flex-grow-1 py-3">
        <a href="dashboard.php" class="list-group-item list-group-item-action d-flex align-items-center dashboard-link">
          <i class="bi bi-house-door me-3" aria-hidden="true"></i>
          <span>Dashboard</span>
        </a>

        <button class="list-group-item list-group-item-action d-flex align-items-center justify-content-between bg-transparent text-muted small fw-bold sidebar-category mt-2" data-bs-toggle="collapse" data-bs-target="#collapse-transaksi" aria-expanded="true" aria-controls="collapse-transaksi">
          <span>Transaksi</span>
          <i class="bi bi-chevron-right sidebar-toggle-icon"></i>
        </button>
        <div class="collapse show" id="collapse-transaksi">
          <a href="orders.php" class="list-group-item list-group-item-action d-flex align-items-center">
            <i class="bi bi-receipt me-3" aria-hidden="true"></i>
            <span>Pemesanan</span>
          </a>
        </div>

        <button class="list-group-item list-group-item-action d-flex align-items-center justify-content-between bg-transparent text-muted small fw-bold sidebar-category mt-2" data-bs-toggle="collapse" data-bs-target="#collapse-master" aria-expanded="true" aria-controls="collapse-master">
          <span>Master</span>
          <i class="bi bi-chevron-right sidebar-toggle-icon"></i>
        </button>
        <div class="collapse show" id="collapse-master">
          <a href="suppliers.php" class="list-group-item list-group-item-action d-flex align-items-center">
            <i class="bi bi-truck me-3" aria-hidden="true"></i>
            <span>Supplier</span>
          </a>
          <a href="menu.php" class="list-group-item list-group-item-action d-flex align-items-center">
            <i class="bi bi-grid me-3" aria-hidden="true"></i>
            <span>Menu Makanan</span>
          </a>
        </div>

        <?php if ($role === 'admin' || $role === 'hrd'): ?>
          <button class="list-group-item list-group-item-action d-flex align-items-center justify-content-between bg-transparent text-muted small fw-bold sidebar-category mt-2" data-bs-toggle="collapse" data-bs-target="#collapse-admin" aria-expanded="true" aria-controls="collapse-admin">
            <span>Admin</span>
            <i class="bi bi-chevron-right sidebar-toggle-icon"></i>
          </button>
          <div class="collapse show" id="collapse-admin">
            <a href="users.php" class="list-group-item list-group-item-action d-flex align-items-center">
              <i class="bi bi-people me-3" aria-hidden="true"></i>
              <span>Kelola Pengguna</span>
            </a>
            <a href="notifications.php" class="list-group-item list-group-item-action d-flex align-items-center">
              <i class="bi bi-bell me-3" aria-hidden="true"></i>
              <span>Notifikasi</span>
            </a>
            <a href="settings.php" class="list-group-item list-group-item-action d-flex align-items-center">
              <i class="bi bi-gear me-3" aria-hidden="true"></i>
              <span>Pengaturan</span>
            </a>
          </div>
        <?php endif; ?>

        <button class="list-group-item list-group-item-action d-flex align-items-center justify-content-between bg-transparent text-muted small fw-bold sidebar-category mt-2" data-bs-toggle="collapse" data-bs-target="#collapse-akun" aria-expanded="true" aria-controls="collapse-akun">
          <span>Akun</span>
          <i class="bi bi-chevron-right sidebar-toggle-icon"></i>
        </button>
        <div class="collapse show" id="collapse-akun">
          <a href="logout.php" class="list-group-item list-group-item-action text-danger d-flex align-items-center">
            <i class="bi bi-box-arrow-right me-3" aria-hidden="true"></i>
            <span>Logout</span>
          </a>
        </div>
      </div>

      <div class="border-top p-3 small text-muted">
        <div class="d-flex align-items-center">
              <div class="me-2">
                <div class="avatar-circle bg-secondary text-white d-flex align-items-center justify-content-center rounded-circle"><?= esc($initial) ?></div>
              </div>
              <div>
                <div><?= esc($_SESSION['name']) ?></div>
                <div class="text-muted" style="font-size:.85rem"><?php echo esc($_SESSION['role'] ?? ''); ?></div>
              </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="page-content-wrapper" class="w-100">
  <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom ps-3 pe-0">
    <div class="container-fluid">
      <button class="btn btn-outline-secondary me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="#offcanvasSidebar">
        <span class="navbar-toggler-icon"></span>
      </button>
      <a class="navbar-brand me-auto" href="dashboard.php"><?php if (!empty($site_logo)): ?><img src="<?= $site_logo ?>" class="site-logo" alt="Logo"><?php else: ?>Kantin Pabrik<?php endif; ?></a>

      <form class="d-none d-md-flex mx-3" role="search">
        <input class="form-control form-control-sm" type="search" placeholder="Cari..." aria-label="Search">
      </form>

      <ul class="navbar-nav ms-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle position-relative" href="#" id="notifDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-bell"></i>
            <span id="notifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none;">0</span>
            <span id="sseStatus" title="Realtime status" style="display:inline-block;width:10px;height:10px;border-radius:50%;margin-left:8px;vertical-align:middle;background:#6c757d;box-shadow:0 0 0 0 rgba(0,0,0,0.0)"></span>
          </a>
              <ul id="notifMenu" class="dropdown-menu dropdown-menu-end" aria-labelledby="notifDropdown">
                <li><span class="dropdown-item-text">Memuat notifikasi...</span></li>
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
    // highlight active links inside the sidebar/offcanvas (list-group)
    var path = window.location.pathname.toLowerCase();
    document.querySelectorAll('#offcanvasSidebar .list-group a').forEach(function(a){
      var href = a.getAttribute('href');
      if (!href) return;
      if (path.indexOf(href.toLowerCase()) !== -1 || path.endsWith('/' + href.toLowerCase())) {
        a.classList.add('active');
      }
    });

    // allow desktop toggle to collapse the static sidebar
    var desktopToggler = document.querySelector('.navbar .btn[data-bs-toggle="offcanvas"]');
    if (desktopToggler) {
      desktopToggler.addEventListener('click', function(e){
        if (window.innerWidth >= 992) {
          // toggle collapsed class instead of Bootstrap offcanvas behavior
          e.preventDefault();
          document.body.classList.toggle('sidebar-collapsed');
        }
        // for smaller screens, Bootstrap will handle the offcanvas automatically
      });
    }

    // rotate sidebar toggle icons when collapse panels open/close
    document.querySelectorAll('.sidebar-category[data-bs-toggle="collapse"]').forEach(function(btn){
      var target = btn.getAttribute('data-bs-target');
      var icon = btn.querySelector('.sidebar-toggle-icon');
      if (!target || !icon) return;
      var el = document.querySelector(target);
      if (!el) return;
      el.addEventListener('show.bs.collapse', function(){ icon.classList.add('rotate'); });
      el.addEventListener('hide.bs.collapse', function(){ icon.classList.remove('rotate'); });
      // set initial rotation if panel is not shown
      if (!el.classList.contains('show')) icon.classList.remove('rotate');
      else icon.classList.add('rotate');
    });

    // notifications rendering and SSE (unchanged behavior)
    function renderNotifs(data){
      var menu = document.getElementById('notifMenu');
      var badge = document.getElementById('notifBadge');
      if (!menu) return;
      menu.innerHTML = '';
      if (Array.isArray(data.notifications)) {
        var items = data.notifications;
        if (items.length === 0) {
          menu.innerHTML = '<li><span class="dropdown-item-text">Tidak ada notifikasi</span></li>';
          badge.style.display = 'none';
        } else {
          items.forEach(function(it){
            var text = it.message || ('Order #' + it.order_id);
            var when = it.created_at ? (' <small class="text-muted d-block">'+it.created_at+'</small>') : '';
            var a = document.createElement('a');
            a.className = 'dropdown-item notif-item';
            a.href = 'orders.php';
            a.setAttribute('data-id', it.id || '');
            if (it.is_read == 0 || it.is_read === '0') a.classList.add('fw-bold');
            a.innerHTML = text + when;
            var li = document.createElement('li');
            li.appendChild(a);
            menu.appendChild(li);
          });
          var li2 = document.createElement('li');
          li2.innerHTML = '<hr class="dropdown-divider"><a class="dropdown-item text-center small text-muted" href="#" id="markAllNotifs">Tandai semua sudah dibaca</a>';
          menu.appendChild(li2);
          badge.textContent = items.length;
          badge.style.display = items.length>0? 'inline-block':'none';
        }
      } else if (data && typeof data.pending !== 'undefined') {
        var c = parseInt(data.pending) || 0;
        if (c === 0) menu.innerHTML = '<li><span class="dropdown-item-text">Tidak ada notifikasi</span></li>';
        else menu.innerHTML = '<li><a class="dropdown-item" href="orders.php">Ada '+c+' pesanan baru</a></li>';
        badge.textContent = c; badge.style.display = c>0? 'inline-block':'none';
      } else {
        menu.innerHTML = '<li><span class="dropdown-item-text">Tidak ada notifikasi</span></li>';
        badge.style.display = 'none';
      }
    }

    function pollNotifs(){
      fetch('api/notifications.php').then(r=>r.json()).then(function(j){ renderNotifs(j); }).catch(()=>{});
    }

    var es = null, reconnectAttempts = 0, reconnectTimer = null, pollInterval = null;
    var statusEl = document.getElementById('sseStatus');
    function setSseStatus(s){
      if (!statusEl) return;
      if (s === 'connected') { statusEl.style.background = '#28a745'; statusEl.title = 'Realtime: connected'; }
      else if (s === 'polling') { statusEl.style.background = '#ffc107'; statusEl.title = 'Realtime: polling fallback'; }
      else if (s === 'reconnecting') { statusEl.style.background = '#fd7e14'; statusEl.title = 'Realtime: reconnecting'; }
      else if (s === 'error') { statusEl.style.background = '#dc3545'; statusEl.title = 'Realtime: error'; }
      else { statusEl.style.background = '#6c757d'; statusEl.title = 'Realtime: disabled'; }
    }

    function startPolling(){ if (pollInterval) return; pollNotifs(); pollInterval = setInterval(pollNotifs,15000); setSseStatus('polling'); }
    function stopPolling(){ if (!pollInterval) return; clearInterval(pollInterval); pollInterval = null; }

    function startSSE(){
      if (!window.EventSource) { startPolling(); return; }
      try {
        stopPolling();
        if (es) try{ es.close(); }catch(e){}
        es = new EventSource('api/notifications_sse.php');
        es.onopen = function(){ reconnectAttempts = 0; if (reconnectTimer) { clearTimeout(reconnectTimer); reconnectTimer = null; } setSseStatus('connected'); };
        es.onmessage = function(e){
          try { var d = JSON.parse(e.data); if (d.heartbeat) return; renderNotifs({notifications:[d]}); }
          catch(err){ /* ignore malformed */ }
        };
        es.onerror = function(){
          setSseStatus('error');
          try{ es.close(); }catch(e){}
          es = null;
          startPolling();
          reconnectAttempts++;
          var delay = Math.min(60000, 2000 * Math.pow(2, Math.max(0, reconnectAttempts-1)));
          setSseStatus('reconnecting');
          if (reconnectTimer) clearTimeout(reconnectTimer);
          reconnectTimer = setTimeout(function(){ startSSE(); }, delay);
        };
      } catch(err){ startPolling(); }
    }

    // init
    startSSE();

    // click handlers: mark single read when clicking a notif, and mark all
    document.addEventListener('click', function(e){
      var t = e.target;
      if (t && t.classList.contains('notif-item')) {
        var id = t.getAttribute('data-id');
        if (id) {
          var badgeEl = document.getElementById('notifBadge'); if (badgeEl) { badgeEl.style.display = 'none'; }
          var fd = new FormData(); fd.append('_csrf', document.querySelector('input[name="_csrf"]').value); fd.append('id', id);
          fetch('api/notifications_mark_read.php', {method:'POST', body:fd}).finally(function(){ try { pollNotifs(); } catch(e){} });
        }
      }
      if (t && t.id === 'markAllNotifs') {
        e.preventDefault();
        var fd = new FormData(); fd.append('_csrf', document.querySelector('input[name="_csrf"]').value); fd.append('all','1');
        fetch('api/notifications_mark_read.php', {method:'POST', body:fd}).then(function(){ pollNotifs(); });
      }
    });
    // no desktop-static behavior: always use Bootstrap offcanvas (overlay) so desktop matches mobile
  })();
</script>
