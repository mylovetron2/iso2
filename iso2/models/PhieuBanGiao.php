<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

/**
 * Model: PhieuBanGiao (Phiếu Bàn Giao Thiết Bị)
 * Quản lý phiếu bàn giao thiết bị sau sửa chữa
 */
class PhieuBanGiao extends BaseModel
{
    public function __construct()
    {
        parent::__construct('phieubangiao_iso');
        $this->primaryKey = 'stt';
    }
    
    /**
     * Lấy danh sách phiếu bàn giao với filter và pagination
     */
    public function getList(
        string $search = '',
        string $phieuyc = '',
        string $trangthai = '',
        string $donvi = '',
        int $offset = 0,
        int $limit = 20
    ): array {
        $searchEscaped = $this->db->quote("%$search%");
        
        $where = ["1=1"];
        
        if ($search) {
            $where[] = "(p.sophieu LIKE $searchEscaped OR p.phieuyc LIKE $searchEscaped OR p.nguoigiao LIKE $searchEscaped OR p.nguoinhan LIKE $searchEscaped OR h.mavt LIKE $searchEscaped OR h.somay LIKE $searchEscaped)";
        }
        
        if ($phieuyc) {
            $phieuyc_escaped = $this->db->quote($phieuyc);
            $where[] = "p.phieuyc = $phieuyc_escaped";
        }
        
        if ($trangthai !== '') {
            $trangthaiEscaped = $this->db->quote($trangthai);
            $where[] = "p.trangthai = $trangthaiEscaped";
        }
        
        if ($donvi) {
            $donviEscaped = $this->db->quote($donvi);
            $where[] = "(p.donvigiao = $donviEscaped OR p.donvinhan = $donviEscaped)";
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "SELECT p.*, 
                       COUNT(pt.stt) as so_thietbi,
                       GROUP_CONCAT(DISTINCT CONCAT(h.mavt, ' (', h.somay, ')') SEPARATOR ', ') as danh_sach_thietbi,
                       dg.tendv as ten_donvi_giao,
                       dn.tendv as ten_donvi_nhan
                FROM {$this->table} p
                LEFT JOIN phieubangiao_thietbi_iso pt ON p.sophieu = pt.sophieu
                LEFT JOIN hososcbd_iso h ON pt.hososcbd_stt = h.stt
                LEFT JOIN donvi_iso dg ON p.donvigiao = dg.madv
                LEFT JOIN donvi_iso dn ON p.donvinhan = dn.madv
                WHERE $whereClause
                GROUP BY p.stt
                ORDER BY p.stt DESC
                LIMIT $limit OFFSET $offset";
        
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Đếm tổng số phiếu bàn giao
     */
    public function countList(
        string $search = '',
        string $phieuyc = '',
        string $trangthai = '',
        string $donvi = ''
    ): int {
        $searchEscaped = $this->db->quote("%$search%");
        
        $where = ["1=1"];
        
        if ($search) {
            $where[] = "(p.sophieu LIKE $searchEscaped OR p.phieuyc LIKE $searchEscaped OR p.nguoigiao LIKE $searchEscaped OR p.nguoinhan LIKE $searchEscaped OR h.mavt LIKE $searchEscaped OR h.somay LIKE $searchEscaped)";
        }
        
        if ($phieuyc) {
            $phieuyc_escaped = $this->db->quote($phieuyc);
            $where[] = "p.phieuyc = $phieuyc_escaped";
        }
        
        if ($trangthai !== '') {
            $trangthaiEscaped = $this->db->quote($trangthai);
            $where[] = "p.trangthai = $trangthaiEscaped";
        }
        
        if ($donvi) {
            $donviEscaped = $this->db->quote($donvi);
            $where[] = "(p.donvigiao = $donviEscaped OR p.donvinhan = $donviEscaped)";
        }
        
        $whereClause = implode(' AND ', $where);
        
        // JOIN với các bảng liên quan để tìm kiếm theo mavt và somay
        $sql = "SELECT COUNT(DISTINCT p.stt) 
                FROM {$this->table} p
                LEFT JOIN phieubangiao_thietbi_iso pt ON p.sophieu = pt.sophieu
                LEFT JOIN hososcbd_iso h ON pt.hososcbd_stt = h.stt
                WHERE $whereClause";
        $stmt = $this->query($sql);
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Lấy số phiếu bàn giao tiếp theo
     */
    public function getNextSoPhieu(): string
    {
        $sql = "SELECT sophieu FROM {$this->table} ORDER BY stt DESC LIMIT 1";
        $stmt = $this->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && preg_match('/PBG-(\d+)/', $result['sophieu'], $matches)) {
            $nextNumber = (int)$matches[1] + 1;
        } else {
            $nextNumber = 1;
        }
        
        return 'PBG-' . str_pad((string)$nextNumber, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Lấy số phiếu bàn giao tiếp theo cho một phiếu yêu cầu
     * Format: {phieuyc}-{slbg}
     */
    public function getNextSoPhieuForPhieuYC(string $phieuyc): string
    {
        $phieuyc = trim($phieuyc);
        $phieuyc_escaped = $this->db->quote($phieuyc);
        
        // Tìm tất cả phiếu bàn giao của phiếu YC này
        $sql = "SELECT sophieu FROM {$this->table} 
                WHERE phieuyc = $phieuyc_escaped 
                   OR sophieu LIKE CONCAT($phieuyc_escaped, '-%')
                ORDER BY sophieu DESC";
        
        $stmt = $this->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $maxSlbg = 0;
        foreach ($results as $row) {
            // Parse số sau dấu gạch ngang: "1984-1" => 1, "1984-2" => 2
            if (preg_match('/^' . preg_quote($phieuyc, '/') . '-(\d+)$/', $row['sophieu'], $matches)) {
                $slbg = (int)$matches[1];
                if ($slbg > $maxSlbg) {
                    $maxSlbg = $slbg;
                }
            }
        }
        
        $newSlbg = $maxSlbg + 1;
        return $phieuyc . '-' . $newSlbg;
    }
    
    /**
     * Tìm phiếu bàn giao theo số phiếu
     */
    public function findBySoPhieu(string $sophieu): array|false
    {
        $sophieuEscaped = $this->db->quote($sophieu);
        $sql = "SELECT p.*, 
                       dg.tendv as ten_donvi_giao,
                       dn.tendv as ten_donvi_nhan
                FROM {$this->table} p
                LEFT JOIN donvi_iso dg ON p.donvigiao = dg.madv
                LEFT JOIN donvi_iso dn ON p.donvinhan = dn.madv
                WHERE p.sophieu = $sophieuEscaped";
        $stmt = $this->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result : false;
    }
    
    /**
     * Lấy chi tiết phiếu bàn giao kèm thiết bị
     */
    public function getDetailWithDevices(int $stt): array|false
    {
        // Load phiếu với thông tin đơn vị
        $sql = "SELECT p.*,
                       dg.tendv as donvigiao_tendv,
                       dn.tendv as donvinhan_tendv
                FROM {$this->table} p
                LEFT JOIN donvi_iso dg ON p.donvigiao = dg.madv
                LEFT JOIN donvi_iso dn ON p.donvinhan = dn.madv
                WHERE p.stt = :stt";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':stt' => $stt]);
        $phieu = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$phieu) {
            return false;
        }
        
        // Load thiết bị
        $sophieuEscaped = $this->db->quote($phieu['sophieu']);
        $sql = "SELECT pt.*, 
                       h.mavt, 
                       COALESCE(t.tenvt, h.mavt) as tenvt,
                       h.somay, 
                       h.maql, 
                       h.phieu as phieu_yc
                FROM phieubangiao_thietbi_iso pt
                INNER JOIN hososcbd_iso h ON pt.hososcbd_stt = h.stt
                LEFT JOIN thietbi_iso t ON h.mavt = t.mavt AND h.somay = t.somay
                WHERE pt.sophieu = $sophieuEscaped
                ORDER BY pt.stt";
        $stmt = $this->query($sql);
        $phieu['thietbi'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $phieu;
    }
    
    /**
     * Lấy thống kê
     */
    public function getStats(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN trangthai = 0 THEN 1 ELSE 0 END) as nhap,
                    SUM(CASE WHEN trangthai = 1 THEN 1 ELSE 0 END) as daduyet
                FROM {$this->table}";
        
        $stmt = $this->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lấy danh sách phiếu yêu cầu có thiết bị chưa bàn giao
     */
    public function getPhieuYCWithUndeliveredDevices(string $search = ''): array
    {
        $where = "h.ngaykt IS NOT NULL AND h.ngaykt != '0000-00-00'";
        
        if ($search) {
            $searchEscaped = $this->db->quote("%$search%");
            $where .= " AND h.phieu LIKE $searchEscaped";
        }
        
        $sql = "SELECT 
                    h.phieu,
                    COUNT(DISTINCT h.stt) as tong_thietbi,
                    SUM(CASE WHEN h.bg = 1 THEN 1 ELSE 0 END) as da_bangiao,
                    COUNT(DISTINCT h.stt) - SUM(CASE WHEN h.bg = 1 THEN 1 ELSE 0 END) as chua_bangiao,
                    MIN(h.ngaykt) as ngay_sua_som_nhat,
                    MAX(h.ngaykt) as ngay_sua_muon_nhat
                FROM hososcbd_iso h
                WHERE $where
                GROUP BY h.phieu
                HAVING chua_bangiao > 0
                ORDER BY h.phieu DESC";
        
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
