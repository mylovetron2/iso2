<?php
declare(strict_types=1);

/**
 * Script để tạo bảng kế hoạch bảo dưỡng định kỳ
 * Run: php execute_create_ke_hoach_bao_duong_dinh_ky.php
 */

require_once __DIR__ . '/config/database.php';

echo "=== Tạo bảng Kế hoạch Bảo dưỡng thiết bị định kỳ ===\n\n";

try {
    echo "Đang kết nối database...\n";
    $db = getDBConnection();
    echo "✓ Kết nối thành công!\n\n";
    
    // Đọc file SQL
    $sqlFile = __DIR__ . '/create_table_ke_hoach_bao_duong_dinh_ky.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("File SQL không tồn tại: $sqlFile");
    }
    
    echo "Đọc file SQL...\n";
    $sql = file_get_contents($sqlFile);
    echo "✓ Đọc file thành công\n\n";
    
    // Tách các câu lệnh SQL
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    echo "Thực thi các câu lệnh SQL...\n";
    foreach ($statements as $index => $statement) {
        if (empty(trim($statement))) continue;
        
        echo "  - Câu lệnh " . ($index + 1) . "... ";
        $db->exec($statement);
        echo "✓\n";
    }
    
    echo "\n✅ Hoàn thành! Đã tạo bảng ke_hoach_bao_duong_dinh_ky_iso\n\n";
    
    // Kiểm tra bảng
    echo "Kiểm tra cấu trúc bảng:\n";
    $stmt = $db->query("DESCRIBE ke_hoach_bao_duong_dinh_ky_iso");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nCác cột trong bảng:\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    
    echo "\n✓ Bảng đã sẵn sàng sử dụng!\n";
    
} catch (Exception $e) {
    echo "\n❌ Lỗi: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}
