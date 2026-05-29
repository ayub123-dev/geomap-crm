(function (window, $) {
    "use strict";

    var moduleEl = document.getElementById("customersModule");
    if (!moduleEl) {
        return;
    }

    var apiBase = window.GeoMapCRM.apiBase;
    var $tableBody = $("#customersTableBody");
    var $searchInput = $("#searchCustomer");
    var modal = new bootstrap.Modal(document.getElementById("customerModal"));
    var customersCache = [];

    function fetchCustomers() {
        $tableBody.html('<tr><td colspan="6" class="text-center text-muted py-4">Memuat data...</td></tr>');

        $.ajax({
            url: apiBase + "/customers.php",
            method: "GET",
            dataType: "json",
            data: {
                search: $searchInput.val() || "",
                limit: 100,
                offset: 0
            }
        }).done(function (response) {
            customersCache = (response && response.data) ? response.data : [];
            renderRows(customersCache);
        }).fail(function (xhr) {
            var message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : "Gagal memuat data customer.";
            $tableBody.html('<tr><td colspan="6" class="text-center text-danger py-4">' + window.GeoMapCRM.escapeHtml(message) + "</td></tr>");
        });
    }

    function renderRows(rows) {
        if (!rows.length) {
            $tableBody.html('<tr><td colspan="6" class="text-center text-muted py-4">Data customer belum tersedia.</td></tr>');
            return;
        }

        var html = "";
        rows.forEach(function (row) {
            var coordinate = "-";
            if (row.latitude !== null && row.longitude !== null) {
                coordinate = Number(row.latitude).toFixed(5) + ", " + Number(row.longitude).toFixed(5);
            }

            html += "<tr>";
            html += "<td>" + window.GeoMapCRM.escapeHtml(row.customer_code || "-") + "</td>";
            html += "<td>" + window.GeoMapCRM.escapeHtml(row.name || "-") + "</td>";
            html += "<td>";
            html += "<div class='small'>" + window.GeoMapCRM.escapeHtml(row.email || "-") + "</div>";
            html += "<div class='small text-muted'>" + window.GeoMapCRM.escapeHtml(row.phone || "-") + "</div>";
            html += "</td>";
            html += "<td><span class='badge " + (row.status === "active" ? "text-bg-success" : "text-bg-secondary") + "'>" + window.GeoMapCRM.escapeHtml(row.status || "inactive") + "</span></td>";
            html += "<td class='small'>" + window.GeoMapCRM.escapeHtml(coordinate) + "</td>";
            html += "<td class='text-end'>";
            html += "<button class='btn btn-sm btn-outline-primary me-1 btn-edit' data-id='" + row.id + "'>Edit</button>";
            html += "<button class='btn btn-sm btn-outline-danger btn-delete' data-id='" + row.id + "'>Hapus</button>";
            html += "</td>";
            html += "</tr>";
        });

        $tableBody.html(html);
    }

    function resetForm() {
        $("#customerForm")[0].reset();
        $("#customerId").val("");
        $("#customerStatus").val("active");
    }

    function openCreateModal() {
        resetForm();
        document.querySelector("#customerModal .modal-title").textContent = "Tambah Customer";
        modal.show();
    }

    function openEditModal(id) {
        var customer = customersCache.find(function (item) {
            return String(item.id) === String(id);
        });
        if (!customer) {
            window.GeoMapCRM.notify("warning", "Data customer tidak ditemukan pada cache.");
            return;
        }

        $("#customerId").val(customer.id);
        $("#customerCode").val(customer.customer_code || "");
        $("#customerName").val(customer.name || "");
        $("#customerEmail").val(customer.email || "");
        $("#customerPhone").val(customer.phone || "");
        $("#customerAddress").val(customer.address || "");
        $("#customerLatitude").val(customer.latitude || "");
        $("#customerLongitude").val(customer.longitude || "");
        $("#customerStatus").val(customer.status || "active");
        document.querySelector("#customerModal .modal-title").textContent = "Edit Customer";
        modal.show();
    }

    function collectFormPayload() {
        var latitude = $("#customerLatitude").val();
        var longitude = $("#customerLongitude").val();

        return {
            customer_code: $("#customerCode").val().trim(),
            name: $("#customerName").val().trim(),
            email: $("#customerEmail").val().trim(),
            phone: $("#customerPhone").val().trim(),
            address: $("#customerAddress").val().trim(),
            latitude: latitude === "" ? null : Number(latitude),
            longitude: longitude === "" ? null : Number(longitude),
            status: $("#customerStatus").val()
        };
    }

    function submitForm(event) {
        event.preventDefault();

        var id = $("#customerId").val();
        var isUpdate = String(id).trim() !== "";
        var endpoint = isUpdate ? apiBase + "/customer.php?id=" + encodeURIComponent(id) : apiBase + "/customers.php";

        $.ajax({
            url: endpoint,
            method: isUpdate ? "PUT" : "POST",
            dataType: "json",
            contentType: "application/json; charset=utf-8",
            data: JSON.stringify(collectFormPayload())
        }).done(function (response) {
            modal.hide();
            fetchCustomers();
            window.GeoMapCRM.notify("success", response.message || "Data customer berhasil disimpan.");
        }).fail(function (xhr) {
            var message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : "Gagal menyimpan data customer.";
            window.GeoMapCRM.notify("danger", message);
        });
    }

    function removeCustomer(id) {
        var confirmed = window.confirm("Yakin ingin menghapus customer ini? Data akan di-soft delete.");
        if (!confirmed) {
            return;
        }

        $.ajax({
            url: apiBase + "/customer.php?id=" + encodeURIComponent(id),
            method: "DELETE",
            dataType: "json"
        }).done(function (response) {
            fetchCustomers();
            window.GeoMapCRM.notify("success", response.message || "Customer berhasil dihapus.");
        }).fail(function (xhr) {
            var message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : "Gagal menghapus customer.";
            window.GeoMapCRM.notify("danger", message);
        });
    }

    function useMyLocation() {
        if (!("geolocation" in navigator)) {
            window.GeoMapCRM.notify("warning", "Browser tidak mendukung geolocation.");
            return;
        }

        navigator.geolocation.getCurrentPosition(function (position) {
            $("#customerLatitude").val(position.coords.latitude.toFixed(7));
            $("#customerLongitude").val(position.coords.longitude.toFixed(7));
        }, function () {
            window.GeoMapCRM.notify("warning", "Izin lokasi ditolak atau gagal mendapatkan lokasi.");
        });
    }

    $("#btnSearchCustomer").on("click", fetchCustomers);
    $searchInput.on("keydown", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            fetchCustomers();
        }
    });
    $("#btnAddCustomer").on("click", openCreateModal);
    $("#btnUseMyLocation").on("click", useMyLocation);
    $("#customerForm").on("submit", submitForm);

    $tableBody.on("click", ".btn-edit", function () {
        openEditModal($(this).data("id"));
    });

    $tableBody.on("click", ".btn-delete", function () {
        removeCustomer($(this).data("id"));
    });

    fetchCustomers();
})(window, jQuery);
