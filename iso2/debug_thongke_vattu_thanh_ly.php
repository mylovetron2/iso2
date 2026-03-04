<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/config/database.php';

$db = getDBConnection();

echo "<h2>🔍 Debug Thống Kê Vật Tư Thanh Lý</h2>";
echo "<style>
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
    tr:nth-child(even) { background-color: #f2f2f2; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
</style>";

// 1. Kiểm tra bảng vattu_thanh_ly_sudung_iso
echo "<h3>1. Kiểm tra bảng vattu_thanh_ly_sudung_iso</h3>";
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM vattu_thanh_ly_sudung_iso");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p class='success'>✓ Tổng số bản ghi trong bảng sử dụng: <strong>{$result['total']}</strong></p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Lỗi: {$e->getMessage()}</p>";
}

// 2. Kiểm tra các trạng thái
echo "<h3>2. Phân loại theo trạng thái</h3>";
try {
    $stmt = $db->query("SELECT trangthai, COUNT(*) as count FROM vattu_thanh_ly_sudung_iso GROUP BY trangthai");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($results)) {
        echo "<table>";
        echo "<tr><th>Trạng thái</th><th>Số lượng</th></tr>";
        foreach ($results as $row) {
            echo "<tr><td>{$row['trangthai']}</td><td>{$row['count']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠ Chưa có dữ liệu phân loại</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Lỗi: {$e->getMessage()}</p>";
}

// 3. Kiểm tra dữ liệu tháng 3/2026
echo "<h3>3. Dữ liệu thanh lý tháng 3/2026</h3>";
try {
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total,
            MIN(COALESCE(ngayhoanthanh, ngaysd_nhan, updated_at)) as ngay_dau,
            MAX(COALESCE(ngayhoanthanh, ngaysd_nhan, updated_at)) as ngay_cuoi
        FROM vattu_thanh_ly_sudung_iso 
        WHERE trangthai = 'thanh_ly'
        AND YEAR(COALESCE(ngayhoanthanh, ngaysd_nhan, updated_at)) = 2026 
        AND MONTH(COALESCE(ngayhoanthanh, ngaysd_nhan, updated_at)) = 3
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Tổng bản ghi thanh lý tháng 3/2026: <strong>{$result['total']}</strong></p>";
    if ($result['total'] > 0) {
        echo "<p>Từ ngày: {$result['ngay_dau']} đến {$result['ngay_cuoi']}</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Lỗi: {$e->getMessage()}</p>";
}

// 4. Kiểm tra dữ liệu thanh lý cụ thể trong tháng 3/2026
echo "<h3>4. Chi tiết dữ liệu thanh lý tháng 3/2026</h3>";
try {
    $stmt = $db->query("
        SELECT 
            s.*,
            v.mavattu,
            v.ten_tiengviet,
            COALESCE(s.ngayhoanthanh, s.ngaysd_nhan, DATE(s.updated_at)) as ngay_thanh_ly
        FROM vattu_thanh_ly_sudung_iso s
        LEFT JOIN vattu_thanh_ly_iso v ON s.vattu_stt = v.stt
        WHERE s.trangthai = 'thanh_ly'
        AND YEAR(COALESCE(s.ngayhoanthanh, s.ngaysd_nhan, s.updated_at)) = 2026 
        AND MONTH(COALESCE(s.ngayhoanthanh, s.ngaysd_nhan, s.updated_at)) = 3
        ORDER BY ngay_thanh_ly DESC
        LIMIT 20
    ");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($results)) {
        echo "<table>";
        echo "<tr>
                <th>ID</th>
                <th>Mã VT</th>
                <th>Tên VT</th>
                <th>Trạng thái</th>
                <th>SL</th>
                <th>Ngày thanh lý</th>
                <th>Bộ phận</th>
                <th>Ghi chú</th>
              </tr>";
        foreach ($results as $row) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['mavattu']}</td>";
            echo "<td>{$row['ten_tiengviet']}</td>";
            echo "<td>{$row['trangthai']}</td>";
            echo "<td>{$row['soluong']}</td>";
            echo "<td>{$row['ngay_thanh_ly']}</td>";
            echo "<td>{$row['bophan']}</td>";
            echo "<td>{$row['ghichu']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠ Không có dữ liệu thanh lý trong tháng 3/2026</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Lỗi: {$e->getMessage()}</p>";
}

// 5. Kiểm tra query thực tế
echo "<h3>5. Test Query giống với controller</h3>";
$tungay = '2026-03-01';
$denngay = '2026-03-31';

try {
    $sql = "SELECT 
                s.id,
                v.stt as vattu_stt,
                v.mavattu,
                v.ten_tiengviet,
                v.ten_tienganh,
                v.ten_tiengnga,
                v.dactinhkt_tiengviet,
                v.dvt_tiengviet,
                v.dvt_tiengnga,
                v.dongia,
                s.soluong as soluong_thaydoi,
                s.soluong * v.dongia as thanhtien,
                COALESCE(s.ngayhoanthanh, s.ngaysd_nhan, DATE(s.updated_at)) as ngay_thuchien,
                s.ghichu as nguyennhan,
                s.nguoisudung as nguoi_thuchien,
                s.bophan,
                '' as nam_sd
            FROM vattu_thanh_ly_sudung_iso s
            INNER JOIN vattu_thanh_ly_iso v ON s.vattu_stt = v.stt
            WHERE s.trangthai = 'thanh_ly'
            AND DATE(COALESCE(s.ngayhoanthanh, s.ngaysd_nhan, s.updated_at)) BETWEEN :tungay AND :denngay
            ORDER BY ngay_thuchien ASC, v.mavattu ASC";
    
    echo "<p><strong>SQL Query:</strong></p>";
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    echo "<p>Từ ngày: <strong>{$tungay}</strong>, Đến ngày: <strong>{$denngay}</strong></p>";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':tungay' => $tungay,
        ':denngay' => $denngay
    ]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p class='success'>✓ Query thành công! Số kết quả: <strong>" . count($results) . "</strong></p>";
    
    if (!empty($results)) {
        echo "<table>";
        echo "<tr>
                <th>STT</th>
                <th>Mã VT</th>
                <th>Tên</th>
                <th>ĐVT</th>
                <th>SL</th>
                <th>Đơn giá</th>
                <th>Thành tiền</th>
                <th>Ngày</th>
                <th>Bộ phận</th>
                <th>Nguyên nhân</th>
              </tr>";
        $stt = 1;
        foreach ($results as $row) {
            echo "<tr>";
            echo "<td>{$stt}</td>";
            echo "<td>{$row['mavattu']}</td>";
            echo "<td>{$row['ten_tiengviet']}</td>";
            echo "<td>{$row['dvt_tiengviet']}</td>";
            echo "<td>" . number_format($row['soluong_thaydoi'], 2) . "</td>";
            echo "<td>" . number_format($row['dongia'], 2) . "</td>";
            echo "<td>" . number_format($row['thanhtien'], 2) . "</td>";
            echo "<td>{$row['ngay_thuchien']}</td>";
            echo "<td>{$row['bophan']}</td>";
            echo "<td>{$row['nguyennhan']}</td>";
            echo "</tr>";
            $stt++;
        }
        echo "</table>";
    } else {
        echo "<p class='error'>✗ Không có kết quả với điều kiện: trangthai = 'thanh_ly' và ngày từ {$tungay} đến {$denngay}</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Lỗi query: {$e->getMessage()}</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// 6. Gợi ý giải pháp
echo "<h3>6. Gợi ý khắc phục</h3>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;'>";
echo "<p><strong>Nếu không thấy kết quả, có thể do:</strong></p>";
echo "<ol>";
echo "<li>Chưa có dữ liệu với <code>trangthai = 'thanh_ly'</code> trong tháng 3/2026</li>";
echo "<li>Dữ liệu có thể đang dùng trạng thái khác (dangdung, dahoan)</li>";
echo "<li>Ngày hoàn thành/ngày nhận chưa được ghi nhận đúng</li>";
echo "<li>Chưa có bản ghi nào trong bảng <code>vattu_thanh_ly_sudung_iso</code></li>";
echo "</ol>";
echo "<p><strong>Giải pháp:</strong></p>";
echo "<ul>";
echo "<li>Kiểm tra xem khi thanh lý vật tư, có cập nhật <code>trangthai = 'thanh_ly'</code> trong bảng vattu_thanh_ly_sudung_iso không?</li>";
echo "<li>Kiểm tra các trường ngày: ngayhoanthanh, ngaysd_nhan, updated_at</li>";
