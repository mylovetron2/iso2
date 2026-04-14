<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

/**
 * Model: HoSoScBdTamDung
 * Quản lý tạm dừng và tiếp tục hồ sơ SCBĐ
 * 
 * LƯU Ý: Model này KHÔNG sửa bảng hososcbd_iso
 * Trạng thái tạm dừng được xác định bằng record mới nhất trong hososcbd_tamdung
 */
class HoSoScBdTamDung extends BaseModel
{
    public function __construct()
    {
        parent::__construct('hososcbd_tamdung');
        $this->primaryKey = 'id';
    }

    /**
     * Tạm dừng hồ sơ
     * @param string $hoso Mã hồ sơ
     * @param string $nguoiThucHien Người thực hiện
     * @param string $lydoTamDung Lý do tạm dừng (bắt buộc)
     * @return bool|int|array False nếu lỗi, ID của record mới nếu thành công, hoặc array với error message
     */
    public function tamDungHoSo(string $hoso, string $nguoiThucHien, string $lydoTamDung)
    {
        // Kiểm tra bảng hososcbd_tamdung có tồn tại không
        try {
            $checkTable = $this->db->query("SHOW TABLES LIKE 'hososcbd_tamdung'");
            if ($checkTable->rowCount() === 0) {
                error_log("Table hososcbd_tamdung does not exist. Please run migration first.");
                return ['error' => 'table_not_exists', 'message' => 'Bảng dữ liệu chưa được tạo. Vui lòng chạy migration trước.'];
            }
        } catch (PDOException $e) {
            error_log("Error checking table: " . $e->getMessage());
            return ['error' => 'check_failed', 'message' => 'Không thể kiểm tra bảng dữ liệu: ' . $e->getMessage()];
        }

        if (empty($lydoTamDung)) {
            return ['error' => 'empty_reason', 'message' => 'Lý do tạm dừng là bắt buộc'];
        }

        // Kiểm tra hồ sơ có đang tạm dừng không
        if ($this->isTamDung($hoso)) {
            return ['error' => 'already_paused', 'message' => 'Hồ sơ đã tạm dừng rồi'];
        }

        try {
            // Lấy thông tin hồ sơ từ bảng chính để điền vào các cột bổ sung (nếu có)
            $hosoInfo = null;
            try {
                $infoSql = "SELECT mavt, somay, phieu FROM hososcbd_iso WHERE hoso = ? LIMIT 1";
                $infoStmt = $this->db->prepare($infoSql);
                $infoStmt->execute([$hoso]);
                $hosoInfo = $infoStmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // Bỏ qua lỗi nếu không lấy được thông tin
                error_log("Warning: Cannot get hoso info: " . $e->getMessage());
            }

            // Kiểm tra bảng có cột mavt, somay, phieu, ngay_tamdung không
            $columns = $this->db->query("SHOW COLUMNS FROM {$this->table}")->fetchAll(PDO::FETCH_COLUMN);
            $hasMavt = in_array('mavt', $columns);
            $hasSomay = in_array('somay', $columns);
            $hasPhieu = in_array('phieu', $columns);
            $hasNgayTamdung = in_array('ngay_tamdung', $columns);

            // Build INSERT SQL dựa vào cột có sẵn
            $insertCols = ['hoso', 'trangthai', 'nguoi_thuchien', 'ngay_thuchien', 'lydo_tamdung'];
            $insertPlaceholders = ['?', '?', '?', 'NOW()', '?'];
            $params = [$hoso, 'dang_tam_dung', $nguoiThucHien, $lydoTamDung];

            if ($hasMavt && $hosoInfo && isset($hosoInfo['mavt'])) {
                $insertCols[] = 'mavt';
                $insertPlaceholders[] = '?';
                $params[] = $hosoInfo['mavt'];
            }

            if ($hasSomay && $hosoInfo && isset($hosoInfo['somay'])) {
                $insertCols[] = 'somay';
                $insertPlaceholders[] = '?';
                $params[] = $hosoInfo['somay'];
            }

            if ($hasPhieu && $hosoInfo && isset($hosoInfo['phieu'])) {
                $insertCols[] = 'phieu';
                $insertPlaceholders[] = '?';
                $params[] = $hosoInfo['phieu'];
            }

            if ($hasNgayTamdung) {
                $insertCols[] = 'ngay_tamdung';
                $insertPlaceholders[] = 'NOW()';
            }

            $sql = "INSERT INTO {$this->table} 
                    (" . implode(', ', $insertCols) . ") 
                    VALUES (" . implode(', ', $insertPlaceholders) . ")";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error pausing hoso: " . $e->getMessage());
            return ['error' => 'database_error', 'message' => 'Lỗi database: ' . $e->getMessage()];
        }
    }

    /**
     * Tiếp tục hồ sơ đã tạm dừng
     * @param string $hoso Mã hồ sơ
     * @param string $nguoiThucHien Người thực hiện
     * @param string|null $ghichuTiepTuc Ghi chú khi tiếp tục (tùy chọn)
     * @return bool|int|array False nếu lỗi, ID của record mới nếu thành công, hoặc array với error message
     */
    public function tiepTucHoSo(string $hoso, string $nguoiThucHien, ?string $ghichuTiepTuc = null)
    {
        // Kiểm tra bảng hososcbd_tamdung có tồn tại không
        try {
            $checkTable = $this->db->query("SHOW TABLES LIKE 'hososcbd_tamdung'");
            if ($checkTable->rowCount() === 0) {
                error_log("Table hososcbd_tamdung does not exist. Please run migration first.");
                return ['error' => 'table_not_exists', 'message' => 'Bảng dữ liệu chưa được tạo. Vui lòng chạy migration trước.'];
            }
        } catch (PDOException $e) {
            error_log("Error checking table: " . $e->getMessage());
            return ['error' => 'check_failed', 'message' => 'Không thể kiểm tra bảng dữ liệu: ' . $e->getMessage()];
        }

        // Kiểm tra hồ sơ có đang tạm dừng không
        if (!$this->isTamDung($hoso)) {
            return ['error' => 'not_paused', 'message' => 'Hồ sơ không đang tạm dừng'];
        }

        try {
            // Lấy thông tin hồ sơ từ bảng gốc để lấy mavt, somay, phieu
            $hosoInfo = $this->db->query("SELECT mavt, somay, phieu FROM hososcbd_iso WHERE hoso = '" . addslashes($hoso) . "'")->fetch(PDO::FETCH_ASSOC);
            
            // Lấy ngay_tamdung từ record pause gần nhất (nếu cột tồn tại)
            $ngayTamdungValue = null;
            $columnsCheck = $this->db->query("SHOW COLUMNS FROM {$this->table}")->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('ngay_tamdung', $columnsCheck)) {
                $lastPauseRecord = $this->db->query("SELECT ngay_tamdung FROM {$this->table} WHERE hoso = '" . addslashes($hoso) . "' AND trangthai = 'dang_tam_dung' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                $ngayTamdungValue = $lastPauseRecord ? $lastPauseRecord['ngay_tamdung'] : null;
            }
            
            // Kiểm tra các cột có tồn tại trong bảng hososcbd_tamdung
            $columnsResult = $this->db->query("SHOW COLUMNS FROM {$this->table}");
            $columns = $columnsResult->fetchAll(PDO::FETCH_COLUMN);
            
            $hasMavt = in_array('mavt', $columns);
            $hasSomay = in_array('somay', $columns);
            $hasPhieu = in_array('phieu', $columns);
            $hasNgayTamdung = in_array('ngay_tamdung', $columns);
            $hasNgayTieptuc = in_array('ngay_tieptuc', $columns);
            
            // Build INSERT statement động
            $insertCols = ['hoso', 'trangthai', 'nguoi_thuchien', 'ngay_thuchien', 'ghichu_tieptuc'];
            $insertPlaceholders = ['?', '?', '?', 'NOW()', '?'];
            $params = [$hoso, 'da_tiep_tuc', $nguoiThucHien, $ghichuTiepTuc];
            
            if ($hasMavt && $hosoInfo && isset($hosoInfo['mavt'])) {
                $insertCols[] = 'mavt';
                $insertPlaceholders[] = '?';
                $params[] = $hosoInfo['mavt'];
            }

            if ($hasSomay && $hosoInfo && isset($hosoInfo['somay'])) {
                $insertCols[] = 'somay';
                $insertPlaceholders[] = '?';
                $params[] = $hosoInfo['somay'];
            }

            if ($hasPhieu && $hosoInfo && isset($hosoInfo['phieu'])) {
                $insertCols[] = 'phieu';
                $insertPlaceholders[] = '?';
                $params[] = $hosoInfo['phieu'];
            }

            // Cột ngay_tamdung: copy từ record pause, hoặc dùng NOW()
            if ($hasNgayTamdung) {
                $insertCols[] = 'ngay_tamdung';
                $insertPlaceholders[] = '?';
                $params[] = $ngayTamdungValue ?: date('Y-m-d H:i:s');
            }
            
            // Cột ngay_tieptuc: set NOW()
            if ($hasNgayTieptuc) {
                $insertCols[] = 'ngay_tieptuc';
                $insertPlaceholders[] = 'NOW()';
            }
            
            $sql = "INSERT INTO {$this->table} 
                    (" . implode(', ', $insertCols) . ") 
                    VALUES (" . implode(', ', $insertPlaceholders) . ")";
            
            // Debug logging
            error_log("tiepTucHoSo SQL: " . $sql);
            error_log("tiepTucHoSo Params: " . json_encode($params));
            error_log("tiepTucHoSo Columns: " . json_encode($insertCols));
            error_log("tiepTucHoSo Placeholders: " . json_encode($insertPlaceholders));
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error resuming hoso: " . $e->getMessage());
            return ['error' => 'database_error', 'message' => 'Lỗi database: ' . $e->getMessage()];
        }
    }

    /**
     * Kiểm tra hồ sơ có đang tạm dừng không
     * Dựa vào record mới nhất trong bảng hososcbd_tamdung
     * @param string $hoso Mã hồ sơ
     * @return bool True nếu đang tạm dừng, False nếu không
     */
    public function isTamDung(string $hoso): bool
    {
        try {
            // Lấy record mới nhất của hồ sơ này
            $sql = "SELECT trangthai FROM {$this->table} 
                    WHERE hoso = ? 
                    ORDER BY ngay_thuchien DESC, id DESC 
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$hoso]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Nếu không có record hoặc record cuối là 'da_tiep_tuc' => không tạm dừng
            // Nếu record cuối là 'dang_tam_dung' => đang tạm dừng
            return $result && $result['trangthai'] === 'dang_tam_dung';
        } catch (PDOException $e) {
            error_log("Error checking isTamDung: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lấy thông tin tạm dừng hiện tại của hồ sơ
     * @param string $hoso Mã hồ sơ
     * @return array|false Thông tin tạm dừng hoặc false nếu không tìm thấy
     */
    public function getTamDungInfo(string $hoso)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE hoso = ? AND trangthai = 'dang_tam_dung' 
                ORDER BY ngay_thuchien DESC, id DESC 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$hoso]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: false;
    }

    /**
     * Lấy lịch sử tạm dừng/tiếp tục của hồ sơ
     * @param string $hoso Mã hồ sơ
     * @return array Danh sách lịch sử
     */
    public function getLichSu(string $hoso): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE hoso = ? 
                ORDER BY ngay_thuchien DESC, id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$hoso]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách hồ sơ đang tạm dừng với filter và pagination
     * @param string $search Từ khóa tìm kiếm
     * @param string $madv Mã đơn vị
     * @param string $fromDate Từ ngày
     * @param string $toDate Đến ngày
     * @param int $offset Offset
     * @param int $limit Limit
     * @return array Danh sách hồ sơ
     */
    public function getDanhSachTamDung(
        string $search = '',
        string $madv = '',
        string $fromDate = '',
        string $toDate = '',
        int $offset = 0,
        int $limit = 20
    ): array {
        try {
            $searchEscaped = $this->db->quote("%$search%");
            
            $where = ["t.trangthai = 'dang_tam_dung'"];
            
            if ($search) {
                $where[] = "(h.hoso LIKE $searchEscaped OR h.phieu LIKE $searchEscaped OR h.mavt LIKE $searchEscaped OR h.somay LIKE $searchEscaped)";
            }
            
            if ($madv) {
                $madvEscaped = $this->db->quote($madv);
                $where[] = "h.madv = $madvEscaped";
            }
            
            if ($fromDate) {
                $fromDateEscaped = $this->db->quote($fromDate);
                $where[] = "t.ngay_thuchien >= $fromDateEscaped";
            }
            
            if ($toDate) {
                $toDateEscaped = $this->db->quote($toDate);
                $where[] = "t.ngay_thuchien <= $toDateEscaped";
            }
            
            $whereClause = implode(' AND ', $where);
            
            // Query hồ sơ có record cuối cùng là 'tamdung'
            $sql = "SELECT h.*, d.tendv, t.lydo_tamdung, t.ngay_thuchien, t.nguoi_thuchien
                    FROM hososcbd_iso h
                    LEFT JOIN donvi_iso d ON h.madv = d.madv
                    INNER JOIN {$this->table} t ON h.hoso = t.hoso
                    INNER JOIN (
                        SELECT hoso, MAX(id) as max_id
                        FROM {$this->table}
                        GROUP BY hoso
                    ) latest ON t.hoso = latest.hoso AND t.id = latest.max_id
                    WHERE $whereClause
                    ORDER BY t.ngay_thuchien DESC
                    LIMIT $limit OFFSET $offset";
            
            $stmt = $this->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting danh sach tam dung: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Đếm số hồ sơ đang tạm dừng
     * @param string $search Từ khóa tìm kiếm
     * @param string $madv Mã đơn vị
     * @param string $fromDate Từ ngày
     * @param string $toDate Đến ngày
     * @return int Tổng số
     */
    public function countDanhSachTamDung(
        string $search = '',
        string $madv = '',
        string $fromDate = '',
        string $toDate = ''
    ): int {
        try {
            $searchEscaped = $this->db->quote("%$search%");
            
            $where = ["t.trangthai = 'dang_tam_dung'"];
            
            if ($search) {
                $where[] = "(h.hoso LIKE $searchEscaped OR h.phieu LIKE $searchEscaped OR h.mavt LIKE $searchEscaped OR h.somay LIKE $searchEscaped)";
            }
            
            if ($madv) {
                $madvEscaped = $this->db->quote($madv);
                $where[] = "h.madv = $madvEscaped";
            }
            
            if ($fromDate) {
                $fromDateEscaped = $this->db->quote($fromDate);
                $where[] = "t.ngay_thuchien >= $fromDateEscaped";
            }
            
            if ($toDate) {
                $toDateEscaped = $this->db->quote($toDate);
                $where[] = "t.ngay_thuchien <= $toDateEscaped";
            }
            
            $whereClause = implode(' AND ', $where);
            
            $sql = "SELECT COUNT(DISTINCT h.hoso) as total
                    FROM hososcbd_iso h
                    INNER JOIN {$this->table} t ON h.hoso = t.hoso
                    INNER JOIN (
                        SELECT hoso, MAX(id) as max_id
                        FROM {$this->table}
                        GROUP BY hoso
                    ) latest ON t.hoso = latest.hoso AND t.id = latest.max_id
                    WHERE $whereClause";
            
            $stmt = $this->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int)($result['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("Error counting danh sach tam dung: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Lấy báo cáo lịch sử tạm dừng/tiếp tục với filter
     * @param string $trangthai Trạng thái filter (tamdung, tieptuc, hoặc tất cả)
     * @param string $madv Mã đơn vị
     * @param string $fromDate Từ ngày
     * @param string $toDate Đến ngày
     * @param int $offset Offset
     * @param int $limit Limit
     * @return array Danh sách lịch sử
     */
    public function getBaoCaoLichSu(
        string $trangthai = '',
        string $madv = '',
        string $fromDate = '',
        string $toDate = '',
        int $offset = 0,
        int $limit = 50
    ): array {
        $where = ['1=1'];
        
        // Hỗ trợ cả legacy values và new values
        if ($trangthai) {
            $allowedValues = ['dang_tam_dung', 'da_tiep_tuc', 'tamdung', 'tieptuc'];
            if (in_array($trangthai, $allowedValues)) {
                // "dang_tam_dung" = filter đặc biệt: chỉ lấy hồ sơ đang tạm dừng (chưa tiếp tục)
                if ($trangthai === 'dang_tam_dung') {
                    $where[] = "t.trangthai = 'dang_tam_dung'";
                    $where[] = "NOT EXISTS (
                        SELECT 1 FROM {$this->table} t2 
                        WHERE t2.hoso = t.hoso 
                        AND t2.ngay_thuchien > t.ngay_thuchien
                    )";
                } else {
                    // Convert legacy values to new values
                    $trangthaiMap = [
                        'tamdung' => 'dang_tam_dung',
                        'tieptuc' => 'da_tiep_tuc',
                        'da_tiep_tuc' => 'da_tiep_tuc'
                    ];
                    $trangthaiValue = $trangthaiMap[$trangthai];
                    $trangthaiEscaped = $this->db->quote($trangthaiValue);
                    $where[] = "t.trangthai = $trangthaiEscaped";
                }
            }
        }
        
        if ($madv) {
            $madvEscaped = $this->db->quote($madv);
            $where[] = "h.madv = $madvEscaped";
        }
        
        if ($fromDate) {
            $fromDateEscaped = $this->db->quote($fromDate);
            $where[] = "t.ngay_thuchien >= $fromDateEscaped";
        }
        
        if ($toDate) {
            $toDateEscaped = $this->db->quote($toDate);
            $where[] = "t.ngay_thuchien <= $toDateEscaped";
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "SELECT t.*, h.phieu, h.mavt, h.somay, d.tendv
                FROM {$this->table} t
                LEFT JOIN hososcbd_iso h ON t.hoso = h.hoso
                LEFT JOIN donvi_iso d ON h.madv = d.madv
                WHERE $whereClause
                ORDER BY t.ngay_thuchien DESC
                LIMIT $limit OFFSET $offset";
        
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Đếm tổng số record trong báo cáo lịch sử
     * @param string $trangthai Trạng thái filter
     * @param string $madv Mã đơn vị
     * @param string $fromDate Từ ngày
     * @param string $toDate Đến ngày
     * @return int Tổng số
     */
    public function countBaoCaoLichSu(
        string $trangthai = '',
        string $madv = '',
        string $fromDate = '',
        string $toDate = ''
    ): int {
        $where = ['1=1'];
        
        // Hỗ trợ cả legacy values và new values
        if ($trangthai) {
            $allowedValues = ['dang_tam_dung', 'da_tiep_tuc', 'tamdung', 'tieptuc'];
            if (in_array($trangthai, $allowedValues)) {
                // "dang_tam_dung" = filter đặc biệt: chỉ lấy hồ sơ đang tạm dừng (chưa tiếp tục)
                if ($trangthai === 'dang_tam_dung') {
                    $where[] = "t.trangthai = 'dang_tam_dung'";
                    $where[] = "NOT EXISTS (
                        SELECT 1 FROM {$this->table} t2 
                        WHERE t2.hoso = t.hoso 
                        AND t2.ngay_thuchien > t.ngay_thuchien
                    )";
                } else {
                    // Convert legacy values to new values
                    $trangthaiMap = [
                        'tamdung' => 'dang_tam_dung',
                        'tieptuc' => 'da_tiep_tuc',
                        'da_tiep_tuc' => 'da_tiep_tuc'
                    ];
                    $trangthaiValue = $trangthaiMap[$trangthai];
                    $trangthaiEscaped = $this->db->quote($trangthaiValue);
                    $where[] = "t.trangthai = $trangthaiEscaped";
                }
            }
        }
        
        if ($madv) {
            $madvEscaped = $this->db->quote($madv);
            $where[] = "h.madv = $madvEscaped";
        }
        
        if ($fromDate) {
            $fromDateEscaped = $this->db->quote($fromDate);
            $where[] = "t.ngay_thuchien >= $fromDateEscaped";
        }
        
        if ($toDate) {
            $toDateEscaped = $this->db->quote($toDate);
            $where[] = "t.ngay_thuchien <= $toDateEscaped";
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "SELECT COUNT(*) as total
                FROM {$this->table} t
                LEFT JOIN hososcbd_iso h ON t.hoso = h.hoso
                WHERE $whereClause";
        
        $stmt = $this->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)($result['total'] ?? 0);
    }

    /**
     * Lấy thống kê tổng quan
     * @return array Thống kê
     */
    public function getThongKe(): array
    {
        try {
            // Đếm số hồ sơ đang tạm dừng
            $sqlTamDung = "SELECT COUNT(DISTINCT h.hoso) as total
                          FROM hososcbd_iso h
                          INNER JOIN {$this->table} t ON h.hoso = t.hoso
                          INNER JOIN (
                              SELECT hoso, MAX(id) as max_id
                              FROM {$this->table}
                              GROUP BY hoso
                          ) latest ON t.hoso = latest.hoso AND t.id = latest.max_id
                          WHERE t.trangthai = 'dang_tam_dung'";
            $stmtTamDung = $this->query($sqlTamDung);
            $tamDung = $stmtTamDung->fetch(PDO::FETCH_ASSOC);
            
            // Đếm tổng số lần tạm dừng
            $sqlTotalTamDung = "SELECT COUNT(*) as total FROM {$this->table} WHERE trangthai = 'dang_tam_dung'";
            $stmtTotal = $this->query($sqlTotalTamDung);
            $totalTamDung = $stmtTotal->fetch(PDO::FETCH_ASSOC);
            
            // Đếm tổng số lần tiếp tục
            $sqlTotalTiepTuc = "SELECT COUNT(*) as total FROM {$this->table} WHERE trangthai = 'da_tiep_tuc'";
            $stmtTiepTuc = $this->query($sqlTotalTiepTuc);
            $totalTiepTuc = $stmtTiepTuc->fetch(PDO::FETCH_ASSOC);
            
            return [
                'dang_tam_dung' => (int)($tamDung['total'] ?? 0),
                'tong_lan_tam_dung' => (int)($totalTamDung['total'] ?? 0),
                'tong_lan_tiep_tuc' => (int)($totalTiepTuc['total'] ?? 0),
            ];
        } catch (PDOException $e) {
            error_log("Error getting thong ke: " . $e->getMessage());
            return [
                'dang_tam_dung' => 0,
                'tong_lan_tam_dung' => 0,
                'tong_lan_tiep_tuc' => 0,
            ];
        }
    }
}
