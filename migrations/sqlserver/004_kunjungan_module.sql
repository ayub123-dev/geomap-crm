-- Migration: Kunjungan Salesman Module - SQL Server Version
-- Date: 2026-05-29
-- Database: SQL Server 2016+

SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;

-- ============================================
-- Tabel: jadwal_kunjungan (Master Schedule)
-- ============================================
IF OBJECT_ID(N'dbo.jadwal_kunjungan', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.jadwal_kunjungan (
        id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        salesman_id BIGINT NOT NULL,
        customer_id BIGINT NOT NULL,
        hari_dalam_minggu NVARCHAR(20) NOT NULL
            CONSTRAINT ck_jadwal_kunjungan_hari CHECK (hari_dalam_minggu IN ('Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu')),
        minggu_ke INT NULL
            CONSTRAINT df_jadwal_kunjungan_minggu_ke DEFAULT NULL,
        is_active TINYINT NOT NULL
            CONSTRAINT df_jadwal_kunjungan_is_active DEFAULT 1
            CONSTRAINT ck_jadwal_kunjungan_is_active CHECK (is_active IN (0, 1)),
        created_at DATETIME2 NOT NULL
            CONSTRAINT df_jadwal_kunjungan_created_at DEFAULT GETDATE(),
        updated_at DATETIME2 NOT NULL
            CONSTRAINT df_jadwal_kunjungan_updated_at DEFAULT GETDATE(),
        CONSTRAINT fk_jadwal_kunjungan_salesman_id FOREIGN KEY (salesman_id) REFERENCES dbo.users(id),
        CONSTRAINT fk_jadwal_kunjungan_customer_id FOREIGN KEY (customer_id) REFERENCES dbo.customers(id),
        CONSTRAINT uq_jadwal_kunjungan UNIQUE (salesman_id, customer_id, hari_dalam_minggu, minggu_ke)
    );
    
    CREATE NONCLUSTERED INDEX idx_jadwal_kunjungan_salesman_hari
        ON dbo.jadwal_kunjungan (salesman_id, hari_dalam_minggu);
    
    CREATE NONCLUSTERED INDEX idx_jadwal_kunjungan_customer
        ON dbo.jadwal_kunjungan (customer_id);
END;

-- ============================================
-- Tabel: kunjungan (Visit Records)
-- ============================================
IF OBJECT_ID(N'dbo.kunjungan', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.kunjungan (
        id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        salesman_id BIGINT NOT NULL,
        customer_id BIGINT NOT NULL,
        jadwal_kunjungan_id BIGINT NULL,
        tanggal_kunjungan DATE NOT NULL,
        waktu_checkin DATETIME2 NULL,
        waktu_checkout DATETIME2 NULL,
        
        -- GPS Check-in
        gps_latitude DECIMAL(10,7) NOT NULL,
        gps_longitude DECIMAL(10,7) NOT NULL,
        customer_latitude DECIMAL(10,7) NOT NULL,
        customer_longitude DECIMAL(10,7) NOT NULL,
        jarak_meter INT NULL,
        validasi_jarak TINYINT NOT NULL
            CONSTRAINT df_kunjungan_validasi_jarak DEFAULT 1
            CONSTRAINT ck_kunjungan_validasi_jarak CHECK (validasi_jarak IN (0, 1)),
        
        -- Visit Data
        kondisi_toko NVARCHAR(100) NULL,
        potensi_penjualan NVARCHAR(100) NULL,
        catatan NVARCHAR(MAX) NULL,
        rating INT NULL
            CONSTRAINT ck_kunjungan_rating CHECK (rating IS NULL OR (rating >= 1 AND rating <= 5)),
        
        -- Foto
        foto_toko_path NVARCHAR(255) NULL,
        
        status NVARCHAR(20) NOT NULL
            CONSTRAINT df_kunjungan_status DEFAULT 'draft'
            CONSTRAINT ck_kunjungan_status CHECK (status IN ('draft', 'checkin', 'checkout', 'prospek', 'converted')),
        
        created_at DATETIME2 NOT NULL
            CONSTRAINT df_kunjungan_created_at DEFAULT GETDATE(),
        updated_at DATETIME2 NOT NULL
            CONSTRAINT df_kunjungan_updated_at DEFAULT GETDATE(),
        
        CONSTRAINT fk_kunjungan_salesman_id FOREIGN KEY (salesman_id) REFERENCES dbo.users(id),
        CONSTRAINT fk_kunjungan_customer_id FOREIGN KEY (customer_id) REFERENCES dbo.customers(id),
        CONSTRAINT fk_kunjungan_jadwal_id FOREIGN KEY (jadwal_kunjungan_id) REFERENCES dbo.jadwal_kunjungan(id)
    );
    
    CREATE NONCLUSTERED INDEX idx_kunjungan_salesman_tanggal
        ON dbo.kunjungan (salesman_id, tanggal_kunjungan);
    
    CREATE NONCLUSTERED INDEX idx_kunjungan_customer
        ON dbo.kunjungan (customer_id);
    
    CREATE NONCLUSTERED INDEX idx_kunjungan_status
        ON dbo.kunjungan (status);
    
    CREATE NONCLUSTERED INDEX idx_kunjungan_tanggal
        ON dbo.kunjungan (tanggal_kunjungan);
END;

-- ============================================
-- Tabel: prospek (Prospect/Lead)
-- ============================================
IF OBJECT_ID(N'dbo.prospek', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.prospek (
        id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        kunjungan_id BIGINT NOT NULL,
        customer_id BIGINT NULL,
        nama_toko NVARCHAR(150) NOT NULL,
        alamat NVARCHAR(MAX) NOT NULL,
        
        -- Geo Location
        latitude DECIMAL(10,7) NOT NULL,
        longitude DECIMAL(10,7) NOT NULL,
        
        -- Prospek Info
        estimasi_omzet NVARCHAR(50) NULL,
        kategori_produk NVARCHAR(100) NULL,
        prioritas NVARCHAR(1) NOT NULL
            CONSTRAINT df_prospek_prioritas DEFAULT 'C'
            CONSTRAINT ck_prospek_prioritas CHECK (prioritas IN ('A', 'B', 'C')),
        
        -- Status Workflow
        status NVARCHAR(20) NOT NULL
            CONSTRAINT df_prospek_status DEFAULT 'prospek'
            CONSTRAINT ck_prospek_status CHECK (status IN ('prospek', 'qualified', 'aktif', 'customer_inti')),
        
        foto_toko_path NVARCHAR(255) NULL,
        catatan NVARCHAR(MAX) NULL,
        
        created_at DATETIME2 NOT NULL
            CONSTRAINT df_prospek_created_at DEFAULT GETDATE(),
        updated_at DATETIME2 NOT NULL
            CONSTRAINT df_prospek_updated_at DEFAULT GETDATE(),
        
        CONSTRAINT fk_prospek_kunjungan_id FOREIGN KEY (kunjungan_id) REFERENCES dbo.kunjungan(id),
        CONSTRAINT fk_prospek_customer_id FOREIGN KEY (customer_id) REFERENCES dbo.customers(id)
    );
    
    CREATE NONCLUSTERED INDEX idx_prospek_status
        ON dbo.prospek (status);
    
    CREATE NONCLUSTERED INDEX idx_prospek_created_at
        ON dbo.prospek (created_at);
    
    CREATE NONCLUSTERED INDEX idx_prospek_geo
        ON dbo.prospek (latitude, longitude);
END;

-- ============================================
-- Tabel: visit_photos (Multi-Photo Support)
-- ============================================
IF OBJECT_ID(N'dbo.visit_photos', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.visit_photos (
        id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        kunjungan_id BIGINT NOT NULL,
        photo_path NVARCHAR(255) NOT NULL,
        foto_type NVARCHAR(20) NOT NULL
            CONSTRAINT df_visit_photos_tipo DEFAULT 'toko'
            CONSTRAINT ck_visit_photos_type CHECK (foto_type IN ('toko', 'produk', 'display', 'lainnya')),
        created_at DATETIME2 NOT NULL
            CONSTRAINT df_visit_photos_created_at DEFAULT GETDATE(),
        CONSTRAINT fk_visit_photos_kunjungan_id FOREIGN KEY (kunjungan_id) REFERENCES dbo.kunjungan(id)
    );
    
    CREATE NONCLUSTERED INDEX idx_visit_photos_kunjungan
        ON dbo.visit_photos (kunjungan_id);
END;

-- ============================================
-- Tabel: visit_gps_audit (GPS Fraud Detection)
-- ============================================
IF OBJECT_ID(N'dbo.visit_gps_audit', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.visit_gps_audit (
        id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        kunjungan_id BIGINT NOT NULL,
        latitude DECIMAL(10,7) NOT NULL,
        longitude DECIMAL(10,7) NOT NULL,
        accuracy INT NULL,
        timestamp DATETIME2 NOT NULL
            CONSTRAINT df_visit_gps_audit_timestamp DEFAULT GETDATE(),
        CONSTRAINT fk_visit_gps_audit_kunjungan_id FOREIGN KEY (kunjungan_id) REFERENCES dbo.kunjungan(id)
    );
    
    CREATE NONCLUSTERED INDEX idx_visit_gps_audit_kunjungan
        ON dbo.visit_gps_audit (kunjungan_id);
    
    CREATE NONCLUSTERED INDEX idx_visit_gps_audit_timestamp
        ON dbo.visit_gps_audit (timestamp);
END;

-- ============================================
-- Tabel: customer_inti (Customer Master)
-- ============================================
IF OBJECT_ID(N'dbo.customer_inti', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.customer_inti (
        id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        customer_id BIGINT NOT NULL,
        salesman_id BIGINT NOT NULL,
        target_kunjungan_bulan INT NOT NULL
            CONSTRAINT df_customer_inti_target DEFAULT 4,
        last_visit_date DATE NULL,
        created_at DATETIME2 NOT NULL
            CONSTRAINT df_customer_inti_created_at DEFAULT GETDATE(),
        updated_at DATETIME2 NOT NULL
            CONSTRAINT df_customer_inti_updated_at DEFAULT GETDATE(),
        CONSTRAINT uq_customer_inti UNIQUE (customer_id, salesman_id),
        CONSTRAINT fk_customer_inti_customer_id FOREIGN KEY (customer_id) REFERENCES dbo.customers(id),
        CONSTRAINT fk_customer_inti_salesman_id FOREIGN KEY (salesman_id) REFERENCES dbo.users(id)
    );
    
    CREATE NONCLUSTERED INDEX idx_customer_inti_salesman
        ON dbo.customer_inti (salesman_id);
END;

-- ============================================
-- Create Sample Data (Optional)
-- ============================================
-- Uncomment untuk menambah sample data
/*
-- Add sample jadwal_kunjungan
INSERT INTO dbo.jadwal_kunjungan (salesman_id, customer_id, hari_dalam_minggu, minggu_ke, created_at, updated_at)
VALUES 
    (1, 1, 'Senin', NULL, GETDATE(), GETDATE()),
    (1, 2, 'Rabu', NULL, GETDATE(), GETDATE()),
    (2, 3, 'Selasa', 1, GETDATE(), GETDATE());
*/

PRINT 'Kunjungan Salesman Module tables created successfully!';
