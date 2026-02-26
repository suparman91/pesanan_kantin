# Pesanan Kantin (Proyek Demo)

Panduan singkat untuk menjalankan demo sistem pemesanan kantin berbasis PHP + MySQL (mysqli).

1. Letakkan folder `pesanan_kantin` di `htdocs` XAMPP Anda.
2. Buat database MySQL bernama `pesanan_kantin` atau ubah `config.php` sesuai nama DB.
3. Import schema atau jalankan `setup.php` untuk membuat tabel dan akun admin default.

Jika database belum dibuat, jalankan dulu `create_database.php` di browser:

```bash
http://localhost/pesanan_kantin/create_database.php
```

Kemudian jalankan `setup.php` untuk membuat tabel dan akun admin.

URLs contoh setelah XAMPP berjalan:
- Login: http://localhost/pesanan_kantin/login.php
- Dashboard: http://localhost/pesanan_kantin/dashboard.php

API endpoints (require login session):
- `api/orders.php` — daftar pesanan (supplier melihat pesanan pending saja)
- `api/suppliers.php` — daftar supplier (hanya untuk admin/hrd)

Upload logo:
- Admin bisa upload logo lewat halaman `users.php` (tombol "Upload Logo"). File disimpan di `uploads/site_logo.png`.

Security:
- Semua form sekarang memakai token CSRF sederhana; jika token tidak valid, form akan ditolak.

Default admin (setelah menjalankan `setup.php`):
- email: admin@kantin.local
- password: admin123

File penting:
- `config.php` — konfigurasi koneksi MySQL
- `setup.php` — membuat tabel awal dan akun admin
- `login.php`, `logout.php` — autentikasi
- `dashboard.php`, `orders.php`, `suppliers.php`, `users.php` — halaman utama

Catatan keamanan: Ini adalah contoh demo. Untuk produksi, gunakan HTTPS, proteksi CSRF, validasi input menyeluruh, dan pengelolaan password yang lebih ketat.
