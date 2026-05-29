(function (window, $) {
    "use strict";

    console.log('[map.js] Script started. Initializing map...');

    var apiBase = window.GeoMapCRM.apiBase;
    var defaultCenter = [-2.5489, 118.0149];
    var defaultZoom = 5;
    var mapContainer = document.getElementById('mainMap') || document.getElementById('dashboardMap');

    if (!mapContainer) {
        console.warn('[map.js] No map container found on this page. Skipping initialization.');
        return;
    }

    var map = L.map(mapContainer.id).setView(defaultCenter, defaultZoom);
    console.log('[map.js] Map initialized:', map);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
    console.log('[map.js] Tile layer added');

    var intiLayer = L.markerClusterGroup();
    var existingLayer = L.markerClusterGroup();
    var proximityLines = L.layerGroup();
    console.log('[map.js] Layer groups created');

    var intiIndex = {};
    var existingIndex = {};
    var salesmenMap = {};

    function createCircleIcon(radius, color) {
        var size = radius * 2;
        var html = '<div style="width:' + size + 'px;height:' + size + 'px;border-radius:50%;background:' + color + ';opacity:0.95;border:2px solid #fff;box-shadow:0 0 2px rgba(0,0,0,0.3)"></div>';
        return L.divIcon({ html: html, className: '', iconSize: [size, size], iconAnchor: [size/2, size/2] });
    }

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

    
    function isToggleEnabled(selector, fallbackValue) {
        var $el = $(selector);
        if (!$el.length) {
            return fallbackValue;
        }
        return $el.is(':checked');
    }

    function loadData() {
        var radius = Number($('#radiusSlider').val() || 100);
        $('#radiusValue').text(radius);
        console.log('[map.js] loadData started, radius=', radius);

        $.when(
            $.ajax({
                url: apiBase + '/customer_inti.php',
                method: 'GET',
                dataType: 'json',
                data: { limit: 100000, offset: 0 },
                error: function(xhr, status, err) {
                    console.error('[map.js] customer_inti AJAX error:', status, err, xhr.responseText);
                }
            }),
            $.ajax({
                url: apiBase + '/customer_existing.php',
                method: 'GET',
                dataType: 'json',
                data: { limit: 100000, offset: 0 },
                error: function(xhr, status, err) {
                    console.error('[map.js] customer_existing AJAX error:', status, err, xhr.responseText);
                }
            }),
            $.ajax({
                url: apiBase + '/salesman.php',
                method: 'GET',
                dataType: 'json',
                data: { limit: 1000, offset: 0 },
                error: function(xhr, status, err) {
                    console.error('[map.js] salesman AJAX error:', status, err, xhr.responseText);
                }
            })
        ).done(function (intiResp, existingResp, salesmanResp) {
            console.log('[map.js] All AJAX calls returned. intiResp=', intiResp, 'existingResp=', existingResp);
            var intis = (intiResp[0] && intiResp[0].data) ? intiResp[0].data : [];
            var existings = (existingResp[0] && existingResp[0].data) ? existingResp[0].data : [];
            var salesmen = (salesmanResp[0] && salesmanResp[0].data) ? salesmanResp[0].data : [];
            console.log('[map.js] Parsed data: intis count=', intis.length, 'existings count=', existings.length, 'salesmen count=', salesmen.length);
            salesmenMap = {};
            salesmen.forEach(function(s){ salesmenMap[s.id] = s; });

            intiLayer.clearLayers(); existingLayer.clearLayers(); proximityLines.clearLayers(); intiIndex = {}; existingIndex = {};

            var intiIcon = createCircleIcon(10, '#2563EB');
            var existingIcon = createCircleIcon(8, '#DC2626');
            console.log('[map.js] Icons created');

            intis.forEach(function (p) {
                if (p.lat == null || p.lng == null) {
                    console.log('[map.js] Skipping inti marker (no lat/lng):', p);
                    return;
                }
                console.log('[map.js] Adding inti marker:', p.nama_toko, 'at', p.lat, p.lng);
                var marker = createCircleMarker(Number(p.lat), Number(p.lng), 10, '#2563EB');
                marker.bindPopup(buildPopupContent('inti', p, null));
                marker._meta = { type: 'inti', data: p };
                intiLayer.addLayer(marker);
                intiIndex[p.id] = marker;
            });

            existings.forEach(function (p) {
                if (p.lat == null || p.lng == null) {
                    console.log('[map.js] Skipping existing marker (no lat/lng):', p);
                    return;
                }
                console.log('[map.js] Adding existing marker:', p.nama_toko, 'at', p.lat, p.lng);
                var marker = createCircleMarker(Number(p.lat), Number(p.lng), 8, '#DC2626');
                marker.bindPopup(buildPopupContent('existing', p, null));
                marker._meta = { type: 'existing', data: p };
                existingLayer.addLayer(marker);
                existingIndex[p.id] = marker;
            });

            // add layers to map according to toggles
            var showInti = isToggleEnabled('#toggleInti', true);
            var showExisting = isToggleEnabled('#toggleExisting', true);
            var showProximity = isToggleEnabled('#toggleProximity', false);
            console.log('[map.js] toggleInti checked?', showInti);
            console.log('[map.js] toggleExisting checked?', showExisting);
            console.log('[map.js] intiLayer count:', intiLayer.getLayers().length);
            console.log('[map.js] existingLayer count:', existingLayer.getLayers().length);

            if (showInti) {
                console.log('[map.js] Adding intiLayer to map');
                map.addLayer(intiLayer);
            }
            if (showExisting) {
                console.log('[map.js] Adding existingLayer to map');
                map.addLayer(existingLayer);
            }
            if (showProximity) map.addLayer(proximityLines);

            // populate salesman filter
            var $salesman = $('#filterSalesman'); $salesman.html('<option value="">Semua</option>');
            salesmen.forEach(function (s) { $salesman.append('<option value="' + s.id + '">' + (s.nama || s.nik || s.id) + '</option>'); });

            // fit map
            var allPoints = Object.values(intiIndex).concat(Object.values(existingIndex));
            if (allPoints.length) {
                var bounds = L.latLngBounds(allPoints.map(function (m) { return m.getLatLng(); }));
                map.fitBounds(bounds, { padding: [25,25] });
            } else {
                map.setView(defaultCenter, defaultZoom);
            }

            // fetch proximity pairs and draw lines
            fetchProximity(radius);
        }).fail(function (err, statusText, xhr) {
            console.error('[map.js] AJAX call failed:', err, statusText, xhr);
            if (err && err.responseJSON && err.responseJSON.message) {
                window.GeoMapCRM.notify('danger', 'Error: ' + err.responseJSON.message);
            } else {
                window.GeoMapCRM.notify('danger', 'Gagal memuat data peta. Buka browser console (F12) untuk detail error.');
            }
        });
    }

    function buildPopupContent(type, item, extra) {
        var title = type === 'inti' ? (item.nama_toko || '-') : (item.nama_toko || '-');
        var addr = item.alamat || '-';
        var kategoriToko = item.kategori_toko || '-';
        var brandKompetitor = item.brand_kompetitor || '-';
        var lat = item.lat || '-';
        var lng = item.lng || '-';
        var salesman = salesmenMap[item.salesman_id] ? (salesmenMap[item.salesman_id].nama || salesmenMap[item.salesman_id].nik || salesmenMap[item.salesman_id].id) : 'Unknown';
        var dist = extra && extra.distance_meters ? '<div class="badge bg-danger">OVERLAP RISK</div><div class="small mt-1">Jarak: ' + (extra.distance_meters || 0) + ' m</div>' : '';
        var koordinat = '-';

if (item.lat != null && item.lng != null) {
    koordinat = item.lat + ', ' + item.lng;
}
        return '<div><strong>' + window.GeoMapCRM.escapeHtml(title) + '</strong>' +
            '<div class="small text-muted">' + window.GeoMapCRM.escapeHtml(addr) + '</div>' +
            '<div class="small">Channel Inti: ' + window.GeoMapCRM.escapeHtml(kategoriToko) + '</div>' +
            '<div class="small">Channel Existing: ' + window.GeoMapCRM.escapeHtml(brandKompetitor) + '</div>' +
            '<div class="small">Koordinat: ' + window.GeoMapCRM.escapeHtml(koordinat) + '</div>' +
            '<div class="small">Salesman PIC: ' + window.GeoMapCRM.escapeHtml(salesman) + '</div>' + dist + '</div>';
            
    }

    function fetchProximity(radius) {
        $.get(apiBase + '/proximity.php', { radius: radius }).done(function (resp) {
            var data = (resp && resp.data && resp.data.pairs) ? resp.data.pairs : [];
            proximityLines.clearLayers(); $('#proximityList').html('');

            data.forEach(function (pair, idx) {
                var latlngs = [[pair.customer_inti_lat, pair.customer_inti_lng], [pair.customer_existing_lat, pair.customer_existing_lng]];
                var poly = L.polyline(latlngs, { color: '#dc2626', weight: 2, dashArray: '6,6' });
                poly.addTo(proximityLines);

                // add badge to popups by updating marker content
                var intiMarker = intiIndex[pair.customer_inti_id];
                var exMarker = existingIndex[pair.customer_existing_id];
                var extra = { distance_meters: pair.distance_meters };
                if (intiMarker) intiMarker.bindPopup(buildPopupContent('inti', intiMarker._meta.data, extra));
                if (exMarker) exMarker.bindPopup(buildPopupContent('existing', exMarker._meta.data, extra));

                // add to proximity list
                var html = '<div class="card mb-2 p-2"><div class="d-flex justify-content-between align-items-start">' +
                    '<div><strong>' + window.GeoMapCRM.escapeHtml(pair.customer_inti_name) + '</strong><div class="small text-muted">vs ' + window.GeoMapCRM.escapeHtml(pair.customer_existing_name) + '</div></div>' +
                    '<div class="text-end"><span class="badge bg-danger">' + pair.distance_meters + ' m</span><br><button class="btn btn-sm btn-link p-0 proximity-zoom" data-inti="' + pair.customer_inti_id + '" data-ex="' + pair.customer_existing_id + '">Zoom</button></div>' +
                    '</div></div>';
                $('#proximityList').append(html);
            });

            // wire zoom buttons
            $('.proximity-zoom').on('click', function () {
                var intiId = $(this).data('inti'); var exId = $(this).data('ex');
                var m1 = intiIndex[intiId]; var m2 = existingIndex[exId];
                if (m1 && m2) {
                    var bounds = L.latLngBounds([m1.getLatLng(), m2.getLatLng()]);
                    map.fitBounds(bounds, { padding: [40,40] });
                }
            });

            if ($('#toggleProximity').is(':checked')) map.addLayer(proximityLines); else map.removeLayer(proximityLines);
        }).fail(function () { window.GeoMapCRM.notify('danger', 'Gagal menghitung proximity.'); });
    }

    // UI wiring
    $('#btnReloadMap, #btnReloadMapTop').on('click', function () { loadData(); });
    $('#btnLocateMeMap, #btnLocateMeMapTop').on('click', function () {
        if (!('geolocation' in navigator)) return window.GeoMapCRM.notify('warning', 'Browser tidak mendukung geolocation.');
        navigator.geolocation.getCurrentPosition(function (pos) { map.setView([pos.coords.latitude, pos.coords.longitude], 15); L.circleMarker([pos.coords.latitude, pos.coords.longitude], { radius: 9, weight: 2, color: '#f77f00', fillColor: '#fcbf49', fillOpacity: 0.7 }).addTo(map).bindPopup('Lokasi Anda').openPopup(); });
    });

    $('#toggleInti').on('change', function () { if (this.checked) map.addLayer(intiLayer); else map.removeLayer(intiLayer); });
    $('#toggleExisting').on('change', function () { if (this.checked) map.addLayer(existingLayer); else map.removeLayer(existingLayer); });
    $('#toggleProximity').on('change', function () { if (this.checked) map.addLayer(proximityLines); else map.removeLayer(proximityLines); });

    $('#radiusSlider').on('input change', function () { $('#radiusValue').text(this.value); });
    $('#radiusSlider').on('change', function () { fetchProximity(Number(this.value)); });

    $('#filterSalesman').on('change', function () { var v = $(this).val(); if (!v) { if (!map.hasLayer(intiLayer)) map.addLayer(intiLayer); return; } // simple filter: hide inti markers not matching
        intiLayer.clearLayers(); // reload only those matching id via ajax
        $.get(apiBase + '/customer_inti.php', { limit: 100000, offset: 0 }).done(function (r) { var rows = (r && r.data) ? r.data : []; rows.forEach(function (p) { if (String(p.salesman_id) === String(v) && p.lat != null && p.lng != null) { var icon = createCircleIcon(10, '#2563EB'); var m = L.marker([p.lat, p.lng], { icon: icon }); m._meta = { type: 'inti', data: p }; intiLayer.addLayer(m); intiIndex[p.id] = m; } }); }).fail(function () { window.GeoMapCRM.notify('danger', 'Gagal memfilter salesman.'); });
    });

    $('#btnExportProximity').on('click', function () {
        var radius = Number($('#radiusSlider').val() || 100);
        window.location = apiBase + '/proximity_export.php?radius=' + encodeURIComponent(radius) + '&format=excel';
    });

    // initial load
    loadData();

    // Diagnostic function for debugging
    window.testMapDiagnostics = function() {
        console.log('=== MAP DIAGNOSTICS ===');
        console.log('apiBase:', apiBase);
        console.log('mainMap:', map);
        console.log('intiLayer count:', intiLayer.getLayers().length);
        console.log('existingLayer count:', existingLayer.getLayers().length);
        console.log('intiIndex keys:', Object.keys(intiIndex).length);
        console.log('existingIndex keys:', Object.keys(existingIndex).length);
        
        console.log('Testing API calls...');
        $.get(apiBase + '/diagnostic.php').done(function(r) {
            console.log('Diagnostic API response:', r);
        }).fail(function(xhr) {
            console.error('Diagnostic API failed:', xhr);
        });
    };
    
    console.log('[map.js] Map module loaded. Run testMapDiagnostics() in console to diagnose issues.');

})(window, jQuery);

