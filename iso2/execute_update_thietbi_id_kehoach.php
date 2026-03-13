<?php
/**
 * Script để cập nhật thietbi_id vào bảng ke_hoach_bao_duong_dinh_ky_iso
 * Mapping từ STT DB (thietbi_iso.stt) vào ke_hoach_bao_duong_dinh_ky_iso.thietbi_id
 */

require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Bắt đầu cập nhật thietbi_id cho bảng ke_hoach_bao_duong_dinh_ky_iso...\n\n";
    
    // Đọc file SQL
    $sqlFile = __DIR__ . '/update_thietbi_id_kehoach.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Không tìm thấy file SQL: $sqlFile");
    }
    
    $sqlContent = file_get_contents($sqlFile);
    
    // Tách các câu lệnh UPDATE
    $statements = array_filter(
        array_map('trim', explode(';', $sqlContent)),
        function($stmt) {
            return !empty($stmt) && stripos($stmt, 'UPDATE') === 0;
        }
    );
    
    $totalStatements = count($statements);
    echo "Tìm thấy $totalStatements câu lệnh UPDATE.\n";
    echo "Bắt đầu thực thi...\n\n";
    
    $db->beginTransaction();
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    
    foreach ($statements as $index => $statement) {
        try {
            $db->exec($statement . ';');
            $successCount++;
            
            // Hiển thị tiến trình mỗi 50 lệnh
            if (($index + 1) % 50 == 0) {
                echo "Đã thực thi " . ($index + 1) . "/$totalStatements câu lệnh...\n";
            }
        } catch (PDOException $e) {
            $errorCount++;
            $errors[] = [
                'statement_num' => $index + 1,
                'statement' => substr($statement, 0, 100) . '...',
                'error' => $e->getMessage()
            ];
        }
    }
    
    if ($errorCount > 0) {
        echo "\n⚠️ Có $errorCount lỗi trong quá trình thực thi:\n";
        foreach ($errors as $error) {
            echo "  - Câu lệnh #{$error['statement_num']}: {$error['error']}\n";
            echo "    {$error['statement']}\n\n";
        }
        
        echo "\nBạn có muốn COMMIT những thay đổi thành công? (Y/N): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        
        if (trim(strtoupper($line)) === 'Y') {
            $db->commit();
            echo "\n✅ Đã COMMIT $successCount câu lệnh thành công.\n";
        } else {
            $db->rollBack();
            echo "\n❌ Đã ROLLBACK tất cả thay đổi.\n";
        }
    } else {
        $db->commit();
        echo "\n✅ Hoàn tất! Đã cập nhật thành công $successCount records.\n";
    }
    
    // Hiển thị thống kê
    echo "\n=== THỐNG KÊ ===\n";
    echo "Tổng số câu lệnh: $totalStatements\n";
    echo "Thành công: $successCount\n";
    echo "Lỗi: $errorCount\n";
    
    // Kiểm tra một vài records để xác nhận
    echo "\n=== KIỂM TRA MẪU ===\n";
    $samples = [2033, 2034, 2045, 2084, 2191];
    foreach ($samples as $id) {
        $stmt = $db->prepare("SELECT id, thietbi_id, ten_thietbi, so_serial FROM ke_hoach_bao_duong_dinh_ky_iso WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            echo "ID {$row['id']}: thietbi_id={$row['thietbi_id']}, {$row['ten_thietbi']} ({$row['so_serial']})\n";
        }
    }
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
    exit(1);
}
