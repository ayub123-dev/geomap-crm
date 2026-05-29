<section id="customersModule" class="card border-0 shadow-sm">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center mb-3">
            <div class="d-flex gap-2 w-100">
                <input id="searchCustomer" type="text" class="form-control" placeholder="Cari nama, kode, email, atau telepon">
                <button id="btnSearchCustomer" class="btn btn-outline-primary" type="button">Cari</button>
            </div>
            <button id="btnAddCustomer" class="btn btn-primary text-nowrap" type="button">Tambah Customer</button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Kontak</th>
                        <th>Status</th>
                        <th>Koordinat</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody id="customersTableBody">
                    <tr><td colspan="6" class="text-center text-muted py-4">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <form id="customerForm">
                <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars(\App\Core\Csrf::token()); ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Customer Form</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="customerId" name="id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Kode Customer</label>
                            <input type="text" class="form-control" id="customerCode" name="customer_code" placeholder="Kosongkan untuk generate otomatis">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="customerName" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="customerEmail" name="email">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telepon</label>
                            <input type="text" class="form-control" id="customerPhone" name="phone">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" id="customerAddress" name="address" rows="2"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Latitude</label>
                            <input type="number" class="form-control" step="any" id="customerLatitude" name="latitude">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Longitude</label>
                            <input type="number" class="form-control" step="any" id="customerLongitude" name="longitude">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="customerStatus" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <button id="btnUseMyLocation" type="button" class="btn btn-link ps-0 mt-2">Gunakan lokasi saya</button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
