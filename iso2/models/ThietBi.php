<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

/**
 * Model: ThietBi (Thiết Bị)
 * Quản lý danh mục thiết bị
 */
class ThietBi extends BaseModel
{
    public function __construct()
    {
        parent::__construct('thietbi_iso');
        $this->primaryKey = 'stt';
    }
    
    /**
     * Lấy danh sách thiết bị theo đơn vị
     */
    public function getByDonVi(string $madv): array
    {
        $madvEscaped = $this->db->quote($madv);
        $sql = "SELECT * FROM {$this->table} WHERE madv = $madvEscaped ORDER BY mavt ASC";
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Tìm thiết bị theo mã và số máy
     */
    public function findByMaVtAndSoMay(string $mavt, string $somay, string $model = ''): array|false
    {
        $mavtEscaped = $this->db->quote($mavt);
        $somayEscaped = $this->db->quote($somay);
        $modelEscaped = $this->db->quote($model);
        
        $sql = "SELECT * FROM {$this->table} WHERE mavt = $mavtEscaped AND somay = $somayEscaped AND model = $modelEscaped";
        $stmt = $this->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lấy danh sách số máy theo mã thiết bị
     */
    public function getSoMayByMaVt(string $mavt, string $model = ''): array
    {
        $mavtEscaped = $this->db->quote($mavt);
        $modelEscaped = $this->db->quote($model);
        
        $sql = "SELECT DISTINCT somay FROM {$this->table} WHERE mavt = $mavtEscaped AND model = $modelEscaped ORDER BY somay";
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Lấy lịch sử sửa chữa theo thiết bị (giới hạn 5 bản ghi)
     */
    public function getLichSuSuaChua(string $mavt, string $somay, string $model = ''): array
    {
        if (empty($mavt) || empty($somay)) {
            return [];
        }
        
        $mavtEscaped = $this->db->quote($mavt);
        $somayEscaped = $this->db->quote($somay);
        $modelEscaped = $this->db->quote($model);
        
        $sql = "SELECT stt, ngaykt, honghoc, khacphuc, noidung 
                FROM hososcbd_iso 
                WHERE mavt = $mavtEscaped AND somay = $somayEscaped AND model = $modelEscaped 
                ORDER BY ngaykt DESC 
                LIMIT 5";
        
        try {
            $stmt = $this->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching repair history: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Lấy toàn bộ lịch sử sửa chữa/bảo dưỡng (không giới hạn)
     */
    public function getAllLichSuSuaChua(string $mavt, string $somay, string $model = ''): array
    {
        if (empty($mavt) || empty($somay)) {
            return [];
        }
        
        $mavtEscaped = $this->db->quote($mavt);
        $somayEscaped = $this->db->quote($somay);
        $modelEscaped = $this->db->quote($model);
        
        $sql = "SELECT stt, hoso, phieu, ngaykt, honghoc, khacphuc, noidung 
                FROM hososcbd_iso 
                WHERE mavt = $mavtEscaped AND somay = $somayEscaped AND model = $modelEscaped 
                ORDER BY ngaykt DESC";
        
        try {
            $stmt = $this->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching all repair history: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Lấy lịch sử kiểm định theo mavt, somay và model
     */
    public function getLichSuKiemDinh(string $mavt, string $somay, string $model = ''): array
    {
        if (empty($mavt) || empty($somay)) {
            return [];
        }
        
        $mavtEscaped = $this->db->quote($mavt);
        $somayEscaped = $this->db->quote($somay);
        $modelEscaped = $this->db->quote($model);
        
        $sql = "SELECT stt, ngaykt, honghoc, khacphuc, noidung, phieu, hoso 
                FROM hososcbd_iso 
                WHERE mavt = $mavtEscaped AND somay = $somayEscaped AND model = $modelEscaped 
                ORDER BY ngaykt DESC";
        
        try {
            $stmt = $this->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching inspection history: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Lấy lịch sử bàn giao thiết bị
     */
    public function getLichSuBanGiao(string $mavt, string $somay): array
    {
        if (empty($mavt) || empty($somay)) {
            return [];
        }
        
        $mavtEscaped = $this->db->quote($mavt);
        $somayEscaped = $this->db->quote($somay);
        
        $sql = "SELECT pb.sophieu, pb.ngaybangiao, pb.nguoigiao, pb.nguoinhan, 
                       pb.noidung, pb.ghichu, dv.tendv as donvi_nhan
                FROM phieubangiao pb
                LEFT JOIN donvi_iso dv ON pb.madv_nhan = dv.madv
                WHERE pb.mavt = $mavtEscaped AND pb.somay = $somayEscaped 
                ORDER BY pb.ngaybangiao DESC";
        
        try {
            $stmt = $this->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching handover history: " . $e->getMessage());
            return [];
        }
    }
}
