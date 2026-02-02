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
                    COUNT(DISTINCT s.id) as so_lan_sudung,
                    SUM(CASE WHEN s.trangthai = 'dangdung' THEN s.soluong ELSE 0 END) as soluong_dangdung,
                    v.soluong_conlai * COALESCE(v.dongia, 0) as tong_tien
                FROM {$this->table} v
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
            
            // Cập nhật số lượng còn lại
            $this->updateSoLuongConLai((int)$data['vattu_stt']);
            
            return true;
        } catch (PDOException $e) {
            error_log("Error adding chi tiet su dung: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cập nhật chi tiết sử dụng
     */
    public function updateChiTietSuDung(int $id, array $data): bool
    {
        $sql = "UPDATE vattu_thanh_ly_sudung_iso SET
                nguoisudung = :nguoisudung,
                ngaysd_nhan = :ngaysd_nhan,
                soluong = :soluong,
                bophan = :bophan,
                mucdich_sudung = :mucdich_sudung,
                trangthai = :trangthai,
                ngayhoanthanh = :ngayhoanthanh,
                ghichu = :ghichu
                WHERE id = :id";
        
        try {
            $this->query($sql, [
                ':id' => $id,
                ':nguoisudung' => $data['nguoisudung'] ?? null,
                ':ngaysd_nhan' => $data['ngaysd_nhan'] ?? null,
                ':soluong' => $data['soluong'] ?? 0,
                ':bophan' => $data['bophan'] ?? null,
                ':mucdich_sudung' => $data['mucdich_sudung'] ?? null,
                ':trangthai' => $data['trangthai'] ?? 'dangdung',
                ':ngayhoanthanh' => $data['ngayhoanthanh'] ?? null,
                ':ghichu' => $data['ghichu'] ?? null
            ]);
            
            // Lấy vattu_stt và cập nhật số lượng
            $detail = $this->getChiTietById($id);
            if ($detail) {
                $this->updateSoLuongConLai((int)$detail['vattu_stt']);
            }
            
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
        
        $sql = "DELETE FROM vattu_thanh_ly_sudung_iso WHERE id = :id";
        
        try {
            $this->query($sql, [':id' => $id]);
            
            // Cập nhật số lượng còn lại
            if ($detail) {
                $this->updateSoLuongConLai((int)$detail['vattu_stt']);
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Error deleting chi tiet su dung: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lấy chi tiết theo ID
     */
    private function getChiTietById(int $id): ?array
    {
        $sql = "SELECT * FROM vattu_thanh_ly_sudung_iso WHERE id = :id";
        $stmt = $this->query($sql, [':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    /**
     * Cập nhật số lượng còn lại dựa trên chi tiết sử dụng
     */
    private function updateSoLuongConLai(int $vattuStt): void
    {
        // Tính tổng số lượng đang sử dụng
        $sql = "SELECT SUM(soluong) as total_sudung 
                FROM vattu_thanh_ly_sudung_iso 
                WHERE vattu_stt = :vattu_stt AND trangthai = 'dangdung'";
        $stmt = $this->query($sql, [':vattu_stt' => $vattuStt]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Note: Không tự động cập nhật soluong_conlai vì có thể phức tạp
        // Có thể cần logic riêng tùy theo yêu cầu nghiệp vụ
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
