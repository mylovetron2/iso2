<?php
require_once __DIR__ . '/config/database.php';

$db = getDBConnection();

echo "<h2>1. Kiểm tra bảng donvi_iso (các đơn vị)</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>madv</th><th>tendv</th></tr>";

$stmt = $db->query("SELECT madv, tendv FROM donvi_iso ORDER BY tendv");
$donvis = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($donvis as $dv) {
    $highlight = '';
    if (strpos($dv['tendv'], 'Carota') !== false || 
        strpos($dv['tendv'], 'công nghệ cao') !== false ||
        strpos($dv['tendv'], 'ĐVL') !== false) {
        $highlight = ' style="background-color: yellow;"';
    }
    echo "<tr{$highlight}><td>{$dv['madv']}</td><td>{$dv['tendv']}</td></tr>";
}
echo "</table>";

echo "<h2>2. Kiểm tra dữ liệu bophan trong vattu_thanh_ly_sudung_iso</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>bophan (giá trị)</th><th>Số lượng record</th></tr>";

$stmt = $db->query("
    SELECT bophan, COUNT(*) as count 
    FROM vattu_thanh_ly_sudung_iso 
    GROUP BY bophan 
    ORDER BY count DESC
");
$bophans = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($bophans as $bp) {
    echo "<tr><td>" . ($bp['bophan'] ?: '(NULL)') . "</td><td>{$bp['count']}</td></tr>";
}
echo "</table>";

echo "<h2>3. Thử query với bophan IN ('TH', 'CNC')</h2>";
$stmt = $db->query("
    SELECT s.*, v.mavattu, v.ten_tiengviet
    FROM vattu_thanh_ly_sudung_iso s
    LEFT JOIN vattu_thanh_ly_iso v ON s.vattu_stt = v.stt
    WHERE s.bophan IN ('TH', 'CNC')
    LIMIT 5
");
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p><strong>Số kết quả:</strong> " . count($results) . "</p>";

if (!empty($results)) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>bophan</th><th>mavattu</th><th>ten_tiengviet</th></tr>";
    foreach ($results as $r) {
        echo "<tr>";
        echo "<td>{$r['id']}</td>";
        echo "<td>{$r['bophan']}</td>";
        echo "<td>{$r['mavattu']}</td>";
        echo "<td>{$r['ten_tiengviet']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ Không có dữ liệu với bophan IN ('TH', 'CNC')</p>";
}

echo "<h2>4. Sample data - 10 records đầu tiên</h2>";
$stmt = $db->query("
    SELECT s.id, s.bophan, s.nguoisudung, v.mavattu, v.ten_tiengviet
    FROM vattu_thanh_ly_sudung_iso s
    LEFT JOIN vattu_thanh_ly_iso v ON s.vattu_stt = v.stt
    ORDER BY s.id DESC
    LIMIT 10
");
$samples = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>bophan</th><th>nguoisudung</th><th>mavattu</th><th>ten_tiengviet</th></tr>";
foreach ($samples as $s) {
    echo "<tr>";
    echo "<td>{$s['id']}</td>";
    echo "<td>" . ($s['bophan'] ?: '(NULL)') . "</td>";
    echo "<td>{$s['nguoisudung']}</td>";
    echo "<td>{$s['mavattu']}</td>";
    echo "<td>{$s['ten_tiengviet']}</td>";
    echo "</tr>";
}
echo "</table>";
