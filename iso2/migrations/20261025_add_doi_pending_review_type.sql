-- Distinguish team-dossier edit requests from hososcbd_iso requests.
ALTER TABLE hososcbd_pending_edits
    ADD COLUMN record_type ENUM('hososcbd_iso', 'hososcbd_doi_iso')
        NOT NULL DEFAULT 'hososcbd_iso' AFTER hososcbd_stt;

SHOW CREATE TABLE hososcbd_pending_edits;
