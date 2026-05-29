SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS `customer_inti` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_customer` VARCHAR(50) NOT NULL,
    `nama_toko` VARCHAR(200) NOT NULL,
    `pemilik` VARCHAR(150) NULL,
    `no_hp` VARCHAR(30) NULL,
    `alamat` TEXT NULL,
    `kelurahan` VARCHAR(120) NULL,
    `kecamatan` VARCHAR(120) NULL,
    `kota` VARCHAR(120) NULL,
    `provinsi` VARCHAR(120) NULL,
    `lat` DECIMAL(10,8) NULL,
    `lng` DECIMAL(10,8) NULL,
    `kategori_toko` VARCHAR(80) NULL,
    `omzet_estimasi` DECIMAL(14,2) NOT NULL DEFAULT 0,
    `salesman_id` INT NULL,
    `status` ENUM('Aktif', 'NonAktif') NOT NULL DEFAULT 'Aktif',
    `foto_toko` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY `uq_customer_inti_kode_customer` (`kode_customer`),
    KEY `idx_customer_inti_lat_lng` (`lat`, `lng`),
    KEY `idx_customer_inti_salesman` (`salesman_id`),
    KEY `idx_customer_inti_status` (`status`),
    KEY `idx_customer_inti_nama_toko` (`nama_toko`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `customer_existing` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_existing` VARCHAR(50) NOT NULL,
    `nama_toko` VARCHAR(200) NOT NULL,
    `brand_kompetitor` VARCHAR(150) NULL,
    `alamat` TEXT NULL,
    `lat` DECIMAL(10,8) NULL,
    `lng` DECIMAL(10,8) NULL,
    `sumber_data` ENUM('Internal', 'Survei Lapangan', 'Import') NOT NULL DEFAULT 'Internal',
    `catatan` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_customer_existing_nama_toko` (`nama_toko`),
    KEY `idx_customer_existing_brand_kompetitor` (`brand_kompetitor`),
    KEY `idx_customer_existing_lat_lng` (`lat`, `lng`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `salesman` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nik` VARCHAR(30) NOT NULL,
    `nama` VARCHAR(150) NOT NULL,
    `no_hp` VARCHAR(30) NULL,
    `email` VARCHAR(150) NULL,
    `wilayah_id` INT NULL,
    `target_kunjungan_bulan` INT NOT NULL DEFAULT 0,
    `foto` VARCHAR(255) NULL,
    `status` ENUM('Aktif', 'NonAktif') NOT NULL DEFAULT 'Aktif',
    `user_id` INT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_salesman_nik` (`nik`),
    UNIQUE KEY `uq_salesman_user_id` (`user_id`),
    KEY `idx_salesman_status` (`status`),
    KEY `idx_salesman_wilayah_id` (`wilayah_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    UNIQUE KEY `uq_roles_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `permissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(120) NOT NULL,
    `name` VARCHAR(120) NOT NULL,
    `module` VARCHAR(80) NOT NULL,
    UNIQUE KEY `uq_permissions_code` (`code`),
    KEY `idx_permissions_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(100) NOT NULL,
    `full_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `status` ENUM('Aktif', 'NonAktif') NOT NULL DEFAULT 'Aktif',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_users_username` (`username`),
    UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `role_user` (
    `role_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`role_id`, `user_id`),
    KEY `idx_role_user_user` (`user_id`),
    CONSTRAINT `fk_role_user_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_role_user_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `permission_role` (
    `permission_id` INT NOT NULL,
    `role_id` INT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`permission_id`, `role_id`),
    KEY `idx_permission_role_role` (`role_id`),
    CONSTRAINT `fk_permission_role_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_permission_role_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `target_realisasi` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `salesman_id` INT NOT NULL,
    `tahun` INT NOT NULL,
    `bulan` INT NOT NULL,
    `target_kunjungan` INT NOT NULL DEFAULT 0,
    `realisasi_kunjungan` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_target_realisasi_salesman_periode` (`salesman_id`, `tahun`, `bulan`),
    CONSTRAINT `fk_target_realisasi_salesman` FOREIGN KEY (`salesman_id`) REFERENCES `salesman` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `import_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `module_name` VARCHAR(100) NOT NULL,
    `file_name` VARCHAR(255) NULL,
    `total_rows` INT NOT NULL DEFAULT 0,
    `success_rows` INT NOT NULL DEFAULT 0,
    `failed_rows` INT NOT NULL DEFAULT 0,
    `status` ENUM('queued', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'queued',
    `error_message` TEXT NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_import_log_module_status` (`module_name`, `status`),
    CONSTRAINT `fk_import_log_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `audit_log` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `module_name` VARCHAR(100) NOT NULL,
    `action_name` VARCHAR(80) NOT NULL,
    `field_name` VARCHAR(120) NULL,
    `old_value` TEXT NULL,
    `new_value` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_audit_log_user_created` (`user_id`, `created_at`),
    CONSTRAINT `fk_audit_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `notifikasi` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `title` VARCHAR(200) NOT NULL,
    `message` TEXT NOT NULL,
    `channel` ENUM('in-app', 'whatsapp') NOT NULL DEFAULT 'in-app',
    `status` ENUM('draft', 'queued', 'sent', 'failed') NOT NULL DEFAULT 'draft',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_notifikasi_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Upgrade schema lama (hasil 001_initial) agar kompatibel dengan field modul baru.
ALTER TABLE `customer_inti`
    ADD COLUMN IF NOT EXISTS `kode_customer` VARCHAR(50) NULL,
    ADD COLUMN IF NOT EXISTS `nama_toko` VARCHAR(200) NULL,
    ADD COLUMN IF NOT EXISTS `pemilik` VARCHAR(150) NULL,
    ADD COLUMN IF NOT EXISTS `no_hp` VARCHAR(30) NULL,
    ADD COLUMN IF NOT EXISTS `kelurahan` VARCHAR(120) NULL,
    ADD COLUMN IF NOT EXISTS `kecamatan` VARCHAR(120) NULL,
    ADD COLUMN IF NOT EXISTS `kota` VARCHAR(120) NULL,
    ADD COLUMN IF NOT EXISTS `provinsi` VARCHAR(120) NULL,
    ADD COLUMN IF NOT EXISTS `kategori_toko` VARCHAR(80) NULL,
    ADD COLUMN IF NOT EXISTS `salesman_id` INT NULL,
    ADD COLUMN IF NOT EXISTS `foto_toko` VARCHAR(255) NULL;

SET @has_customer_code = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'customer_inti' AND column_name = 'customer_code'
);
SET @sql_customer_code = IF(
    @has_customer_code > 0,
    'UPDATE customer_inti SET kode_customer = COALESCE(kode_customer, customer_code) WHERE (kode_customer IS NULL OR kode_customer = '''')',
    'SELECT 1'
);
PREPARE stmt_customer_code FROM @sql_customer_code;
EXECUTE stmt_customer_code;
DEALLOCATE PREPARE stmt_customer_code;

SET @has_nama_usaha = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'customer_inti' AND column_name = 'nama_usaha'
);
SET @sql_nama_usaha = IF(
    @has_nama_usaha > 0,
    'UPDATE customer_inti SET nama_toko = COALESCE(nama_toko, nama_usaha) WHERE (nama_toko IS NULL OR nama_toko = '''')',
    'SELECT 1'
);
PREPARE stmt_nama_usaha FROM @sql_nama_usaha;
EXECUTE stmt_nama_usaha;
DEALLOCATE PREPARE stmt_nama_usaha;

SET @has_pic_nama = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'customer_inti' AND column_name = 'pic_nama'
);
SET @sql_pic_nama = IF(
    @has_pic_nama > 0,
    'UPDATE customer_inti SET pemilik = COALESCE(pemilik, pic_nama) WHERE (pemilik IS NULL OR pemilik = '''')',
    'SELECT 1'
);
PREPARE stmt_pic_nama FROM @sql_pic_nama;
EXECUTE stmt_pic_nama;
DEALLOCATE PREPARE stmt_pic_nama;

SET @has_pic_hp = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'customer_inti' AND column_name = 'pic_hp'
);
SET @sql_pic_hp = IF(
    @has_pic_hp > 0,
    'UPDATE customer_inti SET no_hp = COALESCE(no_hp, pic_hp) WHERE (no_hp IS NULL OR no_hp = '''')',
    'SELECT 1'
);
PREPARE stmt_pic_hp FROM @sql_pic_hp;
EXECUTE stmt_pic_hp;
DEALLOCATE PREPARE stmt_pic_hp;

SET @has_primary_salesman = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'customer_inti' AND column_name = 'primary_salesman_id'
);
SET @sql_primary_salesman = IF(
    @has_primary_salesman > 0,
    'UPDATE customer_inti SET salesman_id = COALESCE(salesman_id, primary_salesman_id) WHERE salesman_id IS NULL',
    'SELECT 1'
);
PREPARE stmt_primary_salesman FROM @sql_primary_salesman;
EXECUTE stmt_primary_salesman;
DEALLOCATE PREPARE stmt_primary_salesman;

ALTER TABLE `customer_existing`
    ADD COLUMN IF NOT EXISTS `kode_existing` VARCHAR(50) NULL,
    ADD COLUMN IF NOT EXISTS `brand_kompetitor` VARCHAR(150) NULL,
    ADD COLUMN IF NOT EXISTS `nama_toko` VARCHAR(200) NULL,
    ADD COLUMN IF NOT EXISTS `sumber_data` ENUM('Internal', 'Survei Lapangan', 'Import') NOT NULL DEFAULT 'Internal';

SET @has_channel = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'customer_existing' AND column_name = 'channel'
);
SET @sql_customer_existing_channel = IF(
    @has_channel > 0,
    'UPDATE customer_existing SET sumber_data = CASE
        WHEN channel IN (''distributor'', ''subdistributor'', ''direct'') THEN ''Internal''
        WHEN channel = ''marketplace'' THEN ''Import''
        ELSE ''Survei Lapangan''
    END WHERE sumber_data IS NULL OR sumber_data = ''''',
    'SELECT 1'
);
PREPARE stmt_customer_existing_channel FROM @sql_customer_existing_channel;
EXECUTE stmt_customer_existing_channel;
DEALLOCATE PREPARE stmt_customer_existing_channel;

SET @has_external_ref = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'customer_existing' AND column_name = 'external_ref'
);
SET @sql_external_ref = IF(
    @has_external_ref > 0,
    'UPDATE customer_existing SET kode_existing = COALESCE(kode_existing, external_ref) WHERE kode_existing IS NULL OR kode_existing = ''''',
    'SELECT 1'
);
PREPARE stmt_external_ref FROM @sql_external_ref;
EXECUTE stmt_external_ref;
DEALLOCATE PREPARE stmt_external_ref;

ALTER TABLE `salesman`
    ADD COLUMN IF NOT EXISTS `nik` VARCHAR(30) NULL,
    ADD COLUMN IF NOT EXISTS `nama` VARCHAR(150) NULL,
    ADD COLUMN IF NOT EXISTS `wilayah_id` INT NULL,
    ADD COLUMN IF NOT EXISTS `target_kunjungan_bulan` INT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `foto` VARCHAR(255) NULL;

SET @has_kode_salesman = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'salesman' AND column_name = 'kode_salesman'
);
SET @sql_kode_salesman = IF(
    @has_kode_salesman > 0,
    'UPDATE salesman SET nik = COALESCE(nik, kode_salesman) WHERE nik IS NULL OR nik = ''''',
    'SELECT 1'
);
PREPARE stmt_kode_salesman FROM @sql_kode_salesman;
EXECUTE stmt_kode_salesman;
DEALLOCATE PREPARE stmt_kode_salesman;

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `full_name` VARCHAR(150) NULL;

ALTER TABLE `permissions`
    ADD COLUMN IF NOT EXISTS `name` VARCHAR(120) NULL,
    ADD COLUMN IF NOT EXISTS `module` VARCHAR(80) NULL;

UPDATE `permissions`
SET `name` = COALESCE(`name`, `code`),
    `module` = COALESCE(`module`, SUBSTRING_INDEX(`code`, '.', 1))
WHERE `name` IS NULL OR `module` IS NULL;

INSERT INTO `roles` (`code`, `name`, `description`)
VALUES
('SUPER_ADMIN', 'SUPER ADMIN', 'Akses semua fitur + konfigurasi sistem + multi-perusahaan'),
('ADMIN', 'ADMIN', 'CRUD semua master, lihat semua laporan, manage salesman'),
('SUPERVISOR', 'SUPERVISOR', 'Lihat area sendiri, approve prospek, assign target'),
('SALESMAN', 'SALESMAN', 'Mobile app, customer sendiri, input kunjungan'),
('VIEWER', 'VIEWER', 'Read-only peta dan laporan')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`);

INSERT INTO `permissions` (`code`, `name`, `module`)
VALUES
('customer_inti.view', 'Lihat Customer Inti', 'customer_inti'),
('customer_inti.create', 'Tambah Customer Inti', 'customer_inti'),
('customer_inti.update', 'Ubah Customer Inti', 'customer_inti'),
('customer_inti.delete', 'Hapus Customer Inti', 'customer_inti'),
('customer_existing.view', 'Lihat Customer Existing', 'customer_existing'),
('customer_existing.create', 'Tambah Customer Existing', 'customer_existing'),
('customer_existing.update', 'Ubah Customer Existing', 'customer_existing'),
('customer_existing.delete', 'Hapus Customer Existing', 'customer_existing'),
('salesman.view', 'Lihat Salesman', 'salesman'),
('salesman.manage', 'Kelola Salesman', 'salesman'),
('users.manage', 'Kelola User & Role', 'users_roles'),
('wilayah.manage', 'Kelola Master Wilayah', 'wilayah'),
('target_realisasi.manage', 'Kelola Target Realisasi', 'target_realisasi'),
('laporan.view', 'Lihat Laporan', 'laporan'),
('notifikasi.manage', 'Kelola Notifikasi', 'notifikasi'),
('import_export.manage', 'Kelola Import Export', 'import_export'),
('audit_log.view', 'Lihat Audit Log', 'audit_log')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `module` = VALUES(`module`);

INSERT IGNORE INTO `permission_role` (`permission_id`, `role_id`, `created_at`)
SELECT p.id, r.id, NOW()
FROM `permissions` p
INNER JOIN `roles` r ON r.`code` = 'SUPER_ADMIN';

INSERT IGNORE INTO `permission_role` (`permission_id`, `role_id`, `created_at`)
SELECT p.id, r.id, NOW()
FROM `permissions` p
INNER JOIN `roles` r ON r.`code` = 'ADMIN';

INSERT IGNORE INTO `permission_role` (`permission_id`, `role_id`, `created_at`)
SELECT p.id, r.id, NOW()
FROM `permissions` p
INNER JOIN `roles` r ON r.`code` = 'SUPERVISOR'
WHERE p.`code` IN (
    'customer_inti.view',
    'customer_existing.view',
    'salesman.view',
    'target_realisasi.manage',
    'laporan.view',
    'notifikasi.manage'
);

INSERT IGNORE INTO `permission_role` (`permission_id`, `role_id`, `created_at`)
SELECT p.id, r.id, NOW()
FROM `permissions` p
INNER JOIN `roles` r ON r.`code` = 'VIEWER'
WHERE p.`code` IN (
    'customer_inti.view',
    'customer_existing.view',
    'salesman.view',
    'laporan.view',
    'audit_log.view'
);
