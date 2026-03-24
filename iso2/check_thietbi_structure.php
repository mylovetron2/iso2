<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = getDBConnection(true);
    
    echo "=== KIỂM TRA CẤU TRÚC BẢNG THIETBI_ISO ===\n\n";
    
    // Kiểm tra bảng tồn tại
    $stmt = $db->query("SHOW TABLES LIKE 'thietbi_iso'");
    if ($stmt->rowCount() == 0) {
        die("❌ Bảng thietbi_iso KHÔNG TỒN TẠI!\n");
    }
    echo "✓ Bảng thietbi_iso tồn tại\n\n";
    
    // Kiểm tra cấu trúc cột id
    echo "Cấu trúc cột 'id' trong thietbi_iso:\n";
    $stmt = $db->query("DESCRIBE thietbi_iso");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $col) {
        if ($col['Field'] == 'id') {
            print_r($col);
            break;
        }
    }
    echo "\n";
    
    // Kiểm tra engine và charset
    echo "Thông tin bảng thietbi_iso:\n";
    $stmt = $db->query("SHOW TABLE STATUS LIKE 'thietbi_iso'");
    $info = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Engine: " . ($info['Engine'] ?? 'N/A') . "\n";
    echo "Collation: " . ($info['Collation'] ?? 'N/A') . "\n";
    echo "Charset: " . (explode('_', $info['Collation'] ?? 'utf8_')[0]) . "\n\n";
    
    // Kiểm tra indexes/keys
    echo "Keys trong thietbi_iso:\n";
    $stmt = $db->query("SHOW KEYS FROM thietbi_iso");
    $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($keys as $key) {
        if ($key['Column_name'] == 'id') {
            echo "- {$key['Key_name']}: {$key['Column_name']} ({$key['Index_type']})\n";
        }
    }
    
} catch (PDOException $e) {
    die("Lỗi: " . $e->getMessage() . "\n");
}
