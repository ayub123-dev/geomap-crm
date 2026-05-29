<section id="mapModule" class="card border-0 shadow-sm">
    <div class="card-body p-3 p-md-4">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="card p-3 mb-3">
                    <h6 class="mb-2">Filter</h6>
                    <div class="mb-2">
                        <label class="form-label small">Salesman</label>
                        <select id="filterSalesman" class="form-select"><option value="">Semua</option></select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Wilayah (kota/kecamatan)</label>
                        <input id="filterRegion" type="text" class="form-control" placeholder="Nama wilayah">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Radius Threshold: <span id="radiusValue">100</span> m</label>
                        <input id="radiusSlider" type="range" min="50" max="500" step="10" value="100" class="form-range">
                    </div>
                    <div class="d-flex gap-2">
                        <button id="btnReloadMap" class="btn btn-outline-primary btn-sm">Reload</button>
                        <button id="btnLocateMeMap" class="btn btn-secondary btn-sm">Lokasi Saya</button>
                    </div>
                    <hr>
                    <h6 class="mb-2">Layers</h6>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="toggleInti" checked>
                        <label class="form-check-label small" for="toggleInti">Master Customer INTI</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="toggleExisting" checked>
                        <label class="form-check-label small" for="toggleExisting">Master Customer EXISTING</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="toggleProximity" checked>
                        <label class="form-check-label small" for="toggleProximity">Proximity Lines</label>
                    </div>
                    <hr>
                    <button id="btnExportProximity" class="btn btn-success btn-sm w-100">Export Proximity Report</button>
                </div>
            </div>

            <div class="col-md-6">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-2">
                    <div>
                        <h3 class="h5 mb-1">GeoMap Intelligence</h3>
                        <p class="text-muted small mb-0">Pantau persebaran customer dan potensi overlap.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button id="btnReloadMapTop" class="btn btn-outline-primary btn-sm" type="button">Reload Marker</button>
                        <button id="btnLocateMeMapTop" class="btn btn-primary btn-sm" type="button">Lokasi Saya</button>
                    </div>
                </div>

                <div id="mainMap" class="map-area rounded-3"></div>
            </div>

            <div class="col-md-3">
                <div class="card p-3 mb-3">
                    <h6 class="mb-2">Proximity Alerts</h6>
                    <div id="proximityList" style="max-height:520px; overflow:auto;"></div>
                </div>
            </div>
        </div>
    </div>
</section>
