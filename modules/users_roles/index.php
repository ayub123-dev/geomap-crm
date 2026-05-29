<section id="usersRolesModule" class="row g-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center">
                    <div>
                        <p class="text-muted mb-1">Manajemen akses pengguna</p>
                        <h2 class="h4 mb-1">Modul User &amp; Role</h2>
                        <p class="mb-0 text-muted">Kelola akun user, assignment role, dan mapping permission per role.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button id="btnRefreshUsers" class="btn btn-outline-secondary" type="button">Refresh</button>
                        <button id="btnAddUser" class="btn btn-primary" type="button">Tambah User</button>
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
                <h3 class="h5 mb-1">Data User</h3>
                <p class="text-muted mb-0">Daftar akun pengguna aplikasi beserta role yang terpasang.</p>
            </div>
            <div class="d-flex gap-2 w-100 justify-content-md-end" style="max-width: 420px;">
                <input id="searchUsers" type="text" class="form-control" placeholder="Cari username, nama, email">
                <button id="btnSearchUsers" class="btn btn-outline-primary" type="button">Cari</button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Cabang</th>
                        <th>Roles</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
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
                <h3 class="h5 mb-1">Permission per Role</h3>
                <p class="text-muted mb-0">Pilih role lalu centang permission yang ingin diaktifkan.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap justify-content-lg-end">
                <select id="rolePermissionSelect" class="form-select" style="min-width: 280px;">
                    <option value="">Pilih role</option>
                </select>
                <button id="btnSaveRolePermissions" class="btn btn-primary" type="button">Simpan Permission</button>
            </div>
        </div>

        <div id="rolePermissionContainer" class="row g-2">
            <div class="col-12">
                <div class="alert alert-light border mb-0">Pilih role untuk menampilkan daftar permission.</div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <form id="userForm">
                <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars(\App\Core\Csrf::token()); ?>">
                <div class="modal-header">
                    <h5 class="modal-title">User Form</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="userId" name="id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="userUsername" name="username" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="userFullName" name="full_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="userEmail" name="email">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="userStatus" name="status">
                                <option value="Aktif">Aktif</option>
                                <option value="NonAktif">NonAktif</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Database Cabang</label>
                            <select class="form-select" id="userDatabaseAlias" name="database_alias">
                                <option value="">Default / Central</option>
                            </select>
                            <div class="form-text">Pilih cabang untuk menentukan database operasional yang dipakai setelah login.</div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Password <span id="passwordHint" class="text-muted">(wajib untuk user baru)</span></label>
                            <input type="password" class="form-control" id="userPassword" name="password" autocomplete="new-password">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <div id="userRolesContainer" class="row g-2"></div>
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
