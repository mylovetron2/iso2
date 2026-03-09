<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
require_once __DIR__ . '/../../includes/permissions.php';
$title = 'Import Kế hoạch Bảo dưỡng';
require_once __DIR__. '/../layouts/header.php'; 
?>

<div class="container mx-auto px-4 py-6">
    <div class="max-w-3xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold flex items-center">
                <i class="fas fa-file-import mr-2 text-green-600"></i> Import Kế hoạch Bảo dưỡng từ Excel
            </h1>
            <a href="kehoachbaoduongdinhky.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Quay lại
            </a>
        </div>

        <!-- Instructions -->
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
            <h3 class="font-semibold text-blue-900 mb-2">
                <i class="fas fa-info-circle mr-1"></i> Hướng dẫn import:
            </h3>
            <ol class="list-decimal list-inside space-y-1 text-sm text-blue-800">
                <li>Tải file mẫu Excel bằng nút bên dưới</li>
                <li>Điền thông tin thiết bị vào file Excel theo đúng format</li>
                <li>Cột "TO" = có kế hoạch bảo dưỡng trong quý đó</li>
                <li>Chọn file Excel đã điền và nhấn "Import"</li>
                <li>Chọn "Xóa dữ liệu cũ" nếu muốn thay thế hoàn toàn kế hoạch năm đó</li>
            </ol>
        </div>

        <!-- Download Template -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="font-semibold mb-4">
                <i class="fas fa-download mr-2 text-purple-600"></i> Tải file mẫu Excel
            </h3>
            <p class="text-gray-600 mb-4">File mẫu đã có format chuẩn với các cột: STT, Tên thiết bị, Số S/N, Quí 1, Quí 2, Quí 3, Quí 4, Ghi chú</p>
            <a href="download_template_bao_duong.php" class="inline-block bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded">
                <i class="fas fa-file-excel mr-2"></i> Tải file mẫu Excel
            </a>
        </div>

        <!-- Import Form -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold mb-4">
                <i class="fas fa-upload mr-2 text-green-600"></i> Upload file Excel
            </h3>
            
            <form method="POST" action="kehoachbaoduongdinhky.php?action=processImport" enctype="multipart/form-data">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">
                            Năm kế hoạch: <span class="text-red-500">*</span>
                        </label>
                        <select name="nam" required class="w-full border rounded px-3 py-2">
                            <?php
                            $currentYear = (int)date('Y');
                            for ($y = $currentYear + 2; $y >= $currentYear - 2; $y--) {
                                $selected = ($y == $currentYear) ? 'selected' : '';
                                echo "<option value='$y' $selected>$y</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">
                            File Excel: <span class="text-red-500">*</span>
                        </label>
                        <input type="file" name="excel_file" accept=".xlsx,.xls" required 
                               class="w-full border rounded px-3 py-2">
                        <p class="text-xs text-gray-500 mt-1">
                            Chấp nhận file .xlsx hoặc .xls (tối đa 5MB)
                        </p>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="clear_existing" value="1" id="clearExisting" class="mr-2">
                        <label for="clearExisting" class="text-sm">
                            Xóa dữ liệu cũ của năm này trước khi import
                            <span class="text-red-500">(Khuyến nghị)</span>
                        </label>
                    </div>

                    <div class="pt-4 border-t flex gap-2">
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded">
                            <i class="fas fa-upload mr-1"></i> Import dữ liệu
                        </button>
                        <a href="kehoachbaoduongdinhky.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded">
                            <i class="fas fa-times mr-1"></i> Hủy
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Example Data -->
        <div class="bg-white rounded-lg shadow p-6 mt-6">
            <h3 class="font-semibold mb-4">
                <i class="fas fa-table mr-2 text-blue-600"></i> Ví dụ dữ liệu trong Excel
            </h3>
            <div class="overflow-x-auto">
                <table class="min-w-full border text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-1">STT</th>
                            <th class="border px-2 py-1">Tên thiết bị</th>
                            <th class="border px-2 py-1">Số S/N</th>
                            <th class="border px-2 py-1">Quí 1</th>
                            <th class="border px-2 py-1">Quí 2</th>
                            <th class="border px-2 py-1">Quí 3</th>
                            <th class="border px-2 py-1">Quí 4</th>
                            <th class="border px-2 py-1">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border px-2 py-1 text-center">1</td>
                            <td class="border px-2 py-1">GTET</td>
                            <td class="border px-2 py-1">11533904</td>
                            <td class="border px-2 py-1 text-center bg-green-50">TO</td>
                            <td class="border px-2 py-1 text-center"></td>
                            <td class="border px-2 py-1 text-center"></td>
                            <td class="border px-2 py-1 text-center"></td>
                            <td class="border px-2 py-1"></td>
                        </tr>
                        <tr>
                            <td class="border px-2 py-1 text-center">2</td>
                            <td class="border px-2 py-1">IDT</td>
                            <td class="border px-2 py-1">11680456</td>
                            <td class="border px-2 py-1 text-center bg-green-50">TO</td>
                            <td class="border px-2 py-1 text-center"></td>
                            <td class="border px-2 py-1 text-center"></td>
                            <td class="border px-2 py-1 text-center"></td>
                            <td class="border px-2 py-1">Máy đo nhiệt độ cao 180°C</td>
                        </tr>
                        <tr>
                            <td class="border px-2 py-1 text-center">3</td>
                            <td class="border px-2 py-1">DSNT</td>
                            <td class="border px-2 py-1">11534471</td>
                            <td class="border px-2 py-1 text-center"></td>
                            <td class="border px-2 py-1 text-center bg-yellow-50">TO</td>
                            <td class="border px-2 py-1 text-center"></td>
                            <td class="border px-2 py-1 text-center"></td>
                            <td class="border px-2 py-1"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-gray-500 mt-2">
                <strong>Lưu ý:</strong> "TO" = Có kế hoạch bảo dưỡng (Technical Overhaul)
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
