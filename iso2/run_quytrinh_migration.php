<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$sqlFile = __DIR__ . '/migrations/create_quy_trinh.sql';
if (!is_file($sqlFile)) {
    exit("Không tìm thấy file migration: $sqlFile\n");
}

echo "=== Migration: Tạo bảng quy_trinh_iso & quy_trinh_gopy_iso ===\n\n";

try {
    $pdo = getDBConnection();
    $pdo->exec("SET NAMES latin1");

    $sql = (string)file_get_contents($sqlFile);
    $sql = preg_replace('/^\s*--.*(?:\r?\n|$)/m', '', $sql) ?? $sql;
    $statements = array_filter(
        array_map('trim', preg_split('/;[\r\n]+/', $sql) ?: []),
        static fn($stmt) => $stmt !== '' && !preg_match('/^--/', $stmt)
    );

    $ok = 0;
    $fail = 0;
    foreach ($statements as $i => $stmt) {
        try {
            $pdo->exec($stmt);
            $ok++;
            echo '✓ Statement ' . ($i + 1) . " OK\n";
        } catch (PDOException $e) {
            $fail++;
            echo '✗ Statement ' . ($i + 1) . ' FAILED: ' . $e->getMessage() . "\n";
        }
    }

    echo "\nSuccess: $ok, Failed: $fail\n\n";

    $rows = $pdo->query("SELECT id, so_qt, ten_qt FROM quy_trinh_iso ORDER BY thu_tu")->fetchAll(PDO::FETCH_ASSOC);
    echo "Danh sách quy trình:\n";
    foreach ($rows as $r) {
        echo "  #{$r['id']} | QT {$r['so_qt']} | {$r['ten_qt']}\n";
    }

    $storageDir = __DIR__ . '/storage/quy-trinh';
    if (!is_dir($storageDir)) {
        if (mkdir($storageDir, 0750, true)) {
            echo "\n✓ Đã tạo thư mục lưu trữ: $storageDir\n";
        } else {
            echo "\n✗ Không thể tạo thư mục lưu trữ: $storageDir\n";
        }
    } else {
        echo "\n✓ Thư mục lưu trữ đã tồn tại: $storageDir\n";
    }

    echo "\n=== Hoàn tất ===\n";
} catch (Throwable $e) {
    exit('ERROR: ' . $e->getMessage() . "\n");
}
