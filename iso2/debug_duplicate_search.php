<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/config/constants.php';
    require_once __DIR__ . '/includes/auth.php';

    // Test query to check for duplicates
    $db = getDBConnection();
} catch (Exception $e) {
    die("Error loading config: " . $e->getMessage() . "<br>Trace: " . $e->getTraceAsString());
}

// Simulate search query
$search = $_GET['search'] ?? '';
$phanloai_id = !empty($_GET['phanloai_id']) ? (int)$_GET['phanloai_id'] : null;

$conditions = [];
$params = [];

if ($search) {
    $searchLower = mb_strtolower($search, 'UTF-8');
    $searchCap = mb_strtoupper(mb_substr($search, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($searchLower, 1);
    
    $conditions[] = "(
        v.mavattu LIKE :search1a OR v.mavattu LIKE :search1b OR
        v.ten_tienganh LIKE :search2a OR v.ten_tienganh LIKE :search2b OR
        v.ten_tiengnga LIKE :search3a OR v.ten_tiengnga LIKE :search3b OR
        v.ten_tiengviet LIKE :search4a OR v.ten_tiengviet LIKE :search4b OR
        v.nguoiquanly LIKE :search5a OR v.nguoiquanly LIKE :search5b
    )";
    
    $params[':search1a'] = "%$searchLower%";
    $params[':search1b'] = "%$searchCap%";
    $params[':search2a'] = "%$searchLower%";
    $params[':search2b'] = "%$searchCap%";
    $params[':search3a'] = "%$searchLower%";
    $params[':search3b'] = "%$searchCap%";
    $params[':search4a'] = "%$searchLower%";
    $params[':search4b'] = "%$searchCap%";
    $params[':search5a'] = "%$searchLower%";
    $params[':search5b'] = "%$searchCap%";
}

if ($phanloai_id) {
    $conditions[] = "v.phanloai_id = :phanloai_id";
    $params[':phanloai_id'] = $phanloai_id;
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Debug Duplicate Search</title></head><body>";

try {
    // Test query WITHOUT GROUP BY to see raw results
    $sql1 = "SELECT 
                v.stt,
                v.mavattu,
                v.ten_tiengviet,
                pl.ten_phanloai,
                s.id as sudung_id,
                s.soluong as sudung_soluong
            FROM vattu_thanh_ly_iso v
            LEFT JOIN phanloai_vattu_thanh_ly_iso pl ON v.phanloai_id = pl.id
            LEFT JOIN vattu_thanh_ly_sudung_iso s ON v.stt = s.vattu_stt
            $where
            ORDER BY v.stt DESC";

    echo "<h2>Query WITHOUT GROUP BY (Raw JOIN results):</h2>";
    echo "<pre>" . htmlspecialchars($sql1) . "</pre>";
    echo "<h3>Params:</h3>";
    echo "<pre>" . htmlspecialchars(print_r($params, true)) . "</pre>";

    $stmt1 = $db->prepare($sql1);
    $stmt1->execute($params);
    $rawResults = $stmt1->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>Raw Results Count: " . count($rawResults) . "</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>STT</th><th>Mã VT</th><th>Tên</th><th>Phân loại</th><th>Sử dụng ID</th><th>SL sử dụng</th></tr>";
    foreach ($rawResults as $row) {
        echo "<tr>";
        echo "<td>{$row['stt']}</td>";
        echo "<td>{$row['mavattu']}</td>";
        echo "<td>" . htmlspecialchars(substr($row['ten_tiengviet'] ?? '', 0, 30)) . "</td>";
        echo "<td>{$row['ten_phanloai']}</td>";
        echo "<td>{$row['sudung_id']}</td>";
        echo "<td>{$row['sudung_soluong']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Test query WITH GROUP BY (actual query)
    $sql2 = "SELECT 
                v.*,
                pl.ten_phanloai,
                pl.mau_sac as phanloai_mau_sac,
                COUNT(DISTINCT s.id) as so_lan_sudung,
                SUM(CASE WHEN s.trangthai = 'dangdung' THEN s.soluong ELSE 0 END) as soluong_dangdung,
                v.soluong_conlai * COALESCE(v.dongia, 0) as tong_tien
            FROM vattu_thanh_ly_iso v
            LEFT JOIN phanloai_vattu_thanh_ly_iso pl ON v.phanloai_id = pl.id
            LEFT JOIN vattu_thanh_ly_sudung_iso s ON v.stt = s.vattu_stt
            $where
            GROUP BY v.stt
            ORDER BY v.stt DESC
            LIMIT 20";

    echo "<hr><h2>Query WITH GROUP BY (Actual Used):</h2>";
    echo "<pre>" . htmlspecialchars($sql2) . "</pre>";

    $stmt2 = $db->prepare($sql2);
    $stmt2->execute($params);
    $groupedResults = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>Grouped Results Count: " . count($groupedResults) . "</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>STT</th><th>Mã VT</th><th>Tên</th><th>Phân loại</th><th>Số lần sử dụng</th><th>SL còn lại</th></tr>";
    foreach ($groupedResults as $row) {
        echo "<tr>";
        echo "<td>{$row['stt']}</td>";
        echo "<td>{$row['mavattu']}</td>";
        echo "<td>" . htmlspecialchars(substr($row['ten_tiengviet'] ?? '', 0, 30)) . "</td>";
        echo "<td>{$row['ten_phanloai']}</td>";
        echo "<td>{$row['so_lan_sudung']}</td>";
        echo "<td>" . number_format($row['soluong_conlai'], 0) . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Check for duplicate STT in grouped results
    $sttList = array_column($groupedResults, 'stt');
    $duplicates = array_diff_assoc($sttList, array_unique($sttList));
    if (!empty($duplicates)) {
        echo "<hr><h3 style='color: red;'>DUPLICATE STT FOUND IN GROUPED RESULTS:</h3>";
        echo "<pre>" . htmlspecialchars(print_r($duplicates, true)) . "</pre>";
    } else {
        echo "<hr><h3 style='color: green;'>No duplicate STT in grouped results - GROUP BY working correctly</h3>";
    }
} catch (Exception $e) {
    echo "<h2 style='color: red;'>Error occurred:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<h3>Stack Trace:</h3>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
