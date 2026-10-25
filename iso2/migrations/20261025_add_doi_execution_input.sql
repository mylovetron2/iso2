-- Run after 20261023_link_kehoach_hososcbd.sql.
-- Stores the executor name alongside the dates already present on hososcbd_doi_iso.
-- One-time migration: fails if the column already exists.

ALTER TABLE hososcbd_doi_iso
    ADD COLUMN nguoi_thuc_hien_iso VARCHAR(255) NULL AFTER ngaykt;

SHOW CREATE TABLE hososcbd_doi_iso;
