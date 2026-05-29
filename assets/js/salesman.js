(function (window, $) {
    "use strict";

    var moduleEl = document.getElementById("salesmanModule");
    if (!moduleEl) {
        return;
    }

    var apiBase = window.GeoMapCRM.apiBase;
    var salesmanModal = new bootstrap.Modal(document.getElementById("salesmanModal"));
    var $tableBody = $("#salesmanTableBody");
    var $searchInput = $("#searchSalesman");
    var $salesmanDashboardSelect = $("#salesmanDashboardSelect");
    var $salesmanDashboardMonth = $("#salesmanDashboardMonth");
    var $salesmanDashboardYear = $("#salesmanDashboardYear");
    var $salesmanDashboardSummary = $("#salesmanDashboardSummary");
    var $salesmanProgressBar = $("#salesmanProgressBar");
    var $salesmanProgressLabel = $("#salesmanProgressLabel");

    var salesmenCache = [];
    var userOptions = [];
    var selectedSalesmanId = "";
    var dashboardMap = null;
    var heatLayer = null;

    function formatStatus(status) {
        var normalized = String(status || "").toLowerCase();
        if (normalized === "active" || normalized === "aktif") {
            return "Aktif";
        }
        if (normalized === "inactive" || normalized === "nonaktif") {
            return "NonAktif";
        }
        return status || "Aktif";
    }

    function safeNumber(value) {
        var parsed = Number(value);
        if (!isFinite(parsed)) {
            return 0;
        }
        return parsed;
    }

    function renderRows(rows) {
        if (!rows.length) {
            $tableBody.html('<tr><td colspan="7" class="text-center text-muted py-4">Data salesman belum tersedia.</td></tr>');
            return;
        }

        var html = "";
        rows.forEach(function (row) {
            var contact = [row.no_hp || "-", row.email || "-"].join(" • ");
            html += "<tr>";
            html += "<td>" + window.GeoMapCRM.escapeHtml(row.nik || "-") + "</td>";
            html += "<td>" + window.GeoMapCRM.escapeHtml(row.nama || "-") + "</td>";
            html += "<td><div class='small'>" + window.GeoMapCRM.escapeHtml(row.no_hp || "-") + "</div><div class='small text-muted'>" + window.GeoMapCRM.escapeHtml(row.email || "-") + "</div></td>";
            html += "<td>" + window.GeoMapCRM.escapeHtml(String(row.wilayah_id || "-")) + "</td>";
            html += "<td>" + safeNumber(row.target_kunjungan_bulan) + "</td>";
            html += "<td><span class='badge " + (String(row.status || "").toLowerCase() === "active" || String(row.status || "").toLowerCase() === "aktif" ? "text-bg-success" : "text-bg-secondary") + "'>" + window.GeoMapCRM.escapeHtml(formatStatus(row.status)) + "</span></td>";
            html += "<td class='text-end'>";
            html += "<button class='btn btn-sm btn-outline-primary me-1 btn-dashboard' data-id='" + row.id + "'>Dashboard</button>";
            html += "<button class='btn btn-sm btn-outline-secondary me-1 btn-edit' data-id='" + row.id + "'>Edit</button>";
            html += "<button class='btn btn-sm btn-outline-danger btn-delete' data-id='" + row.id + "'>Hapus</button>";
            html += "</td>";
            html += "</tr>";
        });

        $tableBody.html(html);
    }

    function renderUserOptions() {
        var options = '<option value="">Tanpa User</option>';
        userOptions.forEach(function (user) {
            options += '<option value="' + user.id + '">' + window.GeoMapCRM.escapeHtml((user.full_name || user.username || user.email || user.id)) + '</option>';
        });
        $("#salesmanUserId").html(options);
    }

    function updateDashboardSummary() {
        var selected = salesmenCache.find(function (item) {
            return String(item.id) === String(selectedSalesmanId);
        });

        if (!selected) {
            $salesmanDashboardSummary.text("Pilih salesman untuk melihat ringkasan kunjungan dan coverage area.");
            return;
        }

        $salesmanDashboardSummary.text(selected.nama + " • " + (selected.nik || "-"));
    }

    function initDashboardMap() {
        if (dashboardMap) {
            return;
        }

        dashboardMap = L.map("salesmanDashboardMap").setView([-2.5489, 118.0149], 5);
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19,
            attribution: "&copy; OpenStreetMap contributors"
        }).addTo(dashboardMap);

        heatLayer = L.heatLayer([], {
            radius: 30,
            minOpacity: 0.35,
            blur: 20,
            maxZoom: 12,
            max: 1.0
        }).addTo(dashboardMap);
    }

    function renderHeatMap(points) {
        initDashboardMap();

        if (!points || !points.length) {
            heatLayer.setLatLngs([]);
            dashboardMap.setView([-2.5489, 118.0149], 5);
            return;
        }

        var latlngs = points.map(function (point) {
            var lat = Number(point.lat);
            var lng = Number(point.lng);
            if (!isFinite(lat) || !isFinite(lng)) {
                return null;
            }
            return [lat, lng, 0.8];
        }).filter(Boolean);

        heatLayer.setLatLngs(latlngs);
        var bounds = L.latLngBounds(latlngs.map(function (point) {
            return [point[0], point[1]];
        }));
        dashboardMap.fitBounds(bounds, { padding: [30, 30] });
    }

    function populateDashboardSelectors() {
        var currentYear = new Date().getFullYear();
        var currentMonth = new Date().getMonth() + 1;

        var monthOptions = "";
        for (var month = 1; month <= 12; month++) {
            monthOptions += '<option value="' + month + '"' + (month === currentMonth ? ' selected' : '') + '>' + month.toString().padStart(2, "0") + '</option>';
        }

        var yearOptions = "";
        for (var year = currentYear - 2; year <= currentYear + 1; year++) {
            yearOptions += '<option value="' + year + '"' + (year === currentYear ? ' selected' : '') + '>' + year + '</option>';
        }

        $salesmanDashboardMonth.html(monthOptions);
        $salesmanDashboardYear.html(yearOptions);
    }

    function populateSalesmanSelect() {
        var options = '<option value="">Pilih salesman</option>';
        salesmenCache.forEach(function (salesman) {
            options += '<option value="' + salesman.id + '">' + window.GeoMapCRM.escapeHtml(salesman.nama || salesman.nik || salesman.id) + '</option>';
        });
        $salesmanDashboardSelect.html(options);
    }

    function loadSalesmen() {
        $tableBody.html('<tr><td colspan="7" class="text-center text-muted py-4">Memuat data...</td></tr>');

        $.ajax({
            url: apiBase + "/salesman.php",
            method: "GET",
            dataType: "json",
            data: {
                search: $searchInput.val() || "",
                limit: 100,
                offset: 0
            }
        }).done(function (response) {
            salesmenCache = (response && response.data) ? response.data : [];
            renderRows(salesmenCache);
            populateSalesmanSelect();
            updateDashboardSummary();

            if (!selectedSalesmanId && salesmenCache.length) {
                selectedSalesmanId = String(salesmenCache[0].id);
                $salesmanDashboardSelect.val(selectedSalesmanId);
                updateDashboardSummary();
                loadDashboard();
            }
        }).fail(function (xhr) {
            var message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : "Gagal memuat data salesman.";
            $tableBody.html('<tr><td colspan="7" class="text-center text-danger py-4">' + window.GeoMapCRM.escapeHtml(message) + "</td></tr>");
        });
    }

    function loadUsers() {
        $.ajax({
            url: apiBase + "/users.php",
            method: "GET",
            dataType: "json",
            data: {
                limit: 500,
                offset: 0
            }
        }).done(function (response) {
            userOptions = (response && response.data) ? response.data : [];
            renderUserOptions();
        }).fail(function () {
            userOptions = [];
            renderUserOptions();
        });
    }

    function loadDashboard() {
        if (!selectedSalesmanId) {
            return;
        }

        $.ajax({
            url: apiBase + "/salesman_dashboard.php",
            method: "GET",
            dataType: "json",
            data: {
                salesman_id: selectedSalesmanId,
                tahun: $salesmanDashboardYear.val(),
                bulan: $salesmanDashboardMonth.val()
            }
        }).done(function (response) {
            var data = (response && response.data) ? response.data : {};
            var totalCustomer = safeNumber(data.total_customer);
            var realisasi = safeNumber(data.realisasi_kunjungan);
            var target = safeNumber(data.target_kunjungan);
            var percentage = target > 0 ? Number((((realisasi / target) * 100)).toFixed(2)) : 0;

            $("#salesmanTotalCustomer").text(totalCustomer);
            $("#salesmanRealisasiKunjungan").text(realisasi);
            $("#salesmanPersentaseTarget").text(percentage.toFixed(2) + "%");
            $("#salesmanCoverageCount").text((data.coverage_points || []).length);
            $("#salesmanTargetValue").text(target);
            $("#salesmanRealizationValue").text(realisasi);
            $("#salesmanCustomerValue").text(totalCustomer);
            $("#salesmanProgressLabel").text(percentage.toFixed(2) + "%");
            $salesmanProgressBar.css("width", percentage + "%");
            renderHeatMap(data.coverage_points || []);
        }).fail(function (xhr) {
            var message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : "Gagal memuat dashboard salesman.";
            window.GeoMapCRM.notify("danger", message);
        });
    }

    function resetForm() {
        $("#salesmanForm")[0].reset();
        $("#salesmanId").val("");
        $("#salesmanStatus").val("Aktif");
        $("#salesmanTargetKunjungan").val(0);
        $("#salesmanUserId").val("");
    }

    function openCreateModal() {
        resetForm();
        $("#salesmanModal .modal-title").text("Tambah Salesman");
        salesmanModal.show();
    }

    function openEditModal(id) {
        var salesman = salesmenCache.find(function (item) {
            return String(item.id) === String(id);
        });

        if (!salesman) {
            window.GeoMapCRM.notify("warning", "Data salesman tidak ditemukan.");
            return;
        }

        $("#salesmanId").val(salesman.id);
        $("#salesmanNik").val(salesman.nik || "");
        $("#salesmanNama").val(salesman.nama || "");
        $("#salesmanNoHp").val(salesman.no_hp || "");
        $("#salesmanEmail").val(salesman.email || "");
        $("#salesmanWilayahId").val(salesman.wilayah_id || "");
        $("#salesmanTargetKunjungan").val(salesman.target_kunjungan_bulan || 0);
        $("#salesmanFoto").val(salesman.foto || "");
        $("#salesmanStatus").val(formatStatus(salesman.status) === "Aktif" ? "Aktif" : "NonAktif");
        $("#salesmanUserId").val(salesman.user_id || "");
        $("#salesmanModal .modal-title").text("Edit Salesman");
        salesmanModal.show();
    }

    function collectPayload() {
        var userId = $("#salesmanUserId").val();
        if (userId === "") {
            userId = null;
        }

        return {
            nik: $("#salesmanNik").val().trim(),
            nama: $("#salesmanNama").val().trim(),
            no_hp: $("#salesmanNoHp").val().trim(),
            email: $("#salesmanEmail").val().trim(),
            wilayah_id: $("#salesmanWilayahId").val() === "" ? null : Number($("#salesmanWilayahId").val()),
            target_kunjungan_bulan: Number($("#salesmanTargetKunjungan").val() || 0),
            foto: $("#salesmanFoto").val().trim(),
            status: $("#salesmanStatus").val(),
            user_id: userId
        };
    }

    function submitForm(event) {
        event.preventDefault();

        var id = $("#salesmanId").val();
        var payload = collectPayload();
        var isUpdate = String(id).trim() !== "";
        var url = isUpdate ? apiBase + "/salesman_detail.php?id=" + encodeURIComponent(id) : apiBase + "/salesman.php";
        var method = isUpdate ? "PUT" : "POST";

        $.ajax({
            url: url,
            method: method,
            dataType: "json",
            contentType: "application/json; charset=utf-8",
            data: JSON.stringify(payload)
        }).done(function (response) {
            salesmanModal.hide();
            loadSalesmen();
            window.GeoMapCRM.notify("success", response.message || "Data salesman berhasil disimpan.");
        }).fail(function (xhr) {
            var message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : "Gagal menyimpan data salesman.";
            window.GeoMapCRM.notify("danger", message);
        });
    }

    function deleteSalesman(id) {
        var confirmed = window.confirm("Yakin ingin menghapus salesman ini?");
        if (!confirmed) {
            return;
        }

        $.ajax({
            url: apiBase + "/salesman_detail.php?id=" + encodeURIComponent(id),
            method: "DELETE",
            dataType: "json"
        }).done(function (response) {
            loadSalesmen();
            window.GeoMapCRM.notify("success", response.message || "Salesman berhasil dihapus.");
        }).fail(function (xhr) {
            var message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : "Gagal menghapus salesman.";
            window.GeoMapCRM.notify("danger", message);
        });
    }

    $("#btnSearchSalesman").on("click", loadSalesmen);
    $searchInput.on("keydown", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            loadSalesmen();
        }
    });
    $("#btnRefreshSalesmen").on("click", loadSalesmen);
    $("#btnAddSalesman").on("click", openCreateModal);
    $("#btnLoadSalesmanDashboard").on("click", function () {
        selectedSalesmanId = $salesmanDashboardSelect.val();
        updateDashboardSummary();
        loadDashboard();
    });

    $salesmanDashboardSelect.on("change", function () {
        selectedSalesmanId = $(this).val();
        updateDashboardSummary();
    });

    $tableBody.on("click", ".btn-dashboard", function () {
        var id = $(this).data("id");
        selectedSalesmanId = String(id);
        $salesmanDashboardSelect.val(selectedSalesmanId);
        updateDashboardSummary();
        loadDashboard();
    });

    $tableBody.on("click", ".btn-edit", function () {
        openEditModal($(this).data("id"));
    });

    $tableBody.on("click", ".btn-delete", function () {
        deleteSalesman($(this).data("id"));
    });

    $("#salesmanForm").on("submit", submitForm);

    populateDashboardSelectors();
    loadUsers();
    loadSalesmen();
})(window, jQuery);
