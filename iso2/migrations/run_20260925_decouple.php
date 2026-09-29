<?php
declare(strict_types=1);

/**
 * Chay migration 20260925: tach hososcbd_dinhmuc_iso khoi kpi_baoduong_thietbi_iso.
 *
 * Su dung:
 *   php migrations/run_20260925_decouple.php           # chay migration
 *   php migrations/run_20260925_decouple.php rollback  # rollback
 *   php migrations/run_20260925_decouple.php verify    # chi kiem tra trang thai
 */

require_once __DIR__ . '/../config/database.php';

$mode = $argv[1] ?? 'migrate';
$fileMap = [
    'migrate'  => __DIR__ . '/20260925_decouple_hososcbd_dinhmuc_from_kpi.sql',
    'rollback' => __DIR__ . '/20260925_decouple_hososcbd_dinhmuc_from_kpi_ROLLBACK.sql',
];

$pdo = getDBConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($mode === 'verify') {
    verifyState($pdo);
    exit(0);
}

if (!isset($fileMap[$mode])) {
    fwrite(STDERR, "Mode khong hop le: $mode. Dung: migrate | rollback | verify\n");
    exit(2);
}

$sqlFile = $fileMap[$mode];
if (!is_file($sqlFile)) {
    fwrite(STDERR, "Khong tim thay file: $sqlFile\n");
    exit(2);
}

echo "=== " . strtoupper($mode) . ": " . basename($sqlFile) . " ===\n\n";

$sql = file_get_contents($sqlFile);

// Loai bo comment dong (-- ...) truoc khi split
$sqlNoComment = preg_replace('/^\s*--.*$/m', '', $sql);

$statements = array_values(array_filter(
    array_map('trim', preg_split('/;\s*\r?\n/', $sqlNoComment)),
    fn($s) => $s !== ''
));

echo "So statement: " . count($statements) . "\n\n";

$ok = 0; $fail = 0;
foreach ($statements as $i => $stmt) {
    $preview = preg_replace('/\s+/', ' ', substr($stmt, 0, 80));
    echo "[" . ($i + 1) . "] $preview...\n";
    try {
        // Voi cau lenh SELECT thi hien ket qua de kiem tra
        if (preg_match('/^\s*SELECT\b/i', $stmt)) {
            $rows = $pdo->query($stmt)->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                echo "    -> " . json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
            }
        } else {
            $pdo->exec($stmt);
        }
        $ok++;
        echo "    OK\n\n";
    } catch (Throwable $e) {
        $fail++;
        echo "    FAIL: " . $e->getMessage() . "\n\n";
    }
}

echo "=== Ket qua: OK=$ok, FAIL=$fail ===\n";
verifyState($pdo);

exit($fail === 0 ? 0 : 1);


function verifyState(PDO $pdo): void
{
    echo "\n--- Trang thai hien tai ---\n";

    // 1. FK kpi_baoduong_stt con ton tai khong?
    $fk = $pdo->query("
        SELECT CONSTRAINT_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'hososcbd_dinhmuc_iso'
          AND COLUMN_NAME  = 'kpi_baoduong_stt'
          AND REFERENCED_TABLE_NAME IS NOT NULL
    ")->fetchColumn();
    echo "FK kpi_baoduong_stt: " . ($fk ? "CON ($fk)" : "DA DROP") . "\n";

    // 2. Cot dinh_muc_gio_thu_cong da co du du lieu chua?
    $stats = $pdo->query("
        SELECT
            COUNT(*) AS tong,
            SUM(CASE WHEN dinh_muc_gio_thu_cong IS NULL OR dinh_muc_gio_thu_cong = 0 THEN 1 ELSE 0 END) AS thieu_gio,
            SUM(CASE WHEN kpi_baoduong_stt IS NOT NULL THEN 1 ELSE 0 END) AS co_kpi_stt
        FROM hososcbd_dinhmuc_iso
    ")->fetch(PDO::FETCH_ASSOC);
    echo "hososcbd_dinhmuc_iso: tong=" . $stats['tong']
        . ", thieu_gio=" . $stats['thieu_gio']
        . ", co_kpi_stt=" . $stats['co_kpi_stt'] . "\n";

    // 3. Snapshot ton tai?
    $bak = $pdo->query("SHOW TABLES LIKE '_bak_hososcbd_dinhmuc_iso_20260925'")->fetchColumn();
    if ($bak) {
        $bakCount = $pdo->query("SELECT COUNT(*) FROM _bak_hososcbd_dinhmuc_iso_20260925")->fetchColumn();
        echo "Snapshot _bak_..._20260925: CO ($bakCount dong)\n";
    } else {
        echo "Snapshot _bak_..._20260925: CHUA CO\n";
    }
}
