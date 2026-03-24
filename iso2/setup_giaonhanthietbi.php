<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

echo "=== THIẾT LẬP MODULE GIAO NHẬN THIẾT BỊ ===\n\n";

try {
    $conn = getDBConnection(true);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Bước 1: Tạo bảng giao_nhan_thietbi_iso
    echo "Bước 1: Tạo bảng giao_nhan_thietbi_iso...\n";
    $sqlFile = __DIR__ . '/create_table_giao_nhan_thietbi.sql';
    
    if (!file_exists($sqlFile)) {
        die("Lỗi: Không tìm thấy file $sqlFile\n");
    }
    
    $sql = file_get_contents($sqlFile);
    $conn->exec($sql);
    echo "✓ Đã tạo bảng thành công!\n\n";
    
    // Kiểm tra cấu trúc bảng
    echo "Cấu trúc bảng giao_nhan_thietbi_iso:\n";
    $stmt = $conn->query("DESCRIBE giao_nhan_thietbi_iso");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    printf("%-20s %-30s %-10s %-10s\n", "Field", "Type", "Null", "Key");
    echo str_repeat("-", 75) . "\n";
    foreach ($columns as $col) {
        printf("%-20s %-30s %-10s %-10s\n", 
            $col['Field'], 
            $col['Type'], 
            $col['Null'], 
            $col['Key']
        );
    }
    echo "\n";
    
    // Bước 2: Thêm permissions vào roles (CSV format)
    echo "Bước 2: Thêm permissions vào roles...\n";
    
    $permissionsList = 'giaonhanthietbi.view,giaonhanthietbi.create_giao,giaonhanthietbi.create_nhan,giaonhanthietbi.edit,giaonhanthietbi.delete,giaonhanthietbi.export';
    
    // Lấy danh sách roles admin
    $stmt = $conn->query("SELECT id, name, permissions FROM roles WHERE name IN ('Admin', 'admin', 'Manager') OR id = 1");
    $adminRoles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($adminRoles)) {
        echo "  ! Không tìm thấy admin role\n";
    } else {
        foreach ($adminRoles as $role) {
            $currentPerms = $role['permissions'] ?? '';
            
            // Kiểm tra đã có permissions chưa
            if (strpos($currentPerms, 'giaonhanthietbi') !== false) {
                echo "  - Role '{$role['name']}': Đã có permissions\n";
                continue;
            }
            
            // Thêm permissions
            $newPerms = $currentPerms;
            if (empty($newPerms)) {
                $newPerms = $permissionsList;
            } else {
                $newPerms .= ',' . $permissionsList;
            }
            
            $updateStmt = $conn->prepare("UPDATE roles SET permissions = ? WHERE id = ?");
            $updateStmt->execute([$newPerms, $role['id']]);
            
            echo "  ✓ Role '{$role['name']}': Đã thêm permissions\n";
        }
    }
    
    echo "\n";
    
    // Bước 3: Kiểm tra kết quả
    echo "Bước 3: Kiểm tra kết quả...\n";
    $stmt = $conn->query("SELECT id, name FROM roles WHERE permissions LIKE '%giaonhanthietbi%'");
    $rolesWithPerm = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($rolesWithPerm)) {
        echo "  ! Chưa có role nào có permissions giaonhanthietbi\n";
    } else {
        foreach ($rolesWithPerm as $role) {
            echo "  ✓ Role '{$role['name']}' (ID: {$role['id']}): Có permissions ✓\n";
        }
    }
    
    echo "\n=== HOÀN TẤT THIẾT LẬP ===\n";
    echo "Module Giao Nhận Thiết Bị đã được cài đặt thành công!\n";
    echo "\nBước tiếp theo:\n";
    echo "1. Uncomment menu trong views/layouts/header.php\n";
    echo "2. Truy cập giaonhanthietbi.php để kiểm tra\n";
    
} catch (PDOException $e) {
    echo "\nLỖI: " . $e->getMessage() . "\n";
    echo "\nThông tin debug:\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}
