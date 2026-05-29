# API Reference

## Base Path
`/api`

## Endpoint
- `GET /api/customers.php`
  - Ambil daftar customer
- `POST /api/customers.php`
  - Tambah customer baru
- `GET /api/customer.php?id={id}`
  - Detail customer
- `PUT /api/customer.php?id={id}`
  - Update customer
- `DELETE /api/customer.php?id={id}`
  - Soft delete customer
- `GET /api/markers.php`
  - Ambil data marker customer untuk peta

## Format Response Umum
```json
{
  "success": true,
  "message": "Data berhasil diambil.",
  "data": []
}
```

## Catatan Integrasi Mobile
- Header JSON disarankan: `Content-Type: application/json`.
- Untuk update data gunakan method `PUT` dengan body JSON.
- Untuk list data customer gunakan query `search`, `limit`, `offset`.
