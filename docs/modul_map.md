# Dokumentasi Modul Map

## Daftar Isi
1. [Pendahuluan](#pendahuluan)
2. [Manfaat](#manfaat)
3. [Keunggulan](#keunggulan)
4. [Elemen-Elemen Modul](#elemen-elemen-modul)
5. [Fungsi Utama](#fungsi-utama)
6. [Event Handling](#event-handling)

---

## Pendahuluan

Modul Map adalah komponen JavaScript yang bertanggung jawab untuk menampilkan visualisasi geografis data pelanggan dan salesman dalam aplikasi GeoMap CRM. Modul ini mengintegrasikan **Leaflet.js** sebagai library pemetaan dan menyediakan fitur interaktif untuk analisis distribusi toko retail.

**File Location:** `assets/js/map.js`

---

## Manfaat

### 1. **Visualisasi Data Geografis Real-Time**
- Menampilkan lokasi toko inti dan existing dalam satu peta interaktif
- Memudahkan monitoring dan analisis distribusi toko secara visual
- Peningkatan pemahaman tentang layout geografis jaringan retail

### 2. **Identifikasi Overlap Kompetitif**
- Fitur proximity detection mengidentifikasi toko inti dan existing yang berdekatan
- Mencegah double distribution dan konflik territorial antara channel
- Memberikan insight untuk optimasi jaringan distribusi

### 3. **Manajemen Sales Team**
- Filter data berdasarkan salesman PIC (Person In Charge)
- Tracking coverage area setiap salesman
- Memudahkan assignment dan evaluasi performa regional

### 4. **Analisis Radius-Based**
- Slider radius memungkinkan customization untuk analisis proximity
- Export data proximity dalam format Excel untuk reporting
- Fleksibilitas dalam mendefinisikan "overlap" berdasarkan jarak

### 5. **User Experience Interaktif**
- Clustering markers otomatis untuk readability pada high-density areas
- Popup informatif dengan detail toko dan jarak proximity
- Fitur "Locate Me" untuk geolocation user
- Zoom to region functionality dengan satu klik

---

## Keunggulan

### 1. **Performa Tinggi**
- Menggunakan MarkerClusterGroup untuk menangani ribuan markers
- Lazy loading data dengan pagination (limit/offset)
- Efficient DOM manipulation dengan jQuery

### 2. **Keamanan Data**
- HTML escaping untuk semua user input di popup content
- Error handling untuk AJAX calls dengan fallback notifications
- Validation untuk koordinat latitude/longitude sebelum rendering

### 3. **Responsiveness**
- Layout yang adaptif terhadap berbagai ukuran container
- Support untuk geolocation API browser modern
- Dynamic bounds fitting untuk auto-zoom ke data

### 4. **Maintainability**
- Code terstruktur dengan IIFE (Immediately Invoked Function Expression) untuk scope isolation
- Extensive console logging untuk debugging
- Diagnostic function untuk troubleshooting

### 5. **Integrasi Seamless**
- Kompatibel dengan Leaflet dan OpenStreetMap
- Integration dengan backend API melalui standardized endpoints
- Support untuk multiple layer management

---

## Elemen-Elemen Modul

### **1. Inisialisasi & Konfigurasi**

```javascript
var apiBase = window.GeoMapCRM.apiBase;
var defaultCenter = [-2.5489, 118.0149];  // Koordinat default (Indonesia)
var defaultZoom = 5;                       // Zoom level default
var mapContainer = document.getElementById('mainMap') || document.getElementById('dashboardMap');
```

**Penjelasan:**
- `apiBase`: Base URL untuk API endpoints yang akan di-call
- `defaultCenter`: Titik pusat peta default (latitude, longitude) - diset ke pusat Indonesia
- `defaultZoom`: Level zoom awal peta
- `mapContainer`: Element HTML yang akan menjadi container peta

---

### **2. Map Initialization**

```javascript
var map = L.map(mapContainer.id).setView(defaultCenter, defaultZoom);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { 
    maxZoom: 19, 
    attribution: '&copy; OpenStreetMap contributors' 
}).addTo(map);
```

**Penjelasan:**
- Membuat instance Leaflet map pada container yang ditentukan
- Menambahkan tile layer dari OpenStreetMap
- `maxZoom: 19` adalah level zoom maksimal yang bisa di-access
- Attribution ditampilkan sesuai lisensi OpenStreetMap

---

### **3. Layer Groups**

```javascript
var intiLayer = L.markerClusterGroup();          // Untuk Customer Inti
var existingLayer = L.markerClusterGroup();      // Untuk Customer Existing
var proximityLines = L.layerGroup();             // Untuk Garis Proximity
```

**Penjelasan:**
- **intiLayer**: Group berisi markers toko inti (saluran distribusi utama) dengan clustering
- **existingLayer**: Group berisi markers toko existing (saluran lain) dengan clustering
- **proximityLines**: Group berisi polyline yang menghubungkan toko inti dan existing yang berdekatan
- MarkerClusterGroup otomatis menggabungkan markers yang berdekatan menjadi cluster pada zoom level tertentu

---

### **4. Index Objects**

```javascript
var intiIndex = {};          // Map: customer_id -> marker object
var existingIndex = {};      // Map: customer_id -> marker object
var salesmenMap = {};        // Map: salesman_id -> salesman data
```

**Penjelasan:**
- `intiIndex`: Penyimpanan reference marker inti untuk akses cepat berdasarkan ID
- `existingIndex`: Penyimpanan reference marker existing untuk akses cepat
- `salesmenMap`: Cache data salesman untuk menampilkan nama di popup tanpa re-query

---

### **5. Fungsi Pembuatan Icon**

#### **a) createCircleIcon()**
```javascript
function createCircleIcon(radius, color) {
    var size = radius * 2;
    var html = '<div style="width:' + size + 'px;height:' + size + 'px;border-radius:50%;background:' + color + ';opacity:0.95;border:2px solid #fff;box-shadow:0 0 2px rgba(0,0,0,0.3)"></div>';
    return L.divIcon({ html: html, className: '', iconSize: [size, size], iconAnchor: [size/2, size/2] });
}
```

**Penjelasan:**
- Membuat circular HTML icon dengan styling CSS
- `radius`: Ukuran radius dalam pixel
- `color`: Warna background circle (e.g., '#2563EB' untuk biru)
- Mengembalikan Leaflet divIcon yang bisa digunakan sebagai custom marker icon
- Icon dilengkapi dengan shadow untuk kedalaman visual

#### **b) createCircleMarker()**
```javascript
function createCircleMarker(lat, lng, radius, color) {
    return L.circleMarker([lat, lng], {
        radius: radius,
        fillColor: color,
        color: '#fff',
        weight: 2,
        opacity: 0.95,
        fillOpacity: 0.95
    });
}
```

**Penjelasan:**
- Membuat circular marker di koordinat spesifik
- Parameter: latitude, longitude, radius (pixel), dan color
- `weight: 2`: Ketebalan border circle
- `opacity: 0.95`: Transparansi border
- `fillOpacity: 0.95`: Transparansi fill circle
- Lebih fleksibel dari icon untuk styling realtime

---

### **6. Fungsi Data Loading**

#### **loadData()**
```javascript
function loadData() {
    var radius = Number($('#radiusSlider').val() || 100);
    $('#radiusValue').text(radius);
    
    $.when(
        $.ajax({ url: apiBase + '/customer_inti.php', ... }),
        $.ajax({ url: apiBase + '/customer_existing.php', ... }),
        $.ajax({ url: apiBase + '/salesman.php', ... })
    ).done(function (intiResp, existingResp, salesmanResp) {
        // Process data...
    });
}
```

**Penjelasan:**
- Mengambil nilai radius dari slider untuk analisis proximity
- Melakukan 3 AJAX calls parallel:
  1. **customer_inti.php**: Data toko inti
  2. **customer_existing.php**: Data toko existing
  3. **salesman.php**: Data salesman
- `$.when()`: Menunggu semua request selesai sebelum memproses
- `.done()`: Handler jika semua request berhasil
- `.fail()`: Handler jika ada request yang gagal

**Proses dalam done callback:**
1. Ekstrak data dari response
2. Build salesmenMap untuk lookup cepat
3. Clear layer sebelumnya
4. Create markers untuk setiap toko
5. Bind popup content ke marker
6. Tentukan visibility layer berdasarkan toggle UI
7. Fit map bounds ke data
8. Fetch data proximity

---

### **7. Popup Content Builder**

```javascript
function buildPopupContent(type, item, extra) {
    var title = item.nama_toko || '-';
    var addr = item.alamat || '-';
    var kategoriToko = item.kategori_toko || '-';
    var salesman = salesmenMap[item.salesman_id] ? salesmenMap[item.salesman_id].nama : 'Unknown';
    var dist = extra && extra.distance_meters ? '<div class="badge bg-danger">OVERLAP RISK</div>' : '';
    // ... return HTML
}
```

**Penjelasan:**
- Membangun HTML content untuk popup marker
- `type`: 'inti' atau 'existing' untuk membedakan data
- `item`: Data toko yang akan ditampilkan
- `extra`: Data tambahan seperti distance_meters
- Menampilkan: nama toko, alamat, kategori, koordinat, salesman PIC
- Jika ada proximity, tampilkan badge "OVERLAP RISK" dan jarak dalam meter
- Menggunakan `GeoMapCRM.escapeHtml()` untuk XSS prevention

---

### **8. Proximity Detection**

```javascript
function fetchProximity(radius) {
    $.get(apiBase + '/proximity.php', { radius: radius }).done(function (resp) {
        var data = (resp && resp.data && resp.data.pairs) ? resp.data.pairs : [];
        
        data.forEach(function (pair, idx) {
            // Draw polyline between paired customers
            var latlngs = [[pair.customer_inti_lat, pair.customer_inti_lng], [pair.customer_existing_lat, pair.customer_existing_lng]];
            var poly = L.polyline(latlngs, { color: '#dc2626', weight: 2, dashArray: '6,6' });
            poly.addTo(proximityLines);
            
            // Update popup dengan jarak
            // Add to proximity list UI
        });
    });
}
```

**Penjelasan:**
- Request ke `/proximity.php` dengan parameter radius
- Menerima array pairs yang berisi toko inti-existing yang overlap
- Untuk setiap pair:
  - **Draw polyline**: Garis dashed merah menghubungkan 2 toko
  - **Update popup**: Tambahkan badge "OVERLAP RISK" dan distance
  - **List UI**: Tambahkan card ke proximity list dengan zoom button
- Polyline styling: warna merah (#dc2626), garis putus-putus (dashArray: '6,6')

---

### **9. Event Handlers**

| Event | Elemen | Fungsi |
|-------|--------|--------|
| `click` | `#btnReloadMap` | Re-load semua data dan refresh peta |
| `click` | `#btnLocateMeMap` | Gunakan browser geolocation untuk center ke user position |
| `change` | `#toggleInti` | Toggle visibility intiLayer |
| `change` | `#toggleExisting` | Toggle visibility existingLayer |
| `change` | `#toggleProximity` | Toggle visibility proximityLines |
| `input/change` | `#radiusSlider` | Update radius value display |
| `change` | `#radiusSlider` | Re-fetch proximity data dengan radius baru |
| `change` | `#filterSalesman` | Filter markers inti berdasarkan salesman ID |
| `click` | `#btnExportProximity` | Export proximity data ke Excel |
| `click` | `.proximity-zoom` | Zoom ke pair toko yang dipilih |

---

### **10. Utility Functions**

#### **isToggleEnabled()**
```javascript
function isToggleEnabled(selector, fallbackValue) {
    var $el = $(selector);
    if (!$el.length) {
        return fallbackValue;
    }
    return $el.is(':checked');
}
```

**Penjelasan:**
- Aman mengecek status checkbox tanpa error jika element tidak ada
- Return fallback value jika element tidak ditemukan
- Return checked status jika element ditemukan

---

### **11. Diagnostic Function**

```javascript
window.testMapDiagnostics = function() {
    console.log('=== MAP DIAGNOSTICS ===');
    console.log('apiBase:', apiBase);
    console.log('mainMap:', map);
    console.log('intiLayer count:', intiLayer.getLayers().length);
    console.log('existingLayer count:', existingLayer.getLayers().length);
    // ... more diagnostics
}
```

**Penjelasan:**
- Fungsi debugging yang di-expose ke window object
- Bisa dipanggil dari browser console: `testMapDiagnostics()`
- Menampilkan stats dan melakukan test API call
- Berguna untuk troubleshooting issues

---

## Fungsi Utama

### **Marker Rendering Flow**

1. **loadData()** dipanggil
2. Fetch 3 API endpoints (inti, existing, salesman) parallel
3. Clear layers dan indexes
4. Iterate setiap toko, create marker:
   - Tentukan coordinate (lat/lng)
   - Tentukan color dan radius berdasarkan type
   - Bind popup content dengan `buildPopupContent()`
   - Store marker reference di index
   - Add marker ke layer
5. Tentukan layer visibility dari toggle state
6. Fit map bounds ke semua markers
7. Call `fetchProximity(radius)` untuk overlay proximity lines

### **User Interaction Flow**

1. User toggle layer checkbox → `toggleInti/toggleExisting` event
2. Layer add/remove dari map
3. User drag radius slider → `radiusSlider input` event
4. Update display value
5. User release slider → `radiusSlider change` event
6. Call `fetchProximity(newRadius)`
7. Proximity pairs fetched, polylines drawn, list updated
8. User click marker → Popup displayed
9. User click proximity pair's "Zoom" button → `fitBounds()` to pair

---

## Color & Styling Reference

| Elemen | Warna | Ukuran | Keterangan |
|--------|-------|--------|-----------|
| Customer Inti Marker | #2563EB (Biru) | radius: 10px | Primary distribution channel |
| Customer Existing Marker | #DC2626 (Merah) | radius: 8px | Secondary/Alternative channel |
| Proximity Lines | #DC2626 (Merah) | weight: 2px | Dashed pattern (6,6) |
| User Location | #F77F00 (Orange) | radius: 9px | Geolocation indicator |

---

## API Endpoints Used

| Endpoint | Method | Parameter | Response |
|----------|--------|-----------|----------|
| `/customer_inti.php` | GET | limit, offset | `{ data: [...] }` |
| `/customer_existing.php` | GET | limit, offset | `{ data: [...] }` |
| `/salesman.php` | GET | limit, offset | `{ data: [...] }` |
| `/proximity.php` | GET | radius | `{ data: { pairs: [...] } }` |
| `/proximity_export.php` | GET | radius, format | Excel file download |

---

## Data Structure Reference

### **Customer Object (Inti/Existing)**
```javascript
{
    id: integer,              // Customer ID
    nama_toko: string,        // Store name
    alamat: string,           // Store address
    lat: float,               // Latitude
    lng: float,               // Longitude
    kategori_toko: string,    // Store category/channel
    brand_kompetitor: string, // Competitor brand info
    salesman_id: integer      // PIC salesman ID
}
```

### **Salesman Object**
```javascript
{
    id: integer,    // Salesman ID
    nama: string,   // Salesman name
    nik: string     // ID number if nama not available
}
```

### **Proximity Pair Object**
```javascript
{
    customer_inti_id: integer,
    customer_inti_name: string,
    customer_inti_lat: float,
    customer_inti_lng: float,
    customer_existing_id: integer,
    customer_existing_name: string,
    customer_existing_lat: float,
    customer_existing_lng: float,
    distance_meters: integer  // Distance between two customers
}
```

---

## Error Handling

Modul menggunakan multi-layer error handling:

1. **AJAX Error Callbacks**: Log error detail ke console dan display user notification
2. **Element Existence Check**: `isToggleEnabled()` returns fallback jika element tidak ada
3. **Data Validation**: Skip markers dengan null lat/lng
4. **HTML Escaping**: `GeoMapCRM.escapeHtml()` untuk prevent XSS attacks
5. **User Notifications**: Bootstrap alerts via `GeoMapCRM.notify()` untuk error messages

---

## Console Logging

Modul extensively log ke console dengan prefix `[map.js]` untuk mudah identify dalam logs. Logging categories:

- Initialization: "Script started", "Map initialized", "Layer groups created"
- Data Loading: AJAX responses, marker counts
- User Actions: Toggle changes, filter changes, radius changes
- Errors: AJAX failures, missing elements

Enable console (F12) untuk debugging dan diagnostics.

---

## Performance Considerations

1. **Data Limits**: Limit 100,000 untuk customer_inti & customer_existing, 1,000 untuk salesman
2. **Clustering**: MarkerClusterGroup handles 1000+ markers efficiently
3. **Indexes**: O(1) lookup via intiIndex dan existingIndex
4. **Layer Visibility**: Toggle show/hide tanpa redraw
5. **Lazy Proximity**: Only fetch proximity saat slider change, bukan setiap interaction

---

## Version & Dependencies

- **Leaflet.js**: Version 1.x (untuk pemetaan)
- **jQuery**: 3.x (untuk AJAX dan DOM manipulation)
- **Leaflet.markercluster**: Plugin untuk clustering
- **OpenStreetMap**: Tile provider

---

## Catatan Pengembang

- Jangan ubah `defaultCenter` tanpa coordinate validation
- Selalu add lat/lng validation sebelum create marker
- Test proxy dan CORS jika mengubah API endpoints
- Use `testMapDiagnostics()` untuk debug issues
- Monitor console logs untuk performance insights
