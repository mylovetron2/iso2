<?php
declare(strict_types=1);

/**
 * Script thực thi thêm cột nhomsc vào bảng ke_hoach_bao_duong_dinh_ky_iso
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = getDBConnection();
    
    echo "Bắt đầu thêm cột nhomsc...\n";
    
    // Kiểm tra xem cột đã tồn tại chưa
    $checkSql = "SHOW COLUMNS FROM ke_hoach_bao_duong_dinh_ky_iso LIKE 'nhomsc'";
    $result = $db->query($checkSql);
    
    if ($result->rowCount() > 0) {
        echo "Cột nhomsc đã tồn tại! Bỏ qua.\n";
        exit(0);
    }
    
    // Đọc file SQL
    $sqlFile = __DIR__ . '/add_nhomsc_column.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("File SQL không tồn tại: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Loại bỏ comments và dòng trống
    $lines = explode("\n", $sql);
    $cleanSql = '';
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line) && !str_starts_with($line, '--')) {
            $cleanSql .= $line . ' ';
        }
    }
    
    // Thực thi SQL
    $db->exec($cleanSql);
    
    echo "Đã thêm cột nhomsc thành công!\n";
    
    // Verify
    $verifySql = "SHOW COLUMNS FROM ke_hoach_bao_duong_dinh_ky_iso LIKE 'nhomsc'";
    $verifyResult = $db->query($verifySql);
    
    if ($verifyResult->rowCount() > 0) {
        echo "Xác nhận: Cột nhomsc đã được thêm vào bảng.\n";
        $column = $verifyResult->fetch(PDO::FETCH_ASSOC);
        echo "Chi tiết cột:\n";
        print_r($column);
    } else {
        echo "CẢNH BÁO: Không thể xác nhận cột đã được thêm!\n";
    }
    
} catch (Exception $e) {
    echo "LỖI: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
