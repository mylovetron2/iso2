<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Thông tin sửa chữa & Thiết bị đo';
require_once __DIR__ . '/../layouts/header.php'; 

// Get record ID from URL
$stt = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no ID, show message
if (!$stt) {
    echo '<div class="max-w-4xl mx-auto bg-white rounded-lg shadow-md p-6 mt-6">
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Vui lòng chọn hồ sơ từ danh sách để nhập thông tin sửa chữa.
            </div>
            <div class="mt-4">
                <a href="/iso2/hososcbd.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded inline-block">
                    <i class="fas fa-arrow-left mr-2"></i>Quay lại danh sách
                </a>
            </div>
          </div>';
    require_once __DIR__ . '/../layouts/footer.php';
    exit;
}

// Load the record
require_once __DIR__ . '/../../models/HoSoSCBD.php';
$model = new HoSoSCBD();
$item = $model->findById($stt);

if (!$item) {
    echo '<div class="max-w-4xl mx-auto bg-white rounded-lg shadow-md p-6 mt-6">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <i class="fas fa-times-circle mr-2"></i>
                Không tìm thấy hồ sơ.
            </div>
            <div class="mt-4">
                <a href="/iso2/hososcbd.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded inline-block">
                    <i class="fas fa-arrow-left mr-2"></i>Quay lại danh sách
                </a>
            </div>
          </div>';
    require_once __DIR__ . '/../layouts/footer.php';
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nhomsc' => trim($_POST['nhomsc'] ?? ''),
        'ngaybdtt' => trim($_POST['ngaybdtt'] ?? ''),
        'ngayth' => trim($_POST['ngayth'] ?? ''),
        'ngaykt' => trim($_POST['ngaykt'] ?? ''),
        'solg' => (int)($_POST['solg'] ?? 0),
        'ttktbefore' => trim($_POST['ttktbefore'] ?? ''),
        'honghoc' => trim($_POST['honghoc'] ?? ''),
        'khacphuc' => trim($_POST['khacphuc'] ?? ''),
        'ttktafter' => trim($_POST['ttktafter'] ?? ''),
        'noidung' => trim($_POST['noidung'] ?? ''),
        'ketluan' => trim($_POST['ketluan'] ?? ''),
        'xemxetxuong' => trim($_POST['xemxetxuong'] ?? ''),
        'tbdosc' => trim($_POST['tbdosc'] ?? ''),
        'serialtbdosc' => trim($_POST['serialtbdosc'] ?? ''),
        'tbdosc1' => trim($_POST['tbdosc1'] ?? ''),
        'serialtbdosc1' => trim($_POST['serialtbdosc1'] ?? ''),
        'tbdosc2' => trim($_POST['tbdosc2'] ?? ''),
        'serialtbdosc2' => trim($_POST['serialtbdosc2'] ?? ''),
        'tbdosc3' => trim($_POST['tbdosc3'] ?? ''),
        'serialtbdosc3' => trim($_POST['serialtbdosc3'] ?? ''),
        'tbdosc4' => trim($_POST['tbdosc4'] ?? ''),
        'serialtbdosc4' => trim($_POST['serialtbdosc4'] ?? '')
    ];
    
    $success = $model->update($stt, $data);
    if ($success) {
        $successMessage = 'Cập nhật thông tin sửa chữa thành công!';
        $item = $model->findById($stt); // Reload data
    } else {
        $errorMessage = 'Có lỗi xảy ra khi cập nhật';
    }
}
?>

<div class="max-w-6xl mx-auto bg-white rounded-lg shadow-md p-4 md:p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl md:text-2xl font-bold flex items-center">
            <i class="fas fa-wrench mr-2 text-orange-600"></i> Thông tin sửa chữa & Thiết bị đo
        </h1>
        <a href="hososcbd.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm">
            <i class="fas fa-arrow-left mr-2"></i>Quay lại
        </a>
    </div>
    
    <!-- Record Info -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
                <span class="font-semibold text-gray-700">Mã quản lý:</span>
                <span class="ml-2 font-mono text-blue-700"><?php echo htmlspecialchars($item['maql']); ?></span>
            </div>
            <div>
                <span class="font-semibold text-gray-700">Số phiếu:</span>
                <span class="ml-2"><?php echo htmlspecialchars($item['phieu']); ?></span>
            </div>
            <div>
                <span class="font-semibold text-gray-700">Thiết bị:</span>
                <span class="ml-2"><?php echo htmlspecialchars($item['mavt'] . ' - ' . $item['somay']); ?></span>
            </div>
        </div>
    </div>

    <?php if (isset($successMessage)): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        <i class="fas fa-check-circle mr-2"></i><?php echo $successMessage; ?>
    </div>
    <?php endif; ?>

    <?php if (isset($errorMessage)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <i class="fas fa-times-circle mr-2"></i><?php echo $errorMessage; ?>
    </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        <!-- Thông tin sửa chữa -->
        <div class="border-l-4 border-orange-500 pl-4">
            <h2 class="text-lg font-bold mb-3 text-orange-700">
                <i class="fas fa-wrench mr-2"></i>Thông tin sửa chữa
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Nhóm SC <span class="text-red-500">*</span></label>
                    <input type="text" name="nhomsc" required value="<?php echo htmlspecialchars($item['nhomsc']); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Ngày bắt đầu TT</label>
                    <input type="date" name="ngaybdtt" value="<?php echo $item['ngaybdtt']; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Ngày thực hiện</label>
                    <input type="date" name="ngayth" value="<?php echo $item['ngayth']; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Ngày kiểm tra</label>
                    <input type="date" name="ngaykt" value="<?php echo $item['ngaykt']; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Số lượng</label>
                    <input type="number" name="solg" min="0" value="<?php echo $item['solg']; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">TT KT trước</label>
                    <textarea name="ttktbefore" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo htmlspecialchars($item['ttktbefore']); ?></textarea>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Hỏng hóc</label>
                    <textarea name="honghoc" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo htmlspecialchars($item['honghoc']); ?></textarea>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Khắc phục</label>
                    <textarea name="khacphuc" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo htmlspecialchars($item['khacphuc']); ?></textarea>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">TT KT sau</label>
                    <textarea name="ttktafter" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo htmlspecialchars($item['ttktafter']); ?></textarea>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Nội dung</label>
                    <textarea name="noidung" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo htmlspecialchars($item['noidung']); ?></textarea>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Kết luận</label>
                    <textarea name="ketluan" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo htmlspecialchars($item['ketluan']); ?></textarea>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Xem xét xưởng</label>
                    <textarea name="xemxetxuong" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo htmlspecialchars($item['xemxetxuong']); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Thiết bị đo SC -->
        <div class="border-l-4 border-teal-500 pl-4">
            <h2 class="text-lg font-bold mb-3 text-teal-700">
                <i class="fas fa-tools mr-2"></i>Thiết bị đo sửa chữa (5 slot)
            </h2>
            <?php for ($i = 0; $i <= 4; $i++): 
                $tbField = $i == 0 ? 'tbdosc' : "tbdosc$i";
                $serialField = $i == 0 ? 'serialtbdosc' : "serialtbdosc$i";
            ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="block text-sm text-gray-600 mb-1">TB đo SC <?php echo $i + 1; ?></label>
                    <input type="text" name="<?php echo $tbField; ?>" value="<?php echo htmlspecialchars($item[$tbField]); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Serial <?php echo $i + 1; ?></label>
                    <input type="text" name="<?php echo $serialField; ?>" value="<?php echo htmlspecialchars($item[$serialField]); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
            </div>
            <?php endfor; ?>
        </div>

        <!-- Buttons -->
        <div class="flex flex-col md:flex-row gap-2 pt-4 border-t">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded text-base font-semibold w-full md:w-auto">
                <i class="fas fa-save mr-2"></i> Lưu thông tin
            </button>
            <a href="hososcbd.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded text-base font-semibold text-center w-full md:w-auto">
                <i class="fas fa-arrow-left mr-2"></i> Quay lại danh sách
            </a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
