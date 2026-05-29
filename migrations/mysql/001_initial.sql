SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS `provinces` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `province_code` VARCHAR(20) NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `geojson` JSON NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_provinces_code` (`province_code`),
    KEY `idx_provinces_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cities` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `province_id` INT NOT NULL,
    `city_code` VARCHAR(20) NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `city_type` ENUM('kabupaten', 'kota') NOT NULL DEFAULT 'kota',
    `geojson` JSON NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_cities_code` (`province_id`, `city_code`),
    KEY `idx_cities_province_name` (`province_id`, `name`),
    CONSTRAINT `fk_cities_province`
        FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `districts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `city_id` INT NOT NULL,
    `district_code` VARCHAR(20) NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `geojson` JSON NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_districts_code` (`city_id`, `district_code`),
    KEY `idx_districts_city_name` (`city_id`, `name`),
    CONSTRAINT `fk_districts_city`
        FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `villages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `district_id` INT NOT NULL,
    `village_code` VARCHAR(20) NOT NULL,
    `postal_code` VARCHAR(10) NULL,
    `name` VARCHAR(150) NOT NULL,
    `geojson` JSON NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_villages_code` (`district_id`, `village_code`),
    KEY `idx_villages_district_name` (`district_id`, `name`),
    CONSTRAINT `fk_villages_district`
        FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_roles_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `permissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `module_name` VARCHAR(100) NOT NULL,
    `action_name` VARCHAR(50) NOT NULL,
    `code` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_permissions_code` (`code`),
    KEY `idx_permissions_module_action` (`module_name`, `action_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(150) NOT NULL,
    `username` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NULL,
    `phone` VARCHAR(30) NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    `last_login_at` TIMESTAMP NULL DEFAULT NULL,
    `profile_json` JSON NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY `uq_users_username` (`username`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `role_permission` (
    `role_id` INT NOT NULL,
    `permission_id` INT NOT NULL,
    `granted_by` INT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`role_id`, `permission_id`),
    KEY `idx_role_permission_permission_id` (`permission_id`),
    KEY `idx_role_permission_granted_by` (`granted_by`),
    CONSTRAINT `fk_role_permission_role`
        FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_role_permission_permission`
        FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_role_permission_granted_by`
        FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user_role` (
    `user_id` INT NOT NULL,
    `role_id` INT NOT NULL,
    `assigned_by` INT NULL,
    `assigned_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `role_id`),
    KEY `idx_user_role_role_id` (`role_id`),
    CONSTRAINT `fk_user_role_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_user_role_role`
        FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_user_role_assigned_by`
        FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `salesman` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `kode_salesman` VARCHAR(50) NOT NULL,
    `nama` VARCHAR(150) NOT NULL,
    `no_hp` VARCHAR(30) NULL,
    `email` VARCHAR(150) NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `homebase_province_id` INT NULL,
    `homebase_city_id` INT NULL,
    `target_bulanan` DECIMAL(14,2) NOT NULL DEFAULT 0,
    `metadata_json` JSON NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY `uq_salesman_kode` (`kode_salesman`),
    UNIQUE KEY `uq_salesman_user` (`user_id`),
    KEY `idx_salesman_status` (`status`),
    CONSTRAINT `fk_salesman_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_salesman_homebase_province`
        FOREIGN KEY (`homebase_province_id`) REFERENCES `provinces` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_salesman_homebase_city`
        FOREIGN KEY (`homebase_city_id`) REFERENCES `cities` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `salesman_wilayah` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `salesman_id` INT NOT NULL,
    `province_id` INT NULL,
    `city_id` INT NULL,
    `district_id` INT NULL,
    `village_id` INT NULL,
    `coverage_type` ENUM('primary', 'secondary', 'temporary') NOT NULL DEFAULT 'primary',
    `radius_km` DECIMAL(10,2) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_salesman_wilayah_salesman` (`salesman_id`),
    KEY `idx_salesman_wilayah_region` (`province_id`, `city_id`, `district_id`, `village_id`),
    CONSTRAINT `fk_salesman_wilayah_salesman`
        FOREIGN KEY (`salesman_id`) REFERENCES `salesman` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_salesman_wilayah_province`
        FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_salesman_wilayah_city`
        FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_salesman_wilayah_district`
        FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_salesman_wilayah_village`
        FOREIGN KEY (`village_id`) REFERENCES `villages` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `customer_inti` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_code` VARCHAR(50) NOT NULL,
    `nama_usaha` VARCHAR(200) NOT NULL,
    `pic_nama` VARCHAR(150) NULL,
    `pic_hp` VARCHAR(30) NULL,
    `email` VARCHAR(150) NULL,
    `alamat` TEXT NULL,
    `province_id` INT NULL,
    `city_id` INT NULL,
    `district_id` INT NULL,
    `village_id` INT NULL,
    `primary_salesman_id` INT NULL,
    `kategori` ENUM('retail', 'grosir', 'modern_trade', 'horeca', 'lainnya') NOT NULL DEFAULT 'retail',
    `segment` ENUM('gold', 'silver', 'bronze', 'prospect') NOT NULL DEFAULT 'prospect',
    `status` ENUM('active', 'inactive', 'blacklist') NOT NULL DEFAULT 'active',
    `lat` DECIMAL(10,8) NULL,
    `lng` DECIMAL(10,8) NULL,
    `potential_revenue` DECIMAL(14,2) NOT NULL DEFAULT 0,
    `tags_json` JSON NULL,
    `source` ENUM('manual', 'import', 'mobile', 'api') NOT NULL DEFAULT 'manual',
    `created_by` INT NULL,
    `updated_by` INT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY `uq_customer_inti_code` (`customer_code`),
    KEY `idx_customer_inti_lat_lng` (`lat`, `lng`),
    KEY `idx_customer_inti_region` (`province_id`, `city_id`, `district_id`, `village_id`),
    KEY `idx_customer_inti_salesman` (`primary_salesman_id`),
    CONSTRAINT `fk_customer_inti_province`
        FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_customer_inti_city`
        FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_customer_inti_district`
        FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_customer_inti_village`
        FOREIGN KEY (`village_id`) REFERENCES `villages` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_customer_inti_primary_salesman`
        FOREIGN KEY (`primary_salesman_id`) REFERENCES `salesman` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_customer_inti_created_by`
        FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_customer_inti_updated_by`
        FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `customer_existing` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_inti_id` INT NOT NULL,
    `account_owner_salesman_id` INT NULL,
    `external_ref` VARCHAR(100) NULL,
    `channel` ENUM('distributor', 'subdistributor', 'direct', 'marketplace', 'lainnya') NOT NULL DEFAULT 'direct',
    `status` ENUM('aktif', 'nonaktif', 'churn') NOT NULL DEFAULT 'aktif',
    `omset_bulanan` DECIMAL(14,2) NOT NULL DEFAULT 0,
    `last_order_at` TIMESTAMP NULL DEFAULT NULL,
    `metadata_json` JSON NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_customer_existing_customer` (`customer_inti_id`),
    KEY `idx_customer_existing_owner` (`account_owner_salesman_id`),
    KEY `idx_customer_existing_channel_status` (`channel`, `status`),
    CONSTRAINT `fk_customer_existing_inti`
        FOREIGN KEY (`customer_inti_id`) REFERENCES `customer_inti` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_customer_existing_owner_salesman`
        FOREIGN KEY (`account_owner_salesman_id`) REFERENCES `salesman` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `customer_poi_nearby` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_inti_id` INT NOT NULL,
    `poi_name` VARCHAR(200) NOT NULL,
    `poi_type` ENUM('kompetitor', 'fasilitas_umum', 'transportasi', 'perumahan', 'lainnya') NOT NULL DEFAULT 'lainnya',
    `distance_km` DECIMAL(10,4) NULL,
    `lat` DECIMAL(10,8) NULL,
    `lng` DECIMAL(10,8) NULL,
    `notes` TEXT NULL,
    `metadata_json` JSON NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_customer_poi_customer_distance` (`customer_inti_id`, `distance_km`),
    KEY `idx_customer_poi_lat_lng` (`lat`, `lng`),
    CONSTRAINT `fk_customer_poi_inti`
        FOREIGN KEY (`customer_inti_id`) REFERENCES `customer_inti` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `kunjungan` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_inti_id` INT NOT NULL,
    `salesman_id` INT NOT NULL,
    `created_by_user_id` INT NULL,
    `tanggal_kunjungan` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `jenis_kunjungan` ENUM('prospek', 'followup', 'penagihan', 'complaint', 'retensi', 'lainnya') NOT NULL DEFAULT 'followup',
    `status` ENUM('planned', 'checked_in', 'completed', 'cancelled') NOT NULL DEFAULT 'planned',
    `checkin_at` TIMESTAMP NULL DEFAULT NULL,
    `checkout_at` TIMESTAMP NULL DEFAULT NULL,
    `lat_checkin` DECIMAL(10,8) NULL,
    `lng_checkin` DECIMAL(10,8) NULL,
    `lat_checkout` DECIMAL(10,8) NULL,
    `lng_checkout` DECIMAL(10,8) NULL,
    `catatan` TEXT NULL,
    `hasil_kunjungan` TEXT NULL,
    `next_action_at` TIMESTAMP NULL DEFAULT NULL,
    `metadata_json` JSON NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_kunjungan_salesman_tanggal` (`salesman_id`, `tanggal_kunjungan`),
    KEY `idx_kunjungan_customer_tanggal` (`customer_inti_id`, `tanggal_kunjungan`),
    CONSTRAINT `fk_kunjungan_customer`
        FOREIGN KEY (`customer_inti_id`) REFERENCES `customer_inti` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_kunjungan_salesman`
        FOREIGN KEY (`salesman_id`) REFERENCES `salesman` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_kunjungan_created_by_user`
        FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `kunjungan_foto` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kunjungan_id` INT NOT NULL,
    `uploaded_by` INT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_name` VARCHAR(200) NULL,
    `mime_type` VARCHAR(100) NULL,
    `file_size_kb` INT NULL,
    `lat` DECIMAL(10,8) NULL,
    `lng` DECIMAL(10,8) NULL,
    `captured_at` TIMESTAMP NULL DEFAULT NULL,
    `metadata_json` JSON NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_kunjungan_foto_kunjungan` (`kunjungan_id`),
    CONSTRAINT `fk_kunjungan_foto_kunjungan`
        FOREIGN KEY (`kunjungan_id`) REFERENCES `kunjungan` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_kunjungan_foto_uploaded_by`
        FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `prospek_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_inti_id` INT NOT NULL,
    `salesman_id` INT NULL,
    `stage_from` ENUM('lead', 'qualified', 'proposal', 'negotiation', 'won', 'lost') NOT NULL DEFAULT 'lead',
    `stage_to` ENUM('lead', 'qualified', 'proposal', 'negotiation', 'won', 'lost') NOT NULL DEFAULT 'qualified',
    `probability` TINYINT UNSIGNED NULL,
    `estimated_value` DECIMAL(14,2) NULL,
    `notes` TEXT NULL,
    `metadata_json` JSON NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_prospek_history_customer_created` (`customer_inti_id`, `created_at`),
    CONSTRAINT `fk_prospek_history_customer`
        FOREIGN KEY (`customer_inti_id`) REFERENCES `customer_inti` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_prospek_history_salesman`
        FOREIGN KEY (`salesman_id`) REFERENCES `salesman` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_prospek_history_created_by`
        FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `import_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `module_name` VARCHAR(100) NOT NULL,
    `file_name` VARCHAR(255) NULL,
    `source` ENUM('web', 'api', 'scheduler') NOT NULL DEFAULT 'web',
    `total_rows` INT NOT NULL DEFAULT 0,
    `success_rows` INT NOT NULL DEFAULT 0,
    `failed_rows` INT NOT NULL DEFAULT 0,
    `status` ENUM('queued', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'queued',
    `error_json` JSON NULL,
    `imported_by` INT NULL,
    `started_at` TIMESTAMP NULL DEFAULT NULL,
    `finished_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_import_log_status_created` (`status`, `created_at`),
    CONSTRAINT `fk_import_log_imported_by`
        FOREIGN KEY (`imported_by`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `audit_log` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `module_name` VARCHAR(100) NOT NULL,
    `action_name` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(100) NULL,
    `entity_id` VARCHAR(100) NULL,
    `request_method` VARCHAR(10) NULL,
    `request_url` VARCHAR(255) NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `old_values_json` JSON NULL,
    `new_values_json` JSON NULL,
    `metadata_json` JSON NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_audit_log_user_created` (`user_id`, `created_at`),
    KEY `idx_audit_log_module_action` (`module_name`, `action_name`),
    CONSTRAINT `fk_audit_log_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `app_config` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `config_key` VARCHAR(120) NOT NULL,
    `config_value` TEXT NULL,
    `value_type` ENUM('string', 'number', 'boolean', 'json') NOT NULL DEFAULT 'string',
    `is_public` TINYINT(1) NOT NULL DEFAULT 0,
    `description` TEXT NULL,
    `updated_by` INT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_app_config_key` (`config_key`),
    KEY `idx_app_config_public` (`is_public`),
    CONSTRAINT `fk_app_config_updated_by`
        FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `notifikasi` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `channel` ENUM('in_app', 'email', 'sms', 'push') NOT NULL DEFAULT 'in_app',
    `title` VARCHAR(200) NOT NULL,
    `message` TEXT NOT NULL,
    `payload_json` JSON NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `read_at` TIMESTAMP NULL DEFAULT NULL,
    `sent_at` TIMESTAMP NULL DEFAULT NULL,
    `status` ENUM('draft', 'queued', 'sent', 'failed') NOT NULL DEFAULT 'draft',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_notifikasi_user_read_created` (`user_id`, `is_read`, `created_at`),
    CONSTRAINT `fk_notifikasi_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
