<?php
require_once 'config/database.php';

$db = getDBConnection();

// Đếm tổng số thiết bị
$count = $db->query('SELECT COUNT(*) as total FROM thietbihckd_iso')->fetch(PDO::FETCH_ASSOC);
echo "Tổng số thiết bị trong thietbihckd_iso: " . $count['total'] . "\n\n";

// Lấy 10 thiết bị đầu tiên
echo "10 thiết bị đầu tiên:\n";
$result = $db->query('SELECT stt, mavattu, tenthietbi, loaitb, bophansh FROM thietbihckd_iso LIMIT 10')->fetchAll(PDO::FETCH_ASSOC);
foreach($result as $row) {
    echo "STT: " . $row['stt'] . " - " . $row['tenthietbi'] . " (Loại: " . ($row['loaitb'] ?? 'N/A') . ")\n";
}

echo "\n\nTest query từ code:\n";
$where = ['1=1'];
$params = [];
$whereClause = implode(' AND ', $where);

$sql = "SELECT t.*, 
        GROUP_CONCAT(DISTINCT k.thang_thuchien ORDER BY k.thang_thuchien) as planned_months
        FROM thietbihckd_iso t
        LEFT JOIN kehoach_kiemdinh_2026_iso k ON t.stt = k.stt AND k.nam_kehoach = 2026
        WHERE $whereClause
        GROUP BY t.stt
        ORDER BY t.loaitb, t.tenthietbi
        LIMIT 20";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$thietbiList = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Số thiết bị trả về từ query: " . count($thietbiList) . "\n";
foreach($thietbiList as $tb) {
    echo "STT: " . $tb['stt'] . " - " . $tb['tenthietbi'] . " - Planned months: " . ($tb['planned_months'] ?? 'none') . "\n";
}
