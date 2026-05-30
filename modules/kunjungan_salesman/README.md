# Modul KUNJUNGAN SALESMAN - GeoMap CRM PWA

Aplikasi mobile-first Progressive Web App (PWA) untuk tracking kunjungan salesman dengan fitur GPS, offline support, dan workflow prospek.

## 📋 Fitur Utama

### 1. **LOGIN & AUTENTIKASI** 🔐
- JWT token-based authentication
- Token disimpan di localStorage
- Auto-logout on token expiration
- Demo credentials built-in

### 2. **DASHBOARD SALESMAN** 📊
- Daftar target kunjungan harian (dari Master Jadwal)
- Statistik real-time (target, selesai, progress, sisa)
- Mini map dengan rute (Leaflet JS)
- Tombol navigasi cepat ke Google Maps

### 3. **CHECK-IN KUNJUNGAN** 📍
- **Geolocation API** untuk ambil GPS koordinat real-time
- Validasi jarak otomatis (max 200m dari customer)
- Anti-fraud: jika jarak > 200m akan show warning
- Form data kunjungan:
  - Kondisi toko
  - Potensi penjualan
  - Rating 1-5 bintang
  - Catatan
- **Upload foto** langsung dari kamera atau galeri
- Support offline checkin dengan queue

### 4. **KONVERSI TOKO PROSPEK** ⭐
- Tombol "Jadikan Toko Prospek" di halaman detail kunjungan
- Form tambahan:
  - Estimasi omzet
  - Kategori produk
  - Prioritas (A/B/C)
- Status workflow otomatis: **PROSPEK** → **QUALIFIED** → **AKTIF** → **CUSTOMER INTI**
- Track prospek per salesman

### 5. **TAMBAH CUSTOMER BARU** ➕
- Form singkat: nama toko, alamat, foto
- GPS otomatis terisi dari lokasi saat ini
- Status: DRAFT (menunggu approval supervisor)
- Bisa diconvert ke customer inti setelah qualified

### 6. **MASTER JADWAL KUNJUNGAN** 📅
- Setup jadwal mingguan dan harian per salesman
- **Pilihan frekuensi:**
  - ✓ **Setiap Minggu** - kunjungan rutin setiap minggu
  - ✓ **Minggu Tertentu** - kunjungan minggu ke 1/2/3/4 saja
  - ✓ **Hari Tertentu** - pilih Senin-Minggu
- UI dengan tab per hari (Mon-Sun)
- Check/uncheck button untuk multiple selection
- CRUD operasi (Create, Read, Update, Delete)
- Validasi: tidak boleh ada duplikat salesman-customer-hari-minggu

## 🏗️ Struktur Database

### Tabel: `jadwal_kunjungan`
Master jadwal kunjungan yang configurable oleh supervisor
```
- id (PK)
- salesman_id (FK)
- customer_id (FK)
- hari_dalam_minggu (Senin-Minggu)
- minggu_ke (1-4 atau NULL untuk setiap minggu)
- is_active (boolean)
- created_at, updated_at
```

### Tabel: `kunjungan`
Record setiap kunjungan salesman
```
- id (PK)
- salesman_id, customer_id (FK)
- jadwal_kunjungan_id (FK - reference ke master)
- tanggal_kunjungan (DATE)
- waktu_checkin, waktu_checkout (DATETIME)
- gps_latitude, gps_longitude (koordinat salesman saat checkin)
- customer_latitude, customer_longitude (koordinat customer)
- jarak_meter (distance calculation result)
- validasi_jarak (boolean: 1 jika <= 200m)
- kondisi_toko, potensi_penjualan, catatan, rating
- foto_toko_path (path ke foto)
- status (draft, checkin, checkout, prospek, converted)
```

### Tabel: `prospek`
Data prospek dari kunjungan
```
- id (PK)
- kunjungan_id (FK)
- customer_id (FK - NULL jika belum customer)
- nama_toko, alamat
- latitude, longitude (lokasi prospek)
- estimasi_omzet, kategori_produk
- prioritas (A/B/C)
- status (prospek, qualified, aktif, customer_inti)
- foto_toko_path
- created_at, updated_at
```

### Tabel: `visit_photos`
Multi-photo support per kunjungan
```
- id (PK)
- kunjungan_id (FK)
- photo_path
- foto_type (toko, produk, display, dll)
- created_at
```

### Tabel: `visit_gps_audit`
Audit trail GPS untuk fraud detection
```
- id (PK)
- kunjungan_id (FK)
- latitude, longitude, accuracy
- timestamp
```

## 🔌 API Endpoints

### Authentication
```
POST /api/access_setup.php
```

### Salesman Targets
```
GET /api/salesman_targets.php?date=YYYY-MM-DD
```

### Visit Checkin
```
POST /api/visit_checkin.php
PUT /api/visit_checkin.php
GET /api/visit_checkin.php?date=YYYY-MM-DD
```

### Visit Photo
```
POST /api/visit_upload_photo.php
```

### Set Prospek
```
PUT /api/visit_set_prospek.php/{kunjungan_id}
```

### Master Jadwal Kunjungan
```
GET /api/jadwal_kunjungan.php?salesman_id={id}&hari={hari}
POST /api/jadwal_kunjungan.php
PUT /api/jadwal_kunjungan.php/{id}
DELETE /api/jadwal_kunjungan.php/{id}
```

### Prospek
```
GET /api/prospek.php
GET /api/prospek.php/{id}
PUT /api/prospek.php/{id}
DELETE /api/prospek.php/{id}
```

Lihat [API_KUNJUNGAN_SALESMAN.md](../docs/API_KUNJUNGAN_SALESMAN.md) untuk detail lengkap.

## 🚀 PWA Features

### Service Worker
- **Cache Strategy:** Network-first untuk API, Cache-first untuk assets
- **Offline Support:** Fallback ke cache saat offline
- **Background Sync:** Auto-retry pending requests saat online
- **IndexedDB:** Store pending requests untuk offline queue

### Manifest
- Installable di Android/iOS tanpa App Store
- Standalone mode (fullscreen, no address bar)
- Custom splash screen & icons
- Home screen shortcuts untuk quick access

### Offline Capabilities
- ✓ Buka halaman tanpa internet
- ✓ Check-in & submit form saat offline
- ✓ Auto-sync saat online
- ✓ GPS tetap berfungsi offline
- ✓ Geolocation API bekerja offline

## 📱 Mobile Optimization

### Responsive Design
- Mobile-first design
- Touch-optimized buttons (min 44px)
- Viewport configuration
- Safe area support untuk notch

### Performance
- Lazy loading images
- Code splitting
- Minified CSS/JS
- Image optimization (WebP support)

### User Experience
- Loading spinners
- Progress indicators
- Toast notifications
- Smooth transitions
- Dark mode support

## 🔒 Security

- **JWT Tokens:** Secure authentication
- **HTTPS Only:** Enforce HTTPS in production
- **CORS:** Proper CORS headers
- **Input Validation:** Server-side validation untuk semua input
- **File Upload:** Validation tipe file & size limit
- **GPS Validation:** Anti-fraud dengan jarak check

## 📊 Workflow

### Kunjungan Workflow
```
1. Dashboard Salesman
   ↓ (lihat target dari master jadwal)
   
2. Check-In Kunjungan
   ├─ Ambil GPS koordinat
   ├─ Validasi jarak (≤200m)
   ├─ Upload foto
   ├─ Isi kondisi toko & catatan
   └─ Status: CHECKIN
   
3. Detail Kunjungan
   ├─ Lihat data check-in
   ├─ Edit catatan
   ├─ Upload foto tambahan
   └─ Button "Jadikan Prospek"
   
4. Konversi Prospek
   └─ Isi estimasi omzet, kategori, prioritas
   └─ Status: PROSPEK
   
5. Follow-up Prospek
   ├─ Edit status (PROSPEK → QUALIFIED → AKTIF)
   ├─ Ubah prioritas
   └─ Akhirnya: CUSTOMER INTI
```

### Master Jadwal Workflow
```
Supervisor/Admin
   ↓
Buka Master Jadwal Kunjungan
   ↓
Pilih Salesman + Customer
   ↓
Pilih Hari (Senin-Minggu)
   ↓
Pilih Frekuensi
   ├─ Setiap Minggu
   └─ Minggu Tertentu (1/2/3/4)
   ↓
Simpan
   ↓
Jadwal Aktif
   ↓
Salesman lihat di Dashboard → Target Hari Ini
```

## 🛠️ Installation & Setup

### 1. Database Migration
```bash
mysql -u root -p geomap_crm < migrations/mysql/003_kunjungan_module.sql
```

### 2. File Structure
```
/modules/kunjungan_salesman/
├── login.html
├── dashboard.html
├── checkin.html
├── detail.html
├── master-jadwal.html
├── prospek.html
├── js/
│   ├── auth.js
│   ├── kunjungan.js
│   ├── checkin.js
│   └── master-jadwal.js
└── css/
    └── kunjungan.css

/api/
├── visit_checkin.php
├── visit_upload_photo.php
├── visit_set_prospek.php
├── salesman_targets.php
├── jadwal_kunjungan.php
└── prospek.php

/app/Repositories/
├── KunjunganRepository.php
├── JadwalKunjunganRepository.php
└── ProspekRepository.php

/app/Services/
├── KunjunganService.php
└── ProspekService.php
```

### 3. Service Worker & PWA
- `manifest.json` - PWA manifest
- `service-worker.js` - Service worker logic
- `service-worker-init.js` - Service worker registration

## 🔍 Testing

### Test Checkin dengan GPS Valid
```javascript
// Ambil GPS salesman
const salesman = {
  lat: -6.2088,
  lng: 106.8456
};

// Customer lokasi
const customer = {
  lat: -6.2090,
  lng: 106.8460
};

// Jarak: ~65 meter (VALID)
POST /api/visit_checkin.php {
  customer_id: 10,
  gps_latitude: -6.2088,
  gps_longitude: 106.8456,
  customer_latitude: -6.2090,
  customer_longitude: 106.8460
}
```

### Test Checkin dengan GPS Invalid
```javascript
// Jarak: ~2500 meter (INVALID)
POST /api/visit_checkin.php {
  customer_id: 10,
  gps_latitude: -6.1500,
  gps_longitude: 106.8000,
  customer_latitude: -6.2090,
  customer_longitude: 106.8460
}
// Response: validasi_jarak = false, jarak_meter = 2500
```

## 📚 Documentation

- [API_KUNJUNGAN_SALESMAN.md](../docs/API_KUNJUNGAN_SALESMAN.md) - Complete API docs
- [Database Schema](../docs/database.md) - Database structure
- [Setup Guide](../docs/setup.md) - Installation steps

## 🐛 Troubleshooting

### GPS tidak terdeteksi
- Check browser permission untuk location
- Pastikan HTTPS active (Geolocation needs HTTPS)
- Coba GPS di area terbuka, hindari indoor

### Service Worker tidak register
- Check browser support (Chrome, Firefox, Edge)
- Pastikan HTTPS active
- Clear browser cache dan service worker cache

### Offline data tidak ter-sync
- Check browser support untuk Background Sync
- Pastikan connectivity restored
- Check IndexedDB untuk pending data

## 📄 License
Proprietary - GeoMap CRM 2026

## 👥 Support
Tim Development GeoMap CRM

---

**Version:** 1.0  
**Last Updated:** May 29, 2026  
**Status:** Production Ready
