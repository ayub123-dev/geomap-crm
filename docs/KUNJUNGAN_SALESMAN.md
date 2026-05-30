# Modul: Kunjungan Salesman (GeoMap CRM)

Ringkasan
- Modul mobile-first PWA untuk tracking kunjungan salesman: login, dashboard target harian, check-in/check-out, upload foto, offline queue, master jadwal, dan prospek.

Quick Start
1. Jalankan migrasi database (MySQL):
   mysql -u root -p geomap_crm < migrations\mysql\003_kunjungan_module.sql
2. Letakkan modul di: modules/kunjungan_salesman/
3. Pastikan file service-worker.js dan manifest.json ada di root aplikasi (project root).

Struktur file penting
- modules/kunjungan_salesman/
  - login.html — Halaman login khusus modul (PWA aware)
  - dashboard.html — Dashboard salesman
  - checkin.html — Halaman check-in
  - master-jadwal.html — Manajemen jadwal
  - js/
    - auth.js — Auth client (token, API base handling, localStorage)
    - kunjungan.js — Logika dashboard & requests
    - checkin.js — Geolocation, foto, validasi jarak
    - master-jadwal.js — UI jadwal
  - css/kunjungan.css — Styles

- api/
  - access_setup.php — Endpoint auth (login action used by module)
  - salesman_targets.php — GET targets per tanggal
  - visit_checkin.php — POST/PUT checkin data
  - visit_upload_photo.php — POST upload foto kunjungan
  - visit_set_prospek.php — PUT set prospek
  - jadwal_kunjungan.php — CRUD master jadwal
  - prospek.php — Prospek CRUD

- app/Repositories/ (business/data access)
  - KunjunganRepository.php, JadwalKunjunganRepository.php, ProspekRepository.php
- app/Services/
  - KunjunganService.php, ProspekService.php

API Endpoints (ringkasan)
- POST /api/access_setup.php
  - body: { action: 'login', email, password }
  - response: { success:boolean, token?:string, user?:object, message?:string }
- GET /api/salesman_targets.php?date=YYYY-MM-DD
- POST /api/visit_checkin.php (checkin)
- PUT /api/visit_checkin.php (checkout/update)
- POST /api/visit_upload_photo.php (multipart/form-data)
- PUT /api/visit_set_prospek.php/{id}
- GET/POST/PUT/DELETE /api/jadwal_kunjungan.php

Front-end behavior & notes
- auth.js computes API base relative ke path sebelum /modules/ — penting saat app dijalankan di subpath (contoh: /geomap-crm).
- Token disimpan di localStorage key 'token'; user di 'user'. getUser() sudah aman (try/catch terhadap data korup).
- Service worker & manifest harus disajikan dari root aplikasi; login.html kini meng-inject manifest dan mendaftar service worker relatif ke base.
- Jika aplikasi return 404 HTML (mis. server error), browser akan gagal parse JSON ("Unexpected token '<'") — sering disebabkan request ke path yang salah.

Troubleshooting (panduan cepat)
1. Console menunjukkan: "Unexpected token '<'" saat response.json() — buka tab Network, lihat response body: biasanya HTML error/404. Periksa URL request.
2. Pastikan POST ke {APP_BASE}/api/access_setup.php bukan /api/access_setup.php. Jika salah, periksa window.location.pathname dan auth.js API base.
3. Jika manifest/service-worker 404: pastikan manifest.json dan service-worker.js ada di project root atau update path di login.html.
4. Error token/corrupted user: clear localStorage (localStorage.removeItem('user'); localStorage.removeItem('token')).
5. CORS/CSRF: modul PWA menggunakan API yang sama origin; pastikan Base URL sama. Untuk halaman index.php utama, CSRF token disediakan via meta/data attribute — API internal harus memverifikasi jika diperlukan.

Logging & Debugging
- Aktifkan PHP errors di development (display_errors=On) dan periksa Apache/PHP error log (XAMPP: apache\logs\error.log).
- Periksa aplikasi log jika tersedia (app/logs/ atau custom logger di Services).

Testing checklist
- [ ] Migrasi berhasil, tabel jadwal_kunjungan, kunjungan, prospek, visit_photos ada
- [ ] Login berhasil (POST /api/access_setup.php -> success + token)
- [ ] Dashboard menampilkan targets hari ini
- [ ] Checkin valid (jarak <= 200m) mengembalikan validasi_jarak=true
- [ ] Foto upload dan prospek create berhasil
- [ ] Offline queue sinkron saat koneksi kembali

Catatan pengembang
- Jika men-deploy di subpath, selalu jalankan hard reload dan clear site data setelah perubahan JS yang memanipulasi base path.
- Simpan contoh request/response (curl or Postman) saat melaporkan bug.

Referensi
- migrations/mysql/003_kunjungan_module.sql
- modules/kunjungan_salesman/README.md

--
Dokumentasi ini dibuat untuk memudahkan analisa bug dan pengembangan lebih lanjut.