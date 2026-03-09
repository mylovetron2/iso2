<?php
/**
 * Script thực thi migration thêm cột trạng thái hoàn thành bảo dưỡng
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = getDBConnection();
    
    echo "Đang thêm cột trạng thái hoàn thành bảo dưỡng...\n\n";
    
    // Đọc file SQL
    $sql = file_get_contents(__DIR__ . '/add_hoantat_columns.sql');
    
    // Tách các câu lệnh SQL
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && 
                   strpos($stmt, '--') !== 0 && 
                   $stmt !== '';
        }
    );
    
    $success = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
        try {
            $db->exec($statement);
            $success++;
            echo "✓ Thực thi thành công: " . substr($statement, 0, 50) . "...\n";
        } catch (PDOException $e) {
            $errors++;
            echo "✗ Lỗi: " . $e->getMessage() . "\n";
            echo "  SQL: " . substr($statement, 0, 100) . "...\n";
        }
    }
    
    echo "\n";
    echo "=================================\n";
    echo "Kết quả:\n";
    echo "- Thành công: $success\n";
    echo "- Lỗi: $errors\n";
    echo "=================================\n";
    
    if ($errors === 0) {
        echo "\n✓ Migration hoàn thành! Các cột trạng thái hoàn thành đã được thêm.\n";
    } else {
        echo "\n⚠ Migration hoàn thành với một số lỗi. Vui lòng kiểm tra.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
    exit(1);
}
