# Prompt 5 - Spesifikasi Modul Master Data GeoMap CRM

## 1. Tujuan
Dokumen ini menjadi blueprint implementasi untuk seluruh modul master data GeoMap CRM pada stack saat ini:
- PHP 7.x native
- Bootstrap 5 + jQuery
- Leaflet + OpenStreetMap
- MySQL atau SQL Server

Dokumen ini fokus pada desain modul, struktur data, alur CRUD, import/export, RBAC, keamanan, dan rekomendasi implementasi enterprise.

## 2. Ruang Lingkup
Modul wajib:
1. Master Customer Inti
2. Master Customer Existing (Pembanding)
3. Modul Salesman
4. Modul User dan Role (RBAC)

Modul tambahan yang disarankan:
1. Modul Wilayah
2. Modul Target dan Realisasi
3. Modul Laporan dan Dashboard
4. Modul Notifikasi
5. Modul Import dan Export Terpusat
6. Audit Log

## 3. Standar Teknis Umum
1. Semua aksi tulis data wajib memakai transaksi database.
2. Semua form wajib punya CSRF token.
3. Semua password disimpan dengan `password_hash(..., PASSWORD_BCRYPT)`.
4. Semua route/modul harus lewat pemeriksaan permission sebelum render.
5. Semua perubahan data penting dicatat ke `audit_log`.
6. Semua import bulk harus punya mode preview sebelum commit.

## 4. Modul 1 - Master Customer Inti

### 4.1 Struktur Tabel
Nama tabel: `customer_inti`

Kolom:
- `id`
- `kode_customer`
- `nama_toko`
- `pemilik`
- `no_hp`
- `alamat`
- `kelurahan`
- `kecamatan`
- `kota`
- `provinsi`
- `lat`
- `lng`
- `kategori_toko`
- `omzet_estimasi`
- `salesman_id`
- `status` (`Aktif`, `NonAktif`)
- `foto_toko`
- `created_at`
- `updated_at`

Catatan:
- Jika saat ini masih memakai kolom lama (`customer_code`, `nama_usaha`, dll), buat migration mapping atau view kompatibilitas.
- Index wajib: `(lat, lng)`, `(salesman_id)`, `(status)`, `(nama_toko)`.

### 4.2 Fitur
1. CRUD lengkap.
2. Import Excel via PhpSpreadsheet.
3. Export Excel.
4. Export PDF.
5. Geocoding otomatis alamat ke koordinat saat `lat/lng` kosong.
6. Validasi duplikat berbasis nama + radius 50 meter.

### 4.3 Validasi Duplikat Radius 50 Meter
Aturan:
1. Normalisasi `nama_toko` ke lowercase dan trim.
2. Cek kandidat nama sama atau mirip.
3. Hitung jarak Haversine dari titik input ke data kandidat.
4. Jika jarak <= 50 meter maka tandai duplikat.

Rumus jarak Haversine (meter):
```sql
6371000 * ACOS(
  COS(RADIANS(:lat1)) * COS(RADIANS(lat)) *
  COS(RADIANS(lng) - RADIANS(:lng1)) +
  SIN(RADIANS(:lat1)) * SIN(RADIANS(lat))
)
```

### 4.4 Geocoding Otomatis (Nominatim)
Aturan operasional:
1. Geocoding dipanggil jika `lat` atau `lng` kosong.
2. Satu batch import harus berjalan bertahap dan rate-limited.
3. Simpan hasil geocode ke cache tabel `geocode_cache` untuk hemat request.
4. Jika geocode gagal, data tetap masuk dengan status `koordinat_pending`.

Contoh endpoint:
- `GET https://nominatim.openstreetmap.org/search?format=json&q={alamat}`

### 4.5 Import Excel
Kolom template import:
- `kode_customer`
- `nama_toko`
- `pemilik`
- `no_hp`
- `alamat`
- `kelurahan`
- `kecamatan`
- `kota`
- `provinsi`
- `lat`
- `lng`
- `kategori_toko`
- `omzet_estimasi`
- `salesman_id`
- `status`

Flow:
1. Upload file.
2. Baca header dan validasi mapping.
3. Tampilkan preview dan error per baris.
4. Jalankan geocoding batch jika koordinat kosong.
5. Jalankan pengecekan duplikat.
6. Simpan ke database jika user konfirmasi.

## 5. Modul 2 - Master Customer Existing (Pembanding)

### 5.1 Struktur Tabel
Nama tabel: `customer_existing`

Kolom:
- `id`
- `kode_existing`
- `nama_toko`
- `brand_kompetitor`
- `alamat`
- `lat`
- `lng`
- `sumber_data` (`Internal`, `Survei Lapangan`, `Import`)
- `catatan`
- `created_at`

Index:
- `(nama_toko)`
- `(brand_kompetitor)`
- `(lat, lng)`
- `(sumber_data, created_at)`

### 5.2 Fitur
1. CRUD lengkap.
2. Import Excel bulk.
3. Import CSV kompetitor.
4. Preview hasil parsing sebelum simpan.
5. Geocoding opsional jika koordinat kosong.

Flow import:
1. Upload file Excel/CSV.
2. Pilih mapping kolom jika header tidak standar.
3. Preview 100 baris pertama.
4. Validasi data wajib.
5. Commit simpan bertahap.

## 6. Modul 3 - Salesman

### 6.1 Struktur Tabel
Nama tabel: `salesman`

Kolom:
- `id`
- `nik`
- `nama`
- `no_hp`
- `email`
- `wilayah_id`
- `target_kunjungan_bulan`
- `foto`
- `status`
- `user_id`

Index:
- `(nik)` unik
- `(wilayah_id)`
- `(status)`
- `(user_id)` unik

### 6.2 Fitur
1. CRUD salesman.
2. Assign customer ke salesman.
3. Set target kunjungan bulanan.
4. Dashboard performa salesman.
5. Peta coverage area berbasis Leaflet heat-map.

### 6.3 Dashboard Salesman
Metrik:
1. Total customer terassign.
2. Realisasi kunjungan bulan berjalan.
3. Persentase pencapaian target.
4. Heat-map titik kunjungan.

Formula persentase:
```text
persentase_target = (realisasi_kunjungan / target_kunjungan_bulan) * 100
```

## 7. Modul 4 - User, Role, Permission (RBAC)

### 7.1 Tabel
Tabel:
- `users`
- `roles`
- `permissions`
- `role_user`
- `permission_role`

Kolom inti:
1. `users`: identitas user, email, password hash bcrypt, status.
2. `roles`: kode role, nama role, deskripsi.
3. `permissions`: kode permission granular per modul dan aksi.
4. `role_user`: relasi many-to-many user-role.
5. `permission_role`: relasi many-to-many role-permission.

### 7.2 Role Bawaan
Role:
- SUPER ADMIN
- ADMIN
- SUPERVISOR
- SALESMAN
- VIEWER

### 7.3 Permission Matrix Ringkas
1. SUPER ADMIN: full access + konfigurasi sistem + multi-perusahaan.
2. ADMIN: CRUD semua master, lihat semua laporan, manage salesman.
3. SUPERVISOR: area sendiri, approve prospek, assign target.
4. SALESMAN: akses mobile, customer sendiri, input kunjungan.
5. VIEWER: read-only peta dan laporan.

### 7.4 Middleware RBAC
Contract middleware:
```php
authorize($requiredPermission);
```

Flow:
1. Ambil user dari session.
2. Ambil role user.
3. Ambil permission dari role.
4. Cocokkan dengan permission route.
5. Jika gagal, kembalikan `403 Forbidden`.

### 7.5 Keamanan
1. Password hash: `PASSWORD_BCRYPT`.
2. Session native PHP dengan regenerasi session id setelah login.
3. CSRF token per form:
   - token dibuat saat render form
   - token diverifikasi saat submit
4. Batasi percobaan login berulang dengan throttling.

## 8. Modul Tambahan yang Disarankan

### 8.1 Modul Wilayah
Fungsi:
1. Master `provinsi > kota > kecamatan > kelurahan`.
2. Endpoint dropdown berantai untuk form customer.
3. Sinkronisasi referensi wilayah ke customer dan salesman.

### 8.2 Modul Target dan Realisasi
Fungsi:
1. Set target per salesman per bulan.
2. Tarik realisasi dari tabel kunjungan.
3. Tampilkan gap target vs realisasi.

### 8.3 Modul Laporan dan Dashboard
Fungsi:
1. Chart trend kunjungan dengan Chart.js.
2. Coverage map customer dan kunjungan.
3. Filter periode, area, salesman, kategori.

### 8.4 Modul Notifikasi
Fungsi:
1. Notifikasi in-app untuk prospek baru.
2. Integrasi WhatsApp API jika tersedia.
3. Status notifikasi: draft, queued, sent, failed.

### 8.5 Modul Import/Export Terpusat
Fungsi:
1. Satu layar untuk semua job import/export.
2. Riwayat job per modul.
3. Log error per baris.
4. Retry import gagal.

### 8.6 Audit Log
Fungsi:
1. Catat siapa mengubah data.
2. Catat kapan perubahan terjadi.
3. Catat field sebelum dan sesudah.
4. Catat sumber aksi (web/mobile/api).

## 9. Kontrak API Minimal per Modul
Endpoint pola:
1. `GET /api/{modul}.php` list + filter + paging
2. `GET /api/{modul}_detail.php?id={id}` detail
3. `POST /api/{modul}.php` create
4. `PUT /api/{modul}_detail.php?id={id}` update
5. `DELETE /api/{modul}_detail.php?id={id}` soft/hard delete sesuai aturan
6. `POST /api/import/{modul}.php` upload import
7. `POST /api/export/{modul}.php` export excel/pdf

## 10. Rekomendasi Struktur Folder Modul
```text
/modules/customer_inti
/modules/customer_existing
/modules/salesman
/modules/users
/modules/roles
/modules/permissions
/modules/wilayah
/modules/target_realisasi
/modules/laporan
/modules/notifikasi
/modules/import_export
```

## 11. Pustaka yang Direkomendasikan
1. PhpSpreadsheet untuk import/export Excel.
2. Dompdf atau mPDF untuk export PDF.
3. Chart.js untuk chart dashboard.
4. Leaflet.heat untuk heat-map coverage.

## 12. Kriteria Selesai (Definition of Done)
1. Semua modul CRUD berjalan dan terproteksi RBAC.
2. Import Excel/CSV punya preview dan validasi.
3. Export Excel/PDF berjalan untuk customer inti dan existing.
4. Geocoding otomatis bekerja untuk data tanpa koordinat.
5. Validasi duplikat radius 50m aktif saat create/import customer inti.
6. Dashboard salesman menampilkan KPI real-time.
7. Audit log mencatat semua perubahan data master.
