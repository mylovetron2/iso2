-- Run after 20261023_link_kehoach_hososcbd.sql, after a backup.
-- Adds nguon_hoso: the single dossier source a plan reads from.
--   NULL               = no source chosen / not linked yet
--   'hososcbd_iso'     = dossier in hososcbd_iso (CNM / RDNGA), column hososcbd_id
--   'hososcbd_doi_iso' = dossier in hososcbd_doi_iso (other units), column hososcbd_doi_id
-- One-time migration: fails if the column already exists. DDL commits implicitly.
-- Requires MySQL >= 8.0.16 or MariaDB >= 10.2.1 (enforced CHECK constraints).

ALTER TABLE ke_hoach_bao_duong_dinh_ky_iso
    ADD COLUMN nguon_hoso ENUM('hososcbd_iso', 'hososcbd_doi_iso') NULL;

-- Derive the source from links that already exist.
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET nguon_hoso = 'hososcbd_iso'
    WHERE hososcbd_id IS NOT NULL;
UPDATE ke_hoach_bao_duong_dinh_ky_iso SET nguon_hoso = 'hososcbd_doi_iso'
    WHERE hososcbd_doi_id IS NOT NULL;

-- A link column may only be filled for the selected source.
ALTER TABLE ke_hoach_bao_duong_dinh_ky_iso
    ADD CONSTRAINT chk_kh_nguon_hoso CHECK (
        (hososcbd_id IS NULL OR nguon_hoso = 'hososcbd_iso')
        AND (hososcbd_doi_id IS NULL OR nguon_hoso = 'hososcbd_doi_iso')
    );

SHOW CREATE TABLE ke_hoach_bao_duong_dinh_ky_iso;
