<?php
declare(strict_types=1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';
requireAuth();

if (!hasPermission('vattu.create')) {
    die('Không có quyền import vật tư');
}

require_once __DIR__ . '/config/database.php';

// Load PhpSpreadsheet nếu có
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Hàm chuyển đổi ngày từ Excel
 */
function convertExcelDate($value) {
    if (empty($value)) {
        return null;
    }
    
    // Nếu là số (Excel date serial)
    if (is_numeric($value)) {
        $unixDate = ($value - 25569) * 86400;
        return date('Y-m-d', $unixDate);
    }
    
    // Nếu là string (dd/mm/yyyy hoặc yyyy-mm-dd)
    if (is_string($value)) {
        // Thử format dd/mm/yyyy
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $matches)) {
            $day = (int)$matches[1];
            $month = (int)$matches[2];
            $year = (int)$matches[3];
            
            // Validate date
            if ($day >= 1 && $day <= 31 && $month >= 1 && $month <= 12) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }
        // Thử format yyyy-mm-dd
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }
    }
    
    return null;
}

// Nếu có POST, xử lý import
function vattuValuesEqual($old, $new): bool
{
    if ($old === null || $old === '') {
        return $new === null || $new === '';
    }
    if (is_numeric($old) && is_numeric($new)) {
        return abs((float)$old - (float)$new) < 0.005;
    }
    return trim((string)$old) === trim((string)$new);
}

function formatVattuPreviewValue(string $field, $value, array $phanLoaiNames): string
{
    if ($value === null || $value === '') {
        return '-';
    }
    switch ($field) {
        case 'phanloai_id':
            return (string)($phanLoaiNames[$value] ?? $value);
        case 'dongia':
            return number_format((float)$value);
        case 'dongia_usd':
            return number_format((float)$value, 2);
        case 'soluong_conlai':
            return rtrim(rtrim(number_format((float)$value, 2, '.', ''), '0'), '.');
        default:
            return (string)$value;
    }
}

// Các cột hiển thị ở bảng xem trước: field => nhãn
$previewColumns = [
    'vi_tri_sap_xep' => 'STT/Sắp xếp',
    'ten_tiengviet' => 'Tên (Việt)',
    'ten_tienganh' => 'Tên (Anh)',
    'ten_tiengnga' => 'Tên (Nga)',
    'dactinhkt_tiengviet' => 'Đặc tính KT (Việt)',
    'dactinhkt_tiengnga' => 'Đặc tính KT (Nga)',
    'so_pn' => 'Số P/N',
    'so_serial' => 'Số serial',
    'phanloai_id' => 'Phân loại',
    'dvt_tiengviet' => 'ĐVT (Việt)',
    'dvt_tiengnga' => 'ĐVT (Nga)',
    'soluong_conlai' => 'SL tồn',
    'dongia' => 'Đơn giá (VNĐ)',
    'dongia_usd' => 'Đơn giá (USD)',
    'ngaynhan' => 'Ngày nhận',
    'sohd' => 'Số HĐ',
    'ngaykyhd' => 'Ngày ký HĐ',
    'nguoiquanly' => 'Người quản lý',
    'vitribaoquan' => 'Vị trí bảo quản',
];

function cleanupVatTuImportFile(): void
{
    $path = $_SESSION['vattu_import_file'] ?? '';
    if ($path !== '' && is_file($path)) {
        @unlink($path);
    }
    unset($_SESSION['vattu_import_file'], $_SESSION['vattu_import_name']);
}

$mode = null; // 'preview' | 'import'
$previewRows = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_import'])) {
    cleanupVatTuImportFile();
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_FILES['excel_file']) || isset($_POST['confirm_import']))) {
    if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        $error = 'PhpSpreadsheet library not found';
        goto render;
    }
    
    if (isset($_POST['confirm_import'])) {
        // Import thật từ file đã upload ở bước xem trước
        $filePath = $_SESSION['vattu_import_file'] ?? '';
        if ($filePath === '' || !is_file($filePath)) {
            $error = 'Phiên xem trước đã hết hạn, vui lòng upload lại file';
            goto render;
        }
        $mode = 'import';
    } else {
        $uploadedFile = $_FILES['excel_file'];
        
        // Validate file
        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            $error = 'Lỗi upload file';
            goto render;
        }
        
        $allowedExtensions = ['xlsx', 'xls'];
        $fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            $error = 'Chỉ chấp nhận file Excel (.xlsx, .xls)';
            goto render;
        }
        
        cleanupVatTuImportFile();
        $filePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'vattu_import_' . bin2hex(random_bytes(8)) . '.' . $fileExtension;
        if (!move_uploaded_file($uploadedFile['tmp_name'], $filePath)) {
            $error = 'Không lưu được file tạm để xem trước';
            goto render;
        }
        $_SESSION['vattu_import_file'] = $filePath;
        $_SESSION['vattu_import_name'] = $uploadedFile['name'];
        $mode = 'preview';
    }
    
    try {
        // Đọc file Excel
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        
        // Đọc dữ liệu với các tùy chọn giữ nguyên giá trị
        // Tham số: null, calculateFormulas=true, formatData=true, returnCellRef=false
        $rows = $worksheet->toArray(null, true, false, false);
        
        // Bỏ qua dòng header (dòng đầu tiên)
        array_shift($rows);
        
        $db = getDBConnection();
        if ($mode === 'import') {
            $db->beginTransaction();
        }
        
        $success_count = 0;
        $update_count = 0;
        $unchanged_count = 0;
        $skipped_count = 0;
        $error_count = 0;
        $errors = [];
        $seenInFile = [];
        $stmtFind = $db->prepare("SELECT * FROM vattu_thanh_ly_iso WHERE mavattu = ?");
        
        // Load danh sách phân loại để map
        $stmtPhanLoai = $db->query("SELECT id, ma_phanloai, ten_phanloai FROM phanloai_vattu_thanh_ly_iso");
        $phanLoaiMap = [];
        $phanLoaiNameById = [];
        $defaultPhanLoaiId = null;
        while ($row = $stmtPhanLoai->fetch(PDO::FETCH_ASSOC)) {
            if ($defaultPhanLoaiId === null) {
                $defaultPhanLoaiId = $row['id']; // Lấy ID đầu tiên làm mặc định
            }
            $phanLoaiMap[strtoupper($row['ma_phanloai'])] = $row['id'];
            $phanLoaiMap[strtoupper($row['ten_phanloai'])] = $row['id'];
            $phanLoaiNameById[$row['id']] = $row['ten_phanloai'];
        }
        
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // +2 vì bỏ header và index bắt đầu từ 0
            
            // Bỏ qua dòng trống (kiểm tra mã vật tư - cột B)
            if (empty(trim($row[1] ?? ''))) {
                continue;
            }
            
            // Map columns theo thứ tự mới:
            // A: STT (bỏ qua)
            // B: Mã vật tư (required)
            // C: Tên tiếng Anh
            // D: Tên tiếng Nga
            // E: Tên tiếng Việt
            // F: Đặc tính kỹ thuật tiếng Nga
            // G: Đặc tính kỹ thuật tiếng Việt
            // H: Số P/N (Part Number)
            // I: ĐVT tiếng Nga
            // J: ĐVT tiếng Việt
            // K: Số lượng tồn
            // L: Đơn giá (VNĐ)
            // M: Đơn giá (USD)
            // N: Ngày nhận (dd/mm/yyyy)
            // O: Số hợp đồng
            // P: Ngày ký HĐ (dd/mm/yyyy)
            // Q: Người quản lý
            // R: Vị trí bảo quản
            // S: Phân loại (mã hoặc tên)
            // T: Số Serial
            
            $mavattu = trim($row[1] ?? '');
            if (empty($mavattu)) {
                $errors[] = "Dòng $rowNum: Thiếu mã vật tư";
                $error_count++;
                continue;
            }
            
            // Map phân loại (cột S)
            $phanloai_id = $defaultPhanLoaiId; // Mặc định
            $phanloaiInput = trim($row[18] ?? '');
            if (!empty($phanloaiInput)) {
                $phanloaiUpper = strtoupper($phanloaiInput);
                if (isset($phanLoaiMap[$phanloaiUpper])) {
                    $phanloai_id = $phanLoaiMap[$phanloaiUpper];
                } else {
                    $errors[] = "Dòng $rowNum: Phân loại không hợp lệ '$phanloaiInput' (chỉ chấp nhận: VT, CCDC, TS, PL hoặc tên đầy đủ)";
                    $error_count++;
                    continue;
                }
            }
            
            if ($phanloai_id === null) {
                $errors[] = "Dòng $rowNum: Không tìm thấy phân loại trong hệ thống";
                $error_count++;
                continue;
            }
            
            // Chuyển đổi ngày tháng từ Excel
            $ngaynhan = null;
            if (!empty($row[13])) {
                $ngaynhan = convertExcelDate($row[13]);
            }
            
            $ngaykyhd = null;
            if (!empty($row[15])) {
                $ngaykyhd = convertExcelDate($row[15]);
            }
            
            // Prepare data
            $data = [
                'mavattu' => $mavattu,
                'so_serial' => trim($row[19] ?? '') ?: null,
                'so_pn' => trim($row[7] ?? '') ?: null,
                'phanloai_id' => $phanloai_id,
                'vi_tri_sap_xep' => !empty($row[0]) ? (int)$row[0] : 999,
                'ten_tienganh' => trim($row[2] ?? '') ?: null,
                'ten_tiengnga' => trim($row[3] ?? '') ?: null,
                'ten_tiengviet' => trim($row[4] ?? '') ?: null,
                'dactinhkt_tiengnga' => trim($row[5] ?? '') ?: null,
                'dactinhkt_tiengviet' => trim($row[6] ?? '') ?: null,
                'dvt_tiengnga' => trim($row[8] ?? '') ?: null,
                'dvt_tiengviet' => trim($row[9] ?? '') ?: null,
                'soluong_conlai' => !empty($row[10]) ? floatval($row[10]) : null,
                'dongia' => !empty($row[11]) && is_numeric($row[11]) ? floatval($row[11]) : null,
                'dongia_usd' => !empty($row[12]) && is_numeric($row[12]) ? floatval($row[12]) : null,
                'ngaynhan' => $ngaynhan,
                'sohd' => trim($row[14] ?? '') ?: null,
                'ngaykyhd' => $ngaykyhd,
                'nguoiquanly' => trim($row[16] ?? '') ?: null,
                'vitribaoquan' => trim($row[17] ?? '') ?: null,
                'ghichu' => null,
            ];
            
            // Trùng mã trong cùng file: tránh ghi đè lẫn nhau
            $mavattuKey = mb_strtolower($mavattu, 'UTF-8');
            if (isset($seenInFile[$mavattuKey])) {
                $errors[] = "Dòng $rowNum: Mã vật tư '$mavattu' trùng với dòng {$seenInFile[$mavattuKey]} trong file";
                $error_count++;
                continue;
            }
            $seenInFile[$mavattuKey] = $rowNum;
            
            // Mã đã tồn tại → cập nhật, chưa có → thêm mới
            $stmtFind->execute([$mavattu]);
            $existingRows = $stmtFind->fetchAll(PDO::FETCH_ASSOC);
            
            $action = 'insert';
            $oldRow = null;
            $changes = [];
            $duplicates = [];
            if (count($existingRows) > 1) {
                // Nhiều bản ghi cùng mã: không tự cập nhật được, để người dùng chọn Thêm mới / Bỏ qua
                $action = 'ambiguous';
                $duplicates = $existingRows;
            } elseif (count($existingRows) === 1) {
                $oldRow = $existingRows[0];
                
                // Ô để trống trong Excel thì giữ nguyên giá trị cũ
                $provided = [
                    'vi_tri_sap_xep' => !empty($row[0]),
                    'phanloai_id' => $phanloaiInput !== '',
                ];
                foreach ($data as $field => $newValue) {
                    if ($field === 'mavattu' || $field === 'ghichu' || $newValue === null) {
                        continue;
                    }
                    if (isset($provided[$field]) && !$provided[$field]) {
                        continue;
                    }
                    if (!vattuValuesEqual($oldRow[$field] ?? null, $newValue)) {
                        $changes[$field] = ['old' => $oldRow[$field] ?? null, 'new' => $newValue];
                    }
                }
                $action = $changes ? 'update' : 'nochange';
            }
            
            if ($mode === 'preview') {
                $previewRows[] = [
                    'row_num' => $rowNum,
                    'action' => $action,
                    'data' => $data,
                    'old' => $oldRow,
                    'duplicates' => $duplicates,
                    'changes' => $changes,
                ];
                continue;
            }
            
            try {
                // Hành động do người dùng chọn ở bước xem trước (mặc định theo gợi ý)
                $chosen = in_array($action, ['nochange', 'ambiguous'], true) ? 'skip' : $action;
                $requested = $_POST['row_action'][$rowNum] ?? null;
                $allowedActions = $oldRow ? ($changes ? ['update', 'insert', 'skip'] : ['insert', 'skip']) : ['insert', 'skip'];
                if (is_string($requested) && in_array($requested, $allowedActions, true)) {
                    $chosen = $requested;
                }
                
                if ($chosen === 'skip') {
                    $skipped_count++;
                    continue;
                }
                
                if ($chosen === 'update') {
                    if (!$changes) {
                        $unchanged_count++;
                        continue;
                    }
                    
                    $setParts = [];
                    $params = [':__stt' => $oldRow['stt']];
                    foreach ($changes as $field => $change) {
                        $setParts[] = "`$field` = :$field";
                        $params[":$field"] = $change['new'];
                    }
                    $stmt = $db->prepare("UPDATE vattu_thanh_ly_iso SET " . implode(', ', $setParts) . " WHERE stt = :__stt");
                    $stmt->execute($params);
                    $update_count++;
                    continue;
                }
                
                // Insert
                $sql = "INSERT INTO vattu_thanh_ly_iso (
                    mavattu, so_serial, so_pn, phanloai_id, vi_tri_sap_xep, ten_tienganh, ten_tiengnga, ten_tiengviet,
                    dactinhkt_tiengnga, dactinhkt_tiengviet,
                    dvt_tiengnga, dvt_tiengviet, soluong_conlai, dongia, dongia_usd, ngaynhan,
                    sohd, ngaykyhd, nguoiquanly, vitribaoquan, ghichu
                ) VALUES (
                    :mavattu, :so_serial, :so_pn, :phanloai_id, :vi_tri_sap_xep, :ten_tienganh, :ten_tiengnga, :ten_tiengviet,
                    :dactinhkt_tiengnga, :dactinhkt_tiengviet,
                    :dvt_tiengnga, :dvt_tiengviet, :soluong_conlai, :dongia, :dongia_usd, :ngaynhan,
                    :sohd, :ngaykyhd, :nguoiquanly, :vitribaoquan, :ghichu
                )";
                
                $stmt = $db->prepare($sql);
                $stmt->execute($data);
                $success_count++;
            } catch (PDOException $e) {
                $errors[] = "Dòng $rowNum: " . $e->getMessage();
                $error_count++;
            }
        }
        
        if ($mode === 'import') {
            $db->commit();
            cleanupVatTuImportFile();
            
            $success = "Import thành công: thêm mới $success_count, cập nhật $update_count" .
                       ($unchanged_count > 0 ? ", không thay đổi $unchanged_count" : "") .
                       ($skipped_count > 0 ? ", bỏ qua $skipped_count" : "") .
                       ($error_count > 0 ? ", $error_count bản ghi lỗi" : "");
        }
        
    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        $mode = null;
        $previewRows = [];
        $error = 'Lỗi đọc file: ' . $e->getMessage();
    }
}

render:

$title = 'Import Vật Tư từ Excel';
require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="bg-white rounded-lg shadow-md p-6 <?php echo $mode === 'preview' ? 'max-w-full mx-4' : 'max-w-4xl mx-auto'; ?>">
    <h1 class="text-2xl font-bold mb-6 flex items-center">
        <i class="fas fa-file-excel mr-2 text-green-600"></i> Import Vật Tư từ Excel
    </h1>

    <?php if (isset($success)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <i class="fas fa-check-circle mr-2"></i>
            <?php echo htmlspecialchars($success); ?>
            <?php if (!empty($errors)): ?>
                <details class="mt-2">
                    <summary class="cursor-pointer font-semibold">Chi tiết lỗi (<?php echo count($errors); ?>)</summary>
                    <ul class="list-disc list-inside mt-2 text-sm">
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </details>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($mode === 'preview'): ?>
        <style>
            @media print {
                @page { size: A4 landscape; margin: 8mm; }
                body * { visibility: hidden; }
                #importPreview, #importPreview * { visibility: visible; }
                #importPreview { position: absolute; left: 0; top: 0; width: 100%; border: none !important; }
                #importPreview .no-print { display: none !important; }
                #importPreview .preview-scroll { max-height: none !important; overflow: visible !important; }
                #importPreview table { font-size: 9px; }
                #importPreview th, #importPreview td { padding: 1px 3px !important; max-width: 140px !important; }
                #importPreview .row-badge { display: none !important; }
                #importPreview thead { position: static !important; display: table-header-group; }
                #importPreview tr { page-break-inside: avoid; }
                #importPreview select { appearance: none; -webkit-appearance: none; border: none; background: none; padding: 0; font-size: 9px; }
                #importPreview tr.row-skip { display: none !important; }
            }
            #importPreview tr.row-skip { opacity: 0.5; }
        </style>
        <div id="importPreview" class="border border-blue-300 rounded mb-6">
            <div class="bg-blue-50 px-4 py-3 border-b border-blue-300">
                <h2 class="font-bold text-blue-800">
                    <i class="fas fa-eye mr-1"></i> Xem trước dữ liệu — chưa ghi vào hệ thống
                </h2>
                <?php
                $countInsert = count(array_filter($previewRows, fn($r) => $r['action'] === 'insert'));
                $countUpdate = count(array_filter($previewRows, fn($r) => $r['action'] === 'update'));
                $countNoChange = count(array_filter($previewRows, fn($r) => $r['action'] === 'nochange'));
                $countAmbiguous = count(array_filter($previewRows, fn($r) => $r['action'] === 'ambiguous'));
                // Khi in: ẩn các cột phụ, trừ cột nào có giá trị bị thay đổi
                $printOptionalFields = ['vi_tri_sap_xep', 'ten_tiengnga', 'dactinhkt_tiengviet', 'dactinhkt_tiengnga',
                                        'dvt_tiengnga', 'sohd', 'ngaykyhd', 'nguoiquanly', 'vitribaoquan'];
                $changedFields = [];
                foreach ($previewRows as $r) {
                    $changedFields += array_fill_keys(array_keys($r['changes']), true);
                }
                $printHideClass = function (string $field) use ($printOptionalFields, $changedFields): string {
                    return in_array($field, $printOptionalFields, true) && !isset($changedFields[$field]) ? ' no-print' : '';
                };
                ?>
                <p class="text-sm text-blue-900 mt-1">
                    File: <b><?php echo htmlspecialchars($_SESSION['vattu_import_name'] ?? ''); ?></b> —
                    <span class="text-green-700 font-semibold"><?php echo $countInsert; ?> thêm mới</span>,
                    <span class="text-orange-700 font-semibold"><?php echo $countUpdate; ?> cập nhật (mã đã tồn tại)</span>,
                    <span class="text-gray-600 font-semibold"><?php echo $countNoChange; ?> không thay đổi</span>,
                    <?php if ($countAmbiguous > 0): ?>
                        <span class="text-purple-700 font-semibold"><?php echo $countAmbiguous; ?> trùng mã nhiều bản ghi (mặc định bỏ qua, có thể chọn Thêm mới)</span>,
                    <?php endif; ?>
                    <span class="<?php echo $error_count > 0 ? 'text-red-700' : 'text-gray-600'; ?> font-semibold"><?php echo $error_count; ?> dòng lỗi (sẽ bị bỏ qua)</span>
                </p>
                <p class="text-xs text-gray-600 mt-1">
                    Dòng cập nhật: <del class="text-red-600">giá trị cũ</del> → <b class="text-green-700">giá trị mới</b>. Ô để trống trong Excel sẽ giữ nguyên giá trị cũ.
                    Mỗi dòng có thể chọn <b>Thêm mới</b>, <b>Cập nhật</b> hoặc <b>Bỏ qua</b> trước khi xác nhận.
                </p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="bg-red-50 px-4 py-3 border-b border-red-200 text-sm text-red-700">
                    <div class="font-semibold mb-1">Dòng lỗi (<?php echo count($errors); ?>):</div>
                    <ul class="list-disc list-inside max-h-40 overflow-y-auto">
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($previewRows)): ?>
                <div class="overflow-auto preview-scroll" style="max-height: 480px;">
                    <table class="min-w-full text-xs">
                        <thead class="bg-gray-200 sticky top-0">
                            <tr>
                                <th class="px-2 py-1 border">Dòng</th>
                                <th class="px-2 py-1 border">Hành động</th>
                                <th class="px-2 py-1 border">Mã vật tư</th>
                                <?php foreach ($previewColumns as $field => $label): ?>
                                    <th class="px-2 py-1 border<?php echo $printHideClass($field); ?>"><?php echo htmlspecialchars($label); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($previewRows as $r): ?>
                                <?php
                                $rowClass = ['insert' => '', 'update' => 'bg-orange-50', 'nochange' => 'text-gray-400', 'ambiguous' => 'bg-purple-50'][$r['action']];
                                $badge = [
                                    'insert' => '<span class="row-badge bg-green-100 text-green-800 px-2 py-0.5 rounded font-semibold">Thêm mới</span>',
                                    'update' => '<span class="row-badge bg-orange-100 text-orange-800 px-2 py-0.5 rounded font-semibold">Cập nhật</span>',
                                    'nochange' => '<span class="row-badge bg-gray-100 text-gray-600 px-2 py-0.5 rounded">Không đổi</span>',
                                    'ambiguous' => '<span class="row-badge bg-purple-100 text-purple-800 px-2 py-0.5 rounded font-semibold">Trùng nhiều bản ghi</span>',
                                ][$r['action']];
                                $isExisting = $r['old'] || $r['action'] === 'ambiguous';
                                ?>
                                <tr class="<?php echo $rowClass; ?>">
                                    <td class="px-2 py-1 border text-center"><?php echo (int)$r['row_num']; ?></td>
                                    <td class="px-2 py-1 border text-center whitespace-nowrap">
                                        <?php echo $badge; ?>
                                        <select name="row_action[<?php echo (int)$r['row_num']; ?>]" form="confirmImportForm"
                                                class="row-action border rounded px-1 py-0.5 mt-1 block w-full text-gray-900"
                                                data-group="<?php echo $isExisting ? 'existing' : 'new'; ?>">
                                            <?php if ($isExisting): ?>
                                                <?php if ($r['action'] === 'update'): ?>
                                                    <option value="update" selected>Cập nhật</option>
                                                <?php endif; ?>
                                                <option value="insert">Thêm mới (trùng mã)</option>
                                                <option value="skip" <?php echo in_array($r['action'], ['nochange', 'ambiguous'], true) ? 'selected' : ''; ?>>Bỏ qua</option>
                                            <?php else: ?>
                                                <option value="insert" selected>Thêm mới</option>
                                                <option value="skip">Bỏ qua</option>
                                            <?php endif; ?>
                                        </select>
                                    </td>
                                    <td class="px-2 py-1 border font-mono">
                                        <?php echo htmlspecialchars($r['data']['mavattu']); ?>
                                        <?php if ($r['duplicates']): ?>
                                            <div class="font-sans text-purple-700 mt-1">
                                                Hệ thống có <?php echo count($r['duplicates']); ?> bản ghi:
                                                <?php foreach ($r['duplicates'] as $dup): ?>
                                                    <div>#<?php echo (int)$dup['stt']; ?><?php
                                                        $dupInfo = array_filter([
                                                            !empty($dup['so_serial']) ? 'S/N ' . $dup['so_serial'] : '',
                                                            !empty($dup['so_pn']) ? 'P/N ' . $dup['so_pn'] : '',
                                                        ]);
                                                        echo $dupInfo ? ' (' . htmlspecialchars(implode(', ', $dupInfo)) . ')' : '';
                                                    ?></div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <?php foreach ($previewColumns as $field => $label): ?>
                                        <td class="px-2 py-1 border<?php echo $printHideClass($field); ?>" style="max-width: 220px; overflow-wrap: anywhere;">
                                            <?php if (isset($r['changes'][$field])): ?>
                                                <del class="text-red-600"><?php echo htmlspecialchars(formatVattuPreviewValue($field, $r['changes'][$field]['old'], $phanLoaiNameById)); ?></del>
                                                → <b class="text-green-700"><?php echo htmlspecialchars(formatVattuPreviewValue($field, $r['changes'][$field]['new'], $phanLoaiNameById)); ?></b>
                                            <?php else: ?>
                                                <?php
                                                $shown = in_array($r['action'], ['insert', 'ambiguous'], true) ? $r['data'][$field] : ($r['old'][$field] ?? null);
                                                echo htmlspecialchars(formatVattuPreviewValue($field, $shown, $phanLoaiNameById));
                                                ?>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="px-4 py-2 bg-gray-50 border-t text-sm flex flex-wrap items-center gap-2 no-print">
                <span class="font-semibold">Chọn nhanh:</span>
                <?php if ($countInsert > 0): ?>
                    <span>Mã mới →</span>
                    <button type="button" class="bulk-btn border rounded px-2 py-1 bg-white hover:bg-gray-100" data-group="new" data-value="insert">Thêm mới</button>
                    <button type="button" class="bulk-btn border rounded px-2 py-1 bg-white hover:bg-gray-100" data-group="new" data-value="skip">Bỏ qua</button>
                <?php endif; ?>
                <?php if ($countUpdate + $countNoChange + $countAmbiguous > 0): ?>
                    <span class="ml-3">Mã đã tồn tại →</span>
                    <button type="button" class="bulk-btn border rounded px-2 py-1 bg-white hover:bg-gray-100" data-group="existing" data-value="update">Cập nhật</button>
                    <button type="button" class="bulk-btn border rounded px-2 py-1 bg-white hover:bg-gray-100" data-group="existing" data-value="insert">Thêm mới (trùng mã)</button>
                    <button type="button" class="bulk-btn border rounded px-2 py-1 bg-white hover:bg-gray-100" data-group="existing" data-value="skip">Bỏ qua</button>
                <?php endif; ?>
            </div>

            <form method="POST" id="confirmImportForm" class="px-4 py-3 bg-gray-50 border-t flex gap-3 no-print">
                <?php if (!empty($previewRows)): ?>
                    <button type="submit" id="confirmImportBtn" name="confirm_import" value="1" class="bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white px-6 py-2 rounded">
                        <i class="fas fa-check mr-1"></i> <span id="confirmImportLabel">Xác nhận import</span>
                    </button>
                <?php endif; ?>
                <button type="button" onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">
                    <i class="fas fa-print mr-1"></i> In danh sách xem trước
                </button>
                <button type="submit" name="cancel_import" value="1" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded">
                    <i class="fas fa-times mr-1"></i> Hủy
                </button>
            </form>
        </div>
        <script>
        (function () {
            var selects = document.querySelectorAll('select.row-action');
            var btn = document.getElementById('confirmImportBtn');
            var label = document.getElementById('confirmImportLabel');
            function refresh() {
                var ins = 0, upd = 0, skip = 0;
                selects.forEach(function (s) {
                    var tr = s.closest('tr');
                    if (tr) tr.classList.toggle('row-skip', s.value === 'skip');
                    if (s.value === 'insert') ins++;
                    else if (s.value === 'update') upd++;
                    else skip++;
                });
                if (label) label.textContent = 'Xác nhận import (thêm ' + ins + ', cập nhật ' + upd + ', bỏ qua ' + skip + ')';
                if (btn) btn.disabled = (ins + upd) === 0;
            }
            selects.forEach(function (s) { s.addEventListener('change', refresh); });
            document.querySelectorAll('.bulk-btn').forEach(function (b) {
                b.addEventListener('click', function () {
                    document.querySelectorAll('select.row-action[data-group="' + b.dataset.group + '"]').forEach(function (s) {
                        if (s.querySelector('option[value="' + b.dataset.value + '"]')) s.value = b.dataset.value;
                    });
                    refresh();
                });
            });
            if (btn) {
                btn.addEventListener('click', function (e) {
                    if (!confirm(label.textContent.replace('Xác nhận import', 'Ghi vào hệ thống') + '?')) e.preventDefault();
                });
            }
            refresh();
        })();
        </script>
    <?php endif; ?>

    <!-- Form upload -->
    <form method="POST" enctype="multipart/form-data" class="mb-6">
        <div class="mb-4">
            <label class="block font-medium mb-2">
                <i class="fas fa-file-upload mr-1"></i> Chọn file Excel
            </label>
            <input type="file" name="excel_file" accept=".xlsx,.xls" required
                   class="block w-full text-sm text-gray-500
                          file:mr-4 file:py-2 file:px-4
                          file:rounded file:border-0
                          file:text-sm file:font-semibold
                          file:bg-blue-50 file:text-blue-700
                          hover:file:bg-blue-100
                          cursor-pointer">
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded">
                <i class="fas fa-eye mr-1"></i> Upload và Xem trước
            </button>
            <a href="vattuthanhly.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded">
                <i class="fas fa-arrow-left mr-1"></i> Quay lại
            </a>
        </div>
    </form>

    <!-- Hướng dẫn -->
    <div class="bg-blue-50 border border-blue-200 rounded p-4 mb-4">
        <h3 class="font-bold mb-2 text-blue-800">
            <i class="fas fa-info-circle mr-1"></i> Hướng dẫn
        </h3>
        <ol class="list-decimal list-inside space-y-1 text-sm text-blue-900">
            <li>Tải file Excel mẫu bên dưới</li>
            <li>Điền thông tin vật tư vào các cột theo đúng thứ tự</li>
            <li>Lưu file và upload lên hệ thống để xem trước</li>
            <li>Kiểm tra bảng xem trước, sau đó bấm "Xác nhận import" để ghi vào hệ thống</li>
            <li>Mã vật tư chưa có sẽ được thêm mới; mã đã tồn tại sẽ được <b>cập nhật</b> (ô để trống giữ nguyên giá trị cũ)</li>
            <li>Dòng đầu tiên (header) sẽ bị bỏ qua</li>
            <li>Chỉ cột "Mã vật tư" là bắt buộc</li>
        </ol>
    </div>

    <!-- Cấu trúc file Excel -->
    <div class="bg-gray-50 border rounded p-4 mb-4">
        <h3 class="font-bold mb-3">
            <i class="fas fa-table mr-1"></i> Cấu trúc file Excel (theo thứ tự cột)
        </h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="px-2 py-1 border text-center">Cột</th>
                        <th class="px-2 py-1 border">Tên trường</th>
                        <th class="px-2 py-1 border text-center">Bắt buộc</th>
                        <th class="px-2 py-1 border">Ví dụ</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td class="px-2 py-1 border text-center font-mono">A</td><td class="px-2 py-1 border">STT</td><td class="px-2 py-1 border text-center">-</td><td class="px-2 py-1 border font-mono">1, 2, 3...</td></tr>
                    <tr><td class="px-2 py-1 border text-center font-mono">B</td><td class="px-2 py-1 border">Mã vật tư</td><td class="px-2 py-1 border text-center text-red-600">✓</td><td class="px-2 py-1 border font-mono">011.004.00575</td></tr>
                    <tr><td class="px-2 py-1 border text-center font-mono">C</td><td class="px-2 py-1 border">Tên tiếng Anh</td><td class="px-2 py-1 border text-center">-</td><td class="px-2 py-1 border">CAP ALUM 22UF 20%...</td></tr>
                    <tr><td class="px-2 py-1 border text-center font-mono">D</td><td class="px-2 py-1 border">Tên tiếng Nga</td><td class="px-2 py-1 border text-center">-</td><td class="px-2 py-1 border">Конденсатор...</td></tr>
                    <tr><td class="px-2 py-1 border text-center font-mono">E</td><td class="px-2 py-1 border">Tên tiếng Việt</td><td class="px-2 py-1 border text-center">-</td><td class="px-2 py-1 border">Tụ điện ALUM...</td></tr>
                    <tr><td class="px-2 py-1 border text-center font-mono">F</td><td class="px-2 py-1 border">Đặc tính kỹ thuật tiếng Nga</td><td class="px-2 py-1 border text-center">-</td><td class="px-2 py-1 border">Размер...</td></tr>
                    <tr><td class="px-2 py-1 border text-center font-mono">G</td><td class="px-2 py-1 border">Đặc tính kỹ thuật tiếng Việt</td><td class="px-2 py-1 border text-center">-</td><td class="px-2 py-1 border">Kích thước...</td></tr>
                    <tr><td class="px-2 py-1 border text-center font-mono">H</td><td class="px-2 py-1 border">Số P/N (Part Number)</td><td class="px-2 py-1 border text-center">-</td><td class="px-2 py-1 border font-mono">PN-12345</td></tr>
                    <tr><td class="px-2 py-1 border text-center font-mono">I</td><td class="px-2 py-1 border">ĐVT tiếng Nga</td><td class="px-2 py-1 border text-center">-</td><td class="px-2 py-1 border">Cái</td></tr>
                    <tr><td class="px-2 py-1 border text-center font-mono">J</td><td class="px-2 py-1 border">ĐVT tiếng Việt</td><td class="px-2 py-1 border text-center">-</td><td class="px-2 py-1 border">Cái</td></tr>
                    <tr><td class="px-2 py-1 border text-center font-mono">K</td><td class="px-2 py-1 border">Số lượng tồn</td><td class="px-2 py-1 border text-center">-</td><td class="px-2 py-1 border font-mono">50</td></tr>
                    <tr><td class="px-2 py-1 border text-center font-mono">L</td><td class="px-2 py-1 border">Đơn giá (VNĐ)</td><td class="px-2 py-1 border text-center">-</td><td class="px-2 py-1 border font-mono">50500</td></tr>
                    <tr><td class="px-2 py-1 border text-center font-mono">M</td><td class="px-2 py-1 border">Đơn giá (USD)</td><td class="px-2 py-1 border text-center">-</td><td class="px-2 py-1 border font-mono">2.15</td></tr>
                    <tr><td class="px-2 py-1 border text-center font-mono">N</td><td class="px-2 py-1 border">Ngày nhận</td><td class="px-2 py-1 border text-center">-</td><td class="px-2 py-1 border font-mono">20/11/2025</td></tr>
                    <tr><td class="px-2 py-1 border text-center font-mono">O</td><td class="px-2 py-1 border">Số HĐ</td><td class="px-2 py-1 border text-center">-</td><td class="px-2 py-1 border font-mono">0044/25/DV-LSTE</td></tr>
                    <tr><td class="px-2 py-1 border text-center font-mono">P</td><td class="px-2 py-1 border">Ngày ký HĐ</td><td class="px-2 py-1 border text-center">-</td><td class="px-2 py-1 border font-mono">20/07/2025</td></tr>
                    <tr><td class="px-2 py-1 border text-center font-mono">Q</td><td class="px-2 py-1 border">Người quản lý</td><td class="px-2 py-1 border text-center">-</td><td class="px-2 py-1 border">T.N Sang</td></tr>
                    <tr><td class="px-2 py-1 border text-center font-mono">R</td><td class="px-2 py-1 border">Vị trí bảo quản</td><td class="px-2 py-1 border text-center">-</td><td class="px-2 py-1 border">P1. Nga</td></tr>
                    <tr><td class="px-2 py-1 border text-center font-mono">S</td><td class="px-2 py-1 border">Phân loại</td><td class="px-2 py-1 border text-center">-</td><td class="px-2 py-1 border font-mono">VT hoặc Vật tư</td></tr>
                    <tr><td class="px-2 py-1 border text-center font-mono">T</td><td class="px-2 py-1 border">Số Serial</td><td class="px-2 py-1 border text-center">-</td><td class="px-2 py-1 border font-mono">SN123456</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Download template -->
    <div class="text-center">
        <a href="download_vattu_template.php" 
           class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg">
            <i class="fas fa-download mr-2"></i> Tải file Excel mẫu
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
