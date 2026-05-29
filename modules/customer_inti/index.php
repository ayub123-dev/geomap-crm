<section id="customerIntiModule" class="card border-0 shadow-sm">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center mb-3">
            <div class="d-flex gap-2 w-100">
                <input id="searchCustomerInti" type="text" class="form-control"
                    placeholder="Cari nama, kode, atau alamat">
                <button id="btnSearchCustomerInti" class="btn btn-outline-primary" type="button">Cari</button>
            </div>
            <div class="d-flex gap-2">
                <button id="btnImportCustomerInti" class="btn btn-outline-secondary" type="button">Import</button>
                <button id="btnExportCustomerInti" class="btn btn-outline-success" type="button">Export</button>
                <button id="btnAddCustomerInti" class="btn btn-primary text-nowrap" type="button">Tambah</button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Toko</th>
                        <th>Channel Toko</th>
                        <th>Kontak</th>
                        <th>Koordinat</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="customerIntiTableBody">
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Memuat data...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
            <div id="customerIntiSummary" class="small text-muted">Memuat data...</div>
            <div id="customerIntiPagination" class="d-flex flex-wrap gap-2"></div>
        </div>
    </div>
</section>

<?php // Modal: Form ?>
<div class="modal fade" id="customerIntiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <form id="customerIntiForm">
                <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars(\App\Core\Csrf::token()); ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Customer Inti</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="customerIntiId" name="id">
                    <input type="hidden" id="customerIntiKelurahan" name="kelurahan" value="">
                    <input type="hidden" id="customerIntiKecamatan" name="kecamatan" value="">
                    <input type="hidden" id="customerIntiKota" name="kota" value="">
                    <input type="hidden" id="customerIntiProvinsi" name="provinsi" value="">
                    <input type="hidden" id="customerIntiKategori" name="kategori_toko" value="">
                    <input type="hidden" id="customerIntiOmzet" name="omzet_estimasi" value="0">
                    <input type="hidden" id="customerIntiSalesman" name="salesman_id" value="">
                    <input type="hidden" id="customerIntiPhotoUrl" name="foto_toko" value="">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Kode Customer</label>
                            <input type="text" class="form-control" id="customerIntiCode" name="kode_customer">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Toko <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="customerIntiName" name="nama_toko" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pemilik</label>
                            <input type="text" class="form-control" id="customerIntiOwner" name="pemilik">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">No HP</label>
                            <input type="text" class="form-control" id="customerIntiPhone" name="no_hp">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" id="customerIntiAddress" name="alamat" rows="2"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Latitude</label>
                            <input type="number" class="form-control" step="any" id="customerIntiLat" name="lat">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Longitude</label>
                            <input type="number" class="form-control" step="any" id="customerIntiLng" name="lng">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="customerIntiStatus" name="status">
                                <option value="Aktif">Aktif</option>
                                <option value="NonAktif">NonAktif</option>
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

<?php // Modal: Import Preview ?>
<div class="modal fade" id="customerIntiImportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="customerIntiImportForm" enctype="multipart/form-data">
                <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars(\App\Core\Csrf::token()); ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Import Customer Inti (Preview)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="file" id="customerIntiImportFile" name="file" accept=".csv,.xls,.xlsx" required>
                    </div>
                    <div id="customerIntiImportPreview"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" id="customerIntiImportCommit" class="btn btn-primary">Simpan Semua</button>
                </div>
            </form>
        </div>
    </div>
</div>
