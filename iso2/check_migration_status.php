<?php
/**
 * CHECK MIGRATION STATUS - NO AUTH REQUIRED
 * Kiểm tra xem database đã migrate chưa
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
</style></head><body>";

echo "<h1>🔍 CHECK MIGRATION STATUS - Giao Nhận Thiết Bị</h1>";

try {
    // Connect database
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<div class='section'>";
    echo "<h2>✅ 1. DATABASE CONNECTION</h2>";
    echo "<p class='success'>Kết nối thành công!</p>";
    echo "<p>Host: " . DB_HOST . " | Database: " . DB_NAME . "</p>";
    echo "</div>";
    
    // Check table structure
    echo "<div class='section'>";
    echo "<h2>📋 2. TABLE STRUCTURE - giao_nhan_thietbi_iso</h2>";
    $stmt = $db->query("DESCRIBE giao_nhan_thietbi_iso");
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
    echo "</div>";
    
    // Check ENUM values
    echo "<div class='section'>";
    echo "<h2>🔄 3. TRANGTHAI ENUM VALUES</h2>";
    $found_trangthai = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'trangthai') {
            $found_trangthai = true;
            echo "<p><strong>Type:</strong> {$col['Type']}</p>";
            
            // Parse ENUM values
            preg_match("/^enum\(\'(.*)\'\)$/", $col['Type'], $matches);
            if (isset($matches[1])) {
                $values = explode("','", $matches[1]);
                echo "<p><strong>Giá trị hiện tại:</strong></p><ul>";
                foreach ($values as $val) {
                    echo "<li><code>{$val}</code></li>";
                }
                echo "</ul>";
                
                // Check if NEW values exist
                $new_values = ['da_nhan', 'dang_kiem_dinh', 'da_giao'];
                $old_values = ['cho_nhan', 'hoan_thanh'];
                
                echo "<p><strong>Kiểm tra migration:</strong></p>";
                $has_new = true;
                foreach ($new_values as $nv) {
                    $exists = in_array($nv, $values);
                    $class = $exists ? 'success' : 'error';
                    $icon = $exists ? '✅' : '❌';
                    echo "<p class='{$class}'>{$icon} Giá trị MỚI '<code>{$nv}</code>': " . ($exists ? 'CÓ' : 'KHÔNG CÓ') . "</p>";
                    if (!$exists) $has_new = false;
                }
                
                echo "<p><strong>Giá trị CŨ (nên bị xóa):</strong></p>";
                foreach ($old_values as $ov) {
                    $exists = in_array($ov, $values);
                    $class = $exists ? 'warning' : 'success';
                    $icon = $exists ? '⚠️' : '✅';
                    echo "<p class='{$class}'>{$icon} Giá trị CŨ '<code>{$ov}</code>': " . ($exists ? 'VẪN CÒN (cần xóa)' : 'ĐÃ XÓA') . "</p>";
                }
                
                if ($has_new) {
                    echo "<p class='success'>✅ <strong>MIGRATION ĐÃ CHẠY - ENUM đã được cập nhật!</strong></p>";
                } else {
                    echo "<p class='error'>❌ <strong>MIGRATION CHƯA CHẠY - Cần chạy fix_enum_final.sql!</strong></p>";
                }
            }
        }
    }
    
    if (!$found_trangthai) {
        echo "<p class='error'>❌ Không tìm thấy cột 'trangthai'!</p>";
    }
    echo "</div>";
    
    // Check OLD columns (should be removed)
    echo "<div class='section'>";
    echo "<h2>🗑️ 4. CHECK OLD COLUMNS (nên bị xóa)</h2>";
    $old_columns = ['loai_giao_nhan', 'phieu_giao_id'];
    foreach ($old_columns as $old_col) {
        $found = false;
        foreach ($columns as $col) {
            if ($col['Field'] === $old_col) {
                $found = true;
                break;
            }
        }
        $class = $found ? 'warning' : 'success';
        $icon = $found ? '⚠️' : '✅';
        $status = $found ? 'VẪN CÒN (cần xóa)' : 'ĐÃ XÓA';
        echo "<p class='{$class}'>{$icon} Cột CŨ '<code>{$old_col}</code>': {$status}</p>";
    }
    echo "</div>";
    
    // Check NEW columns (should exist)
    echo "<div class='section'>";
    echo "<h2>➕ 5. CHECK NEW COLUMNS (cần có)</h2>";
    $new_columns = ['nguoi_gui_kiemdinh', 'donvi_gui_kiemdinh', 'ngay_gui_kiemdinh'];
    $all_new_exist = true;
    foreach ($new_columns as $new_col) {
        $found = false;
        foreach ($columns as $col) {
            if ($col['Field'] === $new_col) {
                $found = true;
                break;
            }
        }
        $class = $found ? 'success' : 'error';
        $icon = $found ? '✅' : '❌';
        $status = $found ? 'CÓ' : 'KHÔNG CÓ';
        echo "<p class='{$class}'>{$icon} Cột MỚI '<code>{$new_col}</code>': {$status}</p>";
        if (!$found) $all_new_exist = false;
    }
    
    if (!$all_new_exist) {
        echo "<p class='error'>❌ Thiếu cột mới - Cần chạy fix_enum_final.sql!</p>";
    }
    echo "</div>";
    
    // Count records
    echo "<div class='section'>";
    echo "<h2>📊 6. DATA COUNT</h2>";
    $stmt = $db->query("SELECT COUNT(*) as total FROM giao_nhan_thietbi_iso");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<p><strong>Tổng số records:</strong> {$total}</p>";
    
    if ($total > 0) {
        // Try to query with new structure
        echo "<p><strong>Thử query với cấu trúc MỚI:</strong></p>";
        try {
            $stmt = $db->query("SELECT id, trangthai, nguoi_giao, nguoi_nhan, created_at 
                                FROM giao_nhan_thietbi_iso 
                                LIMIT 5");
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table>";
            echo "<tr><th>ID</th><th>Trạng thái</th><th>Người giao</th><th>Người nhận</th><th>Created</th></tr>";
            foreach ($records as $rec) {
                echo "<tr>";
                echo "<td>{$rec['id']}</td>";
                echo "<td><strong>{$rec['trangthai']}</strong></td>";
                echo "<td>{$rec['nguoi_giao']}</td>";
                echo "<td>{$rec['nguoi_nhan']}</td>";
                echo "<td>{$rec['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "<p class='success'>✅ Query thành công với cấu trúc MỚI!</p>";
            
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Query LỖI: {$e->getMessage()}</p>";
            echo "<p class='error'>⚠️ <strong>Có thể do database chưa migrate!</strong></p>";
        }
    }
    echo "</div>";
    
    // Final verdict
    echo "<div class='section'>";
    echo "<h2>🎯 7. FINAL VERDICT</h2>";
    
    // Determine migration status
    $migration_ok = true;
    $issues = [];
    
    // Check ENUM
    if (!$found_trangthai) {
        $migration_ok = false;
        $issues[] = "Không tìm thấy cột 'trangthai'";
    }
    
    // Check new columns
    if (!$all_new_exist) {
        $migration_ok = false;
        $issues[] = "Thiếu cột mới (nguoi_gui_kiemdinh, donvi_gui_kiemdinh, ngay_gui_kiemdinh)";
    }
    
    if ($migration_ok) {
        echo "<p class='success' style='font-size: 18px;'>✅ <strong>DATABASE ĐÃ MIGRATE XONG!</strong></p>";
        echo "<p>Tất cả cột và ENUM đã được cập nhật đúng.</p>";
        echo "<p>✅ Có thể sử dụng module Giao Nhận Thiết Bị bình thường.</p>";
    } else {
        echo "<p class='error' style='font-size: 18px;'>❌ <strong>DATABASE CHƯA MIGRATE!</strong></p>";
        echo "<p><strong>Các vấn đề:</strong></p>";
        echo "<ul>";
        foreach ($issues as $issue) {
            echo "<li class='error'>{$issue}</li>";
        }
        echo "</ul>";
        echo "<p class='warning'><strong>⚠️ CẦN CHẠY:</strong> <code>fix_enum_final.sql</code></p>";
        echo "<p>File này thực hiện:</p>";
        echo "<ol>";
        echo "<li>EXPAND ENUM: Thêm giá trị mới vào ENUM</li>";
        echo "<li>UPDATE DATA: Chuyển đổi dữ liệu cũ sang mới</li>";
        echo "<li>SHRINK ENUM: Xóa giá trị cũ khỏi ENUM</li>";
        echo "<li>DROP old columns: Xóa loai_giao_nhan, phieu_giao_id</li>";
        echo "<li>ADD new columns: Thêm nguoi_gui_kiemdinh, donvi_gui_kiemdinh, ngay_gui_kiemdinh</li>";
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
