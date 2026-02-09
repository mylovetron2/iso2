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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        $error = 'PhpSpreadsheet library not found';
        goto render;
    }
    
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
    
    try {
        // Đọc file Excel
        $spreadsheet = IOFactory::load($uploadedFile['tmp_name']);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        // Bỏ qua dòng header (dòng đầu tiên)
        array_shift($rows);
        
        $db = getDBConnection();
        $db->beginTransaction();
        
        $success_count = 0;
        $error_count = 0;
        $errors = [];
        
        // Load danh sách phân loại để map
        $stmtPhanLoai = $db->query("SELECT id, ma_phanloai, ten_phanloai FROM phanloai_vattu_thanh_ly_iso");
        $phanLoaiMap = [];
        $defaultPhanLoaiId = null;
        while ($row = $stmtPhanLoai->fetch(PDO::FETCH_ASSOC)) {
            if ($defaultPhanLoaiId === null) {
                $defaultPhanLoaiId = $row['id']; // Lấy ID đầu tiên làm mặc định
            }
            $phanLoaiMap[strtoupper($row['ma_phanloai'])] = $row['id'];
            $phanLoaiMap[strtoupper($row['ten_phanloai'])] = $row['id'];
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
            // H: ĐVT tiếng Nga
            // I: ĐVT tiếng Việt
            // J: Số lượng tồn
            // K: Đơn giá
            // L: Ngày nhận (dd/mm/yyyy)
            // M: Số hợp đồng
            // N: Ngày ký HĐ (dd/mm/yyyy)
            // O: Người quản lý
            // P: Vị trí bảo quản
            // Q: Phân loại (mã hoặc tên)
            // R: Số Serial
            
            $mavattu = trim($row[1] ?? '');
            if (empty($mavattu)) {
                $errors[] = "Dòng $rowNum: Thiếu mã vật tư";
                $error_count++;
                continue;
            }
            
            // Map phân loại (cột Q)
            $phanloai_id = $defaultPhanLoaiId; // Mặc định
            $phanloaiInput = trim($row[16] ?? '');
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
            if (!empty($row[11])) {
                $ngaynhan = convertExcelDate($row[11]);
            }
            
            $ngaykyhd = null;
            if (!empty($row[13])) {
                $ngaykyhd = convertExcelDate($row[13]);
            }
            
            // Prepare data
            $data = [
                'mavattu' => $mavattu,
                'so_serial' => trim($row[17] ?? '') ?: null,
                'phanloai_id' => $phanloai_id,
                'vi_tri_sap_xep' => !empty($row[0]) ? (int)$row[0] : 999,
                'ten_tienganh' => trim($row[2] ?? '') ?: null,
                'ten_tiengnga' => trim($row[3] ?? '') ?: null,
                'ten_tiengviet' => trim($row[4] ?? '') ?: null,
                'dactinhkt_tiengnga' => trim($row[5] ?? '') ?: null,
                'dactinhkt_tiengviet' => trim($row[6] ?? '') ?: null,
                'dvt_tiengnga' => trim($row[7] ?? '') ?: null,
                'dvt_tiengviet' => trim($row[8] ?? '') ?: null,
                'soluong_conlai' => !empty($row[9]) ? (float)$row[9] : null,
                'dongia' => !empty($row[10]) ? (float)$row[10] : null,
                'ngaynhan' => $ngaynhan,
                'sohd' => trim($row[12] ?? '') ?: null,
                'ngaykyhd' => $ngaykyhd,
                'nguoiquanly' => trim($row[14] ?? '') ?: null,
                'vitribaoquan' => trim($row[15] ?? '') ?: null,
                'ghichu' => null,
            ];
            
            try {
                // Insert
                $sql = "INSERT INTO vattu_thanh_ly_iso (
                    mavattu, so_serial, phanloai_id, vi_tri_sap_xep, ten_tienganh, ten_tiengnga, ten_tiengviet,
                    dactinhkt_tiengnga, dactinhkt_tiengviet,
                    dvt_tiengnga, dvt_tiengviet, soluong_conlai, dongia, ngaynhan,
                    sohd, ngaykyhd, nguoiquanly, vitribaoquan, ghichu
                ) VALUES (
                    :mavattu, :so_serial, :phanloai_id, :vi_tri_sap_xep, :ten_tienganh, :ten_tiengnga, :ten_tiengviet,
                    :dactinhkt_tiengnga, :dactinhkt_tiengviet,
                    :dvt_tiengnga, :dvt_tiengviet, :soluong_conlai, :dongia, :ngaynhan,
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
        
        $db->commit();
        
        $success = "Import thành công $success_count bản ghi" . 
                   ($error_count > 0 ? ", $error_count bản ghi lỗi" : "");
        
    } catch (Exception $e) {
        if (isset($db)) {
            $db->rollBack();
        }
        $error = 'Lỗi đọc file: ' . $e->getMessage();
    }
}

render:

$title = 'Import Vật Tư từ Excel';
require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="bg-white rounded-lg shadow-md p-6 max-w-4xl mx-auto">
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
                <i class="fas fa-upload mr-1"></i> Upload và Import
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
            <li>Lưu file và upload lên hệ thống</li>
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
                    <tr><td class="px-2 py-1 border text-center font-mono">H</td><td class="px-2 py-1 border">ĐVT tiếng Nga</td><td class="px-2 py-1 border text-center">-</td><td class="px-2 py-1 border">Cái</td></tr>
                    <tr><td class="px-2 py-1 border text-center font-mono">I</td><td class="px-2 py-1 border">ĐVT tiếng Việt</td><td class="px-2 py-1 border text-center">-</td><td class="px-2 py-1 border">Cái</td></tr>
                    <tr><td class="px-2 py-1 border text-center font-mono">J</td><td class="px-2 py-1 border">Số lượng tồn</td><td class="px-2 py-1 border text-center">-</td><td class="px-2 py-1 border font-mono">50</td></tr>
                    <tr><td class="px-2 py-1 border text-center font-mono">K</td><td class="px-2 py-1 border">Đơn giá</td><td class="px-2 py-1 border text-center">-</td><td class="px-2 py-1 border font-mono">50500</td></tr>
                    <tr><td class="px-2 py-1 border text-center font-mono">L</td><td class="px-2 py-1 border">Ngày nhận</td><td class="px-2 py-1 border text-center">-</td><td class="px-2 py-1 border font-mono">20/11/2025</td></tr>
                    <tr><td class="px-2 py-1 border text-center font-mono">M</td><td class="px-2 py-1 border">Số HĐ</td><td class="px-2 py-1 border text-center">-</td><td class="px-2 py-1 border font-mono">0044/25/DV-LSTE</td></tr>
                    <tr><td class="px-2 py-1 border text-center font-mono">N</td><td class="px-2 py-1 border">Ngày ký HĐ</td><td class="px-2 py-1 border text-center">-</td><td class="px-2 py-1 border font-mono">20/07/2025</td></tr>
                    <tr><td class="px-2 py-1 border text-center font-mono">O</td><td class="px-2 py-1 border">Người quản lý</td><td class="px-2 py-1 border text-center">-</td><td class="px-2 py-1 border">T.N Sang</td></tr>
                    <tr><td class="px-2 py-1 border text-center font-mono">P</td><td class="px-2 py-1 border">Vị trí bảo quản</td><td class="px-2 py-1 border text-center">-</td><td class="px-2 py-1 border">P1. Nga</td></tr>
                    <tr><td class="px-2 py-1 border text-center font-mono">Q</td><td class="px-2 py-1 border">Phân loại</td><td class="px-2 py-1 border text-center">-</td><td class="px-2 py-1 border font-mono">VT hoặc Vật tư</td></tr>
                    <tr><td class="px-2 py-1 border text-center font-mono">R</td><td class="px-2 py-1 border">Số Serial</td><td class="px-2 py-1 border text-center">-</td><td class="px-2 py-1 border font-mono">SN123456</td></tr>
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
