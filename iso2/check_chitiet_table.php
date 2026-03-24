<?php
/**
 * CHECK CHI TIẾT TABLE - Kiểm tra bảng giao_nhan_thietbi_chitiet
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/constants.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
echo "<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
table { border-collapse: collapse; margin: 20px 0; background: white; }
td, th { border: 1px solid #ddd; padding: 10px; text-align: left; }
th { background: #4CAF50; color: white; }
.section { background: white; padding: 20px; margin: 20px 0; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
h2 { border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
pre { background: #f0f0f0; padding: 15px; border-radius: 5px; overflow-x: auto; }
</style></head><body>";

echo "<h1>🔍 CHECK BẢNG CHI TIẾT - giao_nhan_thietbi_chitiet</h1>";

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<div class='section'>";
    echo "<h2>✅ DATABASE CONNECTION</h2>";
    echo "<p class='success'>Kết nối thành công!</p>";
    echo "</div>";
    
    // Check if chitiet table exists
    echo "<div class='section'>";
    echo "<h2>📋 1. KIỂM TRA BẢNG CHI TIẾT</h2>";
    
    $stmt = $db->query("SHOW TABLES LIKE 'giao_nhan_thietbi_chitiet'");
    $exists = $stmt->rowCount() > 0;
    
    if ($exists) {
        echo "<p class='success'>✅ Bảng 'giao_nhan_thietbi_chitiet' TỒN TẠI</p>";
        
        // Show structure
        echo "<h3>Cấu trúc bảng:</h3>";
        $stmt = $db->query("DESCRIBE giao_nhan_thietbi_chitiet");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td><strong>{$col['Field']}</strong></td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Count records
        $stmt = $db->query("SELECT COUNT(*) as total FROM giao_nhan_thietbi_chitiet");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "<p><strong>Tổng số thiết bị:</strong> {$total}</p>";
        
        if ($total > 0) {
            echo "<h3>5 records mẫu:</h3>";
            $stmt = $db->query("SELECT * FROM giao_nhan_thietbi_chitiet LIMIT 5");
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($records)) {
                echo "<table>";
                echo "<tr>";
                foreach (array_keys($records[0]) as $key) {
                    echo "<th>{$key}</th>";
                }
                echo "</tr>";
                foreach ($records as $rec) {
                    echo "<tr>";
                    foreach ($rec as $val) {
                        echo "<td>" . htmlspecialchars($val ?? 'NULL') . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
        
    } else {
        echo "<p class='error'>❌ Bảng 'giao_nhan_thietbi_chitiet' KHÔNG TỒN TẠI</p>";
        echo "<p class='warning'>⚠️ <strong>Code refactored CẦN bảng này để lưu nhiều thiết bị!</strong></p>";
    }
    echo "</div>";
    
    // Check master table structure
    echo "<div class='section'>";
    echo "<h2>📋 2. CẤU TRÚC MASTER TABLE</h2>";
    $stmt = $db->query("DESCRIBE giao_nhan_thietbi_iso");
    $masterCols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if has device columns in master
    $hasDeviceInMaster = false;
    foreach ($masterCols as $col) {
        if (in_array($col['Field'], ['thietbi_id', 'ten_thietbi', 'ky_ma_hieu'])) {
            $hasDeviceInMaster = true;
            break;
        }
    }
    
    if ($hasDeviceInMaster) {
        echo "<p class='warning'>⚠️ Master table CÓ cột thiết bị (thietbi_id, ten_thietbi, ky_ma_hieu)</p>";
        echo "<p class='warning'>→ Đây là structure CŨ (1 phiếu = 1 thiết bị)</p>";
        echo "<p class='error'>→ Code refactored cần 1 phiếu = NHIỀU thiết bị (lưu trong bảng chitiet)</p>";
    } else {
        echo "<p class='success'>✅ Master table KHÔNG CÓ cột thiết bị</p>";
        echo "<p>→ Structure đúng cho refactored code</p>";
    }
    
    echo "<h3>Các cột liên quan device:</h3>";
    echo "<table>";
    echo "<tr><th>Column</th><th>Type</th></tr>";
    foreach ($masterCols as $col) {
        if (stripos($col['Field'], 'thiet') !== false || 
            stripos($col['Field'], 'device') !== false ||
            in_array($col['Field'], ['ky_ma_hieu', 'tong_thietbi'])) {
            echo "<tr>";
            echo "<td><strong>{$col['Field']}</strong></td>";
            echo "<td>{$col['Type']}</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    echo "</div>";
    
    // Check for missing columns
    echo "<div class='section'>";
    echo "<h2>📊 3. KIỂM TRA CỘT YÊU CẦU</h2>";
    
    $required_master_cols = [
        'nguoi_gui_kiemdinh' => 'VARCHAR(255) - Người gửi kiểm định',
        'donvi_gui_kiemdinh' => 'VARCHAR(255) - Đơn vị kiểm định', 
        'ngay_gui_kiemdinh' => 'DATE - Ngày gửi kiểm định'
    ];
    
    echo "<h3>Master table (giao_nhan_thietbi_iso):</h3>";
    foreach ($required_master_cols as $colName => $desc) {
        $found = false;
        foreach ($masterCols as $col) {
            if ($col['Field'] === $colName) {
                $found = true;
                break;
            }
        }
        $class = $found ? 'success' : 'error';
        $icon = $found ? '✅' : '❌';
        echo "<p class='{$class}'>{$icon} <code>{$colName}</code>: " . ($found ? 'CÓ' : 'KHÔNG CÓ') . " ({$desc})</p>";
    }
    echo "</div>";
    
    // Recommend actions
    echo "<div class='section'>";
    echo "<h2>🎯 HÀNH ĐỘNG CẦN LÀM</h2>";
    
    $actions = [];
    
    if (!$exists) {
        $actions[] = [
            'priority' => 'CRITICAL',
            'task' => 'Tạo bảng giao_nhan_thietbi_chitiet',
            'sql' => 'CREATE TABLE giao_nhan_thietbi_chitiet (...)'
        ];
    }
    
    $missingCols = [];
    foreach ($required_master_cols as $colName => $desc) {
        $found = false;
        foreach ($masterCols as $col) {
            if ($col['Field'] === $colName) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $missingCols[] = $colName;
        }
    }
    
    if (!empty($missingCols)) {
        $actions[] = [
            'priority' => 'HIGH',
            'task' => 'Thêm ' . count($missingCols) . ' cột mới vào master table',
            'sql' => 'ALTER TABLE giao_nhan_thietbi_iso ADD COLUMN ...'
        ];
    }
    
    if ($hasDeviceInMaster && $exists) {
        $actions[] = [
            'priority' => 'MEDIUM',
            'task' => 'Migrate dữ liệu từ master sang chitiet table',
            'sql' => 'INSERT INTO giao_nhan_thietbi_chitiet SELECT ...'
        ];
        $actions[] = [
            'priority' => 'LOW',
            'task' => 'Xóa cột device khỏi master table (sau khi migrate)',
            'sql' => 'ALTER TABLE giao_nhan_thietbi_iso DROP COLUMN thietbi_id, ...'
        ];
    }
    
    if (empty($actions)) {
        echo "<p class='success' style='font-size: 18px;'>✅ <strong>KHÔNG CÓ HÀNH ĐỘNG NÀO - DATABASE ĐÃ HOÀN CHỈNH!</strong></p>";
    } else {
        echo "<p class='error' style='font-size: 18px;'>❌ <strong>CẦN THỰC HIỆN " . count($actions) . " HÀNH ĐỘNG</strong></p>";
        echo "<ol>";
        foreach ($actions as $action) {
            $colorClass = $action['priority'] === 'CRITICAL' ? 'error' : ($action['priority'] === 'HIGH' ? 'warning' : 'success');
            echo "<li class='{$colorClass}'>";
            echo "<strong>[{$action['priority']}]</strong> {$action['task']}<br>";
            echo "<code>{$action['sql']}</code>";
            echo "</li>";
        }
        echo "</ol>";
    }
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='section'>";
    echo "<h2 class='error'>❌ DATABASE ERROR</h2>";
    echo "<p class='error'>{$e->getMessage()}</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p style='text-align: center; color: #666;'>Generated: " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
