<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

$db = getDBConnection();
$nam = 2025;

echo "<h2>Kiểm tra kế hoạch và tô màu - Năm {$nam}</h2>";

$sql = "
    SELECT 
        k.mahieu,
        k.somay,
        k.tenthietbi,
        k.thang as thang_ke_hoach,
        h.ngayhc,
        MONTH(h.ngayhc) as thang_kiem_dinh
    FROM kehoach_iso k
    LEFT JOIN hosohckd_iso h ON k.mahieu = h.tenmay 
        AND YEAR(h.ngayhc) = ?
    WHERE k.namkh = ?
    ORDER BY k.tenthietbi, k.somay, k.thang
    LIMIT 20
";

$stmt = $db->prepare($sql);
$stmt->execute([$nam, $nam]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>";
echo "<tr>";
echo "<th>Tên thiết bị</th>";
echo "<th>Số máy</th>";
echo "<th>Mã hiệu</th>";
echo "<th>Tháng KH</th>";
echo "<th>3 tháng tô xám</th>";
echo "<th>Ngày kiểm định</th>";
echo "<th>Tháng KD</th>";
echo "<th>Màu ô</th>";
echo "</tr>";

foreach ($results as $row) {
    echo "<tr>";
    echo "<td>{$row['tenthietbi']}</td>";
    echo "<td>{$row['somay']}</td>";
    echo "<td>{$row['mahieu']}</td>";
    echo "<td style='background-color: yellow; font-weight: bold;'>{$row['thang_ke_hoach']}</td>";
    
    // Calculate 3 consecutive months ending at thangKH
    $thangKH = (int)$row['thang_ke_hoach'];
    $months = [];
    for ($i = -2; $i <= 0; $i++) {
        $m = $thangKH + $i;
        if ($m >= 1 && $m <= 12) {
            $months[] = $m;
        }
    }
    echo "<td style='background-color: lightgray;'>" . implode(', ', $months) . "</td>";
    
    echo "<td>" . ($row['ngayhc'] ?? '-') . "</td>";
    echo "<td>" . ($row['thang_kiem_dinh'] ?? '-') . "</td>";
    
    // Determine color for each month in range
    $colors = [];
    foreach ($months as $m) {
        if (!empty($row['thang_kiem_dinh']) && $m == $row['thang_kiem_dinh']) {
            $colors[] = "T{$m}(xanh)";
        } else {
            $colors[] = "T{$m}(xám)";
        }
    }
    echo "<td>" . implode(', ', $colors) . "</td>";
    
    echo "</tr>";
}

echo "</table>";

echo "<h3>Chú thích:</h3>";
echo "<ul>";
echo "<li><strong>Tháng KH</strong>: Tháng đầu tiên trong kế hoạch (từ bảng kehoach_iso)</li>";
echo "<li><strong>3 tháng tô xám</strong>: 3 tháng liên tiếp kể từ tháng KH</li>";
echo "<li><strong>Tháng KD</strong>: Tháng thực tế kiểm định (từ bảng hosohckd_iso)</li>";
echo "<li><strong>Màu ô</strong>: Xanh = đã kiểm định, Xám = có kế hoạch chưa KD</li>";
echo "</ul>";

echo "<h3>Ví dụ:</h3>";
echo "<p>Nếu <strong>Tháng KH = 3</strong> → Tô xám tháng 3, 4, 5</p>";
echo "<p>Nếu đã kiểm định tháng 4 → T3(xám), T4(xanh), T5(xám)</p>";
