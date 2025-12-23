<?php
/**
 * Test script để kiểm tra logic filter thiết bị HC/KĐ
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🧪 Test Logic Filter Thiết Bị HC/KĐ</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    table { border-collapse: collapse; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
    th { background: #4CAF50; color: white; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
</style>";

// Test cases
$today = new DateTime('2025-12-19');
echo "<p><strong>Hôm nay:</strong> " . $today->format('d/m/Y') . "</p>";

$testCases = [
    [
        'name' => 'Thiết bị sắp hết hạn (20 ngày)',
        'ngayhc' => '2024-12-01',
        'thoihankd' => 13,
        'expected' => 'saphethan'
    ],
    [
        'name' => 'Thiết bị sắp hết hạn (30 ngày)',
        'ngayhc' => '2024-12-20',
        'thoihankd' => 12,
        'expected' => 'saphethan'
    ],
    [
        'name' => 'Thiết bị sắp hết hạn (1 ngày)',
        'ngayhc' => '2024-12-19',
        'thoihankd' => 12,
        'expected' => 'saphethan'
    ],
    [
        'name' => 'Thiết bị đã hết hạn (1 ngày)',
        'ngayhc' => '2024-11-18',
        'thoihankd' => 12,
        'expected' => 'dahethan'
    ],
    [
        'name' => 'Thiết bị đã hết hạn (60 ngày)',
        'ngayhc' => '2024-10-20',
        'thoihankd' => 12,
        'expected' => 'dahethan'
    ],
    [
        'name' => 'Thiết bị còn hạn (31 ngày)',
        'ngayhc' => '2025-01-20',
        'thoihankd' => 12,
        'expected' => 'conhan'
    ],
    [
        'name' => 'Thiết bị còn hạn lâu (100 ngày)',
        'ngayhc' => '2025-03-01',
        'thoihankd' => 12,
        'expected' => 'conhan'
    ],
];

echo "<h2>📊 Test Cases</h2>";
echo "<table>";
echo "<tr>
        <th>Case</th>
        <th>Ngày HC</th>
        <th>Thời hạn</th>
        <th>Ngày hết hạn</th>
        <th>Số ngày còn lại</th>
        <th>Trạng thái thực tế</th>
        <th>Kỳ vọng</th>
        <th>Kết quả</th>
      </tr>";

foreach ($testCases as $i => $case) {
    $ngayHC = new DateTime($case['ngayhc']);
    $ngayHetHan = clone $ngayHC;
    $ngayHetHan->modify('+' . $case['thoihankd'] . ' months');
    
    // Calculate days_to_expire (same as SQL logic)
    $daysToExpire = $today->diff($ngayHetHan);
    $daysDiff = (int)$daysToExpire->format('%r%a'); // Signed days
    
    // Determine actual status
    $actualStatus = '';
    if ($daysDiff < 0) {
        $actualStatus = 'dahethan';
        $statusLabel = '🔴 Đã hết hạn';
        $statusClass = 'error';
    } elseif ($daysDiff >= 0 && $daysDiff <= 30) {
        $actualStatus = 'saphethan';
        $statusLabel = '🟡 Sắp hết hạn';
        $statusClass = 'warning';
    } else {
        $actualStatus = 'conhan';
        $statusLabel = '🟢 Còn hạn';
        $statusClass = 'success';
    }
    
    $match = ($actualStatus === $case['expected']) ? '✓' : '✗';
    $matchClass = ($actualStatus === $case['expected']) ? 'success' : 'error';
    
    echo "<tr>";
    echo "<td>" . ($i + 1) . ". " . htmlspecialchars($case['name']) . "</td>";
    echo "<td>" . $ngayHC->format('d/m/Y') . "</td>";
    echo "<td>" . $case['thoihankd'] . " tháng</td>";
    echo "<td>" . $ngayHetHan->format('d/m/Y') . "</td>";
    echo "<td><strong>" . $daysDiff . "</strong> ngày</td>";
    echo "<td class='$statusClass'>" . $statusLabel . "</td>";
    echo "<td>" . htmlspecialchars($case['expected']) . "</td>";
    echo "<td class='$matchClass'><strong>" . $match . "</strong></td>";
    echo "</tr>";
}

echo "</table>";

// SQL Query equivalent
echo "<h2>🔍 SQL Logic Tương đương</h2>";
echo "<pre>";
echo "CASE \n";
echo "    WHEN COALESCE(h.ngayhc, t.ngayktnghiemthu) IS NOT NULL \n";
echo "    AND t.thoihankd IS NOT NULL THEN\n";
echo "        DATEDIFF(\n";
echo "            DATE_ADD(COALESCE(h.ngayhc, t.ngayktnghiemthu), \n";
echo "                     INTERVAL CAST(t.thoihankd AS SIGNED) MONTH), \n";
echo "            CURDATE()\n";
echo "        )\n";
echo "    ELSE NULL\n";
echo "END as days_to_expire\n\n";

echo "-- Filter:\n";
echo "HAVING days_to_expire <= 30 AND days_to_expire >= 0  -- Sắp hết hạn\n";
echo "HAVING days_to_expire < 0                             -- Đã hết hạn\n";
echo "</pre>";

echo "<h2>⚠️ Lưu ý quan trọng</h2>";
echo "<ul>";
echo "<li><strong>DATEDIFF(future, present)</strong>: Trả về số ngày từ present đến future</li>";
echo "<li>Nếu future < present → <strong>Âm</strong> (đã hết hạn)</li>";
echo "<li>Nếu future > present → <strong>Dương</strong> (còn hạn)</li>";
echo "<li>SQL sử dụng: <code>DATEDIFF(ngayHetHan, CURDATE())</code></li>";
echo "<li>PHP sử dụng: <code>\$today->diff(\$ngayHetHan)->format('%r%a')</code></li>";
echo "</ul>";

echo "<h2>✅ Kết luận</h2>";
$allPassed = true;
foreach ($testCases as $case) {
    $ngayHC = new DateTime($case['ngayhc']);
    $ngayHetHan = clone $ngayHC;
    $ngayHetHan->modify('+' . $case['thoihankd'] . ' months');
    $daysToExpire = $today->diff($ngayHetHan);
    $daysDiff = (int)$daysToExpire->format('%r%a');
    
    $actualStatus = '';
    if ($daysDiff < 0) {
        $actualStatus = 'dahethan';
    } elseif ($daysDiff >= 0 && $daysDiff <= 30) {
        $actualStatus = 'saphethan';
    } else {
        $actualStatus = 'conhan';
    }
    
    if ($actualStatus !== $case['expected']) {
        $allPassed = false;
        break;
    }
}

if ($allPassed) {
    echo "<p class='success'>✓ Tất cả test cases PASS! Logic filter hoạt động chính xác.</p>";
} else {
    echo "<p class='error'>✗ Có test cases FAIL! Cần kiểm tra lại logic.</p>";
}
?>
