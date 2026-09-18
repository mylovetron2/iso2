<?php
declare(strict_types=1);

// Xoa file nay sau khi kiem tra xong vi file chua thong tin ket noi database.
error_reporting(E_ALL);
ini_set('display_errors', '1');

$databases = [
    'Database cu' => [
        'host' => '118.69.204.200',
        'user' => 'diavatly_master',
        'pass' => '12345678',
        'name' => 'diavatly_db',
        'port' => 3306,
        'charset' => 'latin1',
    ],
    'Database moi' => [
        'host' => 'localhost',
        'user' => 'mapselli676e_iso2',
        'pass' => 'cntt2019@cntt2025',
        'name' => 'mapselli676e_iso2',
        'port' => 3306,
        'charset' => 'latin1',
    ],
];

$importantTables = [
    'hososcbd_iso',
    'thietbi_iso',
    'thietbihckd_iso',
    'ke_hoach_bao_duong_dinh_ky_iso',
    'kehoach_kiemdinh_2026_iso',
    'hosohckd_iso',
];

function htmlEscape(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function connectDatabase(array $config): PDO
{
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['name'],
        $config['charset']
    );

    return new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function quoteIdentifier(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function getTables(PDO $db, string $database): array
{
    $stmt = $db->prepare(
        'SELECT TABLE_NAME FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME'
    );
    $stmt->execute([$database]);

    return array_column($stmt->fetchAll(), 'TABLE_NAME');
}

function getColumns(PDO $db, string $database, string $table): array
{
    $stmt = $db->prepare(
        'SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, EXTRA
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
         ORDER BY ORDINAL_POSITION'
    );
    $stmt->execute([$database, $table]);

    return $stmt->fetchAll();
}

function getRowCount(PDO $db, string $table): int
{
    return (int)$db->query('SELECT COUNT(*) FROM ' . quoteIdentifier($table))->fetchColumn();
}

$connections = [];
$tables = [];
$errors = [];

foreach ($databases as $label => $config) {
    try {
        $connections[$label] = connectDatabase($config);
        $tables[$label] = getTables($connections[$label], $config['name']);
    } catch (Throwable $error) {
        $errors[$label] = $error->getMessage();
    }
}

$oldTables = $tables['Database cu'] ?? [];
$newTables = $tables['Database moi'] ?? [];
$allTables = array_values(array_unique(array_merge($oldTables, $newTables)));
sort($allTables);
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đối chiếu hai database</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #1f2937; }
        table { border-collapse: collapse; width: 100%; margin: 12px 0 28px; }
        th, td { border: 1px solid #cbd5e1; padding: 8px; text-align: left; }
        th { background: #e2e8f0; }
        .ok { background: #dcfce7; }
        .bad { background: #fee2e2; }
        .warn { background: #fef3c7; padding: 12px; border-left: 4px solid #f59e0b; }
        .error { color: #991b1b; white-space: pre-wrap; }
        code { background: #f1f5f9; padding: 2px 4px; }
    </style>
</head>
<body>
<h1>Đối chiếu Database cũ và Database mới</h1>

<?php if ($errors): ?>
    <h2>Lỗi kết nối</h2>
    <?php foreach ($errors as $label => $error): ?>
        <p class="error"><strong><?= htmlEscape($label) ?>:</strong> <?= htmlEscape($error) ?></p>
    <?php endforeach; ?>
<?php endif; ?>

<h2>1. Danh sách bảng</h2>
<table>
    <tr><th>Bảng</th><th>Database cũ</th><th>Database mới</th><th>Kết quả</th></tr>
    <?php foreach ($allTables as $table): ?>
        <?php
        $inOld = in_array($table, $oldTables, true);
        $inNew = in_array($table, $newTables, true);
        $same = $inOld === $inNew;
        ?>
        <tr class="<?= $same ? 'ok' : 'bad' ?>">
            <td><?= htmlEscape($table) ?></td>
            <td><?= $inOld ? 'Có' : 'Thiếu' ?></td>
            <td><?= $inNew ? 'Có' : 'Thiếu' ?></td>
            <td><?= $same ? 'Khớp' : 'Không khớp' ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<h2>2. So sánh số dòng</h2>
<table>
    <tr><th>Bảng</th><th>Database cũ</th><th>Database mới</th><th>Chênh lệch mới - cũ</th><th>Kết quả</th></tr>
    <?php foreach ($allTables as $table): ?>
        <?php
        $oldCount = null;
        $newCount = null;
        try {
            if ($inOld = in_array($table, $oldTables, true)) {
                $oldCount = getRowCount($connections['Database cu'], $table);
            }
            if ($inNew = in_array($table, $newTables, true)) {
                $newCount = getRowCount($connections['Database moi'], $table);
            }
        } catch (Throwable $error) {
            $oldCount = $oldCount ?? 'Lỗi';
            $newCount = $newCount ?? 'Lỗi';
        }
        $same = is_int($oldCount) && is_int($newCount) && $oldCount === $newCount;
        $difference = is_int($oldCount) && is_int($newCount) ? $newCount - $oldCount : '-';
        ?>
        <tr class="<?= $same ? 'ok' : 'bad' ?>">
            <td><?= htmlEscape($table) ?></td>
            <td><?= htmlEscape($oldCount ?? 'Không có bảng') ?></td>
            <td><?= htmlEscape($newCount ?? 'Không có bảng') ?></td>
            <td><?= htmlEscape($difference) ?></td>
            <td><?= $same ? 'Khớp' : 'Cần kiểm tra' ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<h2>3. Cấu trúc các bảng quan trọng</h2>
<?php foreach ($importantTables as $table): ?>
    <?php
    $oldColumns = in_array($table, $oldTables, true)
        ? getColumns($connections['Database cu'], $databases['Database cu']['name'], $table)
        : [];
    $newColumns = in_array($table, $newTables, true)
        ? getColumns($connections['Database moi'], $databases['Database moi']['name'], $table)
        : [];
    $oldByName = [];
    $newByName = [];
    foreach ($oldColumns as $column) $oldByName[$column['COLUMN_NAME']] = $column;
    foreach ($newColumns as $column) $newByName[$column['COLUMN_NAME']] = $column;
    $columnNames = array_values(array_unique(array_merge(array_keys($oldByName), array_keys($newByName))));
    $structureSame = count($oldColumns) === count($newColumns);
    foreach ($columnNames as $columnName) {
        $structureSame = $structureSame && ($oldByName[$columnName] ?? null) === ($newByName[$columnName] ?? null);
    }
    ?>
    <h3><?= htmlEscape($table) ?></h3>
    <p class="<?= $structureSame ? 'ok' : 'bad' ?>">
        <?= $structureSame ? 'Cấu trúc khớp' : 'Cấu trúc không khớp' ?>
    </p>
    <?php if (!$structureSame): ?>
        <table>
            <tr><th>Cột</th><th>Database cũ</th><th>Database mới</th></tr>
            <?php foreach ($columnNames as $columnName): ?>
                <tr>
                    <td><?= htmlEscape($columnName) ?></td>
                    <td><?= htmlEscape($oldByName[$columnName]['COLUMN_TYPE'] ?? 'Thiếu') ?></td>
                    <td><?= htmlEscape($newByName[$columnName]['COLUMN_TYPE'] ?? 'Thiếu') ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
<?php endforeach; ?>

<h2>4. Kiểm tra khóa nghiệp vụ bị trùng trong Database mới</h2>
<?php
$duplicateChecks = [
    'thietbi_iso' => 'SELECT mavt, somay, COUNT(*) AS total FROM thietbi_iso GROUP BY mavt, somay HAVING COUNT(*) > 1',
    'thietbihckd_iso' => 'SELECT mavattu, somay, COUNT(*) AS total FROM thietbihckd_iso GROUP BY mavattu, somay HAVING COUNT(*) > 1',
];
foreach ($duplicateChecks as $table => $sql):
    $duplicateRows = [];
    if (isset($connections['Database moi']) && in_array($table, $newTables, true)) {
        try { $duplicateRows = $connections['Database moi']->query($sql)->fetchAll(); } catch (Throwable) { }
    }
    ?>
    <h3><?= htmlEscape($table) ?></h3>
    <?php if (!$duplicateRows): ?>
        <p class="ok">Không phát hiện khóa bị trùng.</p>
    <?php else: ?>
        <p class="bad">Phát hiện <?= count($duplicateRows) ?> khóa bị trùng.</p>
        <table><tr><?php foreach (array_keys($duplicateRows[0]) as $key): ?><th><?= htmlEscape($key) ?></th><?php endforeach; ?></tr>
        <?php foreach ($duplicateRows as $row): ?><tr><?php foreach ($row as $value): ?><td><?= htmlEscape($value) ?></td><?php endforeach; ?></tr><?php endforeach; ?></table>
    <?php endif; ?>
<?php endforeach; ?>

<p class="warn">Kiểm tra xong phải xóa file <code>compare_databases.php</code> khỏi server và đổi mật khẩu database.</p>
</body>
</html>
