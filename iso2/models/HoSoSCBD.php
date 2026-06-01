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
        int $limit = 15,
        string $fromDate = '',
        string $toDate = ''
    ): array {
        $where = ["1=1"];
        
        // Tách chuỗi search thành các từ riêng biệt
        if ($search) {
            $keywords = array_filter(array_map('trim', explode(' ', $search)));
            foreach ($keywords as $keyword) {
                $keywordEscaped = $this->db->quote("%$keyword%");
                $where[] = "(h.maql LIKE $keywordEscaped OR h.phieu LIKE $keywordEscaped OR h.mavt LIKE $keywordEscaped OR h.somay LIKE $keywordEscaped OR h.madv LIKE $keywordEscaped OR d.tendv LIKE $keywordEscaped)";
            }
        }
        
        if ($nhomsc) {
            $nhomscEscaped = $this->db->quote($nhomsc);
            $where[] = "h.nhomsc = $nhomscEscaped";
        }
        
        if ($madv) {
            $madvEscaped = $this->db->quote($madv);
            $where[] = "h.madv = $madvEscaped";
        }
        
        // Lọc theo ngày yêu cầu
        if ($fromDate) {
            $fromDateEscaped = $this->db->quote($fromDate);
            $where[] = "h.ngayyc >= $fromDateEscaped";
        }
        
        if ($toDate) {
            $toDateEscaped = $this->db->quote($toDate);
            $where[] = "h.ngayyc <= $toDateEscaped";
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
        } elseif ($trangthai === 'TTKTDB') { // TTKTDB
            $where[] = "h.ttktafter = 'TTKTDB'";
        } elseif ($trangthai === 'tamdung') { // Tạm dừng
            // Filter sẽ được xử lý sau khi JOIN với bảng hososcbd_tamdung
            $where[] = "td_latest.trangthai = 'dang_tam_dung'";
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Kiểm tra bảng hososcbd_tamdung có tồn tại không
        $hasTamDungTable = false;
        try {
            $checkTable = $this->db->query("SHOW TABLES LIKE 'hososcbd_tamdung'");
            $hasTamDungTable = $checkTable->rowCount() > 0;
        } catch (PDOException $e) {
            $hasTamDungTable = false;
        }
        
        // Nếu bảng hososcbd_tamdung tồn tại, JOIN để lấy trạng thái tạm dừng
        // Nếu không, query đơn giản không có is_tamdung
        if ($hasTamDungTable) {
            $sql = "SELECT h.*, d.tendv, t.stt as thietbi_stt,
                           MAX(thckd.stt) as thckd_stt, MAX(thckd.mavattu) as thckd_mavattu,
                           COUNT(DISTINCT k.id) as bddk_count,
                           GROUP_CONCAT(DISTINCT 
                               CONCAT(
                                   IF((k.qui_1 IS NOT NULL AND k.qui_1 != '') OR k.qui_1_hoantat = 1, CONCAT('Q1:', COALESCE(k.qui_1_hoantat, 0), ','), ''),
                                   IF((k.qui_2 IS NOT NULL AND k.qui_2 != '') OR k.qui_2_hoantat = 1, CONCAT('Q2:', COALESCE(k.qui_2_hoantat, 0), ','), ''),
                                   IF((k.qui_3 IS NOT NULL AND k.qui_3 != '') OR k.qui_3_hoantat = 1, CONCAT('Q3:', COALESCE(k.qui_3_hoantat, 0), ','), ''),
                                   IF((k.qui_4 IS NOT NULL AND k.qui_4 != '') OR k.qui_4_hoantat = 1, CONCAT('Q4:', COALESCE(k.qui_4_hoantat, 0), ','), '')
                               )
                           ) as bddk_quarters_raw,
                           GROUP_CONCAT(DISTINCT kd.thang_thuchien ORDER BY kd.thang_thuchien) as planned_months,
                           GROUP_CONCAT(DISTINCT kd.thang_dot2 ORDER BY kd.thang_dot2) as planned_months_dot2,
                           GROUP_CONCAT(DISTINCT MONTH(hc.ngayhc) ORDER BY hc.ngayhc) as inspected_months,
                           COALESCE(td_latest.trangthai, 'none') as tamdung_status,
                           IF(td_latest.trangthai = 'dang_tam_dung', 1, 0) as is_tamdung
                    FROM {$this->table} h
                    LEFT JOIN donvi_iso d ON h.madv = d.madv
                    LEFT JOIN thietbi_iso t ON h.mavt = t.mavt AND h.somay = t.somay
                    LEFT JOIN thietbihckd_iso thckd ON (
                        (t.mavt = thckd.mavattu AND t.somay = thckd.somay)
                        OR (CONCAT(t.mavt, '-', t.somay) = thckd.mavattu AND t.somay = thckd.somay)
                    )
                    LEFT JOIN ke_hoach_bao_duong_dinh_ky_iso k ON t.stt = k.thietbi_id AND k.nam = YEAR(h.ngayyc)
                    LEFT JOIN kehoach_kiemdinh_2026_iso kd ON thckd.stt = kd.stt AND kd.nam_kehoach = 2026
                    LEFT JOIN hosohckd_iso hc ON (hc.thietbi_stt = thckd.stt
                        OR (hc.thietbi_stt IS NULL AND hc.tenmay = thckd.mavattu))
                        AND YEAR(hc.ngayhc) = 2026
                    LEFT JOIN (
                        SELECT hoso, trangthai
                        FROM hososcbd_tamdung td1
                        WHERE id = (
                            SELECT MAX(id) 
                            FROM hososcbd_tamdung td2 
                            WHERE td2.hoso = td1.hoso
                        )
                        GROUP BY hoso
                    ) td_latest ON h.hoso = td_latest.hoso
                    WHERE $whereClause
                    GROUP BY h.stt
                    ORDER BY h.ngayyc DESC, h.phieu DESC
                    LIMIT $limit OFFSET $offset";
        } else {
            // Fallback: Query đơn giản khi bảng hososcbd_tamdung chưa tồn tại
            $sql = "SELECT h.*, d.tendv, t.stt as thietbi_stt,
                           MAX(thckd.stt) as thckd_stt, MAX(thckd.mavattu) as thckd_mavattu,
                           COUNT(DISTINCT k.id) as bddk_count,
                           GROUP_CONCAT(DISTINCT 
                               CONCAT(
                                   IF((k.qui_1 IS NOT NULL AND k.qui_1 != '') OR k.qui_1_hoantat = 1, CONCAT('Q1:', COALESCE(k.qui_1_hoantat, 0), ','), ''),
                                   IF((k.qui_2 IS NOT NULL AND k.qui_2 != '') OR k.qui_2_hoantat = 1, CONCAT('Q2:', COALESCE(k.qui_2_hoantat, 0), ','), ''),
                                   IF((k.qui_3 IS NOT NULL AND k.qui_3 != '') OR k.qui_3_hoantat = 1, CONCAT('Q3:', COALESCE(k.qui_3_hoantat, 0), ','), ''),
                                   IF((k.qui_4 IS NOT NULL AND k.qui_4 != '') OR k.qui_4_hoantat = 1, CONCAT('Q4:', COALESCE(k.qui_4_hoantat, 0), ','), '')
                               )
                           ) as bddk_quarters_raw,
                           GROUP_CONCAT(DISTINCT kd.thang_thuchien ORDER BY kd.thang_thuchien) as planned_months,
                           GROUP_CONCAT(DISTINCT kd.thang_dot2 ORDER BY kd.thang_dot2) as planned_months_dot2,
                           GROUP_CONCAT(DISTINCT MONTH(hc.ngayhc) ORDER BY hc.ngayhc) as inspected_months,
                           0 as is_tamdung
                    FROM {$this->table} h
                    LEFT JOIN donvi_iso d ON h.madv = d.madv
                    LEFT JOIN thietbi_iso t ON h.mavt = t.mavt AND h.somay = t.somay
                    LEFT JOIN thietbihckd_iso thckd ON (
                        (t.mavt = thckd.mavattu AND t.somay = thckd.somay)
                        OR (CONCAT(t.mavt, '-', t.somay) = thckd.mavattu AND t.somay = thckd.somay)
                    )
                    LEFT JOIN ke_hoach_bao_duong_dinh_ky_iso k ON t.stt = k.thietbi_id AND k.nam = YEAR(h.ngayyc)
                    LEFT JOIN kehoach_kiemdinh_2026_iso kd ON thckd.stt = kd.stt AND kd.nam_kehoach = 2026
                    LEFT JOIN hosohckd_iso hc ON (hc.thietbi_stt = thckd.stt
                        OR (hc.thietbi_stt IS NULL AND hc.tenmay = thckd.mavattu))
                        AND YEAR(hc.ngayhc) = 2026
                    WHERE $whereClause
                    GROUP BY h.stt
                    ORDER BY h.ngayyc DESC, h.phieu DESC
                    LIMIT $limit OFFSET $offset";
        }
        
        $stmt = $this->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process quarters with completion status
        foreach ($results as &$row) {
            
            if (!empty($row['bddk_quarters_raw'])) {
                $quarters = array_unique(explode(',', trim($row['bddk_quarters_raw'], ',')));
                $quarters = array_filter($quarters);
                sort($quarters);
                
                $quarterData = [];
                foreach ($quarters as $q) {
                    if (strpos($q, ':') !== false) {
                        list($quarter, $status) = explode(':', $q);
                        $quarterData[] = ['quarter' => $quarter, 'completed' => (int)$status === 1];
                    }
                }
                $row['bddk_quarters'] = $quarterData;
            } else {
                $row['bddk_quarters'] = [];
            }
        }
        
        return $results;
    }
    
    /**
     * Đếm tổng số hồ sơ
     */
    public function countList(
        string $search = '',
        string $nhomsc = '',
        string $trangthai = '',
        string $madv = '',
        string $fromDate = '',
        string $toDate = ''
    ): int {
        $where = ["1=1"];
        
        // Tách chuỗi search thành các từ riêng biệt
        if ($search) {
            $keywords = array_filter(array_map('trim', explode(' ', $search)));
            foreach ($keywords as $keyword) {
                $keywordEscaped = $this->db->quote("%$keyword%");
                $where[] = "(h.maql LIKE $keywordEscaped OR h.phieu LIKE $keywordEscaped OR h.mavt LIKE $keywordEscaped OR h.somay LIKE $keywordEscaped OR h.madv LIKE $keywordEscaped OR d.tendv LIKE $keywordEscaped)";
            }
        }
        
        if ($nhomsc) {
            $nhomscEscaped = $this->db->quote($nhomsc);
            $where[] = "h.nhomsc = $nhomscEscaped";
        }
        
        if ($madv) {
            $madvEscaped = $this->db->quote($madv);
            $where[] = "h.madv = $madvEscaped";
        }
        
        // Lọc theo ngày yêu cầu
        if ($fromDate) {
            $fromDateEscaped = $this->db->quote($fromDate);
            $where[] = "h.ngayyc >= $fromDateEscaped";
        }
        
        if ($toDate) {
            $toDateEscaped = $this->db->quote($toDate);
            $where[] = "h.ngayyc <= $toDateEscaped";
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
        } elseif ($trangthai === 'TTKTDB') {
            $where[] = "h.ttktafter = 'TTKTDB'";
        } elseif ($trangthai === 'tamdung') {
            $where[] = "td_latest.trangthai = 'dang_tam_dung'";
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Nếu filter tạm dừng, cần JOIN với bảng hososcbd_tamdung
        if ($trangthai === 'tamdung') {
            $sql = "SELECT COUNT(*) 
                    FROM {$this->table} h
                    LEFT JOIN donvi_iso d ON h.madv = d.madv
                    LEFT JOIN (
                        SELECT hoso, trangthai
                        FROM hososcbd_tamdung td1
                        WHERE id = (
                            SELECT MAX(id) 
                            FROM hososcbd_tamdung td2 
                            WHERE td2.hoso = td1.hoso
                        )
                        GROUP BY hoso
                    ) td_latest ON h.hoso = td_latest.hoso
                    WHERE $whereClause";
        } else {
            $sql = "SELECT COUNT(*) 
                    FROM {$this->table} h
                    LEFT JOIN donvi_iso d ON h.madv = d.madv
                    WHERE $whereClause";
        }
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
     * Lấy index lớn nhất của hồ sơ trong một phiếu
     * Dùng để tính số thứ tự tiếp theo khi thêm thiết bị vào phiếu có sẵn
     * 
     * @param string $phieu Số phiếu
     * @return int Index lớn nhất (0 nếu chưa có thiết bị nào, bắt đầu từ 1)
     */
    public function getMaxHosoIndexForPhieu(string $phieu): int
    {
        $phieuEscaped = $this->db->quote($phieu);
        
        // Query hồ sơ có format: PHIEU-INDEX (ví dụ: 1997-1, 1997-2, 1997-3)
        // Extract index từ hoso bằng cách split '-' và lấy phần sau
        $sql = "SELECT hoso 
                FROM {$this->table}
                WHERE phieu = $phieuEscaped 
                AND hoso LIKE CONCAT($phieuEscaped, '-%')
                ORDER BY CAST(SUBSTRING_INDEX(hoso, '-', -1) AS UNSIGNED) DESC 
                LIMIT 1";
        
        $stmt = $this->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && !empty($result['hoso'])) {
            // Extract index from "1997-2" -> 2
            $parts = explode('-', $result['hoso']);
            if (count($parts) >= 2) {
                return (int)end($parts);
            }
        }
        
        return 0; // 0 nghĩa là chưa có thiết bị nào, index đầu tiên sẽ là 1
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
        
        // Kiểm tra bảng hososcbd_tamdung có tồn tại không
        $hasTamDungTable = false;
        try {
            $checkTable = $this->db->query("SHOW TABLES LIKE 'hososcbd_tamdung'");
            $hasTamDungTable = $checkTable->rowCount() > 0;
        } catch (PDOException $e) {
            $hasTamDungTable = false;
        }
        
        if ($hasTamDungTable) {
            $sql = "SELECT h.*, 
                           COALESCE(t.tenvt, h.mavt) as tenvt,
                           d.tendv,
                           COALESCE(td_latest.trangthai, 'none') as tamdung_status,
                           IF(td_latest.trangthai = 'dang_tam_dung', 1, 0) as is_tamdung
                    FROM {$this->table} h
                    LEFT JOIN thietbi_iso t ON h.mavt = t.mavt AND h.somay = t.somay
                    LEFT JOIN donvi_iso d ON h.madv = d.madv
                    LEFT JOIN (
                        SELECT hoso, trangthai
                        FROM hososcbd_tamdung td1
                        WHERE id = (
                            SELECT MAX(id) 
                            FROM hososcbd_tamdung td2 
                            WHERE td2.hoso = td1.hoso
                        )
                        GROUP BY hoso
                    ) td_latest ON h.hoso = td_latest.hoso
                    WHERE h.phieu = $phieuEscaped 
                      AND h.bg = 0
                      AND h.ngaykt IS NOT NULL 
                      AND h.ngaykt != '0000-00-00'
                    ORDER BY h.maql";
        } else {
            $sql = "SELECT h.*, 
                           COALESCE(t.tenvt, h.mavt) as tenvt,
                           d.tendv,
                           0 as is_tamdung
                    FROM {$this->table} h
                    LEFT JOIN thietbi_iso t ON h.mavt = t.mavt AND h.somay = t.somay
                    LEFT JOIN donvi_iso d ON h.madv = d.madv
                    WHERE h.phieu = $phieuEscaped 
                      AND h.bg = 0
                      AND h.ngaykt IS NOT NULL 
                      AND h.ngaykt != '0000-00-00'
                    ORDER BY h.maql";
        }
        
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
        // Kiểm tra bảng hososcbd_tamdung có tồn tại không
        $hasTamDungTable = false;
        try {
            $checkTable = $this->db->query("SHOW TABLES LIKE 'hososcbd_tamdung'");
            $hasTamDungTable = $checkTable->rowCount() > 0;
        } catch (PDOException $e) {
            $hasTamDungTable = false;
        }
        
        if ($hasTamDungTable) {
            $sql = "SELECT h.*, 
                           COALESCE(t.tenvt, h.mavt) as tenvt,
                           d.tendv,
                           COALESCE(td_latest.trangthai, 'none') as tamdung_status,
                           IF(td_latest.trangthai = 'dang_tam_dung', 1, 0) as is_tamdung
                    FROM {$this->table} h
                    LEFT JOIN thietbi_iso t ON h.mavt = t.mavt AND h.somay = t.somay
                    LEFT JOIN donvi_iso d ON h.madv = d.madv
                    LEFT JOIN (
                        SELECT hoso, trangthai
                        FROM hososcbd_tamdung td1
                        WHERE id = (
                            SELECT MAX(id) 
                            FROM hososcbd_tamdung td2 
                            WHERE td2.hoso = td1.hoso
                        )
                        GROUP BY hoso
                    ) td_latest ON h.hoso = td_latest.hoso
                    WHERE h.stt = :stt";
        } else {
            $sql = "SELECT h.*, 
                           COALESCE(t.tenvt, h.mavt) as tenvt,
                           d.tendv,
                           0 as is_tamdung
                    FROM {$this->table} h
                    LEFT JOIN thietbi_iso t ON h.mavt = t.mavt AND h.somay = t.somay
                    LEFT JOIN donvi_iso d ON h.madv = d.madv
                    WHERE h.stt = :stt";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':stt' => $stt]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách hồ sơ đơn giản để dùng trong dropdown (chọn hồ sơ khi tạo công việc)
     */
    public function getListForSelect(int $limit = 200): array
    {
        $sql = "SELECT h.stt, h.maql, h.phieu, h.mavt, h.somay,
                       COALESCE(t.tenvt, h.mavt) as tenvt,
                       d.tendv
                FROM {$this->table} h
                LEFT JOIN thietbi_iso t ON h.mavt = t.mavt AND h.somay = t.somay
                LEFT JOIN donvi_iso d ON h.madv = d.madv
                WHERE (h.ngaykt IS NULL OR h.ngaykt = '0000-00-00')
                ORDER BY h.ngayyc DESC, h.stt DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
