** Copy dari map.js
function buildPopupContent(type, item, extra) {
        var title = type === 'inti' ? (item.nama_toko || '-') : (item.nama_toko || '-');
        var addr = item.alamat || '-';
        var salesman = '-';
        if (type === 'inti' && item.salesman_id) {
            var s = salesmenMap[item.salesman_id];
            salesman = s ? (s.nama || s.nik || s.id) : item.salesman_id;
        }
        var dist = extra && extra.distance_meters ? '<div class="badge bg-danger">OVERLAP RISK</div><div class="small mt-1">Jarak: ' + (extra.distance_meters || 0) + ' m</div>' : '';
        return '<div><strong>' + window.GeoMapCRM.escapeHtml(title) + '</strong>' +
            '<div class="small text-muted">' + window.GeoMapCRM.escapeHtml(addr) + '</div>' +
            '<div class="small">Salesman PIC: ' + window.GeoMapCRM.escapeHtml(salesman) + '</div>' + dist + '</div>';
    }


 --------

 Saya akan memeriksa struktur tabel MySQL yang sudah ada dan membandingkan dengan migration SQL Server yang tersedia agar saya dapat membuat file SQL Server yang setara dan konsisten.

Saya menyesuaikan path pembacaan file agar sesuai lokasi workspace dan melanjutkan dengan membaca migrasi yang sudah ada.

Saya telah menemukan bahwa migrasi SQL Server yang ada belum mencakup seluruh struktur MySQL yang jauh lebih lengkap. Sekarang saya akan menelusuri file migrasi MySQL yang tersedia untuk memastikan skema target yang harus dijadikan acuan.

Saya sedang memverifikasi bagaimana aplikasi mengisi kolom updated_at dan created_at, supaya migrasi SQL Server tidak hanya cocok secara struktur tetapi juga konsisten dengan cara aplikasi bekerja.

Saya akan menambahkan satu file SQL Server baru yang berfokus pada skema kompatibel dengan migrasi MySQL saat ini, termasuk tabel keamanan/role, master data, dan tabel operasional yang dibutuhkan aplikasi.   

File baru sudah dibuat. Saya sedang melakukan pemeriksaan akhir pada isi file supaya nama, struktur tabel, dan blok seed sesuai dengan skema yang diharapkan.

✅ Perubahan selesai
Saya telah menambahkan file migrasi SQL Server baru di 003_mysql_compatible_schema.sql.

Isi file
Skema master wilayah: provinces, cities, districts, villages
Tabel autentikasi/otorisasi: roles, permissions, users, role_user, permission_role
Tabel utama bisnis: salesman, customer_inti, customer_existing
Tabel operasional tambahan: salesman_wilayah, customer_poi_nearby, kunjungan, kunjungan_foto, prospek_history
Tabel pendukung: target_realisasi, import_log, audit_log, notifikasi
Seed data default untuk roles dan permissions
Script dibuat agar lebih aman dijalankan berulang (idempotent)
Verifikasi
File sudah berhasil dibuat dan saya memverifikasi isinya lewat pembacaan file.
Saya belum menjalankan script terhadap SQL Server karena tidak ada server/CLI SQL Server yang diverifikasi di workspace.

Cara pakai
Jalankan file ini pada database SQL Server tujuan, misalnya:

Jika kamu mau, saya bisa juga menambahkan/update dokumentasi agar file baru ini disebut sebagai migrasi utama SQL Server.
-------  

Implementasi Modul User & Role + RBAC middleware + CSRF sudah saya pasang end-to-end.

Yang sudah dikerjakan
1. Modul baru User & Role (UI + JS):modules/ 
* users_roles/index.php
* assets/js/users_roles.js

RBAC sebelum render modul + login/logout session native:
*index.php
*Sekarang tiap modul dicek permission dulu sebelum dirender.
*Ditambah login form (pakai Auth::login) dan logout.
*Session regeneration tetap dipakai dari Auth

CSRF token:
*Validasi CSRF terpusat untuk semua mutasi API (POST/PUT/DELETE) di api/bootstrap.php
*Header AJAX CSRF global di assets/js/app.js
*Hidden _csrf_token ditambahkan ke form modul:modules/customer_inti/index.php
modules/customer_existing/index.php
modules/customers/index.php
modules/salesman/index.php
modules/users_roles/index.php

Form installer juga saya CSRF-protect:
*install/index.php

Hardening auth/RBAC:Default guest superadmin dimatikan (kecuali env ALLOW_GUEST_SUPERADMIN=true) di app/Core/Auth.php
Bootstrap otomatis coba seed super admin di app/bootstrap.php
Rbac::authorize sekarang melempar status 403 di app/Core/Rbac.php

API permission handling + status code:Endpoint legacy yang belum dijaga RBAC sudah diproteksi (customers/customer/markers/proximity/proximity_export/diagnostic).
Semua catch error API sekarang map status dari exception (403 tetap 403, bukan 500).

Seed role bawaan + permission matrix:migrations/mysql/002_master_data_modules.sql
migrations/sqlserver/002_master_data_modules.sql

Fix penting di update user role:Perbaikan agar update user tidak salah overwrite role saat role_ids tidak dikirim:app/Services/UserRoleService.php

Verifikasi

php -l seluruh file PHP: lolos (PHP LINT OK).
Catatan pakai

Setelah migrasi, login default tetap:
username: admin
password: admin123
Next step yang saya sarankan:

Jalankan migration 002_master_data_modules.sql sesuai DB driver aktif.
Login sebagai admin, buka modul User & Role, lalu cek matrix permission tiap role di UI.

-----------------------
Perbaikan sudah saya lakukan, akar masalahnya ada di struktur modal modal-content > form pada mode modal-dialog-scrollable.
modal-body jadi terlalu dominan di mobile, sehingga modal-footer (tombol Simpan / Batal) terdorong keluar layar.
Yang saya perbaiki
Menormalkan layout form modal agar jadi flex-column.
Memastikan modal-body yang scroll, bukan seluruh konten mendorong footer.
Membuat modal-footer sticky di bagian bawah modal khusus mobile.
Menjaga tinggi form modal tetap aman di viewport mobile (100dvh).
Perubahan di:
app.css
Bagian penting yang ditambahkan:
.modal-content > form { display:flex; flex-direction:column; ... }
.modal-content > form .modal-body { flex:1; overflow-y:auto; ... }
.modal-content > form .modal-footer { position:sticky; bottom:0; ... } (mobile)
Silakan hard refresh di HP (clear cache browser) lalu cek lagi modal tambah/edit di semua modul.
Kalau masih ada 1 modal yang belum muncul tombolnya, kirim nama modul + screenshot terbaru, saya lanjutkan fix spesifik modul itu.