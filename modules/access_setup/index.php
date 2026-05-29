<section id="accessSetupModule" class="row g-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center">
                    <div>
                        <p class="text-muted mb-1">Recovery Mode</p>
                        <h2 class="h4 mb-1">Setup Akses Dinamis</h2>
                        <p class="mb-0 text-muted">Halaman ini membantu memperbaiki assignment role dan permission otomatis agar aplikasi bisa langsung digunakan.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button id="btnReloadAccessSetup" class="btn btn-outline-secondary" type="button">Refresh Status</button>
                        <button id="btnAutoFixAccess" class="btn btn-primary" type="button">Auto Fix Akses</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="card border-0 shadow-sm mt-3">
    <div class="card-body p-3 p-md-4">
        <h3 class="h5 mb-3">Status Akses Saat Ini</h3>
        <div id="accessSetupStatus">
            <div class="text-muted">Memuat status...</div>
        </div>
    </div>
</section>
