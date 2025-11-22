<?php
/**
 * API: Lấy danh sách thiết bị đã sửa xong, chưa bàn giao
 * Dùng cho modal chọn thiết bị trong tạo phiếu bàn giao
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../models/HoSoSCBD.php';
require_once __DIR__ . '/../models/DonVi.php';

$model = new HoSoSCBD();
$donViModel = new DonVi();

// Get filters from request
$search = $_GET['search'] ?? '';
$madv = $_GET['madv'] ?? '';
$phieuyc = $_GET['phieuyc'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

// Build where clause
$where = [];
$where[] = "h.ngaykt IS NOT NULL";
$where[] = "h.ngaykt != '0000-00-00'";
$where[] = "h.bg = 0"; // Chưa bàn giao

if ($search) {
    $search_escaped = $model->db->quote("%$search%");
    $where[] = "(h.mavt LIKE $search_escaped OR h.tenvt LIKE $search_escaped OR h.somay LIKE $search_escaped OR h.maql LIKE $search_escaped)";
}

if ($madv) {
    $madv_escaped = $model->db->quote($madv);
    $where[] = "h.madv = $madv_escaped";
}

if ($phieuyc) {
    $phieuyc_escaped = $model->db->quote($phieuyc);
    $where[] = "h.phieu = $phieuyc_escaped";
}

if ($from_date) {
    $from_escaped = $model->db->quote($from_date);
    $where[] = "h.ngaykt >= $from_escaped";
}

if ($to_date) {
    $to_escaped = $model->db->quote($to_date);
    $where[] = "h.ngaykt <= $to_escaped";
}

$whereClause = implode(' AND ', $where);

// Query
$sql = "SELECT 
            h.stt,
            h.maql,
            h.phieu,
            h.mavt,
            h.tenvt,
            h.somay,
            h.madv,
            d.tendv,
            h.ngaykt as ngay_sua_xong,
            h.bg as da_ban_giao,
            h.slbg as so_lan_bg
        FROM hososcbd_iso h
        LEFT JOIN donvi_iso d ON h.madv = d.madv
        WHERE $whereClause
        ORDER BY h.ngaykt DESC, h.phieu DESC, h.mavt ASC
        LIMIT 100";

try {
    $stmt = $model->query($sql);
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group by phieu for summary
    $summary = [];
    foreach ($devices as $device) {
        $phieu = $device['phieu'];
        if (!isset($summary[$phieu])) {
            $summary[$phieu] = [
                'phieu' => $phieu,
                'count' => 0,
                'madv' => $device['madv'],
                'tendv' => $device['tendv']
            ];
        }
        $summary[$phieu]['count']++;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $devices,
        'summary' => array_values($summary),
        'total' => count($devices)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
