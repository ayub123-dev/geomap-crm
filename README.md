# GeoMap CRM

GeoMap CRM adalah aplikasi enterprise web berbasis native PHP 7.x untuk manajemen customer dengan kemampuan geospasial menggunakan Leaflet + OpenStreetMap.

## Tech Stack

- Backend: PHP 7.x native (tanpa framework)
- Frontend: HTML5, Bootstrap 5, Vanilla JS + jQuery
- Peta: Leaflet.js + OpenStreetMap tiles
- Database: MySQL atau SQL Server (`sqlsrv`) via PDO
- API: REST JSON endpoint pada folder `/api`

## Struktur Folder

```text
/geomap-crm
  /app
  /config
  /modules
  /assets
  /api
  /migrations
```

## Instalasi Singkat

1. Buka `http://localhost/geomap-crm/install/index.php`
2. Pilih driver database: `mysql` atau `sqlsrv`
3. Isi parameter koneksi database
4. Simpan konfigurasi untuk menghasilkan file `.env`
5. Jalankan migration SQL sesuai driver:
   - MySQL: `migrations/mysql/001_initial.sql`
   - SQL Server: `migrations/sqlserver/001_initial.sql`
6. Akses aplikasi di `http://localhost/geomap-crm/index.php`

## Endpoint API

- `GET /api/customers.php`
- `POST /api/customers.php`
- `GET /api/customer.php?id={id}`
- `PUT /api/customer.php?id={id}`
- `DELETE /api/customer.php?id={id}`
- `GET /api/markers.php`

## Konfigurasi Driver Database

Driver database ditentukan oleh environment variable `DB_DRIVER` di file `.env`:

```env
DB_DRIVER="mysql"
```

atau

```env
DB_DRIVER="sqlsrv"
```
