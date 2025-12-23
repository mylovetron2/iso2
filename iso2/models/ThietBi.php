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
     * Lấy lịch sử sửa chữa theo thiết bị ID (mamay)
     */
    public function getLichSuSuaChua(string $mamay): array
    {
        if (empty($mamay)) {
            return [];
        }
        
        $mamayEscaped = $this->db->quote($mamay);
        $sql = "SELECT ngaykt, honghoc, khacphuc, noidung 
                FROM view_lich_su_bao_duong_iso 
                WHERE mamay = $mamayEscaped 
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
}
