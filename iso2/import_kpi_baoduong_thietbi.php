<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';
requireAuth();

if (!hasPermission('kehoachbaoduong.create')) {
    http_response_code(403);
    die('Khong co quyen import du lieu bao duong');
}

require_once __DIR__ . '/config/database.php';

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use PhpOffice\PhpSpreadsheet\IOFactory;

$success = null;
$error = null;
$importStats = null;

function normalizeImportValue(mixed $value): ?string
{
    if ($value === null) {
        return null;
    }

    $text = trim((string)$value);
    if ($text === '') {
        return null;
    }

    if ($text === '-' || $text === '–' || strtoupper($text) === 'N/A') {
        return null;
    }

    return $text;
}

function parseDecimalCell(mixed $value): ?float
{
    $normalized = normalizeImportValue($value);
    if ($normalized === null) {
        return null;
    }

    $normalized = str_replace(',', '.', $normalized);
    return is_numeric($normalized) ? (float)$normalized : null;
}

function parseIntegerCell(mixed $value): ?int
{
    $decimal = parseDecimalCell($value);
    return $decimal === null ? null : (int)$decimal;
}

function ensureKpiBaoDuongTableExists(PDO $db): void
{
    $check = $db->query("SHOW TABLES LIKE 'kpi_baoduong_thietbi_iso'");
    if ($check !== false && $check->fetch()) {
        return;
    }

    $migrationPath = __DIR__ . '/migrations/20260714_create_kpi_baoduong_thietbi_iso.sql';
    if (!is_file($migrationPath)) {
        throw new RuntimeException('Khong tim thay file migration tao bang kpi_baoduong_thietbi_iso');
    }

    $sql = trim((string)file_get_contents($migrationPath));
    if ($sql === '') {
        throw new RuntimeException('File migration tao bang KPI rong');
    }

    $db->exec($sql);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        $error = 'Khong tim thay thu vien PhpSpreadsheet';
        goto render;
    }

    $uploadedFile = $_FILES['excel_file'];

    if (($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $error = 'Loi upload file Excel';
        goto render;
    }

    $extension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['xlsx', 'xls'], true)) {
        $error = 'Chi chap nhan file .xlsx hoac .xls';
        goto render;
    }

    try {
        $spreadsheet = IOFactory::load($uploadedFile['tmp_name']);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, false, false);

        if (count($rows) < 3) {
            throw new RuntimeException('File Excel khong dung dinh dang mong doi');
        }

        $rows = array_slice($rows, 2);

        $db = getDBConnection();
        ensureKpiBaoDuongTableExists($db);
        $db->beginTransaction();

        if (isset($_POST['clear_existing']) && $_POST['clear_existing'] === '1') {
            $db->exec('DELETE FROM kpi_baoduong_thietbi_iso');
        }

        $sql = 'INSERT INTO kpi_baoduong_thietbi_iso (
                    stt_hien_thi,
                    ten_thiet_bi,
                    kiem_tra_nhan_cong,
                    kiem_tra_so_gio,
                    kiem_tra_nguoi_thuc_hien,
                    kiem_tra_noi_dung,
                    bd_cap_1_nhan_cong,
                    bd_cap_1_so_gio,
                    bd_cap_1_nguoi_thuc_hien,
                    bd_cap_1_noi_dung,
                    bd_cap_2_tan_suat_thang,
                    bd_cap_2_nhan_cong,
                    bd_cap_2_so_gio,
                    bd_cap_2_nguoi_thuc_hien,
                    bd_cap_2_noi_dung,
                    bd_cap_3_tan_suat_thang,
                    bd_cap_3_nhan_cong,
                    bd_cap_3_so_gio,
                    bd_cap_3_nguoi_thuc_hien,
                    bd_cap_3_noi_dung,
                    hieu_chuan_tan_suat_thang,
                    hieu_chuan_nhan_cong,
                    hieu_chuan_so_gio,
                    hieu_chuan_nguoi_thuc_hien,
                    hieu_chuan_noi_dung,
                    ghi_chu,
                    created_by
                ) VALUES (
                    :stt_hien_thi,
                    :ten_thiet_bi,
                    :kiem_tra_nhan_cong,
                    :kiem_tra_so_gio,
                    :kiem_tra_nguoi_thuc_hien,
                    :kiem_tra_noi_dung,
                    :bd_cap_1_nhan_cong,
                    :bd_cap_1_so_gio,
                    :bd_cap_1_nguoi_thuc_hien,
                    :bd_cap_1_noi_dung,
                    :bd_cap_2_tan_suat_thang,
                    :bd_cap_2_nhan_cong,
                    :bd_cap_2_so_gio,
                    :bd_cap_2_nguoi_thuc_hien,
                    :bd_cap_2_noi_dung,
                    :bd_cap_3_tan_suat_thang,
                    :bd_cap_3_nhan_cong,
                    :bd_cap_3_so_gio,
                    :bd_cap_3_nguoi_thuc_hien,
                    :bd_cap_3_noi_dung,
                    :hieu_chuan_tan_suat_thang,
                    :hieu_chuan_nhan_cong,
                    :hieu_chuan_so_gio,
                    :hieu_chuan_nguoi_thuc_hien,
                    :hieu_chuan_noi_dung,
                    :ghi_chu,
                    :created_by
                )';
        $stmt = $db->prepare($sql);

        $imported = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $tenThietBi = normalizeImportValue($row[1] ?? null);
            if ($tenThietBi === null) {
                $allEmpty = true;
                foreach ($row as $cell) {
                    if (normalizeImportValue($cell) !== null) {
                        $allEmpty = false;
                        break;
                    }
                }

                if ($allEmpty) {
                    continue;
                }

                $skipped++;
                continue;
            }

            $stmt->execute([
                ':stt_hien_thi' => parseIntegerCell($row[0] ?? null),
                ':ten_thiet_bi' => $tenThietBi,
                ':kiem_tra_nhan_cong' => parseDecimalCell($row[2] ?? null),
                ':kiem_tra_so_gio' => parseDecimalCell($row[3] ?? null),
                ':kiem_tra_nguoi_thuc_hien' => normalizeImportValue($row[4] ?? null),
                ':kiem_tra_noi_dung' => normalizeImportValue($row[5] ?? null),
                ':bd_cap_1_nhan_cong' => parseDecimalCell($row[6] ?? null),
                ':bd_cap_1_so_gio' => parseDecimalCell($row[7] ?? null),
                ':bd_cap_1_nguoi_thuc_hien' => normalizeImportValue($row[8] ?? null),
                ':bd_cap_1_noi_dung' => normalizeImportValue($row[9] ?? null),
                ':bd_cap_2_tan_suat_thang' => parseIntegerCell($row[10] ?? null),
                ':bd_cap_2_nhan_cong' => parseDecimalCell($row[11] ?? null),
                ':bd_cap_2_so_gio' => parseDecimalCell($row[12] ?? null),
                ':bd_cap_2_nguoi_thuc_hien' => normalizeImportValue($row[13] ?? null),
                ':bd_cap_2_noi_dung' => normalizeImportValue($row[14] ?? null),
                ':bd_cap_3_tan_suat_thang' => parseIntegerCell($row[15] ?? null),
                ':bd_cap_3_nhan_cong' => parseDecimalCell($row[16] ?? null),
                ':bd_cap_3_so_gio' => parseDecimalCell($row[17] ?? null),
                ':bd_cap_3_nguoi_thuc_hien' => normalizeImportValue($row[18] ?? null),
                ':bd_cap_3_noi_dung' => normalizeImportValue($row[19] ?? null),
                ':hieu_chuan_tan_suat_thang' => parseIntegerCell($row[20] ?? null),
                ':hieu_chuan_nhan_cong' => parseDecimalCell($row[21] ?? null),
                ':hieu_chuan_so_gio' => parseDecimalCell($row[22] ?? null),
                ':hieu_chuan_nguoi_thuc_hien' => normalizeImportValue($row[23] ?? null),
                ':hieu_chuan_noi_dung' => normalizeImportValue($row[24] ?? null),
                ':ghi_chu' => normalizeImportValue($row[25] ?? null),
                ':created_by' => $_SESSION['user']['username'] ?? 'system',
            ]);

            $imported++;
        }

        $db->commit();

        $importStats = [
            'imported' => $imported,
            'skipped' => $skipped,
        ];
        $success = "Da import thanh cong {$imported} dong du lieu";
    } catch (Throwable $e) {
        if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
            $db->rollBack();
        }
        $error = 'Loi import: ' . $e->getMessage();
    }
}

render:
$title = 'Import KPI Bao duong thiet bi';
require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold flex items-center">
                <i class="fas fa-file-import mr-2 text-green-600"></i> Import KPI bao duong thiet bi
            </h1>
            <a href="dashboard.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Quay lai
            </a>
        </div>

        <?php if ($success !== null): ?>
            <div class="bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded mb-6">
                <div class="font-semibold"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php if ($importStats !== null): ?>
                    <div class="text-sm mt-1">
                        So dong bo qua: <?php echo (int)$importStats['skipped']; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== null): ?>
            <div class="bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
            <h3 class="font-semibold text-blue-900 mb-2">
                <i class="fas fa-info-circle mr-1"></i> Huong dan import
            </h3>
            <ol class="list-decimal list-inside space-y-1 text-sm text-blue-800">
                <li>Tai file mau Excel dung cau truc 2 dong header.</li>
                <li>Du lieu bat dau tu dong 3, giu nguyen thu tu cot nhu bieu mau.</li>
                <li>Gia tri "-" hoac o trong se duoc luu thanh rong.</li>
                <li>Neu chon xoa du lieu cu, he thong se xoa toan bo bang KPI bao duong truoc khi import lai.</li>
            </ol>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="font-semibold mb-4">
                <i class="fas fa-download mr-2 text-purple-600"></i> Tai file mau Excel
            </h3>
            <a href="download_template_kpi_baoduong_thietbi.php" class="inline-block bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded">
                <i class="fas fa-file-excel mr-2"></i> Tai file mau
            </a>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="font-semibold mb-4">
                <i class="fas fa-upload mr-2 text-green-600"></i> Upload file Excel
            </h3>

            <form method="POST" enctype="multipart/form-data">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">File Excel <span class="text-red-500">*</span></label>
                        <input type="file" name="excel_file" accept=".xlsx,.xls" required class="w-full border rounded px-3 py-2">
                        <p class="text-xs text-gray-500 mt-1">Chap nhan file .xlsx hoac .xls, du lieu doc tu sheet dang active.</p>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="clear_existing" value="1" id="clearExisting" class="mr-2">
                        <label for="clearExisting" class="text-sm">Xoa du lieu cu truoc khi import</label>
                    </div>

                    <div class="pt-4 border-t flex gap-2">
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded">
                            <i class="fas fa-upload mr-1"></i> Import du lieu
                        </button>
                        <a href="download_template_kpi_baoduong_thietbi.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded">
                            <i class="fas fa-file-download mr-1"></i> Tai mau
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold mb-4">
                <i class="fas fa-table mr-2 text-blue-600"></i> Cau truc cot du lieu
            </h3>
            <div class="overflow-x-auto">
                <table class="min-w-full border text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-1">A</th>
                            <th class="border px-2 py-1">B</th>
                            <th class="border px-2 py-1">C-F</th>
                            <th class="border px-2 py-1">G-J</th>
                            <th class="border px-2 py-1">K-O</th>
                            <th class="border px-2 py-1">P-T</th>
                            <th class="border px-2 py-1">U-Y</th>
                            <th class="border px-2 py-1">Z</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border px-2 py-1">STT</td>
                            <td class="border px-2 py-1">Ten thiet bi</td>
                            <td class="border px-2 py-1">Kiem tra thiet bi</td>
                            <td class="border px-2 py-1">BD cap 1</td>
                            <td class="border px-2 py-1">BD cap 2</td>
                            <td class="border px-2 py-1">BD cap 3</td>
                            <td class="border px-2 py-1">Hieu chuan thiet bi</td>
                            <td class="border px-2 py-1">Ghi chu</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>