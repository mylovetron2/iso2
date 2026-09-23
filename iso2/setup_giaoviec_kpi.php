<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/config/database.php';
requireAuth();
if (!hasRole(ROLE_ADMIN)) { die('Chi admin duoc chay setup nay.'); }

try {
    $migrationFile = __DIR__ . '/migrations/create_giaoviec_kpi_tables.sql';
    if (!is_readable($migrationFile)) {
        throw new RuntimeException('Khong tim thay file migration: ' . $migrationFile);
    }
    $sql = file_get_contents($migrationFile);
    $db = getDBConnection();
    $db->exec($sql);
    echo "<pre style='font-family:monospace;color:green'>OK - Da tao bang giaoviec_kpi va giaoviec_kpi_nguoi.\n\nQuay lai: <a href='giaoviec_kpi.php'>giaoviec_kpi.php</a></pre>";
} catch (Throwable $e) {
    echo "<pre style='color:red'>Loi: " . htmlspecialchars($e->getMessage()) . "</pre>";
}
