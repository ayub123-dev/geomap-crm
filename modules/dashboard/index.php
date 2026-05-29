<section class="dashboard-shell">
    <div class="row g-3">
        <div class="col-6 col-xl-3">
            <article class="metric-card h-100">
                <p class="metric-label">Total Customer</p>
                <h2 class="metric-value"><?php echo (int) $dashboardStats['total_customers']; ?></h2>
            </article>
        </div>
        <div class="col-6 col-xl-3">
            <article class="metric-card h-100">
                <p class="metric-label">Customer Active</p>
                <h2 class="metric-value"><?php echo (int) $dashboardStats['active_customers']; ?></h2>
            </article>
        </div>
        <div class="col-6 col-xl-3">
            <article class="metric-card h-100">
                <p class="metric-label">Customer Inactive</p>
                <h2 class="metric-value"><?php echo (int) $dashboardStats['inactive_customers']; ?></h2>
            </article>
        </div>
        <div class="col-6 col-xl-3">
            <article class="metric-card h-100">
                <p class="metric-label">Geotagged</p>
                <h2 class="metric-value"><?php echo (int) $dashboardStats['geotagged_customers']; ?></h2>
            </article>
        </div>
    </div>

    <div class="dashboard-content">
        <div class="dashboard-map-panel">
            <section class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h3 class="h5 mb-1">Distribusi Customer</h3>
                            <p class="text-muted mb-0 small">Peta interaktif untuk memantau lokasi Customer Inti dan
                                Existing.</p>
                        </div>
                        <span class="badge rounded-pill text-bg-light">Leaflet + OpenStreetMap</span>
                    </div>
                    <div id="dashboardMap" class="map-area rounded-3"></div>
                </div>
            </section>
        </div>

        <div class="dashboard-table-panel">
            <?php include __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'customer_inti' . DIRECTORY_SEPARATOR . 'index.php'; ?>
        </div>
    </div>
</section>