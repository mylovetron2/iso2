-- Create view_phieu_iso from hososcbd_iso table
-- View includes: phieu, ngayyc, ngyeucau columns

CREATE OR REPLACE VIEW view_phieu_iso AS
SELECT 
    phieu,
    ngayyc,
    ngyeucau
FROM 
    hososcbd_iso
ORDER BY 
    ngayyc DESC, phieu DESC;
