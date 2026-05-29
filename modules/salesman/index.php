<section id="salesmanModule" class="row g-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center">
                    <div>
                        <p class="text-muted mb-1">Kelola sales team dan pantau performa kunjungan</p>
                        <h2 class="h4 mb-1">Modul Salesman</h2>
                        <p class="mb-0 text-muted">Tambah dan edit data salesman, lalu pantau realisasi kunjungan serta coverage area melalui Leaflet.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button id="btnRefreshSalesmen" class="btn btn-outline-secondary" type="button">Refresh</button>
                        <button id="btnAddSalesman" class="btn btn-primary" type="button">Tambah Salesman</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="card border-0 shadow-sm mt-3">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center mb-3">
            <div>
                <h3 class="h5 mb-1">Data Salesman</h3>
                <p class="text-muted mb-0">Klik tombol dashboard pada baris untuk memuat metrik dan heat-map area coverage.</p>
            </div>
            <div class="d-flex gap-2 w-100 justify-content-md-end" style="max-width: 420px;">
                <input id="searchSalesman" type="text" class="form-control" placeholder="Cari NIK, nama, email, atau telepon">
                <button id="btnSearchSalesman" class="btn btn-outline-primary" type="button">Cari</button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>NIK</th>
                        <th>Nama</th>
                        <th>Kontak</th>
                        <th>Wilayah</th>
                        <th>Target</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody id="salesmanTableBody">
                    <tr><td colspan="7" class="text-center text-muted py-4">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="card border-0 shadow-sm mt-3">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center mb-3">
            <div>
                <h3 class="h5 mb-1">Dashboard Salesman</h3>
                <p id="salesmanDashboardSummary" class="text-muted mb-0">Pilih salesman untuk melihat ringkasan kunjungan dan coverage area.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap justify-content-lg-end">
                <select id="salesmanDashboardSelect" class="form-select" style="min-width: 240px;">
                    <option value="">Pilih salesman</option>
                </select>
                <select id="salesmanDashboardMonth" class="form-select" style="width: 140px;"></select>
                <select id="salesmanDashboardYear" class="form-select" style="width: 130px;"></select>
                <button id="btnLoadSalesmanDashboard" class="btn btn-primary" type="button">Tampilkan</button>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6 col-xl-3">
                <article class="metric-card h-100">
                    <p class="metric-label">Total Customer</p>
                    <h2 id="salesmanTotalCustomer" class="metric-value">0</h2>
                </article>
            </div>
            <div class="col-6 col-xl-3">
                <article class="metric-card h-100">
                    <p class="metric-label">Realisasi Kunjungan</p>
                    <h2 id="salesmanRealisasiKunjungan" class="metric-value">0</h2>
                </article>
            </div>
            <div class="col-6 col-xl-3">
                <article class="metric-card h-100">
                    <p class="metric-label">% Target</p>
                    <h2 id="salesmanPersentaseTarget" class="metric-value">0%</h2>
                </article>
            </div>
            <div class="col-6 col-xl-3">
                <article class="metric-card h-100">
                    <p class="metric-label">Coverage Points</p>
                    <h2 id="salesmanCoverageCount" class="metric-value">0</h2>
                </article>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-xl-8">
                <div class="card border-0 bg-light-subtle h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h4 class="h6 mb-0">Heat-map Area Coverage</h4>
                            <span class="badge rounded-pill text-bg-light">Leaflet Heat</span>
                        </div>
                        <div id="salesmanDashboardMap" class="map-area rounded-3"></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-4">
                <div class="card border-0 bg-light-subtle h-100">
                    <div class="card-body">
                        <h4 class="h6 mb-3">Target Bulan Ini</h4>
                        <p class="small text-muted mb-2">Target kunjungan: <strong id="salesmanTargetValue">0</strong></p>
                        <p class="small text-muted mb-2">Realisasi kunjungan: <strong id="salesmanRealizationValue">0</strong></p>
                        <p class="small text-muted mb-3">Total customer: <strong id="salesmanCustomerValue">0</strong></p>
                        <div>
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Progress</span>
                                <strong id="salesmanProgressLabel">0%</strong>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div id="salesmanProgressBar" class="progress-bar bg-success" role="progressbar" style="width: 0%;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="salesmanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <form id="salesmanForm">
                <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars(\App\Core\Csrf::token()); ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Salesman Form</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="salesmanId" name="id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">NIK <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="salesmanNik" name="nik" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="salesmanNama" name="nama" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">No HP</label>
                            <input type="text" class="form-control" id="salesmanNoHp" name="no_hp">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="salesmanEmail" name="email">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Wilayah ID</label>
                            <input type="number" class="form-control" id="salesmanWilayahId" name="wilayah_id" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Target Kunjungan/Bulan</label>
                            <input type="number" class="form-control" id="salesmanTargetKunjungan" name="target_kunjungan_bulan" min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Foto URL</label>
                            <input type="url" class="form-control" id="salesmanFoto" name="foto" placeholder="https://example.com/foto.jpg">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">User</label>
                            <select class="form-select" id="salesmanUserId" name="user_id">
                                <option value="">Tanpa User</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="salesmanStatus" name="status">
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
