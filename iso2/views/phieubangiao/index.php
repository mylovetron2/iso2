<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Quản lý Phiếu Bàn Giao';
require_once __DIR__ . '/../layouts/header.php'; 
?>
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h1 class="text-2xl font-bold mb-4 flex items-center">
        <i class="fas fa-handshake mr-2 text-red-600"></i> Quản lý Phiếu Bàn Giao
    </h1>
    
    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-100 rounded p-4 text-center">
            <div class="text-2xl font-bold text-blue-700"><?php echo $stats['total']; ?></div>
            <div class="text-sm text-gray-600">Tổng số phiếu</div>
        </div>
        <div class="bg-yellow-100 rounded p-4 text-center">
            <div class="text-2xl font-bold text-yellow-700"><?php echo $stats['nhap']; ?></div>
            <div class="text-sm text-gray-600">Nháp</div>
        </div>
        <div class="bg-green-100 rounded p-4 text-center">
            <div class="text-2xl font-bold text-green-700"><?php echo $stats['daduyet']; ?></div>
            <div class="text-sm text-gray-600">Đã duyệt</div>
        </div>
    </div>

    <!-- Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <i class="fas fa-times-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <!-- Search & Filter -->
    <form method="GET" class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Tìm số phiếu, phiếu YC, người giao/nhận..." 
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>
            <div>
                <select name="trangthai" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                    <option value="">-- Trạng thái --</option>
                    <option value="0" <?php echo $trangthai === '0' ? 'selected' : ''; ?>>Nháp</option>
                    <option value="1" <?php echo $trangthai === '1' ? 'selected' : ''; ?>>Đã duyệt</option>
                </select>
            </div>
            <div>
                <select name="donvi" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                    <option value="">-- Đơn vị --</option>
                    <?php foreach ($donViList as $dv): ?>
                        <option value="<?php echo htmlspecialchars($dv['madv']); ?>" <?php echo $donvi === $dv['madv'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dv['tendv']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                    <i class="fas fa-search mr-2"></i>Tìm kiếm
                </button>
            </div>
        </div>
    </form>

    <!-- Create Button -->
    <?php if (hasPermission('phieubangiao.create')): ?>
    <div class="mb-4">
        <a href="phieubangiao.php?action=select" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded inline-block font-semibold">
            <i class="fas fa-plus mr-2"></i>Tạo Phiếu Bàn Giao Mới
        </a>
    </div>
    <?php endif; ?>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 border text-left">Số Phiếu</th>
                    <th class="px-4 py-2 border text-left">Phiếu YC</th>
                    <th class="px-4 py-2 border text-left">Ngày BG</th>
                    <th class="px-4 py-2 border text-left">Người Giao</th>
                    <th class="px-4 py-2 border text-left">Người Nhận</th>
                    <th class="px-4 py-2 border text-center">Số TB</th>
                    <th class="px-4 py-2 border text-center">Trạng thái</th>
                    <th class="px-4 py-2 border text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>Chưa có phiếu bàn giao nào</p>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($items as $item): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 border font-mono text-blue-700 font-semibold">
                        <?php echo htmlspecialchars($item['sophieu']); ?>
                    </td>
                    <td class="px-4 py-2 border"><?php echo htmlspecialchars($item['phieuyc']); ?></td>
                    <td class="px-4 py-2 border"><?php echo date('d/m/Y', strtotime($item['ngaybg'])); ?></td>
                    <td class="px-4 py-2 border"><?php echo htmlspecialchars($item['nguoigiao']); ?></td>
                    <td class="px-4 py-2 border"><?php echo htmlspecialchars($item['nguoinhan']); ?></td>
                    <td class="px-4 py-2 border text-center">
                        <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm font-semibold">
                            <?php echo $item['so_thietbi']; ?> TB
                        </span>
                    </td>
                    <td class="px-4 py-2 border text-center">
                        <?php if ($item['trangthai'] == 1): ?>
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm font-semibold">
                                <i class="fas fa-check-circle mr-1"></i>Đã duyệt
                            </span>
                        <?php else: ?>
                            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-sm font-semibold">
                                <i class="fas fa-file mr-1"></i>Nháp
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-2 border text-center">
                        <a href="phieubangiao.php?action=view&id=<?php echo $item['stt']; ?>" 
                           class="text-blue-600 hover:text-blue-800 mx-1" title="Xem chi tiết">
                            <i class="fas fa-eye"></i>
                        </a>
                        <?php if ($item['trangthai'] == 0 && hasPermission('phieubangiao.delete')): ?>
                        <form method="POST" action="phieubangiao.php?action=delete" 
                              onsubmit="return confirm('Bạn có chắc muốn xóa phiếu này?');" 
                              class="inline">
                            <input type="hidden" name="id" value="<?php echo $item['stt']; ?>">
                            <button type="submit" class="text-red-600 hover:text-red-800 mx-1" title="Xóa">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="mt-6 flex justify-center">
        <nav class="flex space-x-2">
            <?php
            $queryParams = $_GET;
            
            for ($i = 1; $i <= $totalPages; $i++):
                $queryParams['page'] = $i;
                $url = 'phieubangiao.php?' . http_build_query($queryParams);
                $active = ($i == $page) ? 'bg-blue-600 text-white' : 'bg-white text-blue-600 hover:bg-blue-50';
            ?>
                <a href="<?php echo $url; ?>" class="<?php echo $active; ?> px-4 py-2 border rounded">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
