<?php
/**
 * TEST VIEW RENDER - Render view với mock data
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/constants.php';

// Mock data giống controller
$db = new PDO(
    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Get real data
$sql = "SELECT 
            gn.*,
            dv_giao.tendv as ten_donvi_giao,
            dv_nhan.tendv as ten_donvi_nhan,
            COUNT(ct.id) as so_thietbi
        FROM giao_nhan_thietbi_iso gn
        LEFT JOIN donvi_iso dv_giao ON gn.donvi_giao = dv_giao.madv
        LEFT JOIN donvi_iso dv_nhan ON gn.donvi_nhan = dv_nhan.madv
        LEFT JOIN giao_nhan_thietbi_chitiet ct ON gn.id = ct.phieu_id
        WHERE 1=1
        GROUP BY gn.id 
        ORDER BY gn.created_at DESC";

$stmt = $db->query($sql);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtDonVi = $db->query("SELECT madv, tendv FROM donvi_iso ORDER BY tendv");
$donviList = $stmtDonVi->fetchAll(PDO::FETCH_ASSOC);

// Set filter variables
$search = '';
$trangthai = '';
$donvi = '';
$tu_ngay = '';
$den_ngay = '';

echo "<!-- DEBUG: About to render view with " . count($records) . " records -->\n";
echo "<!-- Records: " . json_encode(array_column($records, 'id')) . " -->\n";

// Render view
try {
    require __DIR__ . '/views/giaonhanthietbi/index.php';
    echo "\n<!-- DEBUG: View rendered successfully -->\n";
} catch (Throwable $e) {
    echo "\n<!-- ERROR rendering view: " . htmlspecialchars($e->getMessage()) . " -->\n";
    echo "<!-- Trace: " . htmlspecialchars($e->getTraceAsString()) . " -->\n";
    echo "<div style='background: red; color: white; padding: 20px; margin: 20px;'>";
    echo "<h2>VIEW RENDER ERROR</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}
