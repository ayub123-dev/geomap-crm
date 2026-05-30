# API Documentation - Modul Kunjungan Salesman PWA

## Overview
REST API untuk GeoMap CRM - Modul Kunjungan Salesman (Mobile-First PWA)

**Base URL:** `/api`  
**Authentication:** JWT Bearer Token  
**Content-Type:** `application/json`

---

## Authentication

### Login
Autentikasi dengan email dan password untuk mendapatkan JWT token.

```
POST /access_setup.php
```

**Request Body:**
```json
{
  "action": "login",
  "email": "salesman@geomap.local",
  "password": "salesman123"
}
```

**Response:**
```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "name": "Salesman Name",
    "email": "salesman@geomap.local",
    "role": "salesman"
  }
}
```

**Status Codes:**
- `200 OK` - Login berhasil
- `401 Unauthorized` - Email/password salah
- `400 Bad Request` - Data tidak lengkap

---

## Salesman Targets

### Get Target Kunjungan
Ambil daftar target kunjungan untuk tanggal tertentu (default: hari ini)

```
GET /salesman_targets.php?date=YYYY-MM-DD
```

**Query Parameters:**
- `date` (optional): Format YYYY-MM-DD. Default: hari ini

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "date": "2026-05-29",
  "targets": [
    {
      "customer_inti_id": 1,
      "customer_id": 10,
      "name": "Toko ABC",
      "address": "Jl. Merdeka No. 123, Jakarta",
      "latitude": -6.2088,
      "longitude": 106.8456,
      "phone": "0812345678",
      "email": "toko@abc.com",
      "jadwal_kunjungan_id": 5,
      "kunjungan_hari_ini": null
    }
  ],
  "summary": [
    {
      "status": "checkin",
      "jumlah": 2
    },
    {
      "status": "checkout",
      "jumlah": 1
    }
  ],
  "total_target": 5,
  "total_completed": 1
}
```

**Status Codes:**
- `200 OK` - Success
- `401 Unauthorized` - Token invalid/expired
- `400 Bad Request` - Format date salah

---

## Kunjungan (Visit)

### Check-In Kunjungan
Melakukan check-in dengan validasi GPS (max 200 meter dari lokasi customer)

```
POST /visit_checkin.php
```

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "customer_id": 10,
  "gps_latitude": -6.2088,
  "gps_longitude": 106.8456,
  "customer_latitude": -6.2090,
  "customer_longitude": 106.8460
}
```

**Response Success:**
```json
{
  "success": true,
  "message": "Check-in berhasil",
  "kunjungan_id": 45,
  "validasi_jarak": true,
  "jarak_meter": 65
}
```

**Response Error (Distance Invalid):**
```json
{
  "success": false,
  "message": "Jarak GPS tidak valid. Anda 450m dari lokasi pelanggan (max 200m)",
  "jarak_meter": 450,
  "validasi_jarak": false
}
```

**Status Codes:**
- `200 OK` - Check-in berhasil
- `400 Bad Request` - Data tidak lengkap
- `401 Unauthorized` - Token invalid
- `422 Unprocessable Entity` - Jarak tidak valid atau sudah check-in hari ini

---

### Upload Foto Toko
Upload foto kondisi toko saat check-in

```
POST /visit_upload_photo.php
```

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Request Body (Form Data):**
- `kunjungan_id` (required): ID kunjungan dari check-in
- `photo` (required): File image (JPG, PNG, WebP max 5MB)

**Response:**
```json
{
  "success": true,
  "message": "Photo uploaded successfully",
  "photo_path": "assets/uploads/kunjungan/2026/05/visit_45_1685395200.jpg"
}
```

**Status Codes:**
- `200 OK` - Upload berhasil
- `400 Bad Request` - File tidak ada atau format tidak valid
- `413 Payload Too Large` - File size > 5MB
- `401 Unauthorized` - Token invalid

---

### Update Kondisi Toko
Update data kondisi toko, potensi, rating, catatan

```
PUT /visit_checkin.php
```

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "id": 45,
  "kondisi_toko": "Bersih, Ada Stock",
  "potensi_penjualan": "Tinggi",
  "catatan": "Toko sudah buka, pelayanan ramah",
  "rating": 5,
  "foto_toko_path": "assets/uploads/kunjungan/2026/05/visit_45_1685395200.jpg"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Kunjungan updated successfully"
}
```

---

### Get Daftar Kunjungan
Ambil daftar kunjungan salesman pada tanggal tertentu

```
GET /visit_checkin.php?date=YYYY-MM-DD
```

**Query Parameters:**
- `date` (optional): Format YYYY-MM-DD. Default: hari ini

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 45,
      "salesman_id": 5,
      "customer_id": 10,
      "customer_name": "Toko ABC",
      "tanggal_kunjungan": "2026-05-29",
      "waktu_checkin": "2026-05-29 09:15:00",
      "waktu_checkout": null,
      "gps_latitude": -6.2088,
      "gps_longitude": 106.8456,
      "jarak_meter": 65,
      "validasi_jarak": 1,
      "kondisi_toko": "Bersih",
      "potensi_penjualan": "Tinggi",
      "rating": 5,
      "status": "checkin"
    }
  ],
  "date": "2026-05-29"
}
```

---

## Jadwal Kunjungan (Master Schedule)

### Get Jadwal Kunjungan
Ambil jadwal kunjungan untuk salesman

```
GET /jadwal_kunjungan.php?salesman_id={id}&hari={hari}
```

**Query Parameters:**
- `salesman_id` (required): ID salesman
- `hari` (optional): Nama hari (Senin, Selasa, etc.) untuk filter hari tertentu

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "salesman_id": 3,
      "customer_id": 10,
      "customer_name": "Toko ABC",
      "address": "Jl. Merdeka No. 123",
      "latitude": -6.2088,
      "longitude": 106.8456,
      "phone": "0812345678",
      "hari_dalam_minggu": "Senin",
      "minggu_ke": null
    }
  ],
  "hari": "Senin"
}
```

---

### Buat Jadwal Kunjungan
Buat jadwal kunjungan mingguan atau untuk minggu tertentu

```
POST /jadwal_kunjungan.php
```

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "salesman_id": 3,
  "customer_id": 10,
  "hari_dalam_minggu": "Senin",
  "minggu_ke": null,
  "is_active": 1
}
```

**Parameters:**
- `salesman_id` (required): ID salesman
- `customer_id` (required): ID pelanggan
- `hari_dalam_minggu` (required): Senin, Selasa, Rabu, Kamis, Jumat, Sabtu, Minggu
- `minggu_ke` (optional): 1-4 untuk minggu tertentu, null = setiap minggu
- `is_active` (optional): 0 atau 1 (default: 1)

**Response:**
```json
{
  "success": true,
  "message": "Schedule created successfully"
}
```

---

### Update Jadwal Kunjungan
Update jadwal yang sudah ada

```
PUT /jadwal_kunjungan.php/{id}
```

**Request Body:**
```json
{
  "hari_dalam_minggu": "Selasa",
  "minggu_ke": 2,
  "is_active": 1
}
```

---

### Hapus Jadwal Kunjungan
Soft delete jadwal kunjungan (set is_active = 0)

```
DELETE /jadwal_kunjungan.php/{id}
```

---

## Prospek

### Set Prospek (Konversi dari Kunjungan)
Ubah kunjungan menjadi prospek dengan informasi tambahan

```
PUT /visit_set_prospek.php/{kunjungan_id}
```

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "nama_toko": "Toko Prospek XYZ",
  "alamat": "Jl. Ahmad Yani No. 456, Jakarta",
  "latitude": -6.2088,
  "longitude": 106.8456,
  "estimasi_omzet": "50-100juta/bulan",
  "kategori_produk": "Elektronik, Mainan, Perlengkapan Rumah",
  "prioritas": "A",
  "catatan": "Calon pelanggan dengan potensi tinggi",
  "foto_toko_path": "assets/uploads/kunjungan/2026/05/visit_45_1685395200.jpg"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Prospek berhasil dibuat"
}
```

---

### Get Detail Prospek
Ambil detail prospek berdasarkan ID

```
GET /prospek.php/{id}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 12,
    "kunjungan_id": 45,
    "customer_id": null,
    "nama_toko": "Toko Prospek XYZ",
    "alamat": "Jl. Ahmad Yani No. 456, Jakarta",
    "latitude": -6.2088,
    "longitude": 106.8456,
    "estimasi_omzet": "50-100juta/bulan",
    "kategori_produk": "Elektronik, Mainan",
    "prioritas": "A",
    "status": "prospek",
    "salesman_id": 5,
    "created_at": "2026-05-29 10:30:00"
  }
}
```

---

### Get Daftar Prospek
Ambil daftar prospek dengan filter

```
GET /prospek.php?status={status}&prioritas={prioritas}
```

**Query Parameters:**
- `status` (optional): prospek, qualified, aktif, customer_inti
- `prioritas` (optional): A, B, C

**Response:**
```json
{
  "success": true,
  "data": [...],
  "filters": {
    "status": "prospek",
    "prioritas": "A"
  }
}
```

---

### Update Status Prospek
Ubah status prospek (prospek → qualified → aktif → customer_inti)

```
PUT /prospek.php/{id}
```

**Request Body:**
```json
{
  "status": "qualified"
}
```

---

## Error Handling

### Standard Error Response
```json
{
  "success": false,
  "message": "Deskripsi error",
  "code": "ERROR_CODE"
}
```

### HTTP Status Codes
- `200 OK` - Request berhasil
- `201 Created` - Resource berhasil dibuat
- `204 No Content` - Success tanpa response body
- `400 Bad Request` - Data tidak valid
- `401 Unauthorized` - Token tidak valid/expired
- `403 Forbidden` - Akses ditolak
- `404 Not Found` - Resource tidak ditemukan
- `422 Unprocessable Entity` - Validasi logic gagal (contoh: jarak GPS)
- `429 Too Many Requests` - Rate limit exceeded
- `500 Internal Server Error` - Server error
- `503 Service Unavailable` - Service sedang maintenance

---

## Rate Limiting
- **Limit:** 100 requests per 15 minutes per user
- **Headers Response:**
  ```
  X-RateLimit-Limit: 100
  X-RateLimit-Remaining: 75
  X-RateLimit-Reset: 1685395200
  ```

---

## Offline Support

Aplikasi mobile PWA ini mendukung offline mode dengan caching otomatis:

1. **API Caching:** Semua API response di-cache oleh Service Worker
2. **Background Sync:** Data yang di-submit saat offline akan di-sync otomatis ketika online
3. **Offline Queue:** Pending requests disimpan di IndexedDB

---

## Examples

### Complete Check-In Flow

```javascript
// 1. Get targets
const targets = await fetch('/api/salesman_targets.php', {
  headers: { 'Authorization': 'Bearer ' + token }
}).then(r => r.json());

// 2. Get GPS location
navigator.geolocation.getCurrentPosition(pos => {
  const { latitude, longitude } = pos.coords;

  // 3. Check-in
  const checkin = await fetch('/api/visit_checkin.php', {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer ' + token,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      customer_id: 10,
      gps_latitude: latitude,
      gps_longitude: longitude,
      customer_latitude: target.latitude,
      customer_longitude: target.longitude
    })
  }).then(r => r.json());

  // 4. Upload photo
  const formData = new FormData();
  formData.append('kunjungan_id', checkin.kunjungan_id);
  formData.append('photo', photoFile);

  await fetch('/api/visit_upload_photo.php', {
    method: 'POST',
    headers: { 'Authorization': 'Bearer ' + token },
    body: formData
  });

  // 5. Update kondisi toko
  await fetch('/api/visit_checkin.php', {
    method: 'PUT',
    headers: {
      'Authorization': 'Bearer ' + token,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      id: checkin.kunjungan_id,
      kondisi_toko: 'Bersih',
      potensi_penjualan: 'Tinggi',
      rating: 5
    })
  });
});
```

---

## Version History

- **v1.0** (2026-05-29) - Initial release
  - Check-in dengan validasi GPS
  - Photo upload
  - Master jadwal kunjungan
  - Prospek management
  - Offline support dengan Service Worker
