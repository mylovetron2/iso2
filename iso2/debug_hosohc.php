<?php
// Debug file để kiểm tra dữ liệu hồ sơ HC
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/KeHoachISO.php';
require_once __DIR__ . '/models/HoSoHCKD.php';
require_once __DIR__ . '/models/ThietBiHCKD.php';

echo "<h1>Debug Hồ Sơ Hiệu Chuẩn</h1>";
echo "<style>
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
    tr:nth-child(even) { background-color: #f2f2f2; }
    .info { background-color: #e7f3ff; padding: 10px; margin: 10px 0; border-left: 4px solid #2196F3; }
    .success { background-color: #d4edda; padding: 10px; margin: 10px 0; border-left: 4px solid #28a745; }
    .warning { background-color: #fff3cd; padding: 10px; margin: 10px 0; border-left: 4px solid #ffc107; }
</style>";

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

echo "<div class='info'><strong>Tháng kiểm tra:</strong> $month/$year</div>";

// Test 1: Kiểm tra bảng hosohckd_iso
echo "<h2>1. Kiểm tra Bảng hosohckd_iso</h2>";
try {
    $db = getDBConnection();
    
    // Lấy tất cả hồ sơ có ngày HC trong năm
    $sql = "SELECT stt, sohs, tenmay, ngayhc, ngayhctt, ttkt, nhanvien, noithuchien, namkh, 
                   MONTH(ngayhc) as month_hc, YEAR(ngayhc) as year_hc
            FROM hosohckd_iso 
            WHERE YEAR(ngayhc) = :year
            ORDER BY ngayhc DESC
            LIMIT 50";
    $stmt = $db->prepare($sql);
    $stmt->execute(['year' => $year]);
    $hoSoList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Tổng số hồ sơ có ngày HC trong năm $year:</strong> " . count($hoSoList) . "</p>";
    
    if (!empty($hoSoList)) {
        echo "<table>";
        echo "<tr>
                <th>STT</th>
                <th>Số HS</th>
                <th>Tên Máy (mavattu)</th>
                <th>Ngày HC</th>
                <th>Tháng HC</th>
                <th>Năm HC</th>
                <th>Năm KH</th>
                <th>Tình Trạng</th>
                <th>Người HC</th>
                <th>Nơi TH</th>
              </tr>";
        foreach ($hoSoList as $hs) {
            echo "<tr>";
            echo "<td>{$hs['stt']}</td>";
            echo "<td>{$hs['sohs']}</td>";
            echo "<td>{$hs['tenmay']}</td>";
            echo "<td>" . date('d/m/Y', strtotime($hs['ngayhc'])) . "</td>";
            echo "<td>{$hs['month_hc']}</td>";
            echo "<td>{$hs['year_hc']}</td>";
            echo "<td>{$hs['namkh']}</td>";
            echo "<td>{$hs['ttkt']}</td>";
            echo "<td>{$hs['nhanvien']}</td>";
            echo "<td>{$hs['noithuchien']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Lỗi: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 2: Kiểm tra bảng kehoach_iso
echo "<h2>2. Kiểm tra Bảng kehoach_iso (Tháng $month/$year)</h2>";
try {
    $sql = "SELECT stt, tenthietbi, somay, thang, namkh, noithuchien, loaitb, ghichu
            FROM kehoach_iso 
            WHERE thang = :month AND namkh = :year
            ORDER BY stt
            LIMIT 50";
    $stmt = $db->prepare($sql);
    $stmt->execute(['month' => $month, 'year' => $year]);
    $keHoachList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Tổng số kế hoạch tháng $month/$year:</strong> " . count($keHoachList) . "</p>";
    
    if (!empty($keHoachList)) {
        echo "<table>";
        echo "<tr>
                <th>STT</th>
                <th>Tên Thiết Bị</th>
                <th>Số Máy</th>
                <th>Tháng</th>
                <th>Năm</th>
                <th>Nơi TH</th>
                <th>Loại TB</th>
              </tr>";
        foreach ($keHoachList as $kh) {
            echo "<tr>";
            echo "<td>{$kh['stt']}</td>";
            echo "<td>{$kh['tenthietbi']}</td>";
            echo "<td>{$kh['somay']}</td>";
            echo "<td>{$kh['thang']}</td>";
            echo "<td>{$kh['namkh']}</td>";
            echo "<td>{$kh['noithuchien']}</td>";
            echo "<td>{$kh['loaitb']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Lỗi: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 3: Kiểm tra JOIN giữa các bảng
echo "<h2>3. Kiểm tra JOIN (Kế Hoạch + Thiết Bị + Hồ Sơ)</h2>";
try {
    $sql = "SELECT k.stt as kh_stt,
                   k.tenthietbi, 
                   k.somay,
                   k.thang,
                   k.namkh,
                   t.mavattu,
                   t.tenviettat,
                   t.chusohuu,
                   h.stt as hs_stt,
                   h.sohs,
                   h.ngayhc,
                   h.ttkt,
                   h.nhanvien,
                   MONTH(h.ngayhc) as month_hc
            FROM kehoach_iso k
            LEFT JOIN thietbihckd_iso t ON k.tenthietbi = t.tenthietbi AND k.somay = t.somay
            LEFT JOIN hosohckd_iso h ON h.stt = (
                SELECT h2.stt 
                FROM hosohckd_iso h2 
                WHERE h2.tenmay = t.mavattu 
                AND YEAR(h2.ngayhc) = k.namkh
                ORDER BY h2.ngayhc DESC 
                LIMIT 1
            )
            WHERE k.thang = :month AND k.namkh = :year
            ORDER BY k.stt
            LIMIT 50";
    
    $stmt = $db->prepare($sql);
    $stmt->execute(['month' => $month, 'year' => $year]);
    $joinData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Kết quả JOIN:</strong> " . count($joinData) . " records</p>";
    
    if (!empty($joinData)) {
        echo "<table>";
        echo "<tr>
                <th>KH STT</th>
                <th>Tên TB</th>
                <th>Số Máy</th>
                <th>Mã VT</th>
                <th>Tên VT</th>
                <th>HS STT</th>
                <th>Số HS</th>
                <th>Ngày HC</th>
                <th>Tháng HC</th>
                <th>TTKT</th>
                <th>Người HC</th>
                <th>Status</th>
              </tr>";
        foreach ($joinData as $row) {
            $status = empty($row['ngayhc']) ? '⚪ Chưa HC' : 
                     ($row['ttkt'] === 'Tốt' ? '🟢 HC Tốt' : '🔴 HC Hỏng');
            
            echo "<tr>";
            echo "<td>{$row['kh_stt']}</td>";
            echo "<td>{$row['tenthietbi']}</td>";
            echo "<td>{$row['somay']}</td>";
            echo "<td>{$row['mavattu']}</td>";
            echo "<td>{$row['tenviettat']}</td>";
            echo "<td>" . ($row['hs_stt'] ?? '-') . "</td>";
            echo "<td>" . ($row['sohs'] ?? '-') . "</td>";
            echo "<td>" . ($row['ngayhc'] ? date('d/m/Y', strtotime($row['ngayhc'])) : '-') . "</td>";
            echo "<td>" . ($row['month_hc'] ?? '-') . "</td>";
            echo "<td>" . ($row['ttkt'] ?? '-') . "</td>";
            echo "<td>" . ($row['nhanvien'] ?? '-') . "</td>";
            echo "<td><strong>$status</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Lỗi: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 4: Kiểm tra thiết bị không có mavattu
echo "<h2>4. Kiểm tra Thiết Bị Không Có Mã Vật Tư</h2>";
try {
    $sql = "SELECT k.stt, k.tenthietbi, k.somay, t.mavattu
            FROM kehoach_iso k
            LEFT JOIN thietbihckd_iso t ON k.tenthietbi = t.tenthietbi AND k.somay = t.somay
            WHERE k.thang = :month AND k.namkh = :year
            AND t.mavattu IS NULL";
    
    $stmt = $db->prepare($sql);
    $stmt->execute(['month' => $month, 'year' => $year]);
    $noMavattu = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($noMavattu)) {
        echo "<div class='warning'>";
        echo "<p><strong>⚠️ Cảnh báo:</strong> Có " . count($noMavattu) . " thiết bị trong kế hoạch KHÔNG TÌM THẤY trong bảng thietbihckd_iso</p>";
        echo "<table>";
        echo "<tr><th>STT</th><th>Tên Thiết Bị</th><th>Số Máy</th></tr>";
        foreach ($noMavattu as $row) {
            echo "<tr><td>{$row['stt']}</td><td>{$row['tenthietbi']}</td><td>{$row['somay']}</td></tr>";
        }
        echo "</table>";
        echo "<p><em>→ Cần thêm thiết bị này vào bảng thietbihckd_iso để hiển thị hồ sơ HC</em></p>";
        echo "</div>";
    } else {
        echo "<div class='success'><p>✓ Tất cả thiết bị trong kế hoạch đều có mã vật tư</p></div>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Lỗi: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 5: Kiểm tra hồ sơ HC không khớp với thiết bị
echo "<h2>5. Kiểm tra Hồ Sơ HC Không Khớp Với Thiết Bị</h2>";
try {
    $sql = "SELECT h.stt, h.sohs, h.tenmay, h.ngayhc, t.mavattu, t.tenthietbi
            FROM hosohckd_iso h
            LEFT JOIN thietbihckd_iso t ON h.tenmay = t.mavattu
            WHERE YEAR(h.ngayhc) = :year
            AND t.mavattu IS NULL
            LIMIT 20";
    
    $stmt = $db->prepare($sql);
    $stmt->execute(['year' => $year]);
    $noMatch = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($noMatch)) {
        echo "<div class='warning'>";
        echo "<p><strong>⚠️ Cảnh báo:</strong> Có " . count($noMatch) . " hồ sơ HC có tenmay KHÔNG TÌM THẤY trong bảng thietbihckd_iso</p>";
        echo "<table>";
        echo "<tr><th>HS STT</th><th>Số HS</th><th>Tenmay (trong hosohckd_iso)</th><th>Ngày HC</th></tr>";
        foreach ($noMatch as $row) {
            echo "<tr><td>{$row['stt']}</td><td>{$row['sohs']}</td><td>{$row['tenmay']}</td><td>" . date('d/m/Y', strtotime($row['ngayhc'])) . "</td></tr>";
        }
        echo "</table>";
        echo "<p><em>→ Đây là lý do hồ sơ không hiển thị. Cần check lại tenmay trong hosohckd_iso phải khớp với mavattu trong thietbihckd_iso</em></p>";
        echo "</div>";
    } else {
        echo "<div class='success'><p>✓ Tất cả hồ sơ HC đều có thiết bị tương ứng</p></div>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Lỗi: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<h2>📋 Tóm Tắt</h2>";
echo "<div class='info'>";
echo "<h3>Để hồ sơ HC hiển thị đúng, cần đảm bảo:</h3>";
echo "<ol>";
echo "<li><strong>Thiết bị phải có trong bảng thietbihckd_iso</strong> với tenthietbi và somay khớp với kehoach_iso</li>";
echo "<li><strong>Hồ sơ HC phải có tenmay</strong> khớp với <strong>mavattu</strong> trong thietbihckd_iso (KHÔNG phải tenthietbi)</li>";
echo "<li><strong>Ngày HC</strong> phải có trong cùng năm với kế hoạch</li>";
echo "<li>Query sẽ lấy hồ sơ HC <strong>mới nhất</strong> trong năm của thiết bị đó</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<p>Form chọn tháng/năm:</p>";
echo "<form method='get'>";
echo "Tháng: <select name='month'>";
for ($m = 1; $m <= 12; $m++) {
    $selected = $m == $month ? 'selected' : '';
    echo "<option value='$m' $selected>$m</option>";
}
echo "</select> ";
echo "Năm: <select name='year'>";
for ($y = 2023; $y <= 2026; $y++) {
    $selected = $y == $year ? 'selected' : '';
    echo "<option value='$y' $selected>$y</option>";
}
echo "</select> ";
echo "<button type='submit'>Xem</button>";
echo "</form>";

echo "<p><a href='bangcanhbao.php'>← Quay lại Bảng Cảnh Báo</a></p>";
