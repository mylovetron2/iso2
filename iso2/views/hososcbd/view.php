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

// Lấy danh sách người thực hiện từ bảng ngthuchien_iso
require_once __DIR__ . '/../../config/database.php';
$db = getDBConnection();
$nguoiThucHienList = [];
if (!empty($item['hoso'])) {
    try {
        $stmt = $db->prepare("
            SELECT stt, mahoso, mamay, somay, hoten, giolv, ngayth, ngaykt,
                   giolv1, giolv2, giolv3, giolv4, giolv5, giolv6,
                   giolv7, giolv8, giolv9, giolv10, giolv11, giolv12
            FROM ngthuchien_iso 
            WHERE mahoso = :mahoso 
            ORDER BY ngayth ASC, hoten ASC
        ");
        $stmt->execute([':mahoso' => $item['hoso']]);
        $nguoiThucHienList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching nguoi thuc hien: " . $e->getMessage());
    }
}
?>

<style>
.card {
    transition: transform 0.2s, box-shadow 0.2s;
}
.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.12);
}
.stat-box {
    transition: all 0.3s ease;
}
.stat-box:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
@media print {
    .no-print {
        display: none !important;
    }
    .card {
        break-inside: avoid;
        page-break-inside: avoid;
    }
}
</style>

<div class="container mx-auto px-4 py-3">
    <!-- Breadcrumb -->
    <nav class="mb-3 text-sm no-print">
        <ol class="flex items-center space-x-2">
            <li><a href="/iso2/index.php" class="text-blue-600 hover:text-blue-800">Trang chủ</a></li>
            <li class="text-gray-500">/</li>
            <li><a href="/iso2/hososcbd.php" class="text-blue-600 hover:text-blue-800">Hồ sơ SCBĐ</a></li>
            <li class="text-gray-500">/</li>
            <li class="text-gray-700">Chi tiết #<?php echo $item['stt']; ?></li>
        </ol>
    </nav>

    <!-- Header with Actions -->
    <div class="flex justify-between items-center mb-4 no-print">
        <h2 class="text-2xl font-bold text-gray-800 flex items-center">
            <i class="fas fa-file-alt text-blue-600 mr-2"></i>
            Chi tiết Hồ sơ SCBĐ
        </h2>
        <div class="flex space-x-2">
            <a href="/iso2/hososcbd.php" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded text-sm" title="Quay lại danh sách">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
            <?php if (hasPermission('hososcbd.edit')): ?>
            <a href="/iso2/hososcbd.php?action=edit&id=<?php echo $item['stt']; ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-2 rounded text-sm" title="Chỉnh sửa">
                <i class="fas fa-edit"></i> Sửa
            </a>
            <?php endif; ?>
            <a href="/iso2/hososcbd.php?action=exportpdf&id=<?php echo $item['stt']; ?>" class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-sm" target="_blank" title="Xuất PDF">
                <i class="fas fa-file-pdf"></i> PDF
            </a>
            <a href="/iso2/hososcbd.php?action=exportword&id=<?php echo $item['stt']; ?>" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded text-sm" target="_blank" title="Xuất Word">
                <i class="fas fa-file-word"></i> Word
            </a>
            <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-sm" title="In trang">
                <i class="fas fa-print"></i> In
            </button>
        </div>
    </div>

    <!-- Stats Summary -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <div class="stat-box bg-gradient-to-br from-blue-500 to-blue-600 text-white p-4 rounded-lg shadow-md">
            <div class="text-sm opacity-90">Số phiếu</div>
            <div class="text-2xl font-bold mt-1"><?php echo htmlspecialchars($item['phieu']); ?></div>
        </div>
        <div class="stat-box bg-gradient-to-br from-green-500 to-green-600 text-white p-4 rounded-lg shadow-md">
            <div class="text-sm opacity-90">Nhóm SC</div>
            <div class="text-2xl font-bold mt-1"><?php echo htmlspecialchars($item['nhomsc']); ?></div>
        </div>
        <div class="stat-box bg-gradient-to-br from-purple-500 to-purple-600 text-white p-4 rounded-lg shadow-md">
            <div class="text-sm opacity-90">Trạng thái</div>
            <div class="text-xl font-bold mt-1">
                <?php if ($item['bg'] == 1): ?>
                    <span class="text-sm">✓ Đã bàn giao</span>
                <?php else: ?>
                    <span class="text-sm">○ Chưa bàn giao</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="stat-box bg-gradient-to-br from-orange-500 to-orange-600 text-white p-4 rounded-lg shadow-md">
            <div class="text-sm opacity-90">Người thực hiện</div>
            <div class="text-2xl font-bold mt-1"><?php echo count($nguoiThucHienList); ?> người</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Thông tin cơ bản -->
        <div class="card bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-blue-600 text-white px-4 py-3">
                <h5 class="text-lg font-bold"><i class="fas fa-info-circle mr-2"></i>Thông tin cơ bản</h5>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div style="display: none;">
                        <label class="text-gray-600 text-sm font-semibold">Mã quản lý:</label>
                        <p class="font-semibold mt-1"><code class="bg-blue-100 px-2 py-1 rounded"><?php echo htmlspecialchars($item['maql']); ?></code></p>
                    </div>
                    <div>
                        <label class="text-gray-600 text-sm font-semibold">Số phiếu:</label>
                        <p class="font-semibold mt-1 text-gray-900"><?php echo htmlspecialchars($item['phieu']); ?></p>
                    </div>
                    <div>
                        <label class="text-gray-600 text-sm font-semibold">Ngày yêu cầu:</label>
                        <p class="font-semibold mt-1 text-gray-900"><?php echo $item['ngayyc'] ? date('d/m/Y', strtotime($item['ngayyc'])) : '-'; ?></p>
                    </div>
                    <div>
                        <label class="text-gray-600 text-sm font-semibold">Hồ sơ:</label>
                        <p class="font-semibold mt-1 text-gray-900"><?php echo htmlspecialchars($item['hoso'] ?? '-'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thông tin thiết bị -->
        <div class="card bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-green-600 text-white px-4 py-3">
                <h5 class="text-lg font-bold"><i class="fas fa-cogs mr-2"></i>Thông tin thiết bị</h5>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-gray-600 text-sm font-semibold">Mã vật tư:</label>
                        <p class="font-semibold mt-1 text-gray-900"><?php echo htmlspecialchars($item['mavt']); ?></p>
                    </div>
                    <div>
                        <label class="text-gray-600 text-sm font-semibold">Số máy:</label>
                        <p class="font-semibold mt-1 text-gray-900"><?php echo htmlspecialchars($item['somay']); ?></p>
                    </div>
                    <div>
                        <label class="text-gray-600 text-sm font-semibold">Model:</label>
                        <p class="font-semibold mt-1 text-gray-900"><?php echo htmlspecialchars($item['model']); ?></p>
                    </div>
                    <div>
                        <label class="text-gray-600 text-sm font-semibold">Vị trí máy BD:</label>
                        <p class="font-semibold mt-1 text-gray-900"><?php echo htmlspecialchars($item['vitrimaybd']); ?></p>
                    </div>
                    <div>
                        <label class="text-gray-600 text-sm font-semibold">Lô:</label>
                        <p class="font-semibold mt-1 text-gray-900"><?php echo htmlspecialchars($item['lo']); ?></p>
                    </div>
                    <div>
                        <label class="text-gray-600 text-sm font-semibold">Giếng:</label>
                        <p class="font-semibold mt-1 text-gray-900"><?php echo htmlspecialchars($item['gieng']); ?></p>
                    </div>
                    <div class="md:col-span-3">
                        <label class="text-gray-600 text-sm font-semibold">Mỏ:</label>
                        <p class="font-semibold mt-1 text-gray-900"><?php echo htmlspecialchars($item['mo']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thông tin đơn vị & yêu cầu -->
        <div class="card bg-white rounded-lg shadow-md overflow-hidden lg:col-span-2">
            <div class="bg-purple-600 text-white px-4 py-3">
                <h5 class="text-lg font-bold"><i class="fas fa-building mr-2"></i>Thông tin đơn vị & Yêu cầu</h5>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-gray-600 text-sm font-semibold">Đơn vị:</label>
                        <p class="font-semibold mt-1 text-gray-900"><?php echo htmlspecialchars($item['madv']); ?></p>
                    </div>
                    <div>
                        <label class="text-gray-600 text-sm font-semibold">Điện thoại:</label>
                        <p class="font-semibold mt-1 text-gray-900"><?php echo htmlspecialchars($item['dienthoai'] ?? '-'); ?></p>
                    </div>
                    <div>
                        <label class="text-gray-600 text-sm font-semibold">Người yêu cầu:</label>
                        <p class="font-semibold mt-1 text-gray-900"><?php echo htmlspecialchars($item['ngyeucau'] ?? '-'); ?></p>
                    </div>
                    <div>
                        <label class="text-gray-600 text-sm font-semibold">Người nhận yêu cầu:</label>
                        <p class="font-semibold mt-1 text-gray-900"><?php echo htmlspecialchars($item['ngnhyeucau'] ?? '-'); ?></p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-gray-600 text-sm font-semibold">Công việc:</label>
                        <p class="mt-1 whitespace-pre-wrap bg-gray-50 p-3 rounded border border-gray-200"><?php echo displayText($item['cv']); ?></p>
                    </div>
                    <?php if ($item['ycthemkh']): ?>
                    <div class="md:col-span-2">
                        <label class="text-gray-600 text-sm font-semibold">Yêu cầu thêm của KH:</label>
                        <p class="mt-1 whitespace-pre-wrap bg-yellow-50 p-3 rounded border border-yellow-200"><?php echo displayText($item['ycthemkh']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Thông tin sửa chữa -->
        <div class="card bg-white rounded-lg shadow-md overflow-hidden lg:col-span-2">
            <div class="bg-orange-600 text-white px-4 py-3">
                <h5 class="text-lg font-bold"><i class="fas fa-wrench mr-2"></i>Thông tin sửa chữa</h5>
            </div>
            <div class="p-4">
            <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-gray-600 text-sm font-semibold">Nhóm SC:</label>
                        <p class="font-semibold mt-1 text-gray-900"><?php echo htmlspecialchars($item['nhomsc']); ?></p>
                    </div>
                    <div>
                        <label class="text-gray-600 text-sm font-semibold">Ngày bắt đầu TT:</label>
                        <p class="font-semibold mt-1 text-gray-900"><?php echo $item['ngaybdtt'] ? date('d/m/Y', strtotime($item['ngaybdtt'])) : '-'; ?></p>
                    </div>
                    <div>
                        <label class="text-gray-600 text-sm font-semibold">Ngày thực hiện:</label>
                        <p class="font-semibold mt-1 text-gray-900"><?php echo $item['ngayth'] ? date('d/m/Y', strtotime($item['ngayth'])) : '-'; ?></p>
                    </div>
                    <div>
                        <label class="text-gray-600 text-sm font-semibold">Ngày kết thúc:</label>
                        <p class="font-semibold mt-1 text-gray-900"><?php echo $item['ngaykt'] ? date('d/m/Y', strtotime($item['ngaykt'])) : '-'; ?></p>
                    </div>
                    <div style="display: none;">
                        <label class="text-gray-600 text-sm font-semibold">Số lượng:</label>
                        <p class="font-semibold mt-1 text-gray-900"><?php echo $item['solg'] ?? '0'; ?></p>
                    </div>
                    <?php if ($item['ttktbefore']): ?>
                    <div class="md:col-span-3">
                        <label class="text-gray-600 text-sm font-semibold">TT KT trước:</label>
                        <p class="mt-1 whitespace-pre-wrap bg-blue-50 p-3 rounded border border-blue-200"><?php echo displayText($item['ttktbefore']); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($item['honghoc']): ?>
                    <div class="md:col-span-3">
                        <label class="text-gray-600 text-sm font-semibold">Hỏng hóc:</label>
                        <p class="mt-1 whitespace-pre-wrap bg-red-50 p-3 rounded border border-red-200"><?php echo displayText($item['honghoc']); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($item['khacphuc']): ?>
                    <div class="md:col-span-3">
                        <label class="text-gray-600 text-sm font-semibold">Khắc phục:</label>
                        <p class="mt-1 whitespace-pre-wrap bg-green-50 p-3 rounded border border-green-200"><?php echo displayText($item['khacphuc']); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($item['ttktafter']): ?>
                    <div class="md:col-span-3">
                        <label class="text-gray-600 text-sm font-semibold">Tình trạng kỹ thuật sau khi SC/BĐ:</label>
                        <p class="mt-1 whitespace-pre-wrap bg-blue-50 p-3 rounded border border-blue-200"><?php echo displayText($item['ttktafter']); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($item['noidung']): ?>
                    <div class="md:col-span-3">
                        <label class="text-gray-600 text-sm font-semibold">Nội dung sửa chữa:</label>
                        <p class="mt-1 whitespace-pre-wrap bg-gray-50 p-3 rounded border border-gray-200"><?php echo displayText($item['noidung']); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($item['ketluan']): ?>
                    <div class="md:col-span-3">
                        <label class="text-gray-600 text-sm font-semibold">Kết luận:</label>
                        <p class="mt-1 whitespace-pre-wrap bg-indigo-50 p-3 rounded border border-indigo-200 font-semibold"><?php echo displayText($item['ketluan']); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($item['xemxetxuong']): ?>
                    <div class="md:col-span-3">
                        <label class="text-gray-600 text-sm font-semibold">Xem xét xưởng:</label>
                        <p class="mt-1 whitespace-pre-wrap bg-gray-50 p-3 rounded border border-gray-200"><?php echo displayText($item['xemxetxuong']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Người thực hiện -->
        <?php if (!empty($nguoiThucHienList)): ?>
        <div class="card bg-white rounded-lg shadow-md overflow-hidden lg:col-span-2">
            <div class="bg-indigo-600 text-white px-4 py-3">
                <h5 class="text-lg font-bold"><i class="fas fa-users mr-2"></i>Người thực hiện (<?php echo count($nguoiThucHienList); ?>)</h5>
            </div>
            <div class="p-4">
            <div class="p-4">
                <div class="overflow-x-auto">
                    <table class="min-w-full border border-gray-300">
                        <thead class="bg-indigo-50">
                            <tr>
                                <th class="px-3 py-2 border text-left text-sm font-semibold">STT</th>
                                <th class="px-3 py-2 border text-left text-sm font-semibold">Họ tên</th>
                                <th class="px-3 py-2 border text-left text-sm font-semibold">Mã máy</th>
                                <th class="px-3 py-2 border text-left text-sm font-semibold">Số máy</th>
                                <th class="px-3 py-2 border text-left text-sm font-semibold">Ngày TH</th>
                                <th class="px-3 py-2 border text-left text-sm font-semibold">Ngày KT</th>
                                <th class="px-3 py-2 border text-center text-sm font-semibold">Giờ LV</th>
                                <th class="px-3 py-2 border text-left text-sm font-semibold">Chi tiết giờ (Tháng 1-12)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($nguoiThucHienList as $index => $nguoi): ?>
                            <tr class="hover:bg-indigo-50">
                                <td class="px-3 py-2 border text-center"><?php echo $index + 1; ?></td>
                                <td class="px-3 py-2 border">
                                    <span class="font-semibold text-indigo-700">
                                        <?php echo htmlspecialchars($nguoi['hoten']); ?>
                                    </span>
                                </td>
                                <td class="px-3 py-2 border">
                                    <code class="bg-gray-100 px-2 py-1 rounded text-xs">
                                        <?php echo htmlspecialchars($nguoi['mamay']); ?>
                                    </code>
                                </td>
                                <td class="px-3 py-2 border">
                                    <code class="bg-gray-100 px-2 py-1 rounded text-xs">
                                        <?php echo htmlspecialchars($nguoi['somay']); ?>
                                    </code>
                                </td>
                                <td class="px-3 py-2 border text-sm">
                                    <?php echo $nguoi['ngayth'] ? date('d/m/Y', strtotime($nguoi['ngayth'])) : '-'; ?>
                                </td>
                                <td class="px-3 py-2 border text-sm">
                                    <?php echo $nguoi['ngaykt'] ? date('d/m/Y', strtotime($nguoi['ngaykt'])) : '-'; ?>
                                </td>
                                <td class="px-3 py-2 border text-center">
                                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full font-bold">
                                        <?php echo number_format($nguoi['giolv']); ?>h
                                    </span>
                                </td>
                                <td class="px-3 py-2 border">
                                    <div class="grid grid-cols-6 gap-1 text-xs">
                                        <?php 
                                        $hasDetail = false;
                                        for ($i = 1; $i <= 12; $i++): 
                                            $gioField = 'giolv' . $i;
                                            $gio = $nguoi[$gioField] ?? 0;
                                            if ($gio > 0) $hasDetail = true;
                                        endfor;
                                        
                                        if ($hasDetail):
                                            for ($i = 1; $i <= 12; $i++): 
                                                $gioField = 'giolv' . $i;
                                                $gio = $nguoi[$gioField] ?? 0;
                                                if ($gio > 0):
                                        ?>
                                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-center" title="Tháng <?php echo $i; ?>">
                                                T<?php echo $i; ?>: <strong><?php echo $gio; ?>h</strong>
                                            </span>
                                        <?php 
                                                endif;
                                            endfor;
                                        else:
                                        ?>
                                            <span class="text-gray-400 col-span-6 text-center">-</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-indigo-100 font-bold">
                            <tr>
                                <td colspan="6" class="px-3 py-2 border text-right">Tổng cộng:</td>
                                <td class="px-3 py-2 border text-center">
                                    <span class="bg-indigo-600 text-white px-3 py-1 rounded-full">
                                        <?php 
                                        $tongGio = array_sum(array_column($nguoiThucHienList, 'giolv'));
                                        echo number_format($tongGio);
                                        ?>h
                                    </span>
                                </td>
                                <td class="px-3 py-2 border"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

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
        <div class="card bg-white rounded-lg shadow-md overflow-hidden lg:col-span-2">
            <div class="bg-teal-600 text-white px-4 py-3">
                <h5 class="text-lg font-bold"><i class="fas fa-tools mr-2"></i>Thiết bị đo sửa chữa</h5>
            </div>
            <div class="p-4">
            <div class="p-4">
                <div class="overflow-x-auto">
                    <table class="min-w-full border border-gray-300">
                        <thead class="bg-teal-50">
                            <tr>
                                <th class="px-4 py-2 border text-left font-semibold">STT</th>
                                <th class="px-4 py-2 border text-left font-semibold">Thiết bị đo SC</th>
                                <th class="px-4 py-2 border text-left font-semibold">Serial</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($i = 0; $i <= 4; $i++): 
                                $tbField = $i == 0 ? 'tbdosc' : "tbdosc$i";
                                $serialField = $i == 0 ? 'serialtbdosc' : "serialtbdosc$i";
                                if (!empty($item[$tbField]) || !empty($item[$serialField])):
                            ?>
                            <tr class="hover:bg-teal-50">
                                <td class="px-4 py-2 border text-center font-semibold"><?php echo $i + 1; ?></td>
                                <td class="px-4 py-2 border"><?php echo htmlspecialchars($item[$tbField] ?? '-'); ?></td>
                                <td class="px-4 py-2 border"><code class="bg-gray-100 px-2 py-1 rounded text-sm"><?php echo htmlspecialchars($item[$serialField] ?? '-'); ?></code></td>
                            </tr>
                            <?php endif; endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
