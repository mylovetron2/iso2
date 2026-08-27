<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

const BACKUP_DATABASES = [
    'diavatly_db',
    'diavatly_quanly',
    'diavatly_ltd',
];

function backupDirPath(): string
{
    return __DIR__ . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'database_backups';
}

function formatBackupBytes(int $bytes, int $precision = 2): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }

    return round($bytes, $precision) . ' ' . $units[$i];
}

function ensureBackupDir(): string
{
    $backupDir = backupDirPath();
    if (!is_dir($backupDir) && !mkdir($backupDir, 0755, true)) {
        throw new RuntimeException("Khong tao duoc thu muc backup: {$backupDir}");
    }

    return $backupDir;
}

function quoteIdentifier(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function createBackupConnection(string $database, string $username, string $password): PDO
{
    return new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . $database . ';charset=' . DB_CHARSET,
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . DB_CHARSET,
        ]
    );
}

function normalizeSelectedDatabases(array $selectedDatabases): array
{
    $selectedDatabases = array_values(array_intersect(BACKUP_DATABASES, $selectedDatabases));

    if (empty($selectedDatabases)) {
        throw new InvalidArgumentException('Vui lòng chọn ít nhất 1 database để backup.');
    }

    return $selectedDatabases;
}

function sqlValue(PDO $pdo, $value): string
{
    if ($value === null) {
        return 'NULL';
    }

    return $pdo->quote((string)$value);
}

function dumpDatabaseToFile(string $database, string $username, string $password, string $outputFile): void
{
    $pdo = createBackupConnection($database, $username, $password);
    $handle = fopen($outputFile, 'wb');
    if ($handle === false) {
        throw new RuntimeException("Khong tao duoc file backup: {$outputFile}");
    }

    try {
        fwrite($handle, "-- Database Backup\n");
        fwrite($handle, '-- Generated: ' . date('Y-m-d H:i:s') . "\n");
        fwrite($handle, "-- Database: {$database}\n");
        fwrite($handle, "-- Host: " . DB_HOST . "\n\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n");
        fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
        fwrite($handle, "START TRANSACTION;\n\n");
        fwrite($handle, 'CREATE DATABASE IF NOT EXISTS ' . quoteIdentifier($database) . ";\n");
        fwrite($handle, 'USE ' . quoteIdentifier($database) . ";\n\n");

        $tableStmt = $pdo->query('SHOW FULL TABLES');
        while ($tableInfo = $tableStmt->fetch(PDO::FETCH_NUM)) {
            $table = (string)$tableInfo[0];
            $tableType = strtoupper((string)($tableInfo[1] ?? 'BASE TABLE'));

            if ($tableType !== 'BASE TABLE') {
                continue;
            }

            $createStmt = $pdo->query('SHOW CREATE TABLE ' . quoteIdentifier($table));
            $createTable = $createStmt->fetch(PDO::FETCH_ASSOC);
            $createSql = $createTable['Create Table'] ?? array_values($createTable)[1] ?? '';

            fwrite($handle, "-- --------------------------------------------------------\n");
            fwrite($handle, "-- Table structure for table `{$table}`\n\n");
            fwrite($handle, 'DROP TABLE IF EXISTS ' . quoteIdentifier($table) . ";\n");
            fwrite($handle, $createSql . ";\n\n");

            $dataStmt = $pdo->query('SELECT * FROM ' . quoteIdentifier($table));
            $firstRow = $dataStmt->fetch(PDO::FETCH_ASSOC);
            if ($firstRow === false) {
                continue;
            }

            $columns = array_keys($firstRow);
            $columnList = implode(', ', array_map('quoteIdentifier', $columns));

            fwrite($handle, "-- Dumping data for table `{$table}`\n\n");

            $writeRow = static function (array $row) use ($pdo, $handle, $table, $columnList): void {
                $values = array_map(static fn($value): string => sqlValue($pdo, $value), array_values($row));
                fwrite($handle, 'INSERT INTO ' . quoteIdentifier($table) . " ({$columnList}) VALUES (" . implode(', ', $values) . ");\n");
            };

            $writeRow($firstRow);
            while ($row = $dataStmt->fetch(PDO::FETCH_ASSOC)) {
                $writeRow($row);
            }

            fwrite($handle, "\n");
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
        fwrite($handle, "COMMIT;\n");
    } finally {
        fclose($handle);
    }
}

function runDatabaseBackups(?array $selectedDatabases = null, ?string $username = null, ?string $password = null): array
{
    $backupDir = ensureBackupDir();
    $timestamp = date('Ymd_His');
    $success = [];
    $failed = [];
    $selectedDatabases = normalizeSelectedDatabases($selectedDatabases ?? BACKUP_DATABASES);
    $username = $username ?? DB_USER;
    $password = $password ?? DB_PASS;

    if (function_exists('set_time_limit')) {
        set_time_limit(0);
    }

    foreach ($selectedDatabases as $database) {
        $outputFile = $backupDir . DIRECTORY_SEPARATOR . $database . '_' . $timestamp . '.sql';

        try {
            dumpDatabaseToFile($database, $username, $password, $outputFile);
        } catch (Throwable $e) {
            if (file_exists($outputFile)) {
                unlink($outputFile);
            }

            $failed[] = [
                'database' => $database,
                'message' => $e->getMessage(),
            ];

            continue;
        }

        $success[] = [
            'database' => $database,
            'file' => basename($outputFile),
            'path' => $outputFile,
            'size' => filesize($outputFile) ?: 0,
            'created_at' => filemtime($outputFile) ?: time(),
        ];
    }

    return [
        'success' => $success,
        'failed' => $failed,
        'selected_count' => count($selectedDatabases),
    ];
}

function listDatabaseBackups(): array
{
    $backupDir = backupDirPath();
    if (!is_dir($backupDir)) {
        return [];
    }

    $files = glob($backupDir . DIRECTORY_SEPARATOR . '*.sql') ?: [];
    $backups = [];

    foreach ($files as $file) {
        $backups[] = [
            'name' => basename($file),
            'size' => filesize($file) ?: 0,
            'date' => filemtime($file) ?: 0,
        ];
    }

    usort($backups, static fn(array $a, array $b): int => $b['date'] <=> $a['date']);

    return $backups;
}

if (PHP_SAPI === 'cli') {
    try {
        $results = runDatabaseBackups();
        foreach ($results['success'] as $result) {
            echo "Da backup {$result['database']}: {$result['path']}" . PHP_EOL;
        }
        foreach ($results['failed'] as $failed) {
            fwrite(STDERR, "Backup that bai database {$failed['database']}. {$failed['message']}" . PHP_EOL);
        }
        echo 'Hoan tat backup database. Thanh cong: ' . count($results['success']) . '/' . $results['selected_count'] . PHP_EOL;
        exit(empty($results['success']) ? 1 : 0);
    } catch (Throwable $e) {
        fwrite(STDERR, $e->getMessage() . PHP_EOL);
        exit(1);
    }
}

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/includes/permissions.php';

requireAuth();
if (!hasRole(ROLE_ADMIN) && !hasPermission('backup.view')) {
    header('Location: ' . BASE_URL . '/hososcbd.php?error=no_permission');
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'download') {
    if (!hasRole(ROLE_ADMIN) && !hasPermission('backup.download')) {
        $_SESSION['error'] = 'Bạn không có quyền tải file backup!';
        header('Location: backup_databases.php');
        exit;
    }

    $fileName = basename((string)($_GET['file'] ?? ''));
    $filePath = backupDirPath() . DIRECTORY_SEPARATOR . $fileName;

    if ($fileName === '' || !is_file($filePath) || pathinfo($filePath, PATHINFO_EXTENSION) !== 'sql') {
        $_SESSION['error'] = 'Không tìm thấy file backup cần tải.';
        header('Location: backup_databases.php');
        exit;
    }

    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    readfile($filePath);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_backup') {
    if (!hasRole(ROLE_ADMIN) && !hasPermission('backup.create')) {
        $_SESSION['error'] = 'Bạn không có quyền tạo backup!';
        header('Location: backup_databases.php');
        exit;
    }

    try {
        $selectedDatabases = normalizeSelectedDatabases(array_map('strval', (array)($_POST['databases'] ?? [])));
        $backupUsername = trim((string)($_POST['db_user'] ?? ''));
        $backupPassword = (string)($_POST['db_pass'] ?? '');

        if ($backupUsername === '') {
            throw new InvalidArgumentException('Vui lòng nhập user database.');
        }

        if ($backupPassword === '') {
            throw new InvalidArgumentException('Vui lòng nhập password database.');
        }

        $_SESSION['backup_form_databases'] = $selectedDatabases;
        $_SESSION['backup_form_username'] = $backupUsername;

        $results = runDatabaseBackups($selectedDatabases, $backupUsername, $backupPassword);
        $_SESSION['backup_multi_result'] = $results;

        if (!empty($results['success'])) {
            $_SESSION['success'] = 'Đã tạo backup thành công cho ' . count($results['success']) . '/' . $results['selected_count'] . ' database đã chọn.';
        }

        if (!empty($results['failed'])) {
            $failedNames = array_map(static fn(array $item): string => $item['database'], $results['failed']);
            $_SESSION['error'] = 'Một số database chưa backup được: ' . implode(', ', $failedNames) . '. Vui lòng kiểm tra quyền truy cập database.';
        }
    } catch (Throwable $e) {
        $_SESSION['error'] = $e->getMessage();
    }

    header('Location: backup_databases.php');
    exit;
}

$backupResults = $_SESSION['backup_multi_result'] ?? [];
unset($_SESSION['backup_multi_result']);
$successfulBackups = $backupResults['success'] ?? [];
$failedBackups = $backupResults['failed'] ?? [];
$selectedDatabasesForForm = $_SESSION['backup_form_databases'] ?? BACKUP_DATABASES;
$backupUsernameForForm = $_SESSION['backup_form_username'] ?? DB_USER;

$existingBackups = listDatabaseBackups();
$title = 'Backup nhiều Database';
require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="max-w-6xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="mb-6 pb-4 border-b">
            <div class="flex flex-wrap gap-2 items-center mb-4">
                <h1 class="text-2xl font-bold flex items-center mr-4">
                    <i class="fas fa-database mr-3 text-blue-600"></i>
                    Backup nhiều Database
                </h1>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="/iso2/admin_backup.php" class="bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded text-sm">
                    <i class="fas fa-download mr-1"></i> Backup ISO2
                </a>
                <a href="/iso2/admin_database_switch.php" class="bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-2 rounded text-sm">
                    <i class="fas fa-database mr-1"></i> Chuyển DB
                </a>
                <a href="/iso2/hososcbd.php" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded text-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Trang chủ
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-6 p-4 rounded bg-green-100 border border-green-400 text-green-700">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-6 p-4 rounded bg-red-100 border border-red-400 text-red-700">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($successfulBackups)): ?>
            <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                <h2 class="text-lg font-semibold text-green-800 mb-3 flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    File vừa tạo
                </h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border">
                        <thead class="bg-green-50">
                            <tr>
                                <th class="px-4 py-2 border text-left text-sm font-semibold">Database</th>
                                <th class="px-4 py-2 border text-left text-sm font-semibold">File</th>
                                <th class="px-4 py-2 border text-left text-sm font-semibold">Kích thước</th>
                                <th class="px-4 py-2 border text-left text-sm font-semibold">Tải xuống</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($successfulBackups as $result): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 border text-sm font-mono"><?php echo htmlspecialchars($result['database']); ?></td>
                                    <td class="px-4 py-2 border text-sm font-mono"><?php echo htmlspecialchars($result['file']); ?></td>
                                    <td class="px-4 py-2 border text-sm"><?php echo formatBackupBytes((int)$result['size']); ?></td>
                                    <td class="px-4 py-2 border text-sm">
                                        <a class="text-blue-600 hover:text-blue-800 font-semibold" href="backup_databases.php?action=download&amp;file=<?php echo urlencode($result['file']); ?>">
                                            <i class="fas fa-file-download mr-1"></i>Tải file
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($failedBackups)): ?>
            <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                <h2 class="text-lg font-semibold text-red-800 mb-3 flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    Database chưa backup được
                </h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border">
                        <thead class="bg-red-50">
                            <tr>
                                <th class="px-4 py-2 border text-left text-sm font-semibold">Database</th>
                                <th class="px-4 py-2 border text-left text-sm font-semibold">Thông báo lỗi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($failedBackups as $failed): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 border text-sm font-mono"><?php echo htmlspecialchars($failed['database']); ?></td>
                                    <td class="px-4 py-2 border text-sm text-red-700"><?php echo htmlspecialchars($failed['message']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p class="text-sm text-gray-600 mb-1">Host</p>
                <p class="text-xl font-bold text-blue-700"><?php echo htmlspecialchars(DB_HOST); ?></p>
                <p class="text-xs text-gray-500 mt-1">Nhập user/pass theo database cần backup</p>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-sm text-gray-600 mb-1">Số database</p>
                <p class="text-xl font-bold text-green-700"><?php echo count(BACKUP_DATABASES); ?></p>
                <p class="text-xs text-gray-500 mt-1">Backup toàn bộ schema và dữ liệu</p>
            </div>
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                <p class="text-sm text-gray-600 mb-1">Công cụ</p>
                <p class="text-xl font-bold text-purple-700">PHP PDO</p>
                <p class="text-xs text-gray-500 mt-1">Không cần bật hàm exec trên hosting</p>
            </div>
        </div>

        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
            <div class="flex items-start">
                <i class="fas fa-exclamation-triangle text-yellow-400 text-xl mt-0.5"></i>
                <div class="ml-3 text-sm text-yellow-800">
                    <h2 class="font-semibold mb-2">Lưu ý trước khi backup</h2>
                    <ul class="list-disc list-inside space-y-1 text-yellow-700">
                        <li>Script sẽ backup diavatly_db trước, sau đó mới đến diavatly_quanly và diavatly_ltd.</li>
                        <li>Quá trình có thể mất vài phút nếu dữ liệu lớn.</li>
                        <li>Nếu user hiện tại không có quyền vào database nào, database đó sẽ báo lỗi riêng và không làm mất file đã backup thành công.</li>
                        <li>Khi backup diavatly_ltd, nhập user diavatly_ltd và password tương ứng. Password chỉ dùng cho lần chạy hiện tại, không lưu lại trên form.</li>
                        <li>Backup chạy trực tiếp bằng PDO nên phù hợp với hosting tắt exec.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="mb-8 bg-gray-50 border border-gray-200 rounded-lg p-5">
            <form method="post" class="space-y-5" onsubmit="return confirm('Bạn có chắc muốn backup các database đã chọn? Quá trình có thể mất vài phút.');">
                <input type="hidden" name="action" value="create_backup">
                <div>
                    <h2 class="text-lg font-semibold mb-3 flex items-center">
                        <i class="fas fa-list-check mr-2"></i>
                        Chọn database cần backup
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <?php foreach (BACKUP_DATABASES as $database): ?>
                            <label class="flex items-center gap-3 bg-white border border-gray-200 rounded px-4 py-3 text-sm cursor-pointer hover:border-blue-300">
                                <input type="checkbox" name="databases[]" value="<?php echo htmlspecialchars($database); ?>" class="w-4 h-4 text-blue-600" <?php echo in_array($database, $selectedDatabasesForForm, true) ? 'checked' : ''; ?>>
                                <span class="flex-1">
                                    <span class="font-mono font-semibold block"><?php echo htmlspecialchars($database); ?></span>
                                    <?php if ($database === 'diavatly_db'): ?>
                                        <span class="text-xs text-green-700">Backup trước nếu được chọn</span>
                                    <?php elseif ($database === 'diavatly_ltd'): ?>
                                        <span class="text-xs text-gray-500">User gợi ý: diavatly_ltd</span>
                                    <?php endif; ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="db_user" class="block text-sm font-semibold text-gray-700 mb-1">User database</label>
                        <input type="text" id="db_user" name="db_user" value="<?php echo htmlspecialchars($backupUsernameForForm); ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" autocomplete="username" required>
                    </div>
                    <div>
                        <label for="db_pass" class="block text-sm font-semibold text-gray-700 mb-1">Password database</label>
                        <input type="password" id="db_pass" name="db_pass" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" autocomplete="current-password" required>
                    </div>
                </div>

                <div class="flex justify-center">
                    <button type="submit" class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg text-lg font-semibold shadow-lg hover:shadow-xl transition-all">
                        <i class="fas fa-download mr-3"></i>
                        Tạo backup database đã chọn
                    </button>
                </div>
            </form>
        </div>

        <div class="mb-8">
            <h2 class="text-lg font-semibold mb-4 flex items-center">
                <i class="fas fa-list mr-2"></i>
                Thứ tự backup khi chọn nhiều database
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <?php foreach (BACKUP_DATABASES as $database): ?>
                    <div class="bg-gray-50 border border-gray-200 rounded px-4 py-3 text-sm">
                        <i class="fas fa-database text-blue-500 mr-2"></i>
                        <span class="font-mono font-semibold"><?php echo htmlspecialchars($database); ?></span>
                        <?php if ($database === 'diavatly_db'): ?>
                            <span class="ml-2 text-xs bg-green-100 text-green-700 px-2 py-1 rounded">backup trước</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div>
            <h2 class="text-lg font-semibold mb-4 flex items-center">
                <i class="fas fa-history mr-2"></i>
                File backup đã lưu (<?php echo count($existingBackups); ?>)
            </h2>

            <?php if (empty($existingBackups)): ?>
                <div class="bg-gray-50 border border-gray-200 rounded p-4 text-gray-600">
                    Chưa có file backup nào trong thư mục database_backups.
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 border text-left text-sm font-semibold">Tên file</th>
                                <th class="px-4 py-2 border text-left text-sm font-semibold">Kích thước</th>
                                <th class="px-4 py-2 border text-left text-sm font-semibold">Ngày tạo</th>
                                <th class="px-4 py-2 border text-left text-sm font-semibold">Tải xuống</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($existingBackups as $backup): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 border text-sm font-mono">
                                        <i class="fas fa-file-code text-blue-500 mr-2"></i>
                                        <?php echo htmlspecialchars($backup['name']); ?>
                                    </td>
                                    <td class="px-4 py-2 border text-sm"><?php echo formatBackupBytes((int)$backup['size']); ?></td>
                                    <td class="px-4 py-2 border text-sm"><?php echo date('d/m/Y H:i:s', (int)$backup['date']); ?></td>
                                    <td class="px-4 py-2 border text-sm">
                                        <a class="text-blue-600 hover:text-blue-800 font-semibold" href="backup_databases.php?action=download&amp;file=<?php echo urlencode($backup['name']); ?>">
                                            <i class="fas fa-file-download mr-1"></i>Tải file
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>