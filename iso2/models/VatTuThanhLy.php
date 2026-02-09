<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class VatTuThanhLy extends BaseModel
{
    public function __construct()
    {
        parent::__construct('vattu_thanh_ly_iso');
        $this->primaryKey = 'stt';
    }
    
    /**
     * Lấy tất cả vật tư với thông tin tổng hợp
     */
    public function getAllWithStats(string $where = '', array $params = [], int $limit = 0, int $offset = 0): array
    {
        $sql = "SELECT 
                    v.*,
                    pl.ten_phanloai,
                    pl.mau_sac as phanloai_mau_sac,
                    COUNT(DISTINCT s.id) as so_lan_sudung,
                    SUM(CASE WHEN s.trangthai = 'dangdung' THEN s.soluong ELSE 0 END) as soluong_dangdung,
                    v.soluong_conlai * COALESCE(v.dongia, 0) as tong_tien
                FROM {$this->table} v
                LEFT JOIN phanloai_vattu_thanh_ly_iso pl ON v.phanloai_id = pl.id
                LEFT JOIN vattu_thanh_ly_sudung_iso s ON v.stt = s.vattu_stt
                " . ($where ? $where : "") . "
                GROUP BY v.stt
                ORDER BY v.stt DESC";
        
        if ($limit > 0) {
            $sql .= " LIMIT $limit";
            if ($offset > 0) {
                $sql .= " OFFSET $offset";
            }
        }
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Override count để hỗ trợ alias v trong WHERE clause
     */
    public function count(string $where = '', array $params = []): int
    {
        $sql = "SELECT COUNT(DISTINCT v.stt) 
                FROM {$this->table} v
                LEFT JOIN phanloai_vattu_thanh_ly_iso pl ON v.phanloai_id = pl.id";
        
        if ($where) {
            $sql .= " $where";
        }
        
        $stmt = $this->query($sql, $params);
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Lấy chi tiết sử dụng của một vật tư
     */
    public function getChiTietSuDung(int $vattuStt): array
    {
        $sql = "SELECT * FROM vattu_thanh_ly_sudung_iso 
                WHERE vattu_stt = :vattu_stt 
                ORDER BY ngaysd_nhan DESC";
        $stmt = $this->query($sql, [':vattu_stt' => $vattuStt]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Thêm chi tiết sử dụng
     */
    public function addChiTietSuDung(array $data): bool
    {
        $sql = "INSERT INTO vattu_thanh_ly_sudung_iso 
                (vattu_stt, nguoisudung, ngaysd_nhan, soluong, bophan, mucdich_sudung, trangthai, ghichu)
                VALUES 
                (:vattu_stt, :nguoisudung, :ngaysd_nhan, :soluong, :bophan, :mucdich_sudung, :trangthai, :ghichu)";
        
        try {
            $this->query($sql, [
                ':vattu_stt' => $data['vattu_stt'],
                ':nguoisudung' => $data['nguoisudung'] ?? null,
                ':ngaysd_nhan' => $data['ngaysd_nhan'] ?? null,
                ':soluong' => $data['soluong'] ?? 0,
                ':bophan' => $data['bophan'] ?? null,
                ':mucdich_sudung' => $data['mucdich_sudung'] ?? null,
                ':trangthai' => $data['trangthai'] ?? 'dangdung',
                ':ghichu' => $data['ghichu'] ?? null
            ]);
            
            // Trừ số lượng thanh lý từ số lượng còn lại của master
            $this->deductSoLuongConLai((int)$data['vattu_stt'], floatval($data['soluong'] ?? 0));
            
            return true;
        } catch (PDOException $e) {
            error_log("Error adding chi tiet su dung: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cập nhật chi tiết sử dụng (không cho phép sửa số lượng)
     */
    public function updateChiTietSuDung(int $id, array $data): bool
    {
        $sql = "UPDATE vattu_thanh_ly_sudung_iso SET
                nguoisudung = :nguoisudung,
                ngaysd_nhan = :ngaysd_nhan,
                bophan = :bophan,
                mucdich_sudung = :mucdich_sudung,
                ghichu = :ghichu
                WHERE id = :id";
        
        try {
            $this->query($sql, [
                ':id' => $id,
                ':nguoisudung' => $data['nguoisudung'] ?? null,
                ':ngaysd_nhan' => $data['ngaysd_nhan'] ?? null,
                ':bophan' => $data['bophan'] ?? null,
                ':mucdich_sudung' => $data['mucdich_sudung'] ?? null,
                ':ghichu' => $data['ghichu'] ?? null
            ]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Error updating chi tiet su dung: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Xóa chi tiết sử dụng
     */
    public function deleteChiTietSuDung(int $id): bool
    {
        // Lấy thông tin trước khi xóa
        $detail = $this->getChiTietById($id);
        
        if (!$detail) {
            return false;
        }
        
        $sql = "DELETE FROM vattu_thanh_ly_sudung_iso WHERE id = :id";
        
        try {
            $this->query($sql, [':id' => $id]);
            
            // Cộng lại số lượng đã thanh lý vào số lượng còn lại
            $this->addBackSoLuongConLai((int)$detail['vattu_stt'], floatval($detail['soluong'] ?? 0));
            
            return true;
        } catch (PDOException $e) {
            error_log("Error deleting chi tiet su dung: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lấy chi tiết theo ID
     */
    public function getChiTietById(int $id): ?array
    {
        $sql = "SELECT * FROM vattu_thanh_ly_sudung_iso WHERE id = :id";
        $stmt = $this->query($sql, [':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    /**
     * Trừ số lượng thanh lý từ số lượng còn lại
     */
    private function deductSoLuongConLai(int $vattuStt, float $soluongThanhLy): void
    {
        $sql = "UPDATE vattu_thanh_ly_iso 
                SET soluong_conlai = soluong_conlai - :soluong_thanhly
                WHERE stt = :vattu_stt";
        
        try {
            $this->query($sql, [
                ':soluong_thanhly' => $soluongThanhLy,
                ':vattu_stt' => $vattuStt
            ]);
        } catch (PDOException $e) {
            error_log("Error deducting soluong_conlai: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Cộng lại số lượng khi xóa chi tiết thanh lý
     */
    private function addBackSoLuongConLai(int $vattuStt, float $soluongThanhLy): void
    {
        $sql = "UPDATE vattu_thanh_ly_iso 
                SET soluong_conlai = soluong_conlai + :soluong_thanhly
                WHERE stt = :vattu_stt";
        
        try {
            $this->query($sql, [
                ':soluong_thanhly' => $soluongThanhLy,
                ':vattu_stt' => $vattuStt
            ]);
        } catch (PDOException $e) {
            error_log("Error adding back soluong_conlai: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Lấy lịch sử thay đổi của vật tư
     */
    public function getLichSuThayDoi(int $vattuStt): array
    {
        $sql = "SELECT * FROM vattu_thanh_ly_lichsu_iso 
                WHERE vattu_stt = :vattu_stt 
                ORDER BY ngay_thuchien DESC";
        $stmt = $this->query($sql, [':vattu_stt' => $vattuStt]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
