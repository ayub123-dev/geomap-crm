SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;

IF OBJECT_ID(N'dbo.customer_inti', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.customer_inti (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        kode_customer NVARCHAR(50) NOT NULL,
        nama_toko NVARCHAR(200) NOT NULL,
        pemilik NVARCHAR(150) NULL,
        no_hp NVARCHAR(30) NULL,
        alamat NVARCHAR(MAX) NULL,
        kelurahan NVARCHAR(120) NULL,
        kecamatan NVARCHAR(120) NULL,
        kota NVARCHAR(120) NULL,
        provinsi NVARCHAR(120) NULL,
        lat DECIMAL(10,8) NULL,
        lng DECIMAL(10,8) NULL,
        kategori_toko NVARCHAR(80) NULL,
        omzet_estimasi DECIMAL(14,2) NOT NULL CONSTRAINT df_ci_omzet DEFAULT 0,
        salesman_id INT NULL,
        status NVARCHAR(50) NOT NULL CONSTRAINT df_ci_status DEFAULT 'Aktif' CONSTRAINT ck_ci_status CHECK (status IN ('Aktif','NonAktif')),
        foto_toko NVARCHAR(255) NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_ci_created DEFAULT GETDATE(),
        updated_at DATETIME2 NOT NULL CONSTRAINT df_ci_updated DEFAULT GETDATE(),
        deleted_at DATETIME2 NULL,
        CONSTRAINT uq_ci_kode_customer UNIQUE (kode_customer)
    );
    CREATE INDEX idx_ci_lat_lng ON dbo.customer_inti(lat, lng);
    CREATE INDEX idx_ci_salesman ON dbo.customer_inti(salesman_id);
END;

IF OBJECT_ID(N'dbo.customer_existing', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.customer_existing (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        kode_existing NVARCHAR(50) NOT NULL,
        nama_toko NVARCHAR(200) NOT NULL,
        brand_kompetitor NVARCHAR(150) NULL,
        alamat NVARCHAR(MAX) NULL,
        lat DECIMAL(10,8) NULL,
        lng DECIMAL(10,8) NULL,
        sumber_data NVARCHAR(50) NOT NULL CONSTRAINT df_ce_sumber DEFAULT 'Internal'
            CONSTRAINT ck_ce_sumber CHECK (sumber_data IN ('Internal','Survei Lapangan','Import')),
        catatan NVARCHAR(MAX) NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_ce_created DEFAULT GETDATE()
    );
    CREATE INDEX idx_ce_lat_lng ON dbo.customer_existing(lat, lng);
END;

IF OBJECT_ID(N'dbo.salesman', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.salesman (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        nik NVARCHAR(30) NOT NULL,
        nama NVARCHAR(150) NOT NULL,
        no_hp NVARCHAR(30) NULL,
        email NVARCHAR(150) NULL,
        wilayah_id INT NULL,
        target_kunjungan_bulan INT NOT NULL CONSTRAINT df_sales_target DEFAULT 0,
        foto NVARCHAR(255) NULL,
        status NVARCHAR(50) NOT NULL CONSTRAINT df_sales_status DEFAULT 'Aktif' CONSTRAINT ck_sales_status CHECK (status IN ('Aktif','NonAktif')),
        user_id INT NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_sales_created DEFAULT GETDATE(),
        updated_at DATETIME2 NOT NULL CONSTRAINT df_sales_updated DEFAULT GETDATE(),
        CONSTRAINT uq_sales_nik UNIQUE (nik),
        CONSTRAINT uq_sales_user_id UNIQUE (user_id)
    );
END;

IF OBJECT_ID(N'dbo.roles', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.roles (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        code NVARCHAR(50) NOT NULL,
        name NVARCHAR(100) NOT NULL,
        description NVARCHAR(MAX) NULL,
        CONSTRAINT uq_roles_code UNIQUE (code)
    );
END;

IF OBJECT_ID(N'dbo.permissions', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.permissions (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        code NVARCHAR(120) NOT NULL,
        name NVARCHAR(120) NOT NULL,
        module NVARCHAR(80) NOT NULL,
        CONSTRAINT uq_permissions_code UNIQUE (code)
    );
END;

IF OBJECT_ID(N'dbo.users', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.users (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        username NVARCHAR(100) NOT NULL,
        full_name NVARCHAR(150) NOT NULL,
        email NVARCHAR(150) NULL,
        password_hash NVARCHAR(255) NOT NULL,
        status NVARCHAR(50) NOT NULL CONSTRAINT df_users_status_md DEFAULT 'Aktif' CONSTRAINT ck_users_status_md CHECK (status IN ('Aktif','NonAktif')),
        created_at DATETIME2 NOT NULL CONSTRAINT df_users_created_md DEFAULT GETDATE(),
        updated_at DATETIME2 NOT NULL CONSTRAINT df_users_updated_md DEFAULT GETDATE(),
        CONSTRAINT uq_users_username_md UNIQUE (username),
        CONSTRAINT uq_users_email_md UNIQUE (email)
    );
END;

IF OBJECT_ID(N'dbo.role_user', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.role_user (
        role_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_role_user_created DEFAULT GETDATE(),
        CONSTRAINT pk_role_user PRIMARY KEY (role_id, user_id),
        CONSTRAINT fk_role_user_role FOREIGN KEY (role_id) REFERENCES dbo.roles(id) ON DELETE CASCADE,
        CONSTRAINT fk_role_user_user FOREIGN KEY (user_id) REFERENCES dbo.users(id) ON DELETE CASCADE
    );
END;

IF OBJECT_ID(N'dbo.permission_role', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.permission_role (
        permission_id INT NOT NULL,
        role_id INT NOT NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_permission_role_created DEFAULT GETDATE(),
        CONSTRAINT pk_permission_role PRIMARY KEY (permission_id, role_id),
        CONSTRAINT fk_permission_role_permission FOREIGN KEY (permission_id) REFERENCES dbo.permissions(id) ON DELETE CASCADE,
        CONSTRAINT fk_permission_role_role FOREIGN KEY (role_id) REFERENCES dbo.roles(id) ON DELETE CASCADE
    );
END;

IF OBJECT_ID(N'dbo.target_realisasi', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.target_realisasi (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        salesman_id INT NOT NULL,
        tahun INT NOT NULL,
        bulan INT NOT NULL,
        target_kunjungan INT NOT NULL CONSTRAINT df_tr_target DEFAULT 0,
        realisasi_kunjungan INT NOT NULL CONSTRAINT df_tr_real DEFAULT 0,
        created_at DATETIME2 NOT NULL CONSTRAINT df_tr_created DEFAULT GETDATE(),
        updated_at DATETIME2 NOT NULL CONSTRAINT df_tr_updated DEFAULT GETDATE(),
        CONSTRAINT uq_tr_salesman_periode UNIQUE (salesman_id, tahun, bulan),
        CONSTRAINT fk_tr_salesman FOREIGN KEY (salesman_id) REFERENCES dbo.salesman(id) ON DELETE CASCADE
    );
END;

IF OBJECT_ID(N'dbo.import_log', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.import_log (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        module_name NVARCHAR(100) NOT NULL,
        file_name NVARCHAR(255) NULL,
        total_rows INT NOT NULL CONSTRAINT df_il_total DEFAULT 0,
        success_rows INT NOT NULL CONSTRAINT df_il_success DEFAULT 0,
        failed_rows INT NOT NULL CONSTRAINT df_il_failed DEFAULT 0,
        status NVARCHAR(50) NOT NULL CONSTRAINT df_il_status DEFAULT 'queued'
            CONSTRAINT ck_il_status CHECK (status IN ('queued', 'processing', 'completed', 'failed')),
        error_message NVARCHAR(MAX) NULL,
        created_by INT NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_il_created DEFAULT GETDATE(),
        CONSTRAINT fk_il_created_by FOREIGN KEY (created_by) REFERENCES dbo.users(id) ON DELETE SET NULL
    );
END;

IF OBJECT_ID(N'dbo.audit_log', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.audit_log (
        id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        user_id INT NULL,
        module_name NVARCHAR(100) NOT NULL,
        action_name NVARCHAR(80) NOT NULL,
        field_name NVARCHAR(120) NULL,
        old_value NVARCHAR(MAX) NULL,
        new_value NVARCHAR(MAX) NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_al_created DEFAULT GETDATE(),
        CONSTRAINT fk_al_user FOREIGN KEY (user_id) REFERENCES dbo.users(id) ON DELETE SET NULL
    );
    CREATE INDEX idx_al_user_created ON dbo.audit_log(user_id, created_at);
END;

IF OBJECT_ID(N'dbo.notifikasi', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.notifikasi (
        id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        user_id INT NULL,
        title NVARCHAR(200) NOT NULL,
        message NVARCHAR(MAX) NOT NULL,
        channel NVARCHAR(50) NOT NULL CONSTRAINT df_notif_channel DEFAULT 'in-app'
            CONSTRAINT ck_notif_channel CHECK (channel IN ('in-app','whatsapp')),
        status NVARCHAR(50) NOT NULL CONSTRAINT df_notif_status DEFAULT 'draft'
            CONSTRAINT ck_notif_status CHECK (status IN ('draft','queued','sent','failed')),
        created_at DATETIME2 NOT NULL CONSTRAINT df_notif_created DEFAULT GETDATE(),
        CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES dbo.users(id) ON DELETE SET NULL
    );
END;

IF NOT EXISTS (SELECT 1 FROM dbo.roles WHERE code = 'SUPER_ADMIN')
    INSERT INTO dbo.roles (code, name, description) VALUES ('SUPER_ADMIN', 'SUPER ADMIN', 'Akses semua fitur + konfigurasi sistem + multi-perusahaan');
IF NOT EXISTS (SELECT 1 FROM dbo.roles WHERE code = 'ADMIN')
    INSERT INTO dbo.roles (code, name, description) VALUES ('ADMIN', 'ADMIN', 'CRUD semua master, lihat semua laporan, manage salesman');
IF NOT EXISTS (SELECT 1 FROM dbo.roles WHERE code = 'SUPERVISOR')
    INSERT INTO dbo.roles (code, name, description) VALUES ('SUPERVISOR', 'SUPERVISOR', 'Lihat area sendiri, approve prospek, assign target');
IF NOT EXISTS (SELECT 1 FROM dbo.roles WHERE code = 'SALESMAN')
    INSERT INTO dbo.roles (code, name, description) VALUES ('SALESMAN', 'SALESMAN', 'Mobile app, customer sendiri, input kunjungan');
IF NOT EXISTS (SELECT 1 FROM dbo.roles WHERE code = 'VIEWER')
    INSERT INTO dbo.roles (code, name, description) VALUES ('VIEWER', 'VIEWER', 'Read-only peta dan laporan');

IF NOT EXISTS (SELECT 1 FROM dbo.permissions WHERE code = 'customer_inti.view')
    INSERT INTO dbo.permissions (code, name, module) VALUES ('customer_inti.view', 'Lihat Customer Inti', 'customer_inti');
IF NOT EXISTS (SELECT 1 FROM dbo.permissions WHERE code = 'customer_inti.create')
    INSERT INTO dbo.permissions (code, name, module) VALUES ('customer_inti.create', 'Tambah Customer Inti', 'customer_inti');
IF NOT EXISTS (SELECT 1 FROM dbo.permissions WHERE code = 'customer_inti.update')
    INSERT INTO dbo.permissions (code, name, module) VALUES ('customer_inti.update', 'Ubah Customer Inti', 'customer_inti');
IF NOT EXISTS (SELECT 1 FROM dbo.permissions WHERE code = 'customer_inti.delete')
    INSERT INTO dbo.permissions (code, name, module) VALUES ('customer_inti.delete', 'Hapus Customer Inti', 'customer_inti');
IF NOT EXISTS (SELECT 1 FROM dbo.permissions WHERE code = 'customer_existing.view')
    INSERT INTO dbo.permissions (code, name, module) VALUES ('customer_existing.view', 'Lihat Customer Existing', 'customer_existing');
IF NOT EXISTS (SELECT 1 FROM dbo.permissions WHERE code = 'customer_existing.create')
    INSERT INTO dbo.permissions (code, name, module) VALUES ('customer_existing.create', 'Tambah Customer Existing', 'customer_existing');
IF NOT EXISTS (SELECT 1 FROM dbo.permissions WHERE code = 'customer_existing.update')
    INSERT INTO dbo.permissions (code, name, module) VALUES ('customer_existing.update', 'Ubah Customer Existing', 'customer_existing');
IF NOT EXISTS (SELECT 1 FROM dbo.permissions WHERE code = 'customer_existing.delete')
    INSERT INTO dbo.permissions (code, name, module) VALUES ('customer_existing.delete', 'Hapus Customer Existing', 'customer_existing');
IF NOT EXISTS (SELECT 1 FROM dbo.permissions WHERE code = 'salesman.view')
    INSERT INTO dbo.permissions (code, name, module) VALUES ('salesman.view', 'Lihat Salesman', 'salesman');
IF NOT EXISTS (SELECT 1 FROM dbo.permissions WHERE code = 'salesman.manage')
    INSERT INTO dbo.permissions (code, name, module) VALUES ('salesman.manage', 'Kelola Salesman', 'salesman');
IF NOT EXISTS (SELECT 1 FROM dbo.permissions WHERE code = 'users.manage')
    INSERT INTO dbo.permissions (code, name, module) VALUES ('users.manage', 'Kelola User & Role', 'users_roles');
IF NOT EXISTS (SELECT 1 FROM dbo.permissions WHERE code = 'wilayah.manage')
    INSERT INTO dbo.permissions (code, name, module) VALUES ('wilayah.manage', 'Kelola Master Wilayah', 'wilayah');
IF NOT EXISTS (SELECT 1 FROM dbo.permissions WHERE code = 'target_realisasi.manage')
    INSERT INTO dbo.permissions (code, name, module) VALUES ('target_realisasi.manage', 'Kelola Target Realisasi', 'target_realisasi');
IF NOT EXISTS (SELECT 1 FROM dbo.permissions WHERE code = 'laporan.view')
    INSERT INTO dbo.permissions (code, name, module) VALUES ('laporan.view', 'Lihat Laporan', 'laporan');
IF NOT EXISTS (SELECT 1 FROM dbo.permissions WHERE code = 'notifikasi.manage')
    INSERT INTO dbo.permissions (code, name, module) VALUES ('notifikasi.manage', 'Kelola Notifikasi', 'notifikasi');
IF NOT EXISTS (SELECT 1 FROM dbo.permissions WHERE code = 'import_export.manage')
    INSERT INTO dbo.permissions (code, name, module) VALUES ('import_export.manage', 'Kelola Import Export', 'import_export');
IF NOT EXISTS (SELECT 1 FROM dbo.permissions WHERE code = 'audit_log.view')
    INSERT INTO dbo.permissions (code, name, module) VALUES ('audit_log.view', 'Lihat Audit Log', 'audit_log');

DECLARE @seed_now DATETIME2 = GETDATE();

INSERT INTO dbo.permission_role (permission_id, role_id, created_at)
SELECT p.id, r.id, @seed_now
FROM dbo.permissions p
INNER JOIN dbo.roles r ON r.code = 'SUPER_ADMIN'
WHERE NOT EXISTS (
    SELECT 1 FROM dbo.permission_role pr
    WHERE pr.permission_id = p.id AND pr.role_id = r.id
);

INSERT INTO dbo.permission_role (permission_id, role_id, created_at)
SELECT p.id, r.id, @seed_now
FROM dbo.permissions p
INNER JOIN dbo.roles r ON r.code = 'ADMIN'
WHERE NOT EXISTS (
    SELECT 1 FROM dbo.permission_role pr
    WHERE pr.permission_id = p.id AND pr.role_id = r.id
);

INSERT INTO dbo.permission_role (permission_id, role_id, created_at)
SELECT p.id, r.id, @seed_now
FROM dbo.permissions p
INNER JOIN dbo.roles r ON r.code = 'SUPERVISOR'
WHERE p.code IN ('customer_inti.view', 'customer_existing.view', 'salesman.view', 'target_realisasi.manage', 'laporan.view', 'notifikasi.manage')
  AND NOT EXISTS (
    SELECT 1 FROM dbo.permission_role pr
    WHERE pr.permission_id = p.id AND pr.role_id = r.id
);

INSERT INTO dbo.permission_role (permission_id, role_id, created_at)
SELECT p.id, r.id, @seed_now
FROM dbo.permissions p
INNER JOIN dbo.roles r ON r.code = 'VIEWER'
WHERE p.code IN ('customer_inti.view', 'customer_existing.view', 'salesman.view', 'laporan.view', 'audit_log.view')
  AND NOT EXISTS (
    SELECT 1 FROM dbo.permission_role pr
    WHERE pr.permission_id = p.id AND pr.role_id = r.id
);
