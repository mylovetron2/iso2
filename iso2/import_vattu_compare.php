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
        
        // Đọc dữ liệu với các tùy chọn giữ nguyên giá trị
        $rows = $worksheet->toArray(null, true, false, false);
        
        // Bỏ qua dòng header (dòng đầu tiên)
        array_shift($rows);
        
        $db = getDBConnection();
        
        // Lấy danh sách mã vật tư đã có trong database
        $stmtExisting = $db->query("SELECT mavattu FROM vattu_thanh_ly_iso");
        $existingMaVatTu = [];
        while ($row = $stmtExisting->fetch(PDO::FETCH_ASSOC)) {
            $existingMaVatTu[trim($row['mavattu'])] = true;
        }
        
        $db->beginTransaction();
        
        $added_count = 0;
        $updated_count = 0;
        $zeroed_count = 0;
        $error_count = 0;
        $errors = [];
        $added_items = [];
        $updated_items = [];
        $zeroed_items = [];
        $excelMaVatTu = []; // Track mã vật tư từ file Excel
        
        // Load danh sách phân loại để map
        $stmtPhanLoai = $db->query("SELECT id, ma_phanloai, ten_phanloai FROM phanloai_vattu_thanh_ly_iso");
        $phanLoaiMap = [];
        $defaultPhanLoaiId = null;
        while ($row = $stmtPhanLoai->fetch(PDO::FETCH_ASSOC)) {
            if ($defaultPhanLoaiId === null) {
                $defaultPhanLoaiId = $row['id'];
            }
            $phanLoaiMap[strtoupper(trim($row['ma_phanloai']))] = $row['id'];
            $phanLoaiMap[strtoupper(trim($row['ten_phanloai']))] = $row['id'];
        }
        
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // +2 vì bỏ header và index bắt đầu từ 0
            
            // Cấu trúc file Excel theo hình:
            // A: STT
            // B: Mã vật tư (required)
            // C: Tên vật tư (có thể có cả tiếng Nga và tiếng Việt, ngăn cách bởi " - ")
            // D: Don gia(usd)
            // E: Tồn (số lượng)
            // F: Phân loại
            
            $mavattu = trim($row[1] ?? '');
            
            // Bỏ qua dòng trống
            if (empty($mavattu)) {
                continue;
            }
            
            // Track mã vật tư từ Excel
            $excelMaVatTu[$mavattu] = true;
            
            // Xử lý tên vật tư (tách tiếng Nga và tiếng Việt nếu có dấu " - ")
            $tenVatTu = trim($row[2] ?? '');
            $ten_tiengnga = null;
            $ten_tiengviet = null;
            
            if (!empty($tenVatTu)) {
                // Tìm dấu " - " để tách tiếng Nga và tiếng Việt
                if (strpos($tenVatTu, ' - ') !== false) {
                    $parts = explode(' - ', $tenVatTu, 2);
                    $ten_tiengnga = trim($parts[0]);
                    $ten_tiengviet = trim($parts[1]);
                } else {
                    // Nếu không có dấu " - ", ưu tiên coi là tiếng Nga (vì trong hình chủ yếu là tiếng Nga)
                    $ten_tiengnga = $tenVatTu;
                }
            }
            
            // Đơn giá USD
            $dongia_usd = !empty($row[3]) && is_numeric($row[3]) ? floatval($row[3]) : null;
            
            // Số lượng tồn
            $soluong_conlai = !empty($row[4]) && is_numeric($row[4]) ? floatval($row[4]) : null;
            
            // Phân loại
            $phanloai_id = $defaultPhanLoaiId; // Mặc định
            $phanloaiInput = trim($row[5] ?? '');
            if (!empty($phanloaiInput)) {
                $phanloaiUpper = strtoupper($phanloaiInput);
                if (isset($phanLoaiMap[$phanloaiUpper])) {
                    $phanloai_id = $phanLoaiMap[$phanloaiUpper];
                }
            }
            
            // Kiểm tra xem mã vật tư đã tồn tại chưa
            if (isset($existingMaVatTu[$mavattu])) {
                // Đã tồn tại → Cập nhật số lượng còn lại
                try {
                    $sqlUpdate = "UPDATE vattu_thanh_ly_iso 
                                  SET soluong_conlai = :soluong_conlai
                                  WHERE mavattu = :mavattu";
                    
                    $stmtUpdate = $db->prepare($sqlUpdate);
                    $stmtUpdate->execute([
                        ':soluong_conlai' => $soluong_conlai,
                        ':mavattu' => $mavattu
                    ]);
                    
                    $updated_count++;
                    $updated_items[] = [
                        'row' => $rowNum,
                        'mavattu' => $mavattu,
                        'ten' => $ten_tiengnga ?? $ten_tiengviet ?? 'N/A',
                        'soluong' => $soluong_conlai
                    ];
                } catch (PDOException $e) {
                    $errors[] = "Dòng $rowNum ($mavattu) - Lỗi cập nhật: " . $e->getMessage();
                    $error_count++;
                }
                continue;
            }
            
            // Chưa tồn tại → Thêm mới
            // Prepare data
            $data = [
                'mavattu' => $mavattu,
                'so_serial' => null,
                'phanloai_id' => $phanloai_id,
                'vi_tri_sap_xep' => !empty($row[0]) ? (int)$row[0] : 999,
                'ten_tienganh' => null,
                'ten_tiengnga' => $ten_tiengnga,
                'ten_tiengviet' => $ten_tiengviet,
                'dactinhkt_tiengnga' => null,
                'dactinhkt_tiengviet' => null,
                'dvt_tiengnga' => 'шт.',
                'dvt_tiengviet' => 'Cái',
                'soluong_conlai' => $soluong_conlai,
                'dongia' => null, // VNĐ không có trong file này
                'dongia_usd' => $dongia_usd,
                'ngaynhan' => null,
                'sohd' => null,
                'ngaykyhd' => null,
                'nguoiquanly' => null,
                'vitribaoquan' => null,
                'ghichu' => 'Import từ file Excel',
            ];
            
            try {
                // Insert
                $sql = "INSERT INTO vattu_thanh_ly_iso (
                    mavattu, so_serial, phanloai_id, vi_tri_sap_xep, ten_tienganh, ten_tiengnga, ten_tiengviet,
                    dactinhkt_tiengnga, dactinhkt_tiengviet,
                    dvt_tiengnga, dvt_tiengviet, soluong_conlai, dongia, dongia_usd, ngaynhan,
                    sohd, ngaykyhd, nguoiquanly, vitribaoquan, ghichu
                ) VALUES (
                    :mavattu, :so_serial, :phanloai_id, :vi_tri_sap_xep, :ten_tienganh, :ten_tiengnga, :ten_tiengviet,
                    :dactinhkt_tiengnga, :dactinhkt_tiengviet,
                    :dvt_tiengnga, :dvt_tiengviet, :soluong_conlai, :dongia, :dongia_usd, :ngaynhan,
                    :sohd, :ngaykyhd, :nguoiquanly, :vitribaoquan, :ghichu
                )";
                
                $stmt = $db->prepare($sql);
                $stmt->execute($data);
                $added_count++;
                $added_items[] = [
                    'row' => $rowNum,
                    'mavattu' => $mavattu,
                    'ten' => $ten_tiengnga ?? $ten_tiengviet ?? 'N/A',
                    'dongia_usd' => $dongia_usd,
                    'soluong' => $soluong_conlai
                ];
            } catch (PDOException $e) {
                $errors[] = "Dòng $rowNum ($mavattu): " . $e->getMessage();
                $error_count++;
            }
        }
        
        // Xử lý các vật tư có trong DB nhưng KHÔNG có trong file Excel → Set số lượng = 0
        foreach ($existingMaVatTu as $mavattu => $exists) {
            // Nếu mã vật tư này KHÔNG có trong file Excel
            if (!isset($excelMaVatTu[$mavattu])) {
                try {
                    $sqlZero = "UPDATE vattu_thanh_ly_iso 
                                SET soluong_conlai = 0
                                WHERE mavattu = :mavattu";
                    
                    $stmtZero = $db->prepare($sqlZero);
                    $stmtZero->execute([':mavattu' => $mavattu]);
                    
                    // Lấy tên vật tư để hiển thị
                    $stmtInfo = $db->prepare("SELECT ten_tiengnga, ten_tiengviet FROM vattu_thanh_ly_iso WHERE mavattu = :mavattu");
                    $stmtInfo->execute([':mavattu' => $mavattu]);
                    $itemInfo = $stmtInfo->fetch(PDO::FETCH_ASSOC);
                    
                    $zeroed_count++;
                    $zeroed_items[] = [
                        'mavattu' => $mavattu,
                        'ten' => $itemInfo['ten_tiengnga'] ?? $itemInfo['ten_tiengviet'] ?? 'N/A'
                    ];
                } catch (PDOException $e) {
                    $errors[] = "Lỗi set số lượng = 0 cho mã $mavattu: " . $e->getMessage();
                    $error_count++;
                }
            }
        }
        
        $db->commit();
        
        $success = "Hoàn thành! Đã thêm mới $added_count vật tư, cập nhật $updated_count vật tư" .
                   ($zeroed_count > 0 ? ", set số lượng = 0 cho $zeroed_count vật tư" : "") .
                   ($error_count > 0 ? ", $error_count lỗi" : "");
        
    } catch (Exception $e) {
        if (isset($db)) {
            $db->rollBack();
        }
        $error = 'Lỗi đọc file: ' . $e->getMessage();
    }
}

render:

$title = 'Import Vật Tư (So sánh mã)';
require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="bg-white rounded-lg shadow-md p-6 max-w-6xl mx-auto">
    <h1 class="text-2xl font-bold mb-6 flex items-center">
        <i class="fas fa-file-import mr-2 text-blue-600"></i> Import Vật Tư (So sánh mã)
    </h1>

    <?php if (isset($success)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <i class="fas fa-check-circle mr-2"></i>
            <?php echo htmlspecialchars($success); ?>
            
            <?php if (!empty($added_items)): ?>
                <details class="mt-3">
                    <summary class="cursor-pointer font-semibold text-green-800">
                        <i class="fas fa-plus-circle mr-1"></i> Vật tư đã thêm mới (<?php echo count($added_items); ?>)
                    </summary>
                    <div class="mt-2 max-h-96 overflow-y-auto">
                        <table class="min-w-full text-sm border mt-2">
                            <thead class="bg-green-50">
                                <tr>
                                    <th class="border px-2 py-1 text-left">Dòng</th>
                                    <th class="border px-2 py-1 text-left">Mã VT</th>
                                    <th class="border px-2 py-1 text-left">Tên</th>
                                    <th class="border px-2 py-1 text-right">Đơn giá USD</th>
                                    <th class="border px-2 py-1 text-right">Tồn</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($added_items as $item): ?>
                                <tr>
                                    <td class="border px-2 py-1"><?php echo $item['row']; ?></td>
                                    <td class="border px-2 py-1 font-mono text-blue-600"><?php echo htmlspecialchars($item['mavattu']); ?></td>
                                    <td class="border px-2 py-1"><?php echo htmlspecialchars($item['ten']); ?></td>
                                    <td class="border px-2 py-1 text-right"><?php echo $item['dongia_usd'] ? number_format($item['dongia_usd'], 2) : '-'; ?></td>
                                    <td class="border px-2 py-1 text-right"><?php echo $item['soluong'] ? number_format($item['soluong'], 0) : '-'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </details>
            <?php endif; ?>
            
            <?php if (!empty($updated_items)): ?>
                <details class="mt-3">
                    <summary class="cursor-pointer font-semibold text-blue-700">
                        <i class="fas fa-sync-alt mr-1"></i> Vật tư đã cập nhật số lượng (<?php echo count($updated_items); ?>)
                    </summary>
                    <div class="mt-2 max-h-96 overflow-y-auto">
                        <table class="min-w-full text-sm border mt-2">
                            <thead class="bg-blue-50">
                                <tr>
                                    <th class="border px-2 py-1 text-left">Dòng</th>
                                    <th class="border px-2 py-1 text-left">Mã VT</th>
                                    <th class="border px-2 py-1 text-left">Tên</th>
                                    <th class="border px-2 py-1 text-right">Số lượng mới</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($updated_items as $item): ?>
                                <tr>
                                    <td class="border px-2 py-1"><?php echo $item['row']; ?></td>
                                    <td class="border px-2 py-1 font-mono text-blue-600"><?php echo htmlspecialchars($item['mavattu']); ?></td>
                                    <td class="border px-2 py-1"><?php echo htmlspecialchars($item['ten']); ?></td>
                                    <td class="border px-2 py-1 text-right font-semibold text-green-600"><?php echo $item['soluong'] ? number_format($item['soluong'], 0) : '-'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </details>
            <?php endif; ?>
            
            <?php if (!empty($zeroed_items)): ?>
                <details class="mt-3">
                    <summary class="cursor-pointer font-semibold text-orange-700">
                        <i class="fas fa-minus-circle mr-1"></i> Vật tư đã set số lượng = 0 (không có trong file) (<?php echo count($zeroed_items); ?>)
                    </summary>
                    <div class="mt-2 max-h-96 overflow-y-auto">
                        <table class="min-w-full text-sm border mt-2">
                            <thead class="bg-orange-50">
                                <tr>
                                    <th class="border px-2 py-1 text-left">Mã VT</th>
                                    <th class="border px-2 py-1 text-left">Tên</th>
                                    <th class="border px-2 py-1 text-center">Số lượng</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($zeroed_items as $item): ?>
                                <tr>
                                    <td class="border px-2 py-1 font-mono text-orange-600"><?php echo htmlspecialchars($item['mavattu']); ?></td>
                                    <td class="border px-2 py-1"><?php echo htmlspecialchars($item['ten']); ?></td>
                                    <td class="border px-2 py-1 text-center font-semibold text-red-600">0</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </details>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <details class="mt-3">
                    <summary class="cursor-pointer font-semibold text-red-700">Chi tiết lỗi (<?php echo count($errors); ?>)</summary>
                    <ul class="list-disc list-inside mt-2 text-sm text-red-700">
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
            <li>Chuẩn bị file Excel với cấu trúc theo mẫu bên dưới</li>
            <li>Upload file lên hệ thống</li>
            <li>Hệ thống sẽ so sánh mã vật tư trong Excel với database</li>
            <li><strong>Thêm mới</strong> những vật tư có mã chưa tồn tại trong hệ thống</li>
            <li><strong>Cập nhật số lượng</strong> những vật tư đã tồn tại (theo số lượng trong file)</li>
            <li><strong>Set số lượng = 0</strong> những vật tư có trong DB nhưng không có trong file</li>
        </ol>
    </div>

    <!-- Cấu trúc file Excel -->
    <div class="bg-gray-50 border rounded p-4 mb-4">
        <h3 class="font-bold mb-3">
            <i class="fas fa-table mr-1"></i> Cấu trúc file Excel (theo thứ tự cột)
        </h3>
        
        <!-- Download template button -->
        <div class="mb-4 text-center">
            <a href="download_vattu_compare_template.php" 
               class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg shadow-md">
                <i class="fas fa-download mr-2"></i> Tải file Excel mẫu
            </a>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm border">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="px-2 py-1 border text-center">Cột</th>
                        <th class="px-2 py-1 border">Tên trường</th>
                        <th class="px-2 py-1 border text-center">Bắt buộc</th>
                        <th class="px-2 py-1 border">Ví dụ</th>
                        <th class="px-2 py-1 border">Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="px-2 py-1 border text-center font-mono bg-gray-100">A</td>
                        <td class="px-2 py-1 border">STT</td>
                        <td class="px-2 py-1 border text-center">-</td>
                        <td class="px-2 py-1 border font-mono">1, 2, 3...</td>
                        <td class="px-2 py-1 border text-xs">Số thứ tự (tùy chọn)</td>
                    </tr>
                    <tr>
                        <td class="px-2 py-1 border text-center font-mono bg-blue-100">B</td>
                        <td class="px-2 py-1 border font-semibold">Mã vật tư</td>
                        <td class="px-2 py-1 border text-center text-red-600">✓</td>
                        <td class="px-2 py-1 border font-mono">030.037.00001</td>
                        <td class="px-2 py-1 border text-xs text-red-600"><strong>Dùng để so sánh, bắt buộc duy nhất</strong></td>
                    </tr>
                    <tr>
                        <td class="px-2 py-1 border text-center font-mono bg-gray-100">C</td>
                        <td class="px-2 py-1 border">Tên vật tư</td>
                        <td class="px-2 py-1 border text-center">-</td>
                        <td class="px-2 py-1 border">Аэрозоль для чистки контактов - Bình xịt công tắc</td>
                        <td class="px-2 py-1 border text-xs">Có thể có cả tiếng Nga và Việt, ngăn cách bởi " - "</td>
                    </tr>
                    <tr>
                        <td class="px-2 py-1 border text-center font-mono bg-yellow-100">D</td>
                        <td class="px-2 py-1 border">Don gia(usd)</td>
                        <td class="px-2 py-1 border text-center">-</td>
                        <td class="px-2 py-1 border font-mono">17.16</td>
                        <td class="px-2 py-1 border text-xs">Đơn giá bằng USD</td>
                    </tr>
                    <tr>
                        <td class="px-2 py-1 border text-center font-mono bg-green-100">E</td>
                        <td class="px-2 py-1 border">Tồn</td>
                        <td class="px-2 py-1 border text-center">-</td>
                        <td class="px-2 py-1 border font-mono">14</td>
                        <td class="px-2 py-1 border text-xs">Số lượng tồn kho</td>
                    </tr>
                    <tr>
                        <td class="px-2 py-1 border text-center font-mono bg-red-100">F</td>
                        <td class="px-2 py-1 border">Phân loại</td>
                        <td class="px-2 py-1 border text-center">-</td>
                        <td class="px-2 py-1 border font-mono">Vật tư</td>
                        <td class="px-2 py-1 border text-xs">VT, CCDC, TS, PL hoặc tên đầy đủ</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="mt-3 p-3 bg-yellow-50 border border-yellow-300 rounded">
            <p class="text-sm text-yellow-800">
                <i class="fas fa-lightbulb mr-1"></i>
                <strong>Lưu ý:</strong> Dòng đầu tiên (header) sẽ bị bỏ qua. File Excel cần có đúng cấu trúc như trên.
            </p>
        </div>
        
        <div class="mt-3 p-3 bg-blue-50 border border-blue-300 rounded">
            <p class="text-sm text-blue-800 font-semibold mb-2">
                <i class="fas fa-sync-alt mr-1"></i> Logic xử lý:
            </p>
            <ul class="text-sm text-blue-800 space-y-1">
                <li>✅ <strong>Vật tư mới</strong> (mã chưa có trong hệ thống) → <span class="text-green-700 font-semibold">THÊM MỚI</span></li>
                <li>🔄 <strong>Vật tư đã có</strong> (mã đã tồn tại) → <span class="text-blue-700 font-semibold">CẬP NHẬT SỐ LƯỢNG</span> theo file</li>
                <li>🔻 <strong>Vật tư không có trong file</strong> (có trong DB nhưng không có trong Excel) → <span class="text-orange-700 font-semibold">SET SỐ LƯỢNG = 0</span></li>
                <li>⚠️ Chỉ cập nhật <strong>số lượng còn lại</strong>, không cập nhật tên, giá, hoặc thông tin khác</li>
            </ul>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
