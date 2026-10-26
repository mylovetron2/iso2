-- Run after 20261023_link_kehoach_hososcbd.sql and the 20261025 migrations.
-- Back up first: DDL commits implicitly. One-time migration.
-- Allow team dossiers for every main unit, including an unassigned main unit.
-- Preserve the CNM/RDNGA restriction for hososcbd_iso links.
-- Preserve chk_kh_single_hoso and chk_kh_nguon_hoso.
SET @ddl_allow_all_units = CONCAT(
    'ALTER TABLE ke_hoach_bao_duong_dinh_ky_iso ',
    IF(VERSION() LIKE '%MariaDB%', 'DROP CONSTRAINT ', 'DROP CHECK '),
    'chk_kh_hoso_donvi, ',
    'ADD CONSTRAINT chk_kh_hoso_donvi CHECK (',
    'hososcbd_id IS NULL OR ',
    'COALESCE(UPPER(TRIM(donvi_lam_chinh)), '''') IN (''CNM'', ''RDNGA''))'
);
PREPARE stmt_allow_all_units FROM @ddl_allow_all_units;
EXECUTE stmt_allow_all_units;
DEALLOCATE PREPARE stmt_allow_all_units;
SET @ddl_allow_all_units = NULL;

SHOW CREATE TABLE ke_hoach_bao_duong_dinh_ky_iso;
