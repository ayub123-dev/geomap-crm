# Setup & Instalasi

## Prasyarat
- PHP 7.x dengan extension:
  - `pdo`
  - `pdo_mysql` (untuk MySQL)
  - `pdo_sqlsrv` (untuk SQL Server)
- Web server (contoh: Apache/XAMPP)
- MySQL 5.7+/8.x atau SQL Server 2019+

## Langkah Instalasi
1. Simpan project di web root (contoh: `C:\xampp\htdocs\geomap-crm`).
2. Buka installer di `http://localhost/geomap-crm/install/index.php`.
3. Pilih `DB_DRIVER`:
   - `mysql`
   - `sqlsrv` (atau `sqlserver`, otomatis dipetakan ke `sqlsrv`)
4. Isi koneksi database dan simpan konfigurasi.
5. Jalankan migration sesuai database (urut):
   - MySQL:
     - `migrations/mysql/001_initial.sql`
     - `migrations/mysql/002_master_data_modules.sql`
   - SQL Server:
     - `migrations/sqlserver/001_initial.sql`
     - `migrations/sqlserver/002_master_data_modules.sql`
6. Akses aplikasi di `http://localhost/geomap-crm/index.php`.

## Konfigurasi Environment
Contoh `.env`:

```env
APP_NAME="GeoMap CRM"
APP_TIMEZONE="Asia/Jakarta"

DB_DRIVER="mysql"
DB_HOST="127.0.0.1"
DB_PORT="3306"
DB_NAME="geomap_crm"
DB_USER="root"
DB_PASS=""
DB_CHARSET="utf8mb4"

DB_ENCRYPT="false"
DB_TRUST_CERT="true"
```

## Catatan Konfigurasi
- `DB_DRIVER="sqlserver"` akan tetap diarahkan ke driver PDO `sqlsrv`.
- Opsi PDO default:
  - `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`
  - `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`
  - `PDO::ATTR_EMULATE_PREPARES => false`
