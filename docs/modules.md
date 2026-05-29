# Modul Aplikasi

## Dashboard
- Menampilkan ringkasan KPI customer
- Menampilkan peta distribusi customer geotag
- Sumber data: statistik customer + endpoint marker

## Customers
- CRUD customer
- Input data geolokasi (lat/lng)
- Pencarian customer berdasarkan kata kunci
- Soft delete untuk menjaga histori data

## Map
- Visualisasi marker customer pada peta Leaflet
- Menggunakan tile OpenStreetMap
- Mendukung reload marker dan fitur lokasi user (browser geolocation)

## Arsitektur Ringkas
- `modules/*` berisi layer presentasi (UI page)
- `app/Repositories` berisi query database
- `app/Services` berisi business logic
- `/api` memakai service yang sama dengan modul web
