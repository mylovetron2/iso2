<?php
declare(strict_types=1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/VatTuThanhLyController.php';

requireAuth();

// Check permissions
if (!hasPermission('vattu.view')) {
    die('No permission to view vat tu');
}

$id = $_GET['id'] ?? null;

if (!$id) {
    die('No ID provided');
}

// Load data
require_once __DIR__ . '/models/VatTuThanhLy.php';
$model = new VatTuThanhLy();

$where = "WHERE v.stt = :stt";
$params = [':stt' => (int)$id];
$items = $model->getAllWithStats($where, $params);

if (empty($items)) {
    die('Item not found');
}

$vattu = $items[0];
$chiTietList = $model->getChiTietSuDung((int)$id);

// Load don vi list
$db = getDBConnection();
$stmtDonVi = $db->query("SELECT madv, tendv FROM donvi_iso ORDER BY tendv ASC");
$donViList = $stmtDonVi->fetchAll(PDO::FETCH_ASSOC);

// Simple HTML output - no complex header
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết vật tư #<?php echo $vattu['stt']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <!-- Header -->
        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-800">
                    <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                    Chi tiết vật tư #<?php echo $vattu['stt']; ?>
                </h1>
                <div class="space-x-2">
                    <a href="/iso2/vattuthanhly.php" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                        <i class="fas fa-arrow-left mr-2"></i>Quay lại
                    </a>
                    <?php if (hasPermission('vattu.edit')): ?>
                    <a href="/iso2/vattuthanhly.php?action=edit&id=<?php echo $vattu['stt']; ?>" class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600">
                        <i class="fas fa-edit mr-2"></i>Sửa
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Info -->
            <div class="lg:col-span-2">
                <div class="bg-white shadow-md rounded-lg overflow-hidden mb-6">
                    <div class="bg-blue-600 text-white px-4 py-3">
                        <h2 class="text-xl font-semibold">Thông tin cơ bản</h2>
                    </div>
                    <div class="p-6">
                        <table class="w-full text-sm">
                            <tr class="border-b">
                                <th class="py-2 text-left bg-gray-50 px-3 w-1/3">STT</th>
                                <td class="py-2 px-3"><strong class="text-blue-600">#<?php echo $vattu['stt']; ?></strong></td>
                            </tr>
                            <tr class="border-b">
                                <th class="py-2 text-left bg-gray-50 px-3">Mã vật tư</th>
                                <td class="py-2 px-3">
                                    <code class="<?php echo htmlspecialchars($vattu['phanloai_mau_sac'] ?? 'bg-blue-100 text-blue-800'); ?> px-4 py-2 rounded font-semibold">
                                        <?php echo htmlspecialchars($vattu['mavattu']); ?>
                                    </code>
                                </td>
                            </tr>
                            <?php if (!empty($vattu['so_serial'])): ?>
                            <tr class="border-b">
                                <th class="py-2 text-left bg-gray-50 px-3">Số Serial</th>
                                <td class="py-2 px-3">
                                    <span class="bg-gray-200 px-3 py-1 rounded"><?php echo htmlspecialchars($vattu['so_serial']); ?></span>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <tr class="border-b">
                                <th class="py-2 text-left bg-gray-50 px-3">Tên (Tiếng Việt)</th>
                                <td class="py-2 px-3 text-green-700 font-semibold"><?php echo htmlspecialchars($vattu['ten_tiengviet'] ?? '-'); ?></td>
                            </tr>
                            <tr class="border-b">
                                <th class="py-2 text-left bg-gray-50 px-3">Tên (Tiếng Anh)</th>
                                <td class="py-2 px-3"><?php echo htmlspecialchars($vattu['ten_tienganh'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th class="py-2 text-left bg-gray-50 px-3">Phân loại</th>
                                <td class="py-2 px-3">
                                    <?php if (!empty($vattu['ten_phanloai'])): ?>
                                        <span class="<?php echo htmlspecialchars($vattu['phanloai_mau_sac'] ?? 'bg-gray-100 text-gray-800'); ?> px-3 py-1 rounded">
                                            <?php echo htmlspecialchars($vattu['ten_phanloai']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-500">Chưa phân loại</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- History -->
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="bg-green-600 text-white px-4 py-3">
                        <h2 class="text-xl font-semibold">Lịch sử sử dụng (<?php echo count($chiTietList); ?>)</h2>
                    </div>
                    <div class="p-6">
                        <?php if (empty($chiTietList)): ?>
                            <p class="text-gray-500 text-center py-8">Chưa có lịch sử sử dụng</p>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="px-3 py-2 text-left">Người SD</th>
                                            <th class="px-3 py-2 text-left">Ngày nhận</th>
                                            <th class="px-3 py-2 text-right">SL</th>
                                            <th class="px-3 py-2 text-left">Bộ phận</th>
                                            <th class="px-3 py-2 text-center">Trạng thái</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        <?php foreach ($chiTietList as $ct): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2"><?php echo htmlspecialchars($ct['nguoisudung'] ?? '-'); ?></td>
                                            <td class="px-3 py-2"><?php echo $ct['ngaysd_nhan'] ? date('d/m/Y', strtotime($ct['ngaysd_nhan'])) : '-'; ?></td>
                                            <td class="px-3 py-2 text-right font-semibold"><?php echo number_format($ct['soluong'] ?? 0, 0); ?></td>
                                            <td class="px-3 py-2"><?php echo htmlspecialchars($ct['bophan'] ?? '-'); ?></td>
                                            <td class="px-3 py-2 text-center">
                                                <?php if ($ct['trangthai'] === 'dangdung'): ?>
                                                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">Đang dùng</span>
                                                <?php else: ?>
                                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">Đã trả</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="bg-cyan-600 text-white px-4 py-3">
                        <h2 class="text-lg font-semibold">Số lượng & Giá trị</h2>
                    </div>
                    <div class="p-4">
                        <table class="w-full text-sm">
                            <tr class="border-b">
                                <th class="py-2 text-left text-gray-700">ĐVT</th>
                                <td class="py-2 text-right font-semibold">
                                    <?php echo htmlspecialchars($vattu['dvt_tiengviet'] ?? $vattu['dvt_tiengnga'] ?? '-'); ?>
                                </td>
                            </tr>
                            <tr class="border-b">
                                <th class="py-2 text-left text-gray-700">SL còn lại</th>
                                <td class="py-2 text-right">
                                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded font-bold text-lg">
                                        <?php echo number_format($vattu['soluong_conlai'] ?? 0, 0); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr class="border-b">
                                <th class="py-2 text-left text-gray-700">Đơn giá</th>
                                <td class="py-2 text-right">
                                    <?php echo $vattu['dongia'] ? number_format($vattu['dongia'], 0) . ' đ' : '-'; ?>
                                </td>
                            </tr>
                            <tr class="border-b">
                                <th class="py-2 text-left text-gray-700">Tổng giá trị</th>
                                <td class="py-2 text-right">
                                    <strong class="text-blue-600 text-lg">
                                        <?php echo number_format($vattu['tong_tien'] ?? 0, 0); ?> đ
                                    </strong>
                                </td>
                            </tr>
                            <tr class="border-b">
                                <th class="py-2 text-left text-gray-700">SL đang dùng</th>
                                <td class="py-2 text-right"><?php echo number_format($vattu['soluong_dangdung'] ?? 0, 0); ?></td>
                            </tr>
                            <tr>
                                <th class="py-2 text-left text-gray-700">Số lần TL</th>
                                <td class="py-2 text-right">
                                    <span class="px-2 py-1 <?php echo ($vattu['so_lan_sudung'] ?? 0) > 0 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?> rounded">
                                        <?php echo $vattu['so_lan_sudung'] ?? 0; ?> lần
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Contract info -->
                <div class="bg-white shadow-md rounded-lg overflow-hidden mt-6">
                    <div class="bg-yellow-500 text-white px-4 py-3">
                        <h2 class="text-lg font-semibold">Hợp đồng & Quản lý</h2>
                    </div>
                    <div class="p-4">
                        <table class="w-full text-sm">
                            <tr class="border-b">
                                <th class="py-2 text-left text-gray-700">Ngày nhận</th>
                                <td class="py-2 text-right"><?php echo $vattu['ngaynhan'] ? date('d/m/Y', strtotime($vattu['ngaynhan'])) : '-'; ?></td>
                            </tr>
                            <tr class="border-b">
                                <th class="py-2 text-left text-gray-700">Số HĐ</th>
                                <td class="py-2 text-right"><?php echo htmlspecialchars($vattu['sohd'] ?? '-'); ?></td>
                            </tr>
                            <tr class="border-b">
                                <th class="py-2 text-left text-gray-700">Người QL</th>
                                <td class="py-2 text-right"><?php echo htmlspecialchars($vattu['nguoiquanly'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th class="py-2 text-left text-gray-700">Vị trí kho</th>
                                <td class="py-2 text-right"><?php echo htmlspecialchars($vattu['vitri_luukho'] ?? '-'); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
