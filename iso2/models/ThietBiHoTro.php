<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class ThietBiHoTro extends BaseModel {
    protected string $table = 'thietbihotro_iso';
    protected string $primaryKey = 'stt'; // Bảng này dùng 'stt' thay vì 'id'

    public function __construct() {
        parent::__construct($this->table);
    }

    /**
     * Lấy danh sách thiết bị hỗ trợ với filter, search, phân trang
     */
    public function getList(string $search = '', string $chusohuu = '', int $offset = 0, int $limit = 15): array {
        $this->db->exec("SET NAMES latin1");
        
        // Build SQL with escaped values
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        
        if ($search) {
            $searchEscaped = $this->db->quote("%$search%");
            $sql .= " AND (tenthietbi LIKE {$searchEscaped} OR tenvt LIKE {$searchEscaped} OR serialnumber LIKE {$searchEscaped} OR hosomay LIKE {$searchEscaped})";
        }
        
        if ($chusohuu) {
            $chusohuuEscaped = $this->db->quote("%$chusohuu%");
            $sql .= " AND chusohuu LIKE {$chusohuuEscaped}";
        }
        
        // Cast to int for safety
        $offset = (int)$offset;
        $limit = (int)$limit;
        $sql .= " ORDER BY stt DESC LIMIT {$offset}, {$limit}";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

        /**
     * Đếm tổng số bản ghi (phục vụ phân trang)
     */
    public function countList(string $search = '', string $chusohuu = ''): int {
        $this->db->exec("SET NAMES latin1");
        
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE 1=1";
        
        if ($search) {
            $searchEscaped = $this->db->quote("%$search%");
            $sql .= " AND (tenthietbi LIKE {$searchEscaped} OR tenvt LIKE {$searchEscaped} OR serialnumber LIKE {$searchEscaped} OR hosomay LIKE {$searchEscaped})";
        }
        
        if ($chusohuu) {
            $chusohuuEscaped = $this->db->quote("%$chusohuu%");
            $sql .= " AND chusohuu LIKE {$chusohuuEscaped}";
        }
        
        $stmt = $this->db->query($sql);
        $row = $stmt->fetch();
        return $row ? (int)$row['total'] : 0;
    }

    /**
     * Thống kê thiết bị
     */
    public function getStats(): array {
        $total = $this->countList();
        
        // Thiết bị còn hạn kiểm định (ngaykdtt > today)
        $stmt = $this->query("SELECT COUNT(*) as count FROM {$this->table} WHERE ngaykdtt > CURDATE()");
        $conhan = $stmt->fetch()['count'];
        
        // Thiết bị hết hạn kiểm định (ngaykdtt <= today)
        $stmt = $this->query("SELECT COUNT(*) as count FROM {$this->table} WHERE ngaykdtt <= CURDATE() AND ngaykdtt IS NOT NULL");
        $hethan = $stmt->fetch()['count'];
        
        // Thiết bị sắp hết hạn (trong vòng 30 ngày)
        $stmt = $this->query("SELECT COUNT(*) as count FROM {$this->table} WHERE ngaykdtt BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
        $saphethan = $stmt->fetch()['count'];
        
        return [
            'total' => $total,
            'conhan' => $conhan,
            'hethan' => $hethan,
            'saphethan' => $saphethan
        ];
    }

    /**
     * Lấy danh sách chủ sở hữu (unique)
     */
    public function getChuSoHuuList(): array {
        $stmt = $this->query("SELECT DISTINCT chusohuu FROM {$this->table} WHERE chusohuu != '' ORDER BY chusohuu");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Lấy thiết bị sắp hết hạn kiểm định
     */
    public function getExpiringSoon(int $days = 30): array {
        $this->db->exec("SET NAMES latin1");
        
        $sql = "SELECT * FROM {$this->table} 
                WHERE ngaykdtt BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
                ORDER BY ngaykdtt ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
