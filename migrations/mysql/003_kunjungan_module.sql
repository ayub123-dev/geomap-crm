-- Migration: Kunjungan Salesman Module
-- Date: 2026-05-29

-- Tabel Master Jadwal Kunjungan (mingguan dan harian)
CREATE TABLE IF NOT EXISTS jadwal_kunjungan (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    salesman_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    hari_dalam_minggu ENUM('Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu') NOT NULL,
    minggu_ke INT NULL DEFAULT NULL COMMENT 'Null=setiap minggu, 1-4=minggu ke X',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_jadwal_kunjungan_salesman_id
        FOREIGN KEY (salesman_id) REFERENCES users(id),
    CONSTRAINT fk_jadwal_kunjungan_customer_id
        FOREIGN KEY (customer_id) REFERENCES customers(id),
    KEY idx_jadwal_kunjungan_salesman_hari (salesman_id, hari_dalam_minggu),
    KEY idx_jadwal_kunjungan_customer (customer_id),
    UNIQUE KEY uq_jadwal_kunjungan (salesman_id, customer_id, hari_dalam_minggu, minggu_ke)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Kunjungan (Visit Records)
CREATE TABLE IF NOT EXISTS kunjungan (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    salesman_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    jadwal_kunjungan_id BIGINT UNSIGNED NULL,
    tanggal_kunjungan DATE NOT NULL,
    waktu_checkin DATETIME NULL,
    waktu_checkout DATETIME NULL,
    
    -- GPS Check-in
    gps_latitude DECIMAL(10,7) NOT NULL,
    gps_longitude DECIMAL(10,7) NOT NULL,
    customer_latitude DECIMAL(10,7) NOT NULL,
    customer_longitude DECIMAL(10,7) NOT NULL,
    jarak_meter INT NULL COMMENT 'Jarak antara GPS checkin vs customer (meter)',
    validasi_jarak TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=valid (<=200m), 0=invalid',
    
    -- Visit Data
    kondisi_toko VARCHAR(100) NULL,
    potensi_penjualan VARCHAR(100) NULL,
    catatan TEXT NULL,
    rating INT NULL COMMENT '1-5 rating',
    
    -- Foto
    foto_toko_path VARCHAR(255) NULL,
    
    status ENUM('draft', 'checkin', 'checkout', 'prospek', 'converted') NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_kunjungan_salesman_id
        FOREIGN KEY (salesman_id) REFERENCES users(id),
    CONSTRAINT fk_kunjungan_customer_id
        FOREIGN KEY (customer_id) REFERENCES customers(id),
    CONSTRAINT fk_kunjungan_jadwal_id
        FOREIGN KEY (jadwal_kunjungan_id) REFERENCES jadwal_kunjungan(id),
    KEY idx_kunjungan_salesman_tanggal (salesman_id, tanggal_kunjungan),
    KEY idx_kunjungan_customer (customer_id),
    KEY idx_kunjungan_status (status),
    KEY idx_kunjungan_tanggal (tanggal_kunjungan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Prospek (Customer Prospek dari Kunjungan)
CREATE TABLE IF NOT EXISTS prospek (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kunjungan_id BIGINT UNSIGNED NOT NULL,
    
    -- Converted dari customer atau baru
    customer_id BIGINT UNSIGNED NULL,
    nama_toko VARCHAR(150) NOT NULL,
    alamat TEXT NOT NULL,
    
    -- Geo Location
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    
    -- Prospek Info
    estimasi_omzet VARCHAR(50) NULL,
    kategori_produk VARCHAR(100) NULL,
    prioritas ENUM('A', 'B', 'C') NOT NULL DEFAULT 'C',
    
    -- Status Workflow
    status ENUM('prospek', 'qualified', 'aktif', 'customer_inti') NOT NULL DEFAULT 'prospek',
    
    foto_toko_path VARCHAR(255) NULL,
    catatan TEXT NULL,
    
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_prospek_kunjungan_id
        FOREIGN KEY (kunjungan_id) REFERENCES kunjungan(id),
    CONSTRAINT fk_prospek_customer_id
        FOREIGN KEY (customer_id) REFERENCES customers(id),
    KEY idx_prospek_status (status),
    KEY idx_prospek_created_at (created_at),
    KEY idx_prospek_geo (latitude, longitude)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Tambahan: Visit Photo (untuk multi-photo support)
CREATE TABLE IF NOT EXISTS visit_photos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kunjungan_id BIGINT UNSIGNED NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    foto_type ENUM('toko', 'produk', 'display', 'lainnya') NOT NULL DEFAULT 'toko',
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_visit_photos_kunjungan_id
        FOREIGN KEY (kunjungan_id) REFERENCES kunjungan(id),
    KEY idx_visit_photos_kunjungan (kunjungan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Audit: GPS Check-in History (untuk fraud detection)
CREATE TABLE IF NOT EXISTS visit_gps_audit (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kunjungan_id BIGINT UNSIGNED NOT NULL,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    accuracy INT NULL COMMENT 'Akurasi GPS dalam meter',
    timestamp DATETIME NOT NULL,
    CONSTRAINT fk_visit_gps_audit_kunjungan_id
        FOREIGN KEY (kunjungan_id) REFERENCES kunjungan(id),
    KEY idx_visit_gps_audit_kunjungan (kunjungan_id),
    KEY idx_visit_gps_audit_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Master: Customer Inti (untuk tracking target kunjungan)
-- Asumsi sudah ada, kalau belum buat ini
CREATE TABLE IF NOT EXISTS customer_inti (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT UNSIGNED NOT NULL,
    salesman_id BIGINT UNSIGNED NOT NULL,
    target_kunjungan_bulan INT NOT NULL DEFAULT 4,
    last_visit_date DATE NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_customer_inti_customer_id
        FOREIGN KEY (customer_id) REFERENCES customers(id),
    CONSTRAINT fk_customer_inti_salesman_id
        FOREIGN KEY (salesman_id) REFERENCES users(id),
    UNIQUE KEY uq_customer_inti (customer_id, salesman_id),
    KEY idx_customer_inti_salesman (salesman_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
