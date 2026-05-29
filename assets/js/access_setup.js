(function (window, $) {
    "use strict";

    var moduleEl = document.getElementById("accessSetupModule");
    if (!moduleEl) {
        return;
    }

    var apiBase = window.GeoMapCRM.apiBase;
    var $status = $("#accessSetupStatus");

    function badge(label, type) {
        return '<span class="badge text-bg-' + type + ' me-1">' + window.GeoMapCRM.escapeHtml(label) + "</span>";
    }

    function renderStatus(data) {
        if (!data) {
            $status.html('<div class="alert alert-warning mb-0">Data status tidak tersedia.</div>');
            return;
        }

        var user = data.user || {};
        var roles = Array.isArray(user.roles) ? user.roles : [];
        var permissions = Array.isArray(user.permissions) ? user.permissions : [];
        var issues = Array.isArray(data.issues) ? data.issues : [];
        var tableInfo = data.tables || {};
        var counts = data.counts || {};

        var roleHtml = roles.length ? roles.map(function (role) {
            var label = role.name || role.code || "-";
            return badge(label, "light");
        }).join("") : '<span class="text-muted">Belum ada role</span>';

        var issueHtml = "";
        if (issues.length) {
            issueHtml = '<div class="alert alert-warning mt-3 mb-0"><strong>Ditemukan issue:</strong><ul class="mb-0 mt-2">' +
                issues.map(function (issue) {
                    return "<li>" + window.GeoMapCRM.escapeHtml(issue) + "</li>";
                }).join("") +
                "</ul></div>";
        } else {
            issueHtml = '<div class="alert alert-success mt-3 mb-0">Tidak ada issue struktur akses.</div>';
        }

        var html = "";
        html += '<div class="row g-3">';
        html += '<div class="col-md-6">';
        html += '<div class="border rounded p-3 h-100">';
        html += '<div class="small text-muted">User</div>';
        html += '<div class="fw-semibold">' + window.GeoMapCRM.escapeHtml(user.full_name || user.username || "-") + "</div>";
        html += '<div class="small text-muted">@' + window.GeoMapCRM.escapeHtml(user.username || "-") + "</div>";
        html += '<div class="mt-2">' + roleHtml + "</div>";
        html += "</div></div>";

        html += '<div class="col-md-6">';
        html += '<div class="border rounded p-3 h-100">';
        html += '<div class="small text-muted">Ringkasan</div>';
        html += '<div>Super Admin: ' + (data.is_super_admin ? badge("YA", "success") : badge("TIDAK", "secondary")) + "</div>";
        html += '<div class="small mt-2">Jumlah role master: <strong>' + Number(counts.roles || 0) + "</strong></div>";
        html += '<div class="small">Jumlah permission master: <strong>' + Number(counts.permissions || 0) + "</strong></div>";
        html += '<div class="small">Permission aktif di session: <strong>' + permissions.length + "</strong></div>";
        html += '<div class="small mt-2">Pivot user-role: <strong>' + window.GeoMapCRM.escapeHtml(tableInfo.user_role_table || "TIDAK ADA") + "</strong></div>";
        html += '<div class="small">Pivot role-permission: <strong>' + window.GeoMapCRM.escapeHtml(tableInfo.permission_role_table || "TIDAK ADA") + "</strong></div>";
        html += "</div></div>";
        html += "</div>";

        html += issueHtml;
        $status.html(html);
    }

    function loadStatus() {
        $status.html('<div class="text-muted">Memuat status...</div>');

        $.ajax({
            url: apiBase + "/access_setup.php",
            method: "GET",
            dataType: "json"
        }).done(function (response) {
            renderStatus(response ? response.data : null);
        }).fail(function (xhr) {
            var message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : "Gagal memuat status akses.";
            $status.html('<div class="alert alert-danger mb-0">' + window.GeoMapCRM.escapeHtml(message) + "</div>");
        });
    }

    function runAutoFix() {
        if (!window.confirm("Jalankan auto-fix akses sekarang?")) {
            return;
        }

        $("#btnAutoFixAccess").prop("disabled", true).text("Memproses...");
        $.ajax({
            url: apiBase + "/access_setup.php",
            method: "POST",
            dataType: "json",
            contentType: "application/json; charset=utf-8",
            data: JSON.stringify({
                action: "auto_fix_access"
            })
        }).done(function (response) {
            window.GeoMapCRM.notify("success", response.message || "Auto-fix berhasil.");
            loadStatus();
            setTimeout(function () {
                window.location.reload();
            }, 1200);
        }).fail(function (xhr) {
            var message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : "Auto-fix gagal dijalankan.";
            window.GeoMapCRM.notify("danger", message);
            loadStatus();
        }).always(function () {
            $("#btnAutoFixAccess").prop("disabled", false).text("Auto Fix Akses");
        });
    }

    $("#btnReloadAccessSetup").on("click", loadStatus);
    $("#btnAutoFixAccess").on("click", runAutoFix);

    loadStatus();
})(window, jQuery);
