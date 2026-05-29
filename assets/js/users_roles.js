(function (window, $) {
    "use strict";

    var moduleEl = document.getElementById("usersRolesModule");
    if (!moduleEl) {
        return;
    }

    var apiBase = window.GeoMapCRM.apiBase;
    var userModal = new bootstrap.Modal(document.getElementById("userModal"));
    var $usersTableBody = $("#usersTableBody");
    var $searchUsers = $("#searchUsers");
    var $rolePermissionSelect = $("#rolePermissionSelect");
    var $rolePermissionContainer = $("#rolePermissionContainer");
    var $userRolesContainer = $("#userRolesContainer");
    var $userDatabaseAlias = $("#userDatabaseAlias");

    var usersCache = [];
    var rolesCache = [];
    var permissionsCache = [];
    var connectionsCache = [];
    var selectedRolePermissionIds = [];

    function groupedPermissions(permissions) {
        var groups = {};
        permissions.forEach(function (permission) {
            var moduleName = permission.module || "lainnya";
            if (!groups[moduleName]) {
                groups[moduleName] = [];
            }
            groups[moduleName].push(permission);
        });
        return groups;
    }

    function renderUserRows(rows) {
        if (!rows.length) {
            $usersTableBody.html('<tr><td colspan="7" class="text-center text-muted py-4">Data user belum tersedia.</td></tr>');
            return;
        }

        var html = "";
        rows.forEach(function (user) {
            var roles = Array.isArray(user.roles) ? user.roles : [];
            var roleBadges = roles.length ? roles.map(function (role) {
                return '<span class="badge text-bg-light border me-1">' + window.GeoMapCRM.escapeHtml(role.name || role.code || "") + "</span>";
            }).join("") : '<span class="text-muted">-</span>';
            var branchLabel = user.database_label || user.database_alias || "Default / Central";

            var status = String(user.status || "");
            var isActive = status.toLowerCase() === "aktif" || status.toLowerCase() === "active";

            html += "<tr>";
            html += "<td>" + window.GeoMapCRM.escapeHtml(user.username || "-") + "</td>";
            html += "<td>" + window.GeoMapCRM.escapeHtml(user.full_name || "-") + "</td>";
            html += "<td>" + window.GeoMapCRM.escapeHtml(user.email || "-") + "</td>";
            html += "<td>" + window.GeoMapCRM.escapeHtml(branchLabel) + "</td>";
            html += "<td>" + roleBadges + "</td>";
            html += "<td><span class='badge " + (isActive ? "text-bg-success" : "text-bg-secondary") + "'>" + window.GeoMapCRM.escapeHtml(status || "-") + "</span></td>";
            html += "<td class='text-end'>";
            html += "<button class='btn btn-sm btn-outline-secondary me-1 btn-edit-user' data-id='" + user.id + "'>Edit</button>";
            html += "<button class='btn btn-sm btn-outline-danger btn-delete-user' data-id='" + user.id + "'>Hapus</button>";
            html += "</td>";
            html += "</tr>";
        });

        $usersTableBody.html(html);
    }

    function renderDatabaseOptions(selectedAlias) {
        var html = '<option value="">Default / Central</option>';
        connectionsCache.forEach(function (connection) {
            var alias = connection.alias || connection.name || connection.key || "";
            if (!alias) {
                return;
            }

            var label = connection.label || alias;
            html += '<option value="' + window.GeoMapCRM.escapeHtml(alias) + '">' + window.GeoMapCRM.escapeHtml(label) + '</option>';
        });

        $userDatabaseAlias.html(html);
        $userDatabaseAlias.val(selectedAlias || "");
    }

    function normalizeConnections(responseConnections) {
        if (!responseConnections) {
            return [];
        }

        if (Array.isArray(responseConnections)) {
            return responseConnections.map(function (connection) {
                return {
                    alias: connection.alias || connection.name || connection.key || "",
                    label: connection.label || connection.alias || connection.name || connection.key || ""
                };
            }).filter(function (connection) {
                return connection.alias !== "";
            });
        }

        return Object.keys(responseConnections).map(function (alias) {
            var connection = responseConnections[alias] || {};
            return {
                alias: alias,
                label: connection.label || alias
            };
        });
    }

    function renderRoleSelectOptions() {
        var options = '<option value="">Pilih role</option>';
        rolesCache.forEach(function (role) {
            options += '<option value="' + role.id + '">' + window.GeoMapCRM.escapeHtml((role.name || role.code || "").toUpperCase()) + "</option>";
        });
        $rolePermissionSelect.html(options);
    }

    function renderUserRoleCheckboxes(selectedRoleIds) {
        selectedRoleIds = Array.isArray(selectedRoleIds) ? selectedRoleIds.map(function (id) { return String(id); }) : [];
        var html = "";
        rolesCache.forEach(function (role) {
            var checked = selectedRoleIds.indexOf(String(role.id)) !== -1 ? " checked" : "";
            html += '<div class="col-12 col-md-6 col-lg-4">';
            html += '<label class="role-select-card" for="roleCheck' + role.id + '">';
            html += '<input class="role-select-input user-role-checkbox" type="checkbox" value="' + role.id + '" id="roleCheck' + role.id + '"' + checked + ">";
            html += '<span class="role-select-content">';
            html += '<strong class="role-select-title">' + window.GeoMapCRM.escapeHtml(role.name || role.code || "") + "</strong>";
            html += '<span class="role-select-desc">' + window.GeoMapCRM.escapeHtml(role.description || "-") + "</span>";
            html += "</span>";
            html += "</label>";
            html += "</div>";
        });
        $userRolesContainer.html(html);
    }

    function renderRolePermissions() {
        var roleId = $rolePermissionSelect.val();
        if (!roleId) {
            $rolePermissionContainer.html('<div class="col-12"><div class="alert alert-light border mb-0">Pilih role untuk menampilkan daftar permission.</div></div>');
            return;
        }

        var selectedMap = {};
        selectedRolePermissionIds.forEach(function (id) {
            selectedMap[String(id)] = true;
        });

        var groups = groupedPermissions(permissionsCache);
        var modules = Object.keys(groups).sort();
        var html = "";
        modules.forEach(function (moduleName) {
            html += '<div class="col-12 col-lg-6">';
            html += '<div class="border rounded p-3 h-100">';
            html += '<h6 class="text-uppercase small text-muted mb-3">' + window.GeoMapCRM.escapeHtml(moduleName) + "</h6>";

            groups[moduleName].forEach(function (permission) {
                var checked = selectedMap[String(permission.id)] ? " checked" : "";
                html += '<label class="permission-line" for="perm' + permission.id + '">';
                html += '<input class="permission-line-input role-permission-checkbox" type="checkbox" value="' + permission.id + '" id="perm' + permission.id + '"' + checked + ">";
                html += '<span class="permission-line-body">';
                html += '<span class="permission-line-title">' + window.GeoMapCRM.escapeHtml(permission.name || permission.code || "") + "</span>";
                html += '<small class="permission-line-code">' + window.GeoMapCRM.escapeHtml(permission.code || "") + "</small>";
                html += "</span>";
                html += "</label>";
            });

            html += "</div>";
            html += "</div>";
        });

        $rolePermissionContainer.html(html || '<div class="col-12"><div class="alert alert-warning mb-0">Permission belum tersedia.</div></div>');
    }

    function loadUsers() {
        $usersTableBody.html('<tr><td colspan="7" class="text-center text-muted py-4">Memuat data...</td></tr>');

        $.ajax({
            url: apiBase + "/users.php",
            method: "GET",
            dataType: "json",
            data: {
                search: $searchUsers.val() || "",
                limit: 200,
                offset: 0
            }
        }).done(function (response) {
            usersCache = (response && response.data) ? response.data : [];
            if (!rolesCache.length && response && response.roles) {
                rolesCache = response.roles;
            }
            if (!permissionsCache.length && response && response.permissions) {
                permissionsCache = response.permissions;
            }
            connectionsCache = normalizeConnections(response && response.connections ? response.connections : []);
            renderUserRows(usersCache);
            renderDatabaseOptions("");
            renderRoleSelectOptions();
            renderUserRoleCheckboxes([]);
        }).fail(function (xhr) {
            var message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : "Gagal memuat data user.";
            $usersTableBody.html('<tr><td colspan="7" class="text-center text-danger py-4">' + window.GeoMapCRM.escapeHtml(message) + "</td></tr>");
        });
    }

    function loadRolePermissionMeta() {
        return $.ajax({
            url: apiBase + "/roles_permissions.php",
            method: "GET",
            dataType: "json"
        }).done(function (response) {
            rolesCache = (response && response.roles) ? response.roles : [];
            permissionsCache = (response && response.permissions) ? response.permissions : [];
            renderRoleSelectOptions();
            renderUserRoleCheckboxes([]);
        }).fail(function () {
            rolesCache = [];
            permissionsCache = [];
            renderRoleSelectOptions();
            renderUserRoleCheckboxes([]);
        });
    }

    function loadSelectedRolePermissions(roleId) {
        selectedRolePermissionIds = [];
        renderRolePermissions();
        if (!roleId) {
            return;
        }

        $.ajax({
            url: apiBase + "/roles_permissions.php",
            method: "GET",
            dataType: "json",
            data: {
                role_id: roleId
            }
        }).done(function (response) {
            var rows = (response && response.permissions) ? response.permissions : [];
            selectedRolePermissionIds = rows.map(function (row) { return Number(row.id); });
            renderRolePermissions();
        }).fail(function (xhr) {
            var message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : "Gagal memuat permission role.";
            window.GeoMapCRM.notify("danger", message);
            renderRolePermissions();
        });
    }

    function resetUserForm() {
        $("#userForm")[0].reset();
        $("#userId").val("");
        $("#userStatus").val("Aktif");
        $("#passwordHint").text("(wajib untuk user baru)");
        renderDatabaseOptions("");
        renderUserRoleCheckboxes([]);
    }

    function openCreateUserModal() {
        resetUserForm();
        $("#userModal .modal-title").text("Tambah User");
        userModal.show();
    }

    function openEditUserModal(id) {
        var user = usersCache.find(function (item) {
            return String(item.id) === String(id);
        });

        if (!user) {
            window.GeoMapCRM.notify("warning", "Data user tidak ditemukan.");
            return;
        }

        var selectedRoleIds = Array.isArray(user.roles) ? user.roles.map(function (role) { return role.id; }) : [];

        $("#userId").val(user.id);
        $("#userUsername").val(user.username || "");
        $("#userFullName").val(user.full_name || "");
        $("#userEmail").val(user.email || "");
        $("#userStatus").val(user.status || "Aktif");
        $("#userPassword").val("");
        $("#passwordHint").text("(opsional, isi jika ingin ganti password)");
        renderDatabaseOptions(user.database_alias || "");
        renderUserRoleCheckboxes(selectedRoleIds);

        $("#userModal .modal-title").text("Edit User");
        userModal.show();
    }

    function collectUserPayload() {
        var roleIds = [];
        $(".user-role-checkbox:checked").each(function () {
            roleIds.push(Number($(this).val()));
        });

        return {
            username: $("#userUsername").val().trim(),
            full_name: $("#userFullName").val().trim(),
            email: $("#userEmail").val().trim(),
            password: $("#userPassword").val(),
            status: $("#userStatus").val(),
            database_alias: $("#userDatabaseAlias").val(),
            role_ids: roleIds
        };
    }

    function submitUserForm(event) {
        event.preventDefault();

        var payload = collectUserPayload();
        if (!payload.role_ids.length) {
            window.GeoMapCRM.notify("warning", "Pilih minimal 1 role.");
            return;
        }

        var id = $("#userId").val();
        var isUpdate = String(id).trim() !== "";
        var url = isUpdate ? apiBase + "/users_detail.php?id=" + encodeURIComponent(id) : apiBase + "/users.php";
        var method = isUpdate ? "PUT" : "POST";

        if (isUpdate && payload.password.trim() === "") {
            delete payload.password;
        }

        $.ajax({
            url: url,
            method: method,
            dataType: "json",
            contentType: "application/json; charset=utf-8",
            data: JSON.stringify(payload)
        }).done(function (response) {
            userModal.hide();
            loadUsers();
            window.GeoMapCRM.notify("success", response.message || "Data user berhasil disimpan.");
        }).fail(function (xhr) {
            var message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : "Gagal menyimpan data user.";
            window.GeoMapCRM.notify("danger", message);
        });
    }

    function deleteUser(id) {
        if (!window.confirm("Yakin ingin menghapus user ini?")) {
            return;
        }

        $.ajax({
            url: apiBase + "/users_detail.php?id=" + encodeURIComponent(id),
            method: "DELETE",
            dataType: "json"
        }).done(function (response) {
            loadUsers();
            window.GeoMapCRM.notify("success", response.message || "User berhasil dihapus.");
        }).fail(function (xhr) {
            var message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : "Gagal menghapus user.";
            window.GeoMapCRM.notify("danger", message);
        });
    }

    function saveRolePermissions() {
        var roleId = Number($rolePermissionSelect.val() || 0);
        if (!roleId) {
            window.GeoMapCRM.notify("warning", "Pilih role terlebih dahulu.");
            return;
        }

        var permissionIds = [];
        $(".role-permission-checkbox:checked").each(function () {
            permissionIds.push(Number($(this).val()));
        });

        $.ajax({
            url: apiBase + "/roles_permissions.php",
            method: "POST",
            dataType: "json",
            contentType: "application/json; charset=utf-8",
            data: JSON.stringify({
                role_id: roleId,
                permission_ids: permissionIds
            })
        }).done(function (response) {
            window.GeoMapCRM.notify("success", response.message || "Permission role berhasil diperbarui.");
            loadSelectedRolePermissions(roleId);
            loadUsers();
        }).fail(function (xhr) {
            var message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : "Gagal memperbarui permission role.";
            window.GeoMapCRM.notify("danger", message);
        });
    }

    $("#btnRefreshUsers").on("click", function () {
        loadRolePermissionMeta().always(loadUsers);
    });
    $("#btnAddUser").on("click", openCreateUserModal);
    $("#btnSearchUsers").on("click", loadUsers);
    $searchUsers.on("keydown", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            loadUsers();
        }
    });
    $rolePermissionSelect.on("change", function () {
        loadSelectedRolePermissions($(this).val());
    });
    $("#btnSaveRolePermissions").on("click", saveRolePermissions);
    $("#userForm").on("submit", submitUserForm);

    $usersTableBody.on("click", ".btn-edit-user", function () {
        openEditUserModal($(this).data("id"));
    });

    $usersTableBody.on("click", ".btn-delete-user", function () {
        deleteUser($(this).data("id"));
    });

    $.when(loadRolePermissionMeta()).always(function () {
        loadUsers();
    });
})(window, jQuery);
