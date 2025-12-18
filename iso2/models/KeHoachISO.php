<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class KeHoachISO extends BaseModel
{
    protected string $primaryKey = 'stt';
    
    public function __construct()
    {
        parent::__construct('kehoach_iso');
        $this->primaryKey = 'stt';
    }

    /**
     * Lấy kế hoạch theo tháng và năm
     */
    public function getByMonthYear(int $month, int $year, int $limit = 10, int $offset = 0): array
    {
        $sql = "SELECT k.*, t.tenviettat, t.chusohuu, t.mavattu
                FROM {$this->table} k
                LEFT JOIN thietbihckd_iso t ON k.tenthietbi = t.tenthietbi AND k.somay = t.somay
                WHERE k.thang = :month AND k.namkh = :year
                ORDER BY k.stt
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':month', $month, PDO::PARAM_INT);
        $stmt->bindValue(':year', $year, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Đếm tổng số thiết bị theo tháng và năm (có tìm kiếm)
     */
    public function countByMonthYear(int $month, int $year, string $searchTerm = '', string $searchType = 'all', string $statusFilter = 'all'): int
    {
        // Trim và normalize search term
        $searchTerm = trim($searchTerm);
        $hasSearch = ($searchTerm !== '');
        
        $sql = "SELECT COUNT(*) as total FROM {$this->table} k
                LEFT JOIN thietbihckd_iso t ON k.tenthietbi = t.tenthietbi AND k.somay = t.somay
                LEFT JOIN hosohckd_iso h ON h.stt = (
                    SELECT h2.stt 
                    FROM hosohckd_iso h2 
                    WHERE h2.tenmay = t.mavattu 
                    AND YEAR(h2.ngayhc) = " . (int)$year . "
                    ORDER BY h2.ngayhc DESC 
                    LIMIT 1
                )
                WHERE k.thang = :month AND k.namkh = :year";
        
        // Thêm điều kiện tìm kiếm
        if ($hasSearch) {
            // Tạo escaped search value cho literal substitution
            $escapedSearch = $this->db->quote('%' . $searchTerm . '%');
            
            switch ($searchType) {
                case 'device':
                    $sql .= " AND (k.tenthietbi LIKE $escapedSearch OR t.tenviettat LIKE $escapedSearch)";
                    break;
                case 'code':
                    $sql .= " AND (k.somay LIKE $escapedSearch OR t.mavattu LIKE $escapedSearch)";
                    break;
                case 'owner':
                    $sql .= " AND t.chusohuu LIKE $escapedSearch";
                    break;
                default: // 'all'
                    $sql .= " AND (k.tenthietbi LIKE $escapedSearch OR t.tenviettat LIKE $escapedSearch 
                             OR k.somay LIKE $escapedSearch OR t.mavattu LIKE $escapedSearch 
                             OR t.chusohuu LIKE $escapedSearch)";
            }
        }
        
        // Thêm điều kiện lọc theo trạng thái
        if ($statusFilter !== 'all') {
            switch ($statusFilter) {
                case 'not_calibrated':
                    $sql .= " AND h.ngayhc IS NULL";
                    break;
                case 'calibrated':
                    $sql .= " AND h.ngayhc IS NOT NULL";
                    break;
                case 'calibrated_broken':
                    $sql .= " AND h.ngayhc IS NOT NULL AND h.ttkt = 'Hỏng'";
                    break;
            }
        }
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':month', $month, PDO::PARAM_INT);
            $stmt->bindValue(':year', $year, PDO::PARAM_INT);
            
            $stmt->execute();
            
            $result = $stmt->fetch();
            return (int)($result['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("SQL Error in countByMonthYear: " . $e->getMessage());
            error_log("SQL Query: " . $sql);
            error_log("Month: $month, Year: $year, Search: '$searchTerm', Type: $searchType, HasSearch: " . ($hasSearch ? 'true' : 'false'));
            throw $e;
        }
    }

    /**
     * Lấy kế hoạch với trạng thái hiệu chuẩn (có tìm kiếm)
     */
    public function getWithHCStatus(int $month, int $year, int $limit = 10, int $offset = 0, string $searchTerm = '', string $searchType = 'all', string $statusFilter = 'all'): array
    {
        // Trim và normalize search term
        $searchTerm = trim($searchTerm);
        $hasSearch = ($searchTerm !== '');
        
        // Dùng literal value cho year trong subquery để tránh duplicate parameter
        $sql = "SELECT k.*, 
                       t.tenviettat, 
                       t.chusohuu, 
                       t.mavattu,
                       t.loaitb,
                       h.ngayhc,
                       h.ttkt,
                       h.sohs,
                       h.nhanvien
                FROM {$this->table} k
                LEFT JOIN thietbihckd_iso t ON k.tenthietbi = t.tenthietbi AND k.somay = t.somay
                LEFT JOIN hosohckd_iso h ON h.stt = (
                    SELECT h2.stt 
                    FROM hosohckd_iso h2 
                    WHERE h2.tenmay = t.mavattu 
                    AND YEAR(h2.ngayhc) = " . (int)$year . "
                    ORDER BY h2.ngayhc DESC 
                    LIMIT 1
                )
                WHERE k.thang = :month AND k.namkh = :year";
        
        // Thêm điều kiện tìm kiếm
        if ($hasSearch) {
            // Tạo escaped search value cho literal substitution
            $escapedSearch = $this->db->quote('%' . $searchTerm . '%');
            
            switch ($searchType) {
                case 'device':
                    $sql .= " AND (k.tenthietbi LIKE $escapedSearch OR t.tenviettat LIKE $escapedSearch)";
                    break;
                case 'code':
                    $sql .= " AND (k.somay LIKE $escapedSearch OR t.mavattu LIKE $escapedSearch)";
                    break;
                case 'owner':
                    $sql .= " AND t.chusohuu LIKE $escapedSearch";
                    break;
                default: // 'all'
                    $sql .= " AND (k.tenthietbi LIKE $escapedSearch OR t.tenviettat LIKE $escapedSearch 
                             OR k.somay LIKE $escapedSearch OR t.mavattu LIKE $escapedSearch 
                             OR t.chusohuu LIKE $escapedSearch)";
            }
        }
        
        // Thêm điều kiện lọc theo trạng thái
        if ($statusFilter !== 'all') {
            switch ($statusFilter) {
                case 'not_calibrated':
                    $sql .= " AND h.ngayhc IS NULL";
                    break;
                case 'calibrated':
                    $sql .= " AND h.ngayhc IS NOT NULL";
                    break;
                case 'calibrated_broken':
                    $sql .= " AND h.ngayhc IS NOT NULL AND h.ttkt = 'Hỏng'";
                    break;
            }
        }
        
        $sql .= " ORDER BY k.stt LIMIT :limit OFFSET :offset";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':month', $month, PDO::PARAM_INT);
            $stmt->bindValue(':year', $year, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("SQL Error in getWithHCStatus: " . $e->getMessage());
            error_log("SQL Query: " . $sql);
            throw $e;
        }
    }

    /**
     * Lấy danh sách năm có kế hoạch
     */
    public function getAvailableYears(): array
    {
        $sql = "SELECT DISTINCT namkh FROM {$this->table} ORDER BY namkh DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Tìm kiếm thiết bị theo tên
     */
    public function searchByName(string $keyword, int $month, int $year): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE (tenthietbi LIKE :keyword OR somay LIKE :keyword)
                AND thang = :month AND namkh = :year
                ORDER BY tenthietbi";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':keyword', "%$keyword%");
        $stmt->bindValue(':month', $month, PDO::PARAM_INT);
        $stmt->bindValue(':year', $year, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
