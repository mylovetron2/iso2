<?php
$title = 'Phiếu Đặt Hàng';
require_once __DIR__ . '/../layouts/header.php';
?>

<style>
@media print {
    .no-print {
        display: none !important;
    }
    body {
        background: white;
    }
    .signature-section {
        page-break-inside: avoid;
        margin-top: 50px;
    }
}
</style>

<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="bg-blue-600 text-white px-6 py-4">
            <h5 class="text-xl font-bold mb-0">
                <i class="fas fa-file-invoice mr-2"></i> Phiếu Đặt Hàng / Order Form / Заказ на поставку
            </h5>
        </div>
        <div class="p-6">
            <div class="mb-4 text-center">
                <h6 class="text-lg font-semibold">Ngày / Date / Дата: <strong><?= date('d/m/Y') ?></strong></h6>
            </div>

            <form id="formPhieuDatHang" method="POST" action="/iso2/vattuthanhly.php?action=xuatphieudathang">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse border border-gray-300" id="tablePhieuDatHang">
                        <thead>
                            <tr class="bg-blue-100">
                                <th rowspan="2" class="border border-gray-300 px-2 py-3 text-center align-middle" style="width: 60px;">
                                    <strong>П/п<br>(Stt)</strong>
                                </th>
                                <th colspan="3" class="border border-gray-300 px-2 py-3 text-center">
                                    <strong>Наименование (Tên hàng hóa)</strong>
                                </th>
                                <th rowspan="2" class="border border-gray-300 px-2 py-3 text-center align-middle" style="width: 200px;">
                                    <strong>Тех. Характеристики<br>(Đặc tính kỹ thuật)</strong>
                                </th>
                                <th rowspan="2" class="border border-gray-300 px-2 py-3 text-center align-middle" style="width: 100px;">
                                    <strong>Ед.изм<br>Đơn vị tính</strong>
                                </th>
                                <th rowspan="2" class="border border-gray-300 px-2 py-3 text-center align-middle" style="width: 100px;">
                                    <strong>Объем<br>(Số lượng)</strong>
                                </th>
                                <th rowspan="2" class="border border-gray-300 px-2 py-3 text-center align-middle" style="width: 150px;">
                                    <strong>Примечание<br>(Ghi chú)</strong>
                                </th>
                                <th rowspan="2" class="border border-gray-300 px-2 py-3 text-center align-middle no-print" style="width: 80px;">
                                    <strong>Thao tác</strong>
                                </th>
                            </tr>
                            <tr class="bg-blue-100">
                                <th class="border border-gray-300 px-2 py-2 text-center"><strong>На Англ. Языке<br>(Tiếng Anh)</strong></th>
                                <th class="border border-gray-300 px-2 py-2 text-center"><strong>На Русс. языке<br>(Tiếng Nga)</strong></th>
                                <th class="border border-gray-300 px-2 py-2 text-center"><strong>На Вьетнам. Языке<br>(Tiếng Việt)</strong></th>
                            </tr>
                            <tr class="bg-gray-100">
                                <th class="border border-gray-300 px-2 py-2 text-center"><strong>1</strong></th>
                                <th class="border border-gray-300 px-2 py-2 text-center"><strong>2</strong></th>
                                <th class="border border-gray-300 px-2 py-2 text-center"><strong>3</strong></th>
                                <th class="border border-gray-300 px-2 py-2 text-center"><strong>4</strong></th>
                                <th class="border border-gray-300 px-2 py-2 text-center"><strong>5</strong></th>
                                <th class="border border-gray-300 px-2 py-2 text-center"><strong>6</strong></th>
                                <th class="border border-gray-300 px-2 py-2 text-center"><strong>7</strong></th>
                                <th class="border border-gray-300 px-2 py-2 text-center"><strong>8</strong></th>
                                <th class="border border-gray-300 px-2 py-2 text-center no-print"></th>
                            </tr>
                        </thead>
                        <tbody id="tbodyPhieu">
                            <?php 
                            $stt = 1;
                            foreach ($items as $item): 
                                $hasDacTinh = !empty($item['dactinhkt_tiengnga']) || !empty($item['dactinhkt_tiengviet']);
                                $dactinhDisplay = $hasDacTinh ? 'Xem YCKT/ Просмотр TT' : '';
                            ?>
                            <tr class="item-row hover:bg-gray-50">
                                <td class="border border-gray-300 px-2 py-2 text-center align-middle font-semibold"><?= $stt++ ?></td>
                                <td class="border border-gray-300 px-2 py-2 text-sm">
                                    <input type="hidden" name="items[<?= $item['stt'] ?>][ten_tienganh]" value="<?= htmlspecialchars($item['ten_tienganh'] ?? '') ?>">
                                    <?= htmlspecialchars($item['ten_tienganh'] ?? '') ?>
                                </td>
                                <td class="border border-gray-300 px-2 py-2 text-sm">
                                    <input type="hidden" name="items[<?= $item['stt'] ?>][ten_tiengnga]" value="<?= htmlspecialchars($item['ten_tiengnga'] ?? '') ?>">
                                    <?= htmlspecialchars($item['ten_tiengnga'] ?? '') ?>
                                </td>
                                <td class="border border-gray-300 px-2 py-2 text-sm">
                                    <input type="hidden" name="items[<?= $item['stt'] ?>][ten_tiengviet]" value="<?= htmlspecialchars($item['ten_tiengviet'] ?? '') ?>">
                                    <?= htmlspecialchars($item['ten_tiengviet'] ?? '') ?>
                                </td>
                                <td class="border border-gray-300 px-2 py-2 text-center text-sm">
                                    <input type="hidden" name="items[<?= $item['stt'] ?>][dactinhkt_tiengnga]" value="<?= htmlspecialchars($item['dactinhkt_tiengnga'] ?? '') ?>">
                                    <input type="hidden" name="items[<?= $item['stt'] ?>][dactinhkt_tiengviet]" value="<?= htmlspecialchars($item['dactinhkt_tiengviet'] ?? '') ?>">
                                    <span class="text-gray-600"><?= $dactinhDisplay ?></span>
                                    <?php if ($hasDacTinh): ?>
                                        <button type="button" class="ml-1 text-blue-600 hover:text-blue-800 no-print" 
                                                onclick="alert('Tiếng Nga: <?= htmlspecialchars(str_replace(['\r', '\n', '\''], [' ', ' ', '\\\''], $item['dactinhkt_tiengnga'] ?? '')) ?>\n\nTiếng Việt: <?= htmlspecialchars(str_replace(['\r', '\n', '\''], [' ', ' ', '\\\''], $item['dactinhkt_tiengviet'] ?? '')) ?>')">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <td class="border border-gray-300 px-2 py-2 text-center">
                                    <input type="hidden" name="items[<?= $item['stt'] ?>][dvt_tiengnga]" value="<?= htmlspecialchars($item['dvt_tiengnga'] ?? '') ?>">
                                    <input type="hidden" name="items[<?= $item['stt'] ?>][dvt_tiengviet]" value="<?= htmlspecialchars($item['dvt_tiengviet'] ?? '') ?>">
                                    <?= htmlspecialchars($item['dvt_tiengviet'] ?? $item['dvt_tiengnga'] ?? '') ?>
                                </td>
                                <td class="border border-gray-300 px-2 py-2">
                                    <input type="number" 
                                           name="items[<?= $item['stt'] ?>][soluong]" 
                                           class="w-full border rounded px-2 py-1 text-center soluong-input" 
                                           value="<?= $item['soluong_conlai'] ?? 1 ?>" 
                                           min="0" 
                                           step="1"
                                           required>
                                </td>
                                <td class="border border-gray-300 px-2 py-2">
                                    <input type="text" 
                                           name="items[<?= $item['stt'] ?>][ghichu]" 
                                           class="w-full border rounded px-2 py-1 text-sm" 
                                           placeholder="Ghi chú...">
                                </td>
                                <td class="border border-gray-300 px-2 py-2 text-center no-print">
                                    <button type="button" class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-sm btn-remove-row">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 bg-blue-50 border-l-4 border-blue-500 p-4 no-print">
                    <strong class="text-blue-700"><i class="fas fa-info-circle mr-2"></i> Hướng dẫn:</strong>
                    <ul class="list-disc list-inside mt-2 text-sm text-gray-700">
                        <li>Nhập số lượng cần đặt hàng cho từng vật tư</li>
                        <li>Có thể thêm ghi chú cho từng vật tư</li>
                        <li>Click <strong>Xuất Excel</strong> để tải file phiếu đặt hàng</li>
                        <li>Click <strong>In Phiếu</strong> để in trực tiếp</li>
                    </ul>
                </div>

                <div class="mt-6 text-center flex flex-wrap gap-3 justify-center no-print">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold">
                        <i class="fas fa-file-excel mr-2"></i> Xuất Excel
                    </button>
                    <button type="button" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold" id="btnPrint">
                        <i class="fas fa-print mr-2"></i> In Phiếu
                    </button>
                    <a href="/iso2/phieudathang.php?action=create&step=1" class="bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg font-semibold inline-block">
                        <i class="fas fa-edit mr-2"></i> Chọn lại
                    </a>
                    <a href="/iso2/vattuthanhly.php" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-semibold inline-block">
                        <i class="fas fa-times mr-2"></i> Hủy
                    </a>
                </div>

                <div class="grid grid-cols-2 gap-8 mt-12 signature-section">
                    <div class="text-center">
                        <strong class="block mb-16">Người lập phiếu / Prepared by / Подготовлено:</strong>
                        <div class="border-t-2 border-black pt-2 mx-auto" style="width: 200px;">
                            <small>(Ký và ghi rõ họ tên)</small>
                        </div>
                    </div>
                    <div class="text-center">
                        <strong class="block mb-16">Phê duyệt / Approved by / Утверждено:</strong>
                        <div class="border-t-2 border-black pt-2 mx-auto" style="width: 200px;">
                            <small>(Ký và ghi rõ họ tên)</small>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Remove row
    $(document).on('click', '.btn-remove-row', function() {
        if ($('.item-row').length > 1) {
            if (confirm('Xóa vật tư này khỏi phiếu?')) {
                $(this).closest('tr').remove();
                updateSTT();
            }
        } else {
            alert('Phải có ít nhất 1 vật tư trong phiếu!');
        }
    });
    
    // Update STT
    function updateSTT() {
        $('.item-row').each(function(index) {
            $(this).find('td:first').text(index + 1);
        });
    }
    
    // Print
    $('#btnPrint').on('click', function() {
        window.print();
    });
    
    // Validate form
    $('#formPhieuDatHang').on('submit', function(e) {
        let hasError = false;
        $('.soluong-input').each(function() {
            const val = parseFloat($(this).val());
            if (isNaN(val) || val <= 0) {
                hasError = true;
                $(this).addClass('border-red-500');
            } else {
                $(this).removeClass('border-red-500');
            }
        });
        
        if (hasError) {
            e.preventDefault();
            alert('Vui lòng nhập số lượng hợp lệ cho tất cả vật tư (> 0)!');
            return false;
        }
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
