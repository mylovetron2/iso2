<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Chi tiết Hồ sơ SCBĐ';
require_once __DIR__ . '/../layouts/header.php'; 

// Get item details
$stt = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$stt) {
    header('Location: /iso2/hososcbd.php');
    exit;
}

require_once __DIR__ . '/../../models/HoSoSCBD.php';
$model = new HoSoSCBD();
$item = $model->findById($stt);

if (!$item) {
    header('Location: /iso2/hososcbd.php?error=notfound');
    exit;
}
?>
<div class="max-w-6xl mx-auto bg-white rounded-lg shadow-md p-4 md:p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-xl md:text-2xl font-bold flex items-center">
            <i class="fas fa-file-alt mr-2"></i> Chi tiết Hồ sơ SCBĐ
        </h1>
        <div class="flex gap-2">
            <a href="hososcbd.php?action=edit&id=<?php echo $item['stt']; ?>" 
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                <i class="fas fa-edit mr-1"></i> Sửa
            </a>
            <a href="hososcbd.php" 
               class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Quay lại
            </a>
        </div>
    </div>

    <!-- Thông tin cơ bản -->
    <div class="border-l-4 border-blue-500 pl-4 mb-6">
        <h2 class="text-lg font-bold mb-3 text-blue-700">
            <i class="fas fa-info-circle mr-2"></i>Thông tin cơ bản
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="text-gray-600 text-sm">Mã quản lý:</label>
                <p class="font-semibold"><code class="bg-blue-100 px-2 py-1 rounded"><?php echo htmlspecialchars($item['maql']); ?></code></p>
            </div>
            <div>
                <label class="text-gray-600 text-sm">Tình trạng kỹ thuật sau khi SC/BĐ:</label>
                <p class="font-semibold"><?php echo htmlspecialchars($item['phieu']); ?></p>
            </div>
            <div>
                <label class="text-gray-600 text-sm">Ngày yêu cầu:</label>
                <p class="font-semibold"><?php echo $item['ngayyc'] ? date('d/m/Y', strtotime($item['ngayyc'])) : '-'; ?></p>
            </div>
        </div>
    </div>

    <!-- Thông tin thiết bị -->
    <div class="border-l-4 border-green-500 pl-4 mb-6">
        <h2 class="text-lg font-bold mb-3 text-green-700">
            <i class="fas fa-cogs mr-2"></i>Thông tin thiết bị
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="text-gray-600 text-sm">Mã vật tư:</label>
                <p class="font-semibold"><?php echo htmlspecialchars($item['mavt']); ?></p>
            </div>
            <div>
                <label class="text-gray-600 text-sm">Số máy:</label>
                <p class="font-semibold"><?php echo htmlspecialchars($item['somay']); ?></p>
            </div>
            <div>
                <label class="text-gray-600 text-sm">Model:</label>
                <p class="font-semibold"><?php echo htmlspecialchars($item['model']); ?></p>
            </div>
            <div>
                <label class="text-gray-600 text-sm">Vị trí máy BD:</label>
                <p class="font-semibold"><?php echo htmlspecialchars($item['vitrimaybd']); ?></p>
            </div>
            <div>
                <label class="text-gray-600 text-sm">Lô:</label>
                <p class="font-semibold"><?php echo htmlspecialchars($item['lo']); ?></p>
            </div>
            <div>
                <label class="text-gray-600 text-sm">Giếng:</label>
                <p class="font-semibold"><?php echo htmlspecialchars($item['gieng']); ?></p>
            </div>
            <div class="md:col-span-3">
                <label class="text-gray-600 text-sm">Mỏ:</label>
                <p class="font-semibold"><?php echo htmlspecialchars($item['mo']); ?></p>
            </div>
        </div>
    </div>

    <!-- Thông tin đơn vị & yêu cầu -->
    <div class="border-l-4 border-purple-500 pl-4 mb-6">
        <h2 class="text-lg font-bold mb-3 text-purple-700">
            <i class="fas fa-building mr-2"></i>Thông tin đơn vị & Yêu cầu
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-gray-600 text-sm">Đơn vị:</label>
                <p class="font-semibold"><?php echo htmlspecialchars($item['madv']); ?></p>
            </div>
            <div>
                <label class="text-gray-600 text-sm">Điện thoại:</label>
                <p class="font-semibold"><?php echo htmlspecialchars($item['dienthoai'] ?? '-'); ?></p>
            </div>
            <div>
                <label class="text-gray-600 text-sm">Người yêu cầu:</label>
                <p class="font-semibold"><?php echo htmlspecialchars($item['ngyeucau'] ?? '-'); ?></p>
            </div>
            <div>
                <label class="text-gray-600 text-sm">Người nhận yêu cầu:</label>
                <p class="font-semibold"><?php echo htmlspecialchars($item['ngnhyeucau'] ?? '-'); ?></p>
            </div>
            <div class="md:col-span-2">
                <label class="text-gray-600 text-sm">Công việc:</label>
                <p class="font-semibold whitespace-pre-wrap bg-gray-50 p-3 rounded"><?php echo displayText($item['cv']); ?></p>
            </div>
            <?php if ($item['ycthemkh']): ?>
            <div class="md:col-span-2">
                <label class="text-gray-600 text-sm">Yêu cầu thêm của KH:</label>
                <p class="font-semibold whitespace-pre-wrap bg-gray-50 p-3 rounded"><?php echo displayText($item['ycthemkh']); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Thông tin sửa chữa -->
    <div class="border-l-4 border-orange-500 pl-4 mb-6">
        <h2 class="text-lg font-bold mb-3 text-orange-700">
            <i class="fas fa-wrench mr-2"></i>Thông tin sửa chữa
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="text-gray-600 text-sm">Nhóm SC:</label>
                <p class="font-semibold"><?php echo htmlspecialchars($item['nhomsc']); ?></p>
            </div>
            <div>
                <label class="text-gray-600 text-sm">Ngày bắt đầu TT:</label>
                <p class="font-semibold"><?php echo $item['ngaybdtt'] ? date('d/m/Y', strtotime($item['ngaybdtt'])) : '-'; ?></p>
            </div>
            <div>
                <label class="text-gray-600 text-sm">Ngày thực hiện:</label>
                <p class="font-semibold"><?php echo $item['ngayth'] ? date('d/m/Y', strtotime($item['ngayth'])) : '-'; ?></p>
            </div>
            <div>
                <label class="text-gray-600 text-sm">Ngày kết thúc:</label>
                <p class="font-semibold"><?php echo $item['ngaykt'] ? date('d/m/Y', strtotime($item['ngaykt'])) : '-'; ?></p>
            </div>
            <div>
                <label class="text-gray-600 text-sm">Số lượng:</label>
                <p class="font-semibold"><?php echo $item['solg'] ?? '0'; ?></p>
            </div>
            <div>
                <label class="text-gray-600 text-sm">Hồ sơ:</label>
                <p class="font-semibold"><?php echo htmlspecialchars($item['hoso'] ?? '-'); ?></p>
            </div>
            <?php if ($item['ttktbefore']): ?>
            <div class="md:col-span-3">
                <label class="text-gray-600 text-sm">TT KT trước:</label>
                <p class="whitespace-pre-wrap bg-gray-50 p-3 rounded"><?php echo displayText($item['ttktbefore']); ?></p>
            </div>
            <?php endif; ?>
            <?php if ($item['honghoc']): ?>
            <div class="md:col-span-3">
                <label class="text-gray-600 text-sm">Hỏng hóc:</label>
                <p class="whitespace-pre-wrap bg-red-50 p-3 rounded"><?php echo displayText($item['honghoc']); ?></p>
            </div>
            <?php endif; ?>
            <?php if ($item['khacphuc']): ?>
            <div class="md:col-span-3">
                <label class="text-gray-600 text-sm">Khắc phục:</label>
                <p class="whitespace-pre-wrap bg-green-50 p-3 rounded"><?php echo displayText($item['khacphuc']); ?></p>
            </div>
            <?php endif; ?>
            <?php if ($item['ttktafter']): ?>
            <div class="md:col-span-3">
                <label class="text-gray-600 text-sm">Tình trạng kỹ thuật sau khi SC/BĐ:</label>
                <p class="whitespace-pre-wrap bg-gray-50 p-3 rounded"><?php echo displayText($item['ttktafter']); ?></p>
            </div>
            <?php endif; ?>
            <?php if ($item['noidung']): ?>
            <div class="md:col-span-3">
                <label class="text-gray-600 text-sm">Nội dung sửa chữa:</label>
                <p class="whitespace-pre-wrap bg-gray-50 p-3 rounded"><?php echo htmlspecialchars($item['noidung']); ?></p>
            </div>
            <?php endif; ?>
            <?php if ($item['ketluan']): ?>
            <div class="md:col-span-3">
                <label class="text-gray-600 text-sm">Kết luận:</label>
                <p class="whitespace-pre-wrap bg-blue-50 p-3 rounded font-semibold"><?php echo htmlspecialchars($item['ketluan']); ?></p>
            </div>
            <?php endif; ?>
            <?php if ($item['xemxetxuong']): ?>
            <div class="md:col-span-3">
                <label class="text-gray-600 text-sm">Xem xét xưởng:</label>
                <p class="whitespace-pre-wrap bg-gray-50 p-3 rounded"><?php echo htmlspecialchars($item['xemxetxuong']); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Thiết bị đo SC -->
    <?php 
    $hasTools = false;
    for ($i = 0; $i <= 4; $i++) {
        $tbField = $i == 0 ? 'tbdosc' : "tbdosc$i";
        $serialField = $i == 0 ? 'serialtbdosc' : "serialtbdosc$i";
        if (!empty($item[$tbField]) || !empty($item[$serialField])) {
            $hasTools = true;
            break;
        }
    }
    if ($hasTools): 
    ?>
    <div class="border-l-4 border-teal-500 pl-4 mb-6">
        <h2 class="text-lg font-bold mb-3 text-teal-700">
            <i class="fas fa-tools mr-2"></i>Thiết bị đo sửa chữa
        </h2>
        <div class="overflow-x-auto">
            <table class="min-w-full border">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 border text-left">STT</th>
                        <th class="px-4 py-2 border text-left">Thiết bị đo SC</th>
                        <th class="px-4 py-2 border text-left">Serial</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($i = 0; $i <= 4; $i++): 
                        $tbField = $i == 0 ? 'tbdosc' : "tbdosc$i";
                        $serialField = $i == 0 ? 'serialtbdosc' : "serialtbdosc$i";
                        if (!empty($item[$tbField]) || !empty($item[$serialField])):
                    ?>
                    <tr>
                        <td class="px-4 py-2 border"><?php echo $i + 1; ?></td>
                        <td class="px-4 py-2 border"><?php echo htmlspecialchars($item[$tbField] ?? '-'); ?></td>
                        <td class="px-4 py-2 border"><?php echo htmlspecialchars($item[$serialField] ?? '-'); ?></td>
                    </tr>
                    <?php endif; endfor; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bàn giao -->
    <div class="border-l-4 border-red-500 pl-4">
        <h2 class="text-lg font-bold mb-3 text-red-700">
            <i class="fas fa-handshake mr-2"></i>Thông tin bàn giao
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="text-gray-600 text-sm">Trạng thái bàn giao:</label>
                <p class="font-semibold">
                    <?php if ($item['bg'] == 1): ?>
                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full">✓ Đã bàn giao</span>
                    <?php else: ?>
                        <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full">○ Chưa bàn giao</span>
                    <?php endif; ?>
                </p>
            </div>
            <div>
                <label class="text-gray-600 text-sm">Số lần BG:</label>
                <p class="font-semibold"><?php echo $item['slbg'] ?? '0'; ?></p>
            </div>
            <div>
                <label class="text-gray-600 text-sm">Dòng:</label>
                <p class="font-semibold"><?php echo htmlspecialchars($item['dong'] ?? '-'); ?></p>
            </div>
            <?php if ($item['ghichu']): ?>
            <div class="md:col-span-3">
                <label class="text-gray-600 text-sm">Ghi chú:</label>
                <p class="whitespace-pre-wrap bg-gray-50 p-3 rounded"><?php echo htmlspecialchars($item['ghichu']); ?></p>
            </div>
            <?php endif; ?>
            <?php if ($item['ghichufinal']): ?>
            <div class="md:col-span-3">
                <label class="text-gray-600 text-sm">Ghi chú cuối:</label>
                <p class="whitespace-pre-wrap bg-yellow-50 p-3 rounded font-semibold"><?php echo htmlspecialchars($item['ghichufinal']); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
