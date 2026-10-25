-- Step 1 only: schema preparation. No records are created or backfilled.
-- Run manually after backup, on MySQL >= 8.0.16 or MariaDB >= 10.2.1.
-- These versions enforce CHECK constraints. Older versions must not run this file.
-- Use the mysql client SOURCE command or a client supporting DELIMITER.
-- Do not use run_migration.php (it splits SQL statements by semicolons).
-- DDL commits implicitly: on failure, inspect SHOW CREATE TABLE before resuming.
-- One-time migration: deliberately fail if target tables/columns already exist.

DELIMITER $$

CREATE PROCEDURE migrate_20261023_link_kehoach_hososcbd()
BEGIN
    DECLARE server_version VARCHAR(255);
    DECLARE version_core VARCHAR(255);
    DECLARE version_number INT;
    DECLARE hoso_id_type VARCHAR(64);
    DECLARE plan_id_type VARCHAR(64);
    DECLARE object_count INT;

    SET server_version = VERSION();
    SET version_core = SUBSTRING_INDEX(server_version, '-', 1);
    -- Some MariaDB servers advertise the compatibility prefix 5.5.5-.
    IF server_version LIKE '5.5.5-%MariaDB%' THEN
        SET version_core = SUBSTRING_INDEX(SUBSTRING(server_version, 7), '-', 1);
    END IF;
    SET version_number =
        CAST(SUBSTRING_INDEX(version_core, '.', 1) AS UNSIGNED) * 10000
        + CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(version_core, '.', 2), '.', -1) AS UNSIGNED) * 100
        + CAST(SUBSTRING_INDEX(version_core, '.', -1) AS UNSIGNED);
    IF (server_version LIKE '%MariaDB%' AND version_number < 100201)
        OR (server_version NOT LIKE '%MariaDB%' AND version_number < 80016) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Requires enforced CHECK constraints: MySQL >= 8.0.16 or MariaDB >= 10.2.1';
    END IF;

    SELECT COUNT(*) INTO object_count
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME IN ('hososcbd_iso', 'ke_hoach_bao_duong_dinh_ky_iso')
      AND ENGINE = 'InnoDB';
    IF object_count <> 2 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Both source tables must exist and use InnoDB; no automatic engine conversion';
    END IF;

    SELECT COUNT(*) INTO object_count
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME IN ('hososcbd_doi_iso', 'baoduong_dinhky_chitiet_iso');
    IF object_count <> 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Target table already exists; inspect previous migration before continuing';
    END IF;
    SELECT COUNT(*) INTO object_count
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'ke_hoach_bao_duong_dinh_ky_iso'
      AND COLUMN_NAME IN ('hososcbd_id', 'hososcbd_doi_id');
    IF object_count <> 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Plan link columns already exist; inspect previous migration before continuing';
    END IF;

    SELECT MAX(COLUMN_TYPE) INTO hoso_id_type
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hososcbd_iso'
      AND COLUMN_NAME = 'stt' AND COLUMN_KEY = 'PRI'
      AND DATA_TYPE IN ('tinyint', 'smallint', 'mediumint', 'int', 'bigint');
    SELECT MAX(COLUMN_TYPE) INTO plan_id_type
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ke_hoach_bao_duong_dinh_ky_iso'
      AND COLUMN_NAME = 'id' AND COLUMN_KEY = 'PRI'
      AND DATA_TYPE IN ('tinyint', 'smallint', 'mediumint', 'int', 'bigint');
    SELECT COUNT(*) INTO object_count
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'PRIMARY'
      AND TABLE_NAME IN ('hososcbd_iso', 'ke_hoach_bao_duong_dinh_ky_iso');
    IF hoso_id_type IS NULL OR plan_id_type IS NULL OR object_count <> 2 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Expected single-column integer PKs: hososcbd_iso.stt and plan.id';
    END IF;

    -- Copies columns/indexes/options, not rows, foreign keys or triggers.
    CREATE TABLE hososcbd_doi_iso LIKE hososcbd_iso;

    -- Preserve actual parent ID types, including UNSIGNED.
    -- Omitted FK actions default to RESTRICT (no cascading history changes).
    SET @ddl_20261023 = CONCAT(
        'ALTER TABLE ke_hoach_bao_duong_dinh_ky_iso ',
        'ADD COLUMN hososcbd_id ', hoso_id_type, ' NULL, ',
        'ADD COLUMN hososcbd_doi_id ', hoso_id_type, ' NULL, ',
        'ADD UNIQUE KEY uq_kh_hososcbd (hososcbd_id), ',
        'ADD UNIQUE KEY uq_kh_hososcbd_doi (hososcbd_doi_id), ',
        'ADD CONSTRAINT fk_kh_hososcbd FOREIGN KEY (hososcbd_id) ',
        'REFERENCES hososcbd_iso (stt), ',
        'ADD CONSTRAINT fk_kh_hososcbd_doi FOREIGN KEY (hososcbd_doi_id) ',
        'REFERENCES hososcbd_doi_iso (stt), ',
        'ADD CONSTRAINT chk_kh_single_hoso CHECK ',
        '(hososcbd_id IS NULL OR hososcbd_doi_id IS NULL), ',
        'ADD CONSTRAINT chk_kh_hoso_donvi CHECK (',
        '(hososcbd_id IS NULL AND hososcbd_doi_id IS NULL) OR ',
        '(hososcbd_id IS NOT NULL AND COALESCE(UPPER(TRIM(donvi_lam_chinh)), '''') IN (''CNM'', ''RDNGA'')) OR ',
        '(hososcbd_doi_id IS NOT NULL AND COALESCE(UPPER(TRIM(donvi_lam_chinh)), '''') NOT IN ('''', ''CNM'', ''RDNGA'')))'
    );
    PREPARE stmt_20261023 FROM @ddl_20261023;
    EXECUTE stmt_20261023;
    DEALLOCATE PREPARE stmt_20261023;

    -- Detail belongs to the annual plan, whose two FKs identify the one dossier.
    -- Multiple visits in the same quarter are allowed.
    SET @ddl_20261023 = CONCAT(
        'CREATE TABLE baoduong_dinhky_chitiet_iso (',
        'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, ',
        'kehoach_id ', plan_id_type, ' NOT NULL, ',
        'qui TINYINT UNSIGNED NOT NULL, ',
        'ngay_bat_dau DATE NOT NULL, ',
        'ngay_ket_thuc DATE NULL, ',
        'noi_dung TEXT NOT NULL, ',
        'ket_qua TEXT NULL, ',
        'nguoi_thuc_hien VARCHAR(255) NOT NULL, ',
        'created_by VARCHAR(80) NOT NULL, ',
        'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, ',
        'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, ',
        'KEY idx_bd_chitiet_kehoach_qui (kehoach_id, qui), ',
        'CONSTRAINT fk_bd_chitiet_kehoach FOREIGN KEY (kehoach_id) ',
        'REFERENCES ke_hoach_bao_duong_dinh_ky_iso (id) ON DELETE RESTRICT ON UPDATE RESTRICT, ',
        'CONSTRAINT chk_bd_chitiet_qui CHECK (qui BETWEEN 1 AND 4), ',
        'CONSTRAINT chk_bd_chitiet_ngay CHECK ',
        '(ngay_ket_thuc IS NULL OR ngay_ket_thuc >= ngay_bat_dau)',
        ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    PREPARE stmt_20261023 FROM @ddl_20261023;
    EXECUTE stmt_20261023;
    DEALLOCATE PREPARE stmt_20261023;
    SET @ddl_20261023 = NULL;
END$$

CALL migrate_20261023_link_kehoach_hososcbd()$$
DROP PROCEDURE migrate_20261023_link_kehoach_hososcbd$$

DELIMITER ;

-- Verification after successful execution; source dossier rows are unchanged.
SHOW CREATE TABLE ke_hoach_bao_duong_dinh_ky_iso;
SHOW CREATE TABLE hososcbd_doi_iso;
SHOW CREATE TABLE baoduong_dinhky_chitiet_iso;
