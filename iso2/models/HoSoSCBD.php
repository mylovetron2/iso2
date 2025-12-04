<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

/**
 * Model: HoSoSCBD (Hồ Sơ Sửa Chữa Bảo Dưỡng)
 * Quản lý toàn bộ hồ sơ sửa chữa và bảo dưỡng thiết bị
 */
class HoSoSCBD extends BaseModel
{
    public function __construct()
    {
        parent::__construct('hososcbd_iso');
        $this->primaryKey = 'stt';
    }
    
    /**
     * Lấy danh sách hồ sơ với filter và pagination
     */
    public function getList(
        string $search = '',
        string $nhomsc = '',
        string $trangthai = '',
        string $madv = '',
        int $offset = 0,
        int $limit = 15
    ): array {
        $searchEscaped = $this->db->quote("%$search%");
        
        $where = ["1=1"];
        
        if ($search) {
            $where[] = "(h.maql LIKE $searchEscaped OR h.phieu LIKE $searchEscaped OR h.mavt LIKE $searchEscaped OR h.somay LIKE $searchEscaped OR h.madv LIKE $searchEscaped OR d.tendv LIKE $searchEscaped)";
        }
        
        if ($nhomsc) {
            $nhomscEscaped = $this->db->quote($nhomsc);
            $where[] = "h.nhomsc = $nhomscEscaped";
        }
        
        if ($madv) {
            $madvEscaped = $this->db->quote($madv);
            $where[] = "h.madv = $madvEscaped";
        }
        
        // Lọc theo trạng thái
        if ($trangthai === 'chuath') { // Chưa thực hiện
            $where[] = "h.ngayth IS NULL OR h.ngayth = '0000-00-00'";
        } elseif ($trangthai === 'danglam') { // Đang làm
            $where[] = "h.ngayth IS NOT NULL AND h.ngayth != '0000-00-00' AND (h.ngaykt IS NULL OR h.ngaykt = '0000-00-00')";
        } elseif ($trangthai === 'hoanthanh') { // Hoàn thành
            $where[] = "h.ngaykt IS NOT NULL AND h.ngaykt != '0000-00-00'";
        } elseif ($trangthai === 'chuabg') { // Chưa bàn giao
            $where[] = "h.bg = 0 AND h.ngaykt IS NOT NULL AND h.ngaykt != '0000-00-00'";
        } elseif ($trangthai === 'dabg') { // Đã bàn giao
            $where[] = "h.bg = 1";
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "SELECT h.*, d.tendv 
                FROM {$this->table} h
                LEFT JOIN donvi_iso d ON h.madv = d.madv
                WHERE $whereClause
                ORDER BY h.ngayyc DESC, h.phieu DESC
                LIMIT $limit OFFSET $offset";
        
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Đếm tổng số hồ sơ
     */
    public function countList(
        string $search = '',
        string $nhomsc = '',
        string $trangthai = '',
        string $madv = ''
    ): int {
        $searchEscaped = $this->db->quote("%$search%");
        
        $where = ["1=1"];
        
        if ($search) {
            $where[] = "(h.maql LIKE $searchEscaped OR h.phieu LIKE $searchEscaped OR h.mavt LIKE $searchEscaped OR h.somay LIKE $searchEscaped OR h.madv LIKE $searchEscaped OR d.tendv LIKE $searchEscaped)";
        }
        
        if ($nhomsc) {
            $nhomscEscaped = $this->db->quote($nhomsc);
            $where[] = "h.nhomsc = $nhomscEscaped";
        }
        
        if ($madv) {
            $madvEscaped = $this->db->quote($madv);
            $where[] = "h.madv = $madvEscaped";
        }
        
        if ($trangthai === 'chuath') {
            $where[] = "h.ngayth IS NULL OR h.ngayth = '0000-00-00'";
        } elseif ($trangthai === 'danglam') {
            $where[] = "h.ngayth IS NOT NULL AND h.ngayth != '0000-00-00' AND (h.ngaykt IS NULL OR h.ngaykt = '0000-00-00')";
        } elseif ($trangthai === 'hoanthanh') {
            $where[] = "h.ngaykt IS NOT NULL AND h.ngaykt != '0000-00-00'";
        } elseif ($trangthai === 'chuabg') {
            $where[] = "h.bg = 0 AND h.ngaykt IS NOT NULL AND h.ngaykt != '0000-00-00'";
        } elseif ($trangthai === 'dabg') {
            $where[] = "h.bg = 1";
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "SELECT COUNT(*) 
                FROM {$this->table} h
                LEFT JOIN donvi_iso d ON h.madv = d.madv
                WHERE $whereClause";
        $stmt = $this->query($sql);
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Lấy số phiếu tiếp theo
     */
    public function getNextPhieuNumber(): string
    {
        $sql = "SELECT MAX(CAST(phieu AS UNSIGNED)) as max_phieu FROM {$this->table}";
        $stmt = $this->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $nextNumber = ($result['max_phieu'] ?? 0) + 1;
        
        return str_pad((string)$nextNumber, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Lấy thống kê
     */
    public function getStats(string $nhomsc = ''): array
    {
        $where = $nhomsc ? "WHERE nhomsc = " . $this->db->quote($nhomsc) : "";
        
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN ngayth IS NULL OR ngayth = '0000-00-00' THEN 1 ELSE 0 END) as chuath,
                    SUM(CASE WHEN ngayth IS NOT NULL AND ngayth != '0000-00-00' AND (ngaykt IS NULL OR ngaykt = '0000-00-00') THEN 1 ELSE 0 END) as danglam,
                    SUM(CASE WHEN ngaykt IS NOT NULL AND ngaykt != '0000-00-00' AND bg = 0 THEN 1 ELSE 0 END) as chuabg,
                    SUM(CASE WHEN bg = 1 THEN 1 ELSE 0 END) as dabg
                FROM {$this->table}
                $where";
        
        $stmt = $this->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Tìm hồ sơ theo mã quản lý
     */
    public function findByMaQL(string $maql): array
    {
        $maqlEscaped = $this->db->quote($maql);
        $sql = "SELECT * FROM {$this->table} WHERE maql = $maqlEscaped";
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Kiểm tra thiết bị có sẵn sàng không (bg=0 nghĩa là đang bận)
     * Trả về true nếu thiết bị có thể sử dụng (không có bản ghi bg=0)
     * 
     * @param string $mavt Mã vật tư
     * @param string $somay Số máy
     * @param int|null $excludeStt Loại trừ STT này (dùng khi edit)
     * @return bool
     */
    public function isDeviceAvailable(string $mavt, string $somay, ?int $excludeStt = null): bool
    {
        $mavtEscaped = $this->db->quote($mavt);
        $somayEscaped = $this->db->quote($somay);
        
        $sql = "SELECT COUNT(*) as count 
                FROM {$this->table} 
                WHERE mavt = $mavtEscaped 
                  AND somay = $somayEscaped 
                  AND bg = 0";
        
        if ($excludeStt !== null) {
            $sql .= " AND stt != " . (int)$excludeStt;
        }
        
        $stmt = $this->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Trả về true nếu không tìm thấy bản ghi nào (device available)
        return (int)$result['count'] === 0;
    }
    
    /**
     * Cập nhật trạng thái bàn giao
     */
    public function updateBanGiao(int $stt): bool
    {
        $data = [
            'bg' => 1,
            'slbg' => new \PDOStatement('slbg + 1') // Will be handled differently
        ];
        
        $sql = "UPDATE {$this->table} 
                SET bg = 1, slbg = COALESCE(slbg, 0) + 1 
                WHERE stt = :stt";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':stt' => $stt]);
    }
    
    /**
     * Lấy danh sách thiết bị chưa bàn giao theo phiếu YC
     */
    public function getUndeliveredByPhieu(string $phieu): array
    {
        $phieuEscaped = $this->db->quote($phieu);
        
        $sql = "SELECT h.*, 
                       COALESCE(t.tenvt, h.mavt) as tenvt,
                       d.tendv
                FROM {$this->table} h
                LEFT JOIN thietbi_iso t ON h.mavt = t.mavt AND h.somay = t.somay
                LEFT JOIN donvi_iso d ON h.madv = d.madv
                WHERE h.phieu = $phieuEscaped 
                  AND h.bg = 0
                  AND h.ngaykt IS NOT NULL 
                  AND h.ngaykt != '0000-00-00'
                ORDER BY h.maql";
        
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Cập nhật trạng thái bg
     */
    public function updateBGStatus(int $stt, int $bgStatus): bool
    {
        $sql = "UPDATE {$this->table} 
                SET bg = :bg 
                WHERE stt = :stt";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':bg' => $bgStatus,
            ':stt' => $stt
        ]);
    }
    
    /**
     * Lấy thông tin thiết bị với đầy đủ chi tiết (tenvt, tendv)
     */
    public function getDeviceWithDetails(int $stt): array|false
    {
        $sql = "SELECT h.*, 
                       COALESCE(t.tenvt, h.mavt) as tenvt,
                       d.tendv
                FROM {$this->table} h
                LEFT JOIN thietbi_iso t ON h.mavt = t.mavt AND h.somay = t.somay
                LEFT JOIN donvi_iso d ON h.madv = d.madv
                WHERE h.stt = :stt";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':stt' => $stt]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
