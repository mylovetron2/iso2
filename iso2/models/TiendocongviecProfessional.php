<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class TiendocongviecProfessional extends BaseModel {
    protected string $table = 'hososcbd_iso';

    public function __construct() {
        parent::__construct($this->table);
    }

    // Lấy danh sách có filter, search, phân trang
    public function getList(string $search = '', string $status = '', int $offset = 0, int $limit = 15): array {
        $sql = "SELECT * FROM {$this->table} WHERE 1";
        $params = [];
        if ($search) {
            // Tìm kiếm theo các trường phổ biến trong bảng hososcbd_iso
            $sql .= " AND (mavt LIKE :search OR somay LIKE :search OR hoso LIKE :search OR model LIKE :search OR nhomsc LIKE :search)";
            $params['search'] = "%$search%";
        }
        // hososcbd_iso không có cột trang_thai, nên filter status theo nhomsc hoặc ttktafter nếu cần
        if ($status) {
            // Lọc trạng thái đúng chuẩn bảng hososcbd_iso
            if ($status === 'Hoàn thành') {
                $sql .= " AND ngaykt IS NOT NULL AND ngaykt != '0000-00-00' AND ngaykt != ''";
            } elseif ($status === 'Đang thực hiện') {
                $sql .= " AND ngayth IS NOT NULL AND ngayth != '0000-00-00' AND ngayth != '' AND (ngaykt IS NULL OR ngaykt = '0000-00-00' OR ngaykt = '')";
            } elseif ($status === 'Chờ thực hiện') {
                $sql .= " AND (ngayth IS NULL OR ngayth = '0000-00-00' OR ngayth = '') AND (ngaykt IS NULL OR ngaykt = '0000-00-00' OR ngaykt = '')";
            }
            // "Tất cả trạng thái" không thêm điều kiện
        }
    $sql .= " ORDER BY stt DESC LIMIT :offset, :limit";
        $params['offset'] = (int)$offset;
        $params['limit'] = (int)$limit;
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            if ($k === 'offset' || $k === 'limit') {
                $stmt->bindValue(":".$k, $v, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(":".$k, $v);
            }
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countList(string $search = '', string $status = ''): int {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE 1";
        $params = [];
        if ($search) {
            $sql .= " AND (mavt LIKE :search OR somay LIKE :search OR hoso LIKE :search OR model LIKE :search OR nhomsc LIKE :search)";
            $params['search'] = "%$search%";
        }
        if ($status) {
            if ($status === 'Hoàn thành') {
                $sql .= " AND ngaykt IS NOT NULL AND ngaykt != '0000-00-00'";
            } elseif ($status === 'Đang thực hiện') {
                $sql .= " AND ngayth IS NOT NULL AND ngayth != '0000-00-00' AND (ngaykt IS NULL OR ngaykt = '0000-00-00')";
            } elseif ($status === 'Chờ thực hiện') {
                $sql .= " AND (ngayth IS NULL OR ngayth = '0000-00-00') AND (ngaykt IS NULL OR ngaykt = '0000-00-00')";
            }
        }
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(":".$k, $v);
        }
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ? (int)$row['total'] : 0;
    }

    // Thống kê nhanh
    public function getStats(): array {
        $total = $this->countList();
        $completed = $this->countList('', 'Hoàn thành');
        $working = $this->countList('', 'Đang thực hiện');
        $pending = $this->countList('', 'Chờ thực hiện');
        return [
            'total' => $total,
            'completed' => $completed,
            'working' => $working,
            'pending' => $pending
        ];
    }

    // Giả lập danh sách bảo dưỡng định kỳ (có thể mở rộng)
    public function getMaintenanceList(int $offset = 0, int $limit = 15): array {
        // TODO: Lấy từ bảng khác nếu có
        return [];
    }
    
    public function countMaintenanceList(): int {
        // TODO: Lấy từ bảng khác nếu có
        return 0;
    }
}
