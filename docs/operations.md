# Operasional & Troubleshooting

## Praktik Operasional
- Jalankan migration awal hanya sekali per environment.
- Untuk perubahan schema berikutnya, buat migration baru berurutan:
  - `002_...sql`
  - `003_...sql`
- Jangan ubah migration lama yang sudah dipakai production.

## Troubleshooting

### Koneksi Database Gagal
- Cek `DB_DRIVER`, host, port, username, password.
- Pastikan extension PDO sesuai driver aktif.

### SQL Server Tidak Bisa Connect
- Pastikan `pdo_sqlsrv` terpasang dan aktif.
- Cek nilai `DB_ENCRYPT` dan `DB_TRUST_CERT`.
- Pastikan SQL Server menerima koneksi TCP/IP di port yang dipakai.

### Peta Tidak Tampil
- Cek koneksi internet (CDN Bootstrap, Leaflet, OSM).
- Pastikan endpoint `/api/markers.php` bisa diakses.
- Periksa apakah ada data customer dengan `lat`/`lng`.
