<section id="customerExistingModule" class="card border-0 shadow-sm">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center mb-3">
            <div class="d-flex gap-2 w-100">
                <input id="searchCustomerExisting" type="text" class="form-control" placeholder="Cari nama, kode, brand, atau alamat">
                <button id="btnSearchCustomerExisting" class="btn btn-outline-primary" type="button">Cari</button>
            </div>
            <div class="d-flex gap-2">
                <button id="btnImportCustomerExisting" class="btn btn-outline-secondary" type="button">Import</button>
                <button id="btnExportCustomerExisting" class="btn btn-outline-success" type="button">Export</button>
                <button id="btnAddCustomerExisting" class="btn btn-primary text-nowrap" type="button">Tambah</button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Toko</th>
                        <th>Brand</th>
                        <th>Alamat</th>
                        <th>Koordinat</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="customerExistingTableBody">
                    <tr><td colspan="6" class="text-center text-muted py-4">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
            <div id="customerExistingSummary" class="small text-muted">Memuat data...</div>
            <div id="customerExistingPagination" class="d-flex flex-wrap gap-2"></div>
        </div>
    </div>
</section>

<div class="modal fade" id="customerExistingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <form id="customerExistingForm">
                <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars(\App\Core\Csrf::token()); ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Customer Existing</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="customerExistingId" name="id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Kode Existing</label>
                            <input type="text" class="form-control" id="customerExistingCode" name="kode_existing">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Toko <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="customerExistingName" name="nama_toko" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Brand Kompetitor</label>
                            <input type="text" class="form-control" id="customerExistingBrand" name="brand_kompetitor">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" id="customerExistingAddress" name="alamat" rows="2"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Latitude</label>
                            <input type="number" class="form-control" step="any" id="customerExistingLat" name="lat">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Longitude</label>
                            <input type="number" class="form-control" step="any" id="customerExistingLng" name="lng">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sumber Data</label>
                            <select class="form-select" id="customerExistingSource" name="sumber_data">
                                <option value="Internal">Internal</option>
                                <option value="Survei Lapangan">Survei Lapangan</option>
                                <option value="Import">Import</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="customerExistingImportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="customerExistingImportForm" enctype="multipart/form-data">
                <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars(\App\Core\Csrf::token()); ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Import Customer Existing (Preview)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="file" id="customerExistingImportFile" name="file" accept=".csv,.xls,.xlsx,.txt" required>
                    </div>
                    <div id="customerExistingImportPreview"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" id="customerExistingImportCommit" class="btn btn-primary">Simpan Semua</button>
                </div>
            </form>
        </div>
    </div>
</div>
