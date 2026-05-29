SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;

IF OBJECT_ID(N'dbo.provinces', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.provinces (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        province_code NVARCHAR(20) NOT NULL,
        name NVARCHAR(150) NOT NULL,
        geojson NVARCHAR(MAX) NULL,
        is_active TINYINT NOT NULL CONSTRAINT df_provinces_is_active DEFAULT 1 CONSTRAINT ck_provinces_is_active CHECK (is_active IN (0, 1)),
        created_at DATETIME2 NOT NULL CONSTRAINT df_provinces_created_at DEFAULT GETDATE(),
        updated_at DATETIME2 NOT NULL CONSTRAINT df_provinces_updated_at DEFAULT GETDATE(),
        CONSTRAINT uq_provinces_code UNIQUE (province_code),
        CONSTRAINT ck_provinces_geojson CHECK (geojson IS NULL OR ISJSON(geojson) = 1)
    );
END;

IF OBJECT_ID(N'dbo.cities', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.cities (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        province_id INT NOT NULL,
        city_code NVARCHAR(20) NOT NULL,
        name NVARCHAR(150) NOT NULL,
        city_type NVARCHAR(50) NOT NULL
            CONSTRAINT df_cities_city_type DEFAULT 'kota'
            CONSTRAINT ck_cities_city_type CHECK (city_type IN ('kabupaten', 'kota')),
        geojson NVARCHAR(MAX) NULL,
        is_active TINYINT NOT NULL CONSTRAINT df_cities_is_active DEFAULT 1 CONSTRAINT ck_cities_is_active CHECK (is_active IN (0, 1)),
        created_at DATETIME2 NOT NULL CONSTRAINT df_cities_created_at DEFAULT GETDATE(),
        updated_at DATETIME2 NOT NULL CONSTRAINT df_cities_updated_at DEFAULT GETDATE(),
        CONSTRAINT uq_cities_code UNIQUE (province_id, city_code),
        CONSTRAINT ck_cities_geojson CHECK (geojson IS NULL OR ISJSON(geojson) = 1),
        CONSTRAINT fk_cities_province FOREIGN KEY (province_id) REFERENCES dbo.provinces(id)
    );
END;

IF OBJECT_ID(N'dbo.districts', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.districts (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        city_id INT NOT NULL,
        district_code NVARCHAR(20) NOT NULL,
        name NVARCHAR(150) NOT NULL,
        geojson NVARCHAR(MAX) NULL,
        is_active TINYINT NOT NULL CONSTRAINT df_districts_is_active DEFAULT 1 CONSTRAINT ck_districts_is_active CHECK (is_active IN (0, 1)),
        created_at DATETIME2 NOT NULL CONSTRAINT df_districts_created_at DEFAULT GETDATE(),
        updated_at DATETIME2 NOT NULL CONSTRAINT df_districts_updated_at DEFAULT GETDATE(),
        CONSTRAINT uq_districts_code UNIQUE (city_id, district_code),
        CONSTRAINT ck_districts_geojson CHECK (geojson IS NULL OR ISJSON(geojson) = 1),
        CONSTRAINT fk_districts_city FOREIGN KEY (city_id) REFERENCES dbo.cities(id)
    );
END;

IF OBJECT_ID(N'dbo.villages', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.villages (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        district_id INT NOT NULL,
        village_code NVARCHAR(20) NOT NULL,
        postal_code NVARCHAR(10) NULL,
        name NVARCHAR(150) NOT NULL,
        geojson NVARCHAR(MAX) NULL,
        is_active TINYINT NOT NULL CONSTRAINT df_villages_is_active DEFAULT 1 CONSTRAINT ck_villages_is_active CHECK (is_active IN (0, 1)),
        created_at DATETIME2 NOT NULL CONSTRAINT df_villages_created_at DEFAULT GETDATE(),
        updated_at DATETIME2 NOT NULL CONSTRAINT df_villages_updated_at DEFAULT GETDATE(),
        CONSTRAINT uq_villages_code UNIQUE (district_id, village_code),
        CONSTRAINT ck_villages_geojson CHECK (geojson IS NULL OR ISJSON(geojson) = 1),
        CONSTRAINT fk_villages_district FOREIGN KEY (district_id) REFERENCES dbo.districts(id)
    );
END;

IF OBJECT_ID(N'dbo.roles', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.roles (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        code NVARCHAR(50) NOT NULL,
        name NVARCHAR(100) NOT NULL,
        description NVARCHAR(MAX) NULL,
        status NVARCHAR(50) NOT NULL
            CONSTRAINT df_roles_status DEFAULT 'active'
            CONSTRAINT ck_roles_status CHECK (status IN ('active', 'inactive')),
        created_at DATETIME2 NOT NULL CONSTRAINT df_roles_created_at DEFAULT GETDATE(),
        updated_at DATETIME2 NOT NULL CONSTRAINT df_roles_updated_at DEFAULT GETDATE(),
        CONSTRAINT uq_roles_code UNIQUE (code)
    );
END;

IF OBJECT_ID(N'dbo.permissions', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.permissions (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        module_name NVARCHAR(100) NOT NULL,
        action_name NVARCHAR(50) NOT NULL,
        code NVARCHAR(150) NOT NULL,
        description NVARCHAR(MAX) NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_permissions_created_at DEFAULT GETDATE(),
        updated_at DATETIME2 NOT NULL CONSTRAINT df_permissions_updated_at DEFAULT GETDATE(),
        CONSTRAINT uq_permissions_code UNIQUE (code)
    );
END;

IF OBJECT_ID(N'dbo.users', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.users (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        full_name NVARCHAR(150) NOT NULL,
        username NVARCHAR(100) NOT NULL,
        email NVARCHAR(150) NULL,
        phone NVARCHAR(30) NULL,
        password_hash NVARCHAR(255) NOT NULL,
        status NVARCHAR(50) NOT NULL
            CONSTRAINT df_users_status DEFAULT 'active'
            CONSTRAINT ck_users_status CHECK (status IN ('active', 'inactive', 'suspended')),
        last_login_at DATETIME2 NULL,
        profile_json NVARCHAR(MAX) NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_users_created_at DEFAULT GETDATE(),
        updated_at DATETIME2 NOT NULL CONSTRAINT df_users_updated_at DEFAULT GETDATE(),
        deleted_at DATETIME2 NULL,
        CONSTRAINT uq_users_username UNIQUE (username),
        CONSTRAINT uq_users_email UNIQUE (email),
        CONSTRAINT ck_users_profile_json CHECK (profile_json IS NULL OR ISJSON(profile_json) = 1)
    );
END;

IF OBJECT_ID(N'dbo.role_permission', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.role_permission (
        role_id INT NOT NULL,
        permission_id INT NOT NULL,
        granted_by INT NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_role_permission_created_at DEFAULT GETDATE(),
        CONSTRAINT pk_role_permission PRIMARY KEY (role_id, permission_id),
        CONSTRAINT fk_role_permission_role FOREIGN KEY (role_id) REFERENCES dbo.roles(id) ON DELETE CASCADE,
        CONSTRAINT fk_role_permission_permission FOREIGN KEY (permission_id) REFERENCES dbo.permissions(id) ON DELETE CASCADE,
        CONSTRAINT fk_role_permission_granted_by FOREIGN KEY (granted_by) REFERENCES dbo.users(id) ON DELETE SET NULL
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
        status NVARCHAR(50) NOT NULL CONSTRAINT df_users_status DEFAULT 'Aktif' CONSTRAINT ck_users_status CHECK (status IN ('Aktif', 'NonAktif')),
        created_at DATETIME2 NOT NULL CONSTRAINT df_users_created_at DEFAULT GETDATE(),
        updated_at DATETIME2 NOT NULL CONSTRAINT df_users_updated_at DEFAULT GETDATE(),
        CONSTRAINT uq_users_username UNIQUE (username),
        CONSTRAINT uq_users_email UNIQUE (email)
    );
END;
GO

IF OBJECT_ID(N'dbo.salesman', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.salesman (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        nik NVARCHAR(30) NOT NULL,
        nama NVARCHAR(150) NOT NULL,
        no_hp NVARCHAR(30) NULL,
        email NVARCHAR(150) NULL,
        wilayah_id INT NULL,
        target_kunjungan_bulan INT NOT NULL CONSTRAINT df_salesman_target_kunjungan_bulan DEFAULT 0,
        foto NVARCHAR(255) NULL,
        status NVARCHAR(50) NOT NULL CONSTRAINT df_salesman_status DEFAULT 'Aktif' CONSTRAINT ck_salesman_status CHECK (status IN ('Aktif', 'NonAktif')),
        user_id INT NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_salesman_created_at DEFAULT GETDATE(),
        updated_at DATETIME2 NOT NULL CONSTRAINT df_salesman_updated_at DEFAULT GETDATE(),
        CONSTRAINT uq_salesman_nik UNIQUE (nik),
        CONSTRAINT uq_salesman_user_id UNIQUE (user_id)
    );
END;
GO

IF OBJECT_ID(N'dbo.salesman_wilayah', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.salesman_wilayah (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        salesman_id INT NOT NULL,
        province_id INT NULL,
        city_id INT NULL,
        district_id INT NULL,
        village_id INT NULL,
        coverage_type NVARCHAR(50) NOT NULL
            CONSTRAINT df_salesman_wilayah_coverage_type DEFAULT 'primary'
            CONSTRAINT ck_salesman_wilayah_coverage_type CHECK (coverage_type IN ('primary', 'secondary', 'temporary')),
        radius_km DECIMAL(10,2) NULL,
        is_active TINYINT NOT NULL CONSTRAINT df_salesman_wilayah_is_active DEFAULT 1 CONSTRAINT ck_salesman_wilayah_is_active CHECK (is_active IN (0, 1)),
        created_at DATETIME2 NOT NULL CONSTRAINT df_salesman_wilayah_created_at DEFAULT GETDATE(),
        CONSTRAINT fk_salesman_wilayah_salesman FOREIGN KEY (salesman_id) REFERENCES dbo.salesman(id) ON DELETE CASCADE,
        CONSTRAINT fk_salesman_wilayah_province FOREIGN KEY (province_id) REFERENCES dbo.provinces(id) ON DELETE SET NULL,
        CONSTRAINT fk_salesman_wilayah_city FOREIGN KEY (city_id) REFERENCES dbo.cities(id) ON DELETE SET NULL,
        CONSTRAINT fk_salesman_wilayah_district FOREIGN KEY (district_id) REFERENCES dbo.districts(id) ON DELETE SET NULL,
        CONSTRAINT fk_salesman_wilayah_village FOREIGN KEY (village_id) REFERENCES dbo.villages(id) ON DELETE SET NULL
    );
END;

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
        lat DECIMAL(18,8) NULL,
        lng DECIMAL(18,8) NULL,
        kategori_toko NVARCHAR(80) NULL,
        omzet_estimasi DECIMAL(14,2) NOT NULL CONSTRAINT df_customer_inti_omzet_estimasi DEFAULT 0,
        salesman_id INT NULL,
        status NVARCHAR(50) NOT NULL CONSTRAINT df_customer_inti_status DEFAULT 'Aktif' CONSTRAINT ck_customer_inti_status CHECK (status IN ('Aktif', 'NonAktif')),
        foto_toko NVARCHAR(255) NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_customer_inti_created_at DEFAULT GETDATE(),
        updated_at DATETIME2 NOT NULL CONSTRAINT df_customer_inti_updated_at DEFAULT GETDATE(),
        deleted_at DATETIME2 NULL,
        CONSTRAINT uq_customer_inti_kode_customer UNIQUE (kode_customer)
    );

    CREATE INDEX idx_customer_inti_lat_lng ON dbo.customer_inti(lat, lng);
    CREATE INDEX idx_customer_inti_salesman ON dbo.customer_inti(salesman_id);
    CREATE INDEX idx_customer_inti_status ON dbo.customer_inti(status);
    CREATE INDEX idx_customer_inti_nama_toko ON dbo.customer_inti(nama_toko);
END;
GO

IF OBJECT_ID(N'dbo.customer_existing', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.customer_existing (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        kode_existing NVARCHAR(50) NOT NULL,
        nama_toko NVARCHAR(200) NOT NULL,
        brand_kompetitor NVARCHAR(150) NULL,
        alamat NVARCHAR(MAX) NULL,
        lat DECIMAL(18,8) NULL,
        lng DECIMAL(18,8) NULL,
        sumber_data NVARCHAR(50) NOT NULL CONSTRAINT df_customer_existing_sumber_data DEFAULT 'Internal' CONSTRAINT ck_customer_existing_sumber_data CHECK (sumber_data IN ('Internal', 'Survei Lapangan', 'Import')),
        catatan NVARCHAR(MAX) NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_customer_existing_created_at DEFAULT GETDATE()
    );

    CREATE INDEX idx_customer_existing_nama_toko ON dbo.customer_existing(nama_toko);
    CREATE INDEX idx_customer_existing_brand_kompetitor ON dbo.customer_existing(brand_kompetitor);
    CREATE INDEX idx_customer_existing_lat_lng ON dbo.customer_existing(lat, lng);
END;
GO

IF OBJECT_ID(N'dbo.customer_poi_nearby', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.customer_poi_nearby (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        customer_inti_id INT NOT NULL,
        poi_name NVARCHAR(200) NOT NULL,
        poi_type NVARCHAR(50) NOT NULL
            CONSTRAINT df_customer_poi_type DEFAULT 'lainnya'
            CONSTRAINT ck_customer_poi_type CHECK (poi_type IN ('kompetitor', 'fasilitas_umum', 'transportasi', 'perumahan', 'lainnya')),
        distance_km DECIMAL(10,4) NULL,
        lat DECIMAL(18,8) NULL,
        lng DECIMAL(18,8) NULL,
        notes NVARCHAR(MAX) NULL,
        metadata_json NVARCHAR(MAX) NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_customer_poi_created_at DEFAULT GETDATE(),
        updated_at DATETIME2 NOT NULL CONSTRAINT df_customer_poi_updated_at DEFAULT GETDATE(),
        CONSTRAINT ck_customer_poi_metadata_json CHECK (metadata_json IS NULL OR ISJSON(metadata_json) = 1),
        CONSTRAINT fk_customer_poi_inti FOREIGN KEY (customer_inti_id) REFERENCES dbo.customer_inti(id) ON DELETE CASCADE
    );
END;

IF OBJECT_ID(N'dbo.kunjungan', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.kunjungan (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        customer_inti_id INT NOT NULL,
        salesman_id INT NOT NULL,
        created_by_user_id INT NULL,
        tanggal_kunjungan DATETIME2 NOT NULL CONSTRAINT df_kunjungan_tanggal DEFAULT GETDATE(),
        jenis_kunjungan NVARCHAR(50) NOT NULL
            CONSTRAINT df_kunjungan_jenis DEFAULT 'followup'
            CONSTRAINT ck_kunjungan_jenis CHECK (jenis_kunjungan IN ('prospek', 'followup', 'penagihan', 'complaint', 'retensi', 'lainnya')),
        status NVARCHAR(50) NOT NULL
            CONSTRAINT df_kunjungan_status DEFAULT 'planned'
            CONSTRAINT ck_kunjungan_status CHECK (status IN ('planned', 'checked_in', 'completed', 'cancelled')),
        checkin_at DATETIME2 NULL,
        checkout_at DATETIME2 NULL,
        lat_checkin DECIMAL(18,8) NULL,
        lng_checkin DECIMAL(18,8) NULL,
        lat_checkout DECIMAL(18,8) NULL,
        lng_checkout DECIMAL(18,8) NULL,
        catatan NVARCHAR(MAX) NULL,
        hasil_kunjungan NVARCHAR(MAX) NULL,
        next_action_at DATETIME2 NULL,
        metadata_json NVARCHAR(MAX) NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_kunjungan_created_at DEFAULT GETDATE(),
        updated_at DATETIME2 NOT NULL CONSTRAINT df_kunjungan_updated_at DEFAULT GETDATE(),
        CONSTRAINT ck_kunjungan_metadata_json CHECK (metadata_json IS NULL OR ISJSON(metadata_json) = 1),
        CONSTRAINT fk_kunjungan_customer FOREIGN KEY (customer_inti_id) REFERENCES dbo.customer_inti(id) ON DELETE CASCADE,
        CONSTRAINT fk_kunjungan_salesman FOREIGN KEY (salesman_id) REFERENCES dbo.salesman(id),
        CONSTRAINT fk_kunjungan_created_by_user FOREIGN KEY (created_by_user_id) REFERENCES dbo.users(id) ON DELETE SET NULL
    );
END;

IF OBJECT_ID(N'dbo.kunjungan_foto', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.kunjungan_foto (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        kunjungan_id INT NOT NULL,
        uploaded_by INT NULL,
        file_path NVARCHAR(255) NOT NULL,
        file_name NVARCHAR(200) NULL,
        mime_type NVARCHAR(100) NULL,
        file_size_kb INT NULL,
        lat DECIMAL(18,8) NULL,
        lng DECIMAL(18,8) NULL,
        captured_at DATETIME2 NULL,
        metadata_json NVARCHAR(MAX) NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_kunjungan_foto_created_at DEFAULT GETDATE(),
        CONSTRAINT ck_kunjungan_foto_metadata_json CHECK (metadata_json IS NULL OR ISJSON(metadata_json) = 1),
        CONSTRAINT fk_kunjungan_foto_kunjungan FOREIGN KEY (kunjungan_id) REFERENCES dbo.kunjungan(id) ON DELETE CASCADE,
        CONSTRAINT fk_kunjungan_foto_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES dbo.users(id) ON DELETE SET NULL
    );
END;

IF OBJECT_ID(N'dbo.prospek_history', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.prospek_history (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        customer_inti_id INT NOT NULL,
        salesman_id INT NULL,
        stage_from NVARCHAR(50) NOT NULL
            CONSTRAINT df_prospek_history_stage_from DEFAULT 'lead'
            CONSTRAINT ck_prospek_history_stage_from CHECK (stage_from IN ('lead', 'qualified', 'proposal', 'negotiation', 'won', 'lost')),
        stage_to NVARCHAR(50) NOT NULL
            CONSTRAINT df_prospek_history_stage_to DEFAULT 'qualified'
            CONSTRAINT ck_prospek_history_stage_to CHECK (stage_to IN ('lead', 'qualified', 'proposal', 'negotiation', 'won', 'lost')),
        probability TINYINT NULL,
        estimated_value DECIMAL(14,2) NULL,
        notes NVARCHAR(MAX) NULL,
        metadata_json NVARCHAR(MAX) NULL,
        created_by INT NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_prospek_history_created_at DEFAULT GETDATE(),
        CONSTRAINT ck_prospek_history_probability CHECK (probability IS NULL OR (probability >= 0 AND probability <= 100)),
        CONSTRAINT ck_prospek_history_metadata_json CHECK (metadata_json IS NULL OR ISJSON(metadata_json) = 1),
        CONSTRAINT fk_prospek_history_customer FOREIGN KEY (customer_inti_id) REFERENCES dbo.customer_inti(id) ON DELETE CASCADE,
        CONSTRAINT fk_prospek_history_salesman FOREIGN KEY (salesman_id) REFERENCES dbo.salesman(id) ON DELETE SET NULL,
        CONSTRAINT fk_prospek_history_created_by FOREIGN KEY (created_by) REFERENCES dbo.users(id) ON DELETE SET NULL
    );
END;

IF OBJECT_ID(N'dbo.import_log', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.import_log (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        module_name NVARCHAR(100) NOT NULL,
        file_name NVARCHAR(255) NULL,
        total_rows INT NOT NULL CONSTRAINT df_import_log_total_rows DEFAULT 0,
        success_rows INT NOT NULL CONSTRAINT df_import_log_success_rows DEFAULT 0,
        failed_rows INT NOT NULL CONSTRAINT df_import_log_failed_rows DEFAULT 0,
        status NVARCHAR(50) NOT NULL CONSTRAINT df_import_log_status DEFAULT 'queued' CONSTRAINT ck_import_log_status CHECK (status IN ('queued', 'processing', 'completed', 'failed')),
        error_message NVARCHAR(MAX) NULL,
        created_by INT NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_import_log_created_at DEFAULT GETDATE(),
        CONSTRAINT fk_import_log_created_by FOREIGN KEY (created_by) REFERENCES dbo.users(id) ON DELETE SET NULL
    );

    CREATE INDEX idx_import_log_module_status ON dbo.import_log(module_name, status);
END;
GO

IF OBJECT_ID(N'dbo.audit_log', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.audit_log (
        id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        user_id INT NULL,
        module_name NVARCHAR(100) NOT NULL,
        action_name NVARCHAR(100) NOT NULL,
        entity_type NVARCHAR(100) NULL,
        entity_id NVARCHAR(100) NULL,
        request_method NVARCHAR(10) NULL,
        request_url NVARCHAR(255) NULL,
        ip_address NVARCHAR(45) NULL,
        user_agent NVARCHAR(255) NULL,
        old_values_json NVARCHAR(MAX) NULL,
        new_values_json NVARCHAR(MAX) NULL,
        metadata_json NVARCHAR(MAX) NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_audit_log_created_at DEFAULT GETDATE(),
        CONSTRAINT ck_audit_log_old_values_json CHECK (old_values_json IS NULL OR ISJSON(old_values_json) = 1),
        CONSTRAINT ck_audit_log_new_values_json CHECK (new_values_json IS NULL OR ISJSON(new_values_json) = 1),
        CONSTRAINT ck_audit_log_metadata_json CHECK (metadata_json IS NULL OR ISJSON(metadata_json) = 1),
        CONSTRAINT fk_audit_log_user FOREIGN KEY (user_id) REFERENCES dbo.users(id) ON DELETE SET NULL
    );
END;

IF OBJECT_ID(N'dbo.app_config', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.app_config (
        id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        config_key NVARCHAR(120) NOT NULL,
        config_value NVARCHAR(MAX) NULL,
        value_type NVARCHAR(50) NOT NULL
            CONSTRAINT df_app_config_value_type DEFAULT 'string'
            CONSTRAINT ck_app_config_value_type CHECK (value_type IN ('string', 'number', 'boolean', 'json')),
        is_public TINYINT NOT NULL CONSTRAINT df_app_config_is_public DEFAULT 0 CONSTRAINT ck_app_config_is_public CHECK (is_public IN (0, 1)),
        description NVARCHAR(MAX) NULL,
        updated_by INT NULL,
        created_at DATETIME2 NOT NULL CONSTRAINT df_app_config_created_at DEFAULT GETDATE(),
        updated_at DATETIME2 NOT NULL CONSTRAINT df_app_config_updated_at DEFAULT GETDATE(),
        CONSTRAINT uq_app_config_key UNIQUE (config_key),
        CONSTRAINT fk_app_config_updated_by FOREIGN KEY (updated_by) REFERENCES dbo.users(id) ON DELETE SET NULL
    );
END;

IF OBJECT_ID(N'dbo.notifikasi', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.notifikasi (
        id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        user_id INT NULL,
        channel NVARCHAR(50) NOT NULL
            CONSTRAINT df_notifikasi_channel DEFAULT 'in_app'
            CONSTRAINT ck_notifikasi_channel CHECK (channel IN ('in_app', 'email', 'sms', 'push')),
        title NVARCHAR(200) NOT NULL,
        message NVARCHAR(MAX) NOT NULL,
        payload_json NVARCHAR(MAX) NULL,
        is_read TINYINT NOT NULL CONSTRAINT df_notifikasi_is_read DEFAULT 0 CONSTRAINT ck_notifikasi_is_read CHECK (is_read IN (0, 1)),
        read_at DATETIME2 NULL,
        sent_at DATETIME2 NULL,
        status NVARCHAR(50) NOT NULL
            CONSTRAINT df_notifikasi_status DEFAULT 'draft'
            CONSTRAINT ck_notifikasi_status CHECK (status IN ('draft', 'queued', 'sent', 'failed')),
        created_at DATETIME2 NOT NULL CONSTRAINT df_notifikasi_created_at DEFAULT GETDATE(),
        CONSTRAINT ck_notifikasi_payload_json CHECK (payload_json IS NULL OR ISJSON(payload_json) = 1),
        CONSTRAINT fk_notifikasi_user FOREIGN KEY (user_id) REFERENCES dbo.users(id) ON DELETE SET NULL
    );
END;

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_provinces_name' AND object_id = OBJECT_ID('dbo.provinces'))
    CREATE INDEX idx_provinces_name ON dbo.provinces(name);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_cities_province_name' AND object_id = OBJECT_ID('dbo.cities'))
    CREATE INDEX idx_cities_province_name ON dbo.cities(province_id, name);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_districts_city_name' AND object_id = OBJECT_ID('dbo.districts'))
    CREATE INDEX idx_districts_city_name ON dbo.districts(city_id, name);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_villages_district_name' AND object_id = OBJECT_ID('dbo.villages'))
    CREATE INDEX idx_villages_district_name ON dbo.villages(district_id, name);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_permissions_module_action' AND object_id = OBJECT_ID('dbo.permissions'))
    CREATE INDEX idx_permissions_module_action ON dbo.permissions(module_name, action_name);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_users_status' AND object_id = OBJECT_ID('dbo.users'))
    CREATE INDEX idx_users_status ON dbo.users(status);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_role_permission_permission_id' AND object_id = OBJECT_ID('dbo.role_permission'))
    CREATE INDEX idx_role_permission_permission_id ON dbo.role_permission(permission_id);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_role_permission_granted_by' AND object_id = OBJECT_ID('dbo.role_permission'))
    CREATE INDEX idx_role_permission_granted_by ON dbo.role_permission(granted_by);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_user_role_role_id' AND object_id = OBJECT_ID('dbo.user_role'))
    CREATE INDEX idx_user_role_role_id ON dbo.user_role(role_id);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_salesman_status' AND object_id = OBJECT_ID('dbo.salesman'))
    CREATE INDEX idx_salesman_status ON dbo.salesman(status);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_salesman_wilayah_salesman' AND object_id = OBJECT_ID('dbo.salesman_wilayah'))
    CREATE INDEX idx_salesman_wilayah_salesman ON dbo.salesman_wilayah(salesman_id);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_salesman_wilayah_region' AND object_id = OBJECT_ID('dbo.salesman_wilayah'))
    CREATE INDEX idx_salesman_wilayah_region ON dbo.salesman_wilayah(province_id, city_id, district_id, village_id);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_customer_inti_lat_lng' AND object_id = OBJECT_ID('dbo.customer_inti'))
    CREATE INDEX idx_customer_inti_lat_lng ON dbo.customer_inti(lat, lng);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_customer_inti_region' AND object_id = OBJECT_ID('dbo.customer_inti'))
    CREATE INDEX idx_customer_inti_region ON dbo.customer_inti(province_id, city_id, district_id, village_id);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_customer_inti_salesman' AND object_id = OBJECT_ID('dbo.customer_inti'))
    CREATE INDEX idx_customer_inti_salesman ON dbo.customer_inti(primary_salesman_id);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_customer_existing_customer' AND object_id = OBJECT_ID('dbo.customer_existing'))
    CREATE INDEX idx_customer_existing_customer ON dbo.customer_existing(customer_inti_id);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_customer_existing_owner' AND object_id = OBJECT_ID('dbo.customer_existing'))
    CREATE INDEX idx_customer_existing_owner ON dbo.customer_existing(account_owner_salesman_id);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_customer_existing_channel_status' AND object_id = OBJECT_ID('dbo.customer_existing'))
    CREATE INDEX idx_customer_existing_channel_status ON dbo.customer_existing(channel, status);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_customer_poi_customer_distance' AND object_id = OBJECT_ID('dbo.customer_poi_nearby'))
    CREATE INDEX idx_customer_poi_customer_distance ON dbo.customer_poi_nearby(customer_inti_id, distance_km);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_customer_poi_lat_lng' AND object_id = OBJECT_ID('dbo.customer_poi_nearby'))
    CREATE INDEX idx_customer_poi_lat_lng ON dbo.customer_poi_nearby(lat, lng);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_kunjungan_salesman_tanggal' AND object_id = OBJECT_ID('dbo.kunjungan'))
    CREATE INDEX idx_kunjungan_salesman_tanggal ON dbo.kunjungan(salesman_id, tanggal_kunjungan);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_kunjungan_customer_tanggal' AND object_id = OBJECT_ID('dbo.kunjungan'))
    CREATE INDEX idx_kunjungan_customer_tanggal ON dbo.kunjungan(customer_inti_id, tanggal_kunjungan);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_kunjungan_foto_kunjungan' AND object_id = OBJECT_ID('dbo.kunjungan_foto'))
    CREATE INDEX idx_kunjungan_foto_kunjungan ON dbo.kunjungan_foto(kunjungan_id);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_prospek_history_customer_created' AND object_id = OBJECT_ID('dbo.prospek_history'))
    CREATE INDEX idx_prospek_history_customer_created ON dbo.prospek_history(customer_inti_id, created_at);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_import_log_status_created' AND object_id = OBJECT_ID('dbo.import_log'))
    CREATE INDEX idx_import_log_status_created ON dbo.import_log(status, created_at);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_audit_log_user_created' AND object_id = OBJECT_ID('dbo.audit_log'))
    CREATE INDEX idx_audit_log_user_created ON dbo.audit_log(user_id, created_at);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_audit_log_module_action' AND object_id = OBJECT_ID('dbo.audit_log'))
    CREATE INDEX idx_audit_log_module_action ON dbo.audit_log(module_name, action_name);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_app_config_public' AND object_id = OBJECT_ID('dbo.app_config'))
    CREATE INDEX idx_app_config_public ON dbo.app_config(is_public);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_notifikasi_user_read_created' AND object_id = OBJECT_ID('dbo.notifikasi'))
    CREATE INDEX idx_notifikasi_user_read_created ON dbo.notifikasi(user_id, is_read, created_at);
