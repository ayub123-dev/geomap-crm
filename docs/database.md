# Database & Migration

## File Migration
- MySQL:
  - `migrations/mysql/001_initial.sql`
  - `migrations/mysql/002_master_data_modules.sql`
- SQL Server:
  - `migrations/sqlserver/001_initial.sql`
  - `migrations/sqlserver/002_master_data_modules.sql`
  - `migrations/sqlserver/003_mysql_compatible_schema.sql` *(opsional, bila ingin struktur yang lebih selaras dengan skema MySQL yang lengkap)*

## Aturan Kompatibilitas
MySQL:
- `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`
- Tipe yang dipakai termasuk `ENUM`, `TEXT`, `JSON`, `TIMESTAMP`

SQL Server:
- `ENUM` diganti `NVARCHAR(50)` + `CHECK CONSTRAINT`
- `AUTO_INCREMENT` diganti `IDENTITY(1,1)`
- `TIMESTAMP DEFAULT CURRENT_TIMESTAMP` diganti `DATETIME2 DEFAULT GETDATE()`
- `JSON` diganti `NVARCHAR(MAX)` + validasi `ISJSON(...)`
- `TEXT` diganti `NVARCHAR(MAX)`
- Semua string memakai `NVARCHAR` (Unicode)

## Tabel Utama
1. `provinces`, `cities`, `districts`, `villages`
2. `roles`, `permissions`, `role_permission`
3. `users`, `user_role`
4. `salesman`, `salesman_wilayah`
5. `customer_inti`
6. `customer_existing`
7. `customer_poi_nearby`
8. `kunjungan`, `kunjungan_foto`
9. `prospek_history`
10. `import_log`
11. `audit_log`
12. `notifikasi`

## Index Penting
- `customer_inti(lat, lng)`
- `kunjungan(salesman_id, tanggal_kunjungan)`
- `audit_log(user_id, created_at)`

## Step-by-step setup database

### 1. Siapkan driver dan ekstensi PHP
Pastikan aplikasi dapat terhubung ke database yang dipilih.

- Untuk MySQL:
  - Pastikan `pdo_mysql` aktif.
- Untuk SQL Server:
  - Pastikan `pdo_sqlsrv` aktif.
  - Jika Anda memakai Windows + XAMPP, pastikan ekstensi SQL Server sudah tersedia sesuai versi PHP.

### 2. Buat database
#### MySQL
```sql
CREATE DATABASE geomap_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### SQL Server
```sql
CREATE DATABASE geomap_crm;
GO
```

### 3. Konfigurasikan `.env`
Gunakan file `.env` di root aplikasi. Contoh:

#### MySQL
```env
DB_DRIVER="mysql"
DB_HOST="127.0.0.1"
DB_PORT="3306"
DB_NAME="geomap_crm"
DB_USER="root"
DB_PASS=""
DB_CHARSET="utf8mb4"
```

#### SQL Server
```env
DB_DRIVER="sqlsrv"
DB_HOST="127.0.0.1"
DB_PORT="1433"
DB_NAME="geomap_crm"
DB_USER="sa"
DB_PASS="your_password"
DB_ENCRYPT="false"
DB_TRUST_CERT="true"
```

> Jika Anda menggunakan `DB_DRIVER="sqlserver"`, aplikasi akan otomatis dipetakan ke `sqlsrv`.

### 4. Jalankan migrasi
#### MySQL
```bash
mysql -u root -p geomap_crm < migrations/mysql/001_initial.sql
mysql -u root -p geomap_crm < migrations/mysql/002_master_data_modules.sql
```

#### SQL Server
Gunakan urutan berikut:
```bash
sqlcmd -S localhost -d geomap_crm -U sa -P "your_password" -i migrations/sqlserver/001_initial.sql
sqlcmd -S localhost -d geomap_crm -U sa -P "your_password" -i migrations/sqlserver/002_master_data_modules.sql
```

#### SQL Server dengan struktur kompatibel MySQL
Jika Anda ingin skema SQL Server yang lebih selaras dengan struktur MySQL yang lengkap, jalankan file tambahan:
```bash
sqlcmd -S localhost -d geomap_crm -U sa -P "your_password" -i migrations/sqlserver/003_mysql_compatible_schema.sql
```

> `003_mysql_compatible_schema.sql` dirancang agar idempotent, sehingga aman dijalankan ulang.

### 5. Verifikasi koneksi dan skema
#### Cek apakah tabel sudah terbentuk
```sql
SELECT TABLE_NAME
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'geomap_crm';
```

#### Cek data default role/permission
```sql
SELECT TOP 5 code, name
FROM roles
ORDER BY id;

SELECT TOP 10 code, name, module
FROM permissions
ORDER BY id;
```

### 6. Jalankan aplikasi
Setelah migrasi sukses, akses aplikasi dari browser:

```text
http://localhost/geomap-crm/
```

Jika menggunakan installer, Anda juga dapat menjalankan:

```text
http://localhost/geomap-crm/install/index.php
```

### 7. Troubleshooting umum
- **Koneksi gagal**: pastikan `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS` sesuai dan proses server database sedang berjalan.
- **Error `Invalid object name`**: biasanya berarti migrasi belum dijalankan atau database tujuan berbeda dari `.env`.
- **Error `Cannot find driver`**: aktifkan ekstensi PDO yang sesuai di `php.ini`.
- **Role/permission kosong**: pastikan `002_master_data_modules.sql` (atau `003_mysql_compatible_schema.sql` bila memakai SQL Server kompatibel) sudah dieksekusi.

## Menjalankan Migration Secara Cepat
Contoh MySQL:
```bash
mysql -u root -p geomap_crm < migrations/mysql/001_initial.sql
mysql -u root -p geomap_crm < migrations/mysql/002_master_data_modules.sql
```

Contoh SQL Server (`sqlcmd`):
```bash
sqlcmd -S localhost -d geomap_crm -U sa -P "your_password" -i migrations/sqlserver/001_initial.sql
sqlcmd -S localhost -d geomap_crm -U sa -P "your_password" -i migrations/sqlserver/002_master_data_modules.sql




------ Query jika terjadi kendala dengan table - SQL Server

## Hapus Tabel
IF OBJECT_ID(N'dbo.customer_inti', N'U') IS NOT NULL
BEGIN
    DROP TABLE dbo.customer_inti;
END;
GO
```
## Jika muncul error karena masih ada foreign key dari tabel lain yang mengarah ke customer_inti, maka:

1. hapus foreign key terlebih dahulu,
2. baru drop table.

SELECT 
    fk.name AS foreign_key_name,
    tp.name AS parent_table
FROM sys.foreign_keys fk
INNER JOIN sys.tables tp 
    ON fk.parent_object_id = tp.object_id
WHERE fk.referenced_object_id = OBJECT_ID('dbo.customer_inti');


## Contoh hapus FK
ALTER TABLE dbo.customer_inti
DROP CONSTRAINT fk_kunjungan_customer;



## ADD COLUMN BARU
ALTER TABLE dbo.customer_inti
ADD
    kelurahan NVARCHAR(120) NULL,
    kecamatan NVARCHAR(120) NULL,
    kota NVARCHAR(120) NULL,
    provinsi NVARCHAR(120) NULL,
    foto_toko NVARCHAR(255) NULL;
---------------------

ceklist DB baru sql server:

permissions done
permission_role done
role_user done
target_realisasi done    
