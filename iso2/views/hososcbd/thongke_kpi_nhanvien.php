<?php
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

$title = 'Thống kê KPI nhân viên';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold flex items-center text-gray-800">
                <i class="fas fa-user-check mr-2 text-blue-600"></i>
                Thống kê KPI theo nhân viên
            </h1>
            <p class="text-sm text-gray-600 mt-2">
                Tỷ lệ = Số thiết bị hoàn thành đúng tiến độ (chỉ tính BD và KT) / Tổng số thiết bị được giao * 100
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="hososcbd.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm w-fit">
                <i class="fas fa-arrow-left mr-1"></i> Quay lại hồ sơ SCBD
            </a>
            <a href="thongke_kpi_nhanvien_scbd.php?<?php echo http_build_query(['from' => $fromDate, 'to' => $toDate, 'q' => $keyword, 'export' => 'excel']); ?>"
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm w-fit">
                <i class="fas fa-file-excel mr-1"></i> In Excel
            </a>
        </div>
    </div>

    <form method="get" class="bg-gray-50 rounded-lg p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label class="block text-sm text-gray-700 mb-1">Từ ngày</label>
                <input type="date" name="from" value="<?php echo htmlspecialchars($fromDate); ?>" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm text-gray-700 mb-1">Đến ngày</label>
                <input type="date" name="to" value="<?php echo htmlspecialchars($toDate); ?>" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm text-gray-700 mb-1">Nhân viên</label>
                <input type="text" name="q" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Nhập tên nhân viên" class="w-full border rounded px-3 py-2">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded flex-1">
                    <i class="fas fa-filter mr-1"></i> Lọc
                </button>
                <a href="thongke_kpi_nhanvien_scbd.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                    <i class="fas fa-rotate-right"></i>
                </a>
            </div>
        </div>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="text-sm text-blue-600 font-medium">Tổng nhân viên</div>
            <div class="text-2xl font-bold text-blue-800"><?php echo number_format(count($rows)); ?></div>
        </div>
        <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
            <div class="text-sm text-indigo-600 font-medium">Tổng thiết bị được giao</div>
            <div class="text-2xl font-bold text-indigo-800"><?php echo number_format($totalAssigned); ?></div>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="text-sm text-green-600 font-medium">Tỷ lệ chung</div>
            <div class="text-2xl font-bold text-green-800"><?php echo number_format($overallRate, 2); ?>%</div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-3 py-2 text-center">STT</th>
                    <th class="border px-3 py-2 text-left">Nhân viên (nhấp để xem chi tiết)</th>
                    <th class="border px-3 py-2 text-center">Tổng thiết bị được giao</th>
                    <th class="border px-3 py-2 text-center">Đạt tiêu chí KPI</th>
                    <th class="border px-3 py-2 text-center">Chưa đạt</th>
                    <th class="border px-3 py-2 text-center">Tỷ lệ (%)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="6" class="border px-3 py-8 text-center text-gray-500">
                            Không có dữ liệu trong khoảng thời gian đã chọn
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $index = 1; ?>
                    <?php foreach ($rows as $row): ?>
                        <?php
                            $detailRowId = 'detail-row-' . $index;
                            $assigned = (int)$row['tong_thiet_bi_duoc_yeu_cau'];
                            $qualified = (int)$row['so_thiet_bi_dat_tieu_chi'];
                            $notQualified = max(0, $assigned - $qualified);
                            $rate = isset($row['ty_le_phan_tram']) ? (float)$row['ty_le_phan_tram'] : 0.0;
                            $rateClass = $rate >= 90 ? 'text-green-700' : ($rate >= 70 ? 'text-yellow-700' : 'text-red-700');
                            $employeeName = (string)$row['nhan_vien'];
                            $details = $detailMap[$employeeName] ?? [];
                        ?>
                        <tr class="hover:bg-gray-50 cursor-pointer js-toggle-detail" data-target-id="<?php echo $detailRowId; ?>">
                            <td class="border px-3 py-2 text-center"><?php echo $index++; ?></td>
                            <td class="border px-3 py-2 font-medium">
                                <div class="flex items-center justify-between gap-2">
                                    <span><?php echo htmlspecialchars($employeeName); ?></span>
                                    <span class="text-xs text-blue-700 js-toggle-icon">▼</span>
                                </div>
                            </td>
                            <td class="border px-3 py-2 text-center"><?php echo number_format($assigned); ?></td>
                            <td class="border px-3 py-2 text-center text-green-700 font-semibold"><?php echo number_format($qualified); ?></td>
                            <td class="border px-3 py-2 text-center text-red-700"><?php echo number_format($notQualified); ?></td>
                            <td class="border px-3 py-2 text-center font-bold <?php echo $rateClass; ?>"><?php echo number_format($rate, 2); ?>%</td>
                        </tr>
                        <tr id="<?php echo $detailRowId; ?>" class="hidden bg-gray-50">
                            <td colspan="6" class="border px-3 py-3">
                                <?php if (empty($details)): ?>
                                    <div class="text-sm text-gray-500">Không có dữ liệu chi tiết.</div>
                                <?php else: ?>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full border text-xs bg-white">
                                            <thead class="bg-gray-100">
                                                <tr>
                                                    <th class="border px-2 py-2 text-center">Phiếu</th>
                                                    <th class="border px-2 py-2 text-center">Hồ sơ</th>
                                                    <th class="border px-2 py-2 text-center">Mã VT</th>
                                                    <th class="border px-2 py-2 text-center">Số máy</th>
                                                    <th class="border px-2 py-2 text-center">Ngày YC</th>
                                                    <th class="border px-2 py-2 text-center">Ngày TH</th>
                                                    <th class="border px-2 py-2 text-center">Ngày KT</th>
                                                    <th class="border px-2 py-2 text-center">KPI</th>
                                                    <th class="border px-2 py-2 text-center">Chi tiết</th>
                                                    <th class="border px-2 py-2 text-center">Kết quả</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($details as $d): ?>
                                                    <?php
                                                        $kpiValue = (string)($d['ket_luan_kpi'] ?? 'chua_du_du_lieu');
                                                        if ($kpiValue === 'dat') {
                                                            $kpiValue = 'Đạt';
                                                        } elseif ($kpiValue === 'chua_du_du_lieu') {
                                                            $kpiValue = 'chưa gán KPI';
                                                        }
                                                        $isQualified = ((int)($d['dat_tieu_chi'] ?? 0)) === 1;
                                                    ?>
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="border px-2 py-2 text-center"><?php echo htmlspecialchars((string)($d['phieu'] ?? '')); ?></td>
                                                        <td class="border px-2 py-2 text-center"><?php echo htmlspecialchars((string)($d['hoso'] ?? '')); ?></td>
                                                        <td class="border px-2 py-2 text-center"><?php echo htmlspecialchars((string)($d['mavt'] ?? '')); ?></td>
                                                        <td class="border px-2 py-2 text-center"><?php echo htmlspecialchars((string)($d['somay'] ?? '')); ?></td>
                                                        <td class="border px-2 py-2 text-center"><?php echo htmlspecialchars((string)($d['ngayyc'] ?? '')); ?></td>
                                                        <td class="border px-2 py-2 text-center"><?php echo htmlspecialchars((string)($d['ngayth'] ?? '')); ?></td>
                                                        <td class="border px-2 py-2 text-center"><?php echo htmlspecialchars((string)($d['ngaykt'] ?? '')); ?></td>
                                                        <td class="border px-2 py-2 text-center"><?php echo htmlspecialchars($kpiValue); ?></td>
                                                        <td class="border px-2 py-2 text-center">
                                                            <a href="hososcbd_repair_details.php?id=<?php echo (int)($d['hososcbd_stt'] ?? 0); ?>"
                                                               class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium"
                                                               target="_blank"
                                                               rel="noopener noreferrer">
                                                                <i class="fas fa-external-link-alt mr-1"></i> Xem
                                                            </a>
                                                        </td>
                                                        <td class="border px-2 py-2 text-center font-semibold <?php echo $isQualified ? 'text-green-700' : 'text-red-700'; ?>">
                                                            <?php echo $isQualified ? 'Đạt' : 'Chưa đạt'; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-5 text-xs text-gray-500 leading-6">
        <p><strong>Lưu ý:</strong> Chỉ tính các hồ sơ có loại công việc BD hoặc KT; loại SC và BDDK không được tính vào KPI.</p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var toggles = document.querySelectorAll('.js-toggle-detail');
    toggles.forEach(function (row) {
        row.addEventListener('click', function () {
            var targetId = row.getAttribute('data-target-id');
            if (!targetId) {
                return;
            }

            var detailRow = document.getElementById(targetId);
            if (!detailRow) {
                return;
            }

            var icon = row.querySelector('.js-toggle-icon');
            var willShow = detailRow.classList.contains('hidden');

            detailRow.classList.toggle('hidden');
            if (icon) {
                icon.textContent = willShow ? '▲' : '▼';
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
