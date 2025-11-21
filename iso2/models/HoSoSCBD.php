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
            $where[] = "(maql LIKE $searchEscaped OR phieu LIKE $searchEscaped OR mavt LIKE $searchEscaped OR somay LIKE $searchEscaped)";
        }
        
        if ($nhomsc) {
            $nhomscEscaped = $this->db->quote($nhomsc);
            $where[] = "nhomsc = $nhomscEscaped";
        }
        
        if ($madv) {
            $madvEscaped = $this->db->quote($madv);
            $where[] = "madv = $madvEscaped";
        }
        
        // Lọc theo trạng thái
        if ($trangthai === 'chuath') { // Chưa thực hiện
            $where[] = "ngayth IS NULL OR ngayth = '0000-00-00'";
        } elseif ($trangthai === 'danglam') { // Đang làm
            $where[] = "ngayth IS NOT NULL AND ngayth != '0000-00-00' AND (ngaykt IS NULL OR ngaykt = '0000-00-00')";
        } elseif ($trangthai === 'hoanthanh') { // Hoàn thành
            $where[] = "ngaykt IS NOT NULL AND ngaykt != '0000-00-00'";
        } elseif ($trangthai === 'chuabg') { // Chưa bàn giao
            $where[] = "bg = 0 AND ngaykt IS NOT NULL AND ngaykt != '0000-00-00'";
        } elseif ($trangthai === 'dabg') { // Đã bàn giao
            $where[] = "bg = 1";
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
            $where[] = "(maql LIKE $searchEscaped OR phieu LIKE $searchEscaped OR mavt LIKE $searchEscaped OR somay LIKE $searchEscaped)";
        }
        
        if ($nhomsc) {
            $nhomscEscaped = $this->db->quote($nhomsc);
            $where[] = "nhomsc = $nhomscEscaped";
        }
        
        if ($madv) {
            $madvEscaped = $this->db->quote($madv);
            $where[] = "madv = $madvEscaped";
        }
        
        if ($trangthai === 'chuath') {
            $where[] = "ngayth IS NULL OR ngayth = '0000-00-00'";
        } elseif ($trangthai === 'danglam') {
            $where[] = "ngayth IS NOT NULL AND ngayth != '0000-00-00' AND (ngaykt IS NULL OR ngaykt = '0000-00-00')";
        } elseif ($trangthai === 'hoanthanh') {
            $where[] = "ngaykt IS NOT NULL AND ngaykt != '0000-00-00'";
        } elseif ($trangthai === 'chuabg') {
            $where[] = "bg = 0 AND ngaykt IS NOT NULL AND ngaykt != '0000-00-00'";
        } elseif ($trangthai === 'dabg') {
            $where[] = "bg = 1";
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE $whereClause";
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
}
