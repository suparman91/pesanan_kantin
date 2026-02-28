# Pesanan Kantin (Proyek Demo)

Panduan singkat untuk menjalankan demo sistem pemesanan kantin berbasis PHP + MySQL (mysqli).

Quick start (full):
1. Pastikan XAMPP/Apache + MySQL berjalan.
2. Salin folder `pesanan_kantin` ke `htdocs` Anda.
3. Buka `http://localhost/pesanan_kantin/create_database.php` (jika database belum ada).
4. Buka `http://localhost/pesanan_kantin/setup.php` untuk membuat tabel dan akun admin.
5. Login: `http://localhost/pesanan_kantin/login_karyawan` (default admin: admin@kantin.local / admin123).

Main files & flow
- `index.php` — router friendly URLs (contoh `/login_karyawan`, `/dashboard`).
- `config.php` — konfigurasi koneksi MySQL (sesuaikan bila perlu).
- `setup.php` — buat tabel awal dan akun admin.
- `create_database.php` — buat database jika belum ada.
- `includes/*` — header, sidebar, footer, CSRF helper.

AJAX & API
- `api/orders.php` — daftar pesanan (supplier melihat pending saja).
- `api/suppliers.php` — daftar supplier (admin/hrd only).
- `api/users.php` — daftar pengguna (admin/hrd only).
- `api/create_order.php` — create order (POST, csrf).
- `api/create_supplier.php` — create supplier (POST, csrf, admin/hrd).
- `api/create_user.php` — create user (POST, csrf, admin/hrd).
- `api/notifications.php` — returns pending orders count for topbar badge.

Frontend behaviour
- DataTables load data via AJAX from the `api/*.php` endpoints.
- Create forms (orders/suppliers/users) submit via AJAX to corresponding `create_*` endpoints and refresh the DataTables.
- CSRF tokens are included in forms using the helper `includes/csrf.php`.
- Upload logo: admin → `Users` → `Upload Logo` (stored in `uploads/site_logo.png`). After upload the logo appears in the sidebar/topbar.

Security & deployment notes
- This is a demo scaffold; do not use in production as-is.
- Use HTTPS in production.
- Harden DB credentials and avoid root/default passwords.
- Consider adding rate limiting, stronger input validation, and CSRF rotation.
- To enable friendly URLs ensure Apache has `mod_rewrite` enabled and the site directory allows `.htaccess` (AllowOverride All).

Troubleshooting
- "No connection could be made" — verify MySQL is reachable and credentials in `config.php`.
- Session notices — `config.php` and includes use `session_status()` checks to avoid duplicate session_start.

Next steps you can ask me to implement:
- AJAX login (login without full-page reload).
- Supplier action: claim/accept orders with assignment to supplier and status changes.
- Styling tweaks: custom theme colors, icon sets, or animations.
