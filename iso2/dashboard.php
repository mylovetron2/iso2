<?php
declare(strict_types=1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

requireAuth();

$title = 'Dashboard Thống Kê';

// Kết nối database
$db = getDBConnection();
$db->exec("SET NAMES latin1");

// ============================================
// 1. THIẾT BỊ HỖ TRỢ - SẮP HẾT HẠN 30 NGÀY
// ============================================
try {
    $sql = "SELECT COUNT(*) as count 
            FROM thietbihotro_iso 
            WHERE ngaykdtt BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
    $stmt = $db->query($sql);
    $sapHetHan30Ngay = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Lấy danh sách thiết bị sắp hết hạn (top 10)
    $sql = "SELECT tenthietbi, serialnumber, ngaykdtt, chusohuu,
                   DATEDIFF(ngaykdtt, CURDATE()) as so_ngay_con_lai
            FROM thietbihotro_iso 
            WHERE ngaykdtt BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            ORDER BY ngaykdtt ASC
            LIMIT 10";
    $stmt = $db->query($sql);
    $danhSachSapHetHan = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $sapHetHan30Ngay = 0;
    $danhSachSapHetHan = [];
}

// Thống kê chi tiết thiết bị hỗ trợ
try {
    $sql = "SELECT COUNT(*) as count FROM thietbihotro_iso WHERE ngaykdtt > CURDATE()";
    $stmt = $db->query($sql);
    $tbhtConHan = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $sql = "SELECT COUNT(*) as count FROM thietbihotro_iso WHERE ngaykdtt <= CURDATE() AND ngaykdtt IS NOT NULL";
    $stmt = $db->query($sql);
    $tbhtHetHan = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $sql = "SELECT COUNT(*) as count FROM thietbihotro_iso";
    $stmt = $db->query($sql);
    $tbhtTotal = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (Exception $e) {
    $tbhtConHan = 0;
    $tbhtHetHan = 0;
    $tbhtTotal = 0;
}

// ============================================
// 2. GIAO NHẬN THIẾT BỊ
// ============================================
try {
    // Thiết bị đang kiểm định
    $sql = "SELECT COUNT(*) as count 
            FROM giao_nhan_thietbi_iso 
            WHERE trangthai = 'dang_kiem_dinh'";
    $stmt = $db->query($sql);
    $dangKiemDinh = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Danh sách phiếu đang kiểm định (top 10)
    $sql = "SELECT gn.id, gn.nguoi_giao, gn.ngay_giao, gn.tong_thietbi,
                   dv_giao.tendv as ten_donvi_giao
            FROM giao_nhan_thietbi_iso gn
            LEFT JOIN donvi_iso dv_giao ON gn.donvi_giao = dv_giao.madv
            WHERE gn.trangthai = 'dang_kiem_dinh'
            ORDER BY gn.id DESC
            LIMIT 10";
    $stmt = $db->query($sql);
    $danhSachDangKiemDinh = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Thiết bị đã nhận (chưa gửi kiểm định)
    $sql = "SELECT COUNT(*) as count 
            FROM giao_nhan_thietbi_iso 
            WHERE trangthai = 'da_nhan'";
    $stmt = $db->query($sql);
    $daNhan = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Danh sách phiếu đã nhận (top 10)
    $sql = "SELECT gn.id, gn.nguoi_giao, gn.ngay_giao, gn.tong_thietbi,
                   dv_giao.tendv as ten_donvi_giao
            FROM giao_nhan_thietbi_iso gn
            LEFT JOIN donvi_iso dv_giao ON gn.donvi_giao = dv_giao.madv
            WHERE gn.trangthai = 'da_nhan'
            ORDER BY gn.id DESC
            LIMIT 10";
    $stmt = $db->query($sql);
    $danhSachDaNhan = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Thiết bị đã giao (hoàn thành)
    $sql = "SELECT COUNT(*) as count 
            FROM giao_nhan_thietbi_iso 
            WHERE trangthai = 'da_giao'";
    $stmt = $db->query($sql);
    $daGiao = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $gnTotal = $dangKiemDinh + $daNhan + $daGiao;
} catch (Exception $e) {
    $dangKiemDinh = 0;
    $daNhan = 0;
    $daGiao = 0;
    $gnTotal = 0;
    $danhSachDangKiemDinh = [];
    $danhSachDaNhan = [];
}


// ============================================
// 3. HỒ SƠ SCBĐ
// ============================================
try {
    // Chưa thực hiện
    $sql = "SELECT COUNT(*) as count 
            FROM hososcbd_iso 
            WHERE ngayth IS NULL OR ngayth = '0000-00-00'";
    $stmt = $db->query($sql);
    $chuaThucHien = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Đang làm
    $sql = "SELECT COUNT(*) as count 
            FROM hososcbd_iso 
            WHERE ngayth IS NOT NULL 
              AND ngayth != '0000-00-00' 
              AND (ngaykt IS NULL OR ngaykt = '0000-00-00')";
    $stmt = $db->query($sql);
    $dangLam = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Chưa bàn giao (hoàn thành nhưng chưa bg)
    $sql = "SELECT COUNT(*) as count 
            FROM hososcbd_iso 
            WHERE bg = 0 
              AND ngaykt IS NOT NULL 
              AND ngaykt != '0000-00-00'";
    $stmt = $db->query($sql);
    $chuaBanGiao = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $hsTotal = $chuaThucHien + $dangLam + $chuaBanGiao;
} catch (Exception $e) {
    $chuaThucHien = 0;
    $dangLam = 0;
    $chuaBanGiao = 0;
    $hsTotal = 0;
}

require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="max-w-7xl mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-8 pb-5 border-b-2 border-gray-300">
        <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
            <i class="fas fa-chart-line text-blue-600"></i>
            Dashboard Thống Kê
        </h1>
        <p class="text-gray-600 text-sm mt-2">
            Tổng quan tình hình quản lý thiết bị và hồ sơ SCBĐ
        </p>
    </div>

    <!-- Detailed Statistics -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- 1. THIẾT BỊ HỖ TRỢ -->
        <div class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-shadow border-l-4 border-orange-500">
            <div class="px-5 py-4 border-b border-gray-200 bg-gradient-to-r from-orange-50 to-white">
                <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-tools text-orange-600"></i>
                    Thiết Bị Hỗ Trợ
                </h2>
            </div>
            <div class="p-5 space-y-3">
                <!-- Sắp hết hạn -->
                <?php if ($sapHetHan30Ngay > 0): ?>
                <div class="bg-gradient-to-br from-orange-50 to-red-50 border-2 border-orange-300 rounded-lg p-4 shadow-md hover:shadow-lg transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <span class="text-xs font-medium text-orange-700 uppercase tracking-wide">Cảnh báo</span>
                            <p class="text-sm font-semibold text-orange-900 mt-1">Sắp hết hạn (30 ngày)</p>
                        </div>
                        <div class="bg-orange-600 text-white rounded-full w-14 h-14 flex items-center justify-center shadow-lg">
                            <span class="text-2xl font-bold"><?php echo $sapHetHan30Ngay; ?></span>
                        </div>
                    </div>
                    <a href="/iso2/thietbihotro.php?trangthai=saphethan" 
                       class="inline-flex items-center text-xs bg-orange-600 hover:bg-orange-700 text-white px-3 py-1.5 rounded-md font-medium transition-colors shadow">
                        <i class="fas fa-arrow-right mr-1"></i> Xem chi tiết
                    </a>
                    
                    <?php if (count($danhSachSapHetHan) > 0): ?>
                    <div class="mt-4 pt-3 border-t-2 border-orange-300 space-y-2 max-h-48 overflow-y-auto">
                        <?php foreach ($danhSachSapHetHan as $tb): ?>
                        <div class="text-xs bg-white rounded-md p-3 border border-orange-200 shadow hover:shadow-md transition-shadow">
                            <div class="font-semibold text-gray-800 truncate"><?php echo htmlspecialchars($tb['tenthietbi']); ?></div>
                            <div class="text-gray-500 mt-1.5">SN: <?php echo htmlspecialchars($tb['serialnumber'] ?? 'N/A'); ?></div>
                            <div class="flex justify-between items-center mt-2 pt-2 border-t border-orange-100">
                                <span class="text-gray-500 text-[10px]">
                                    <i class="far fa-calendar-alt mr-1"></i>
                                    <?php echo date('d/m/Y', strtotime($tb['ngaykdtt'])); ?>
                                </span>
                                <span class="bg-orange-600 text-white px-2 py-0.5 rounded-full font-bold text-[10px]">
                                    <?php echo $tb['so_ngay_con_lai']; ?> ngày
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Stats -->
                <div class="space-y-2">
                    <div class="flex justify-between items-center py-2.5 px-3 bg-green-50 rounded-md border border-green-200">
                        <span class="text-sm text-green-800 font-medium">
                            <i class="fas fa-check-circle mr-1"></i>
                            Còn hạn
                        </span>
                        <span class="font-bold text-green-700 text-base"><?php echo number_format($tbhtConHan); ?></span>
                    </div>
                    <div class="flex justify-between items-center py-2.5 px-3 bg-red-50 rounded-md border border-red-200">
                        <span class="text-sm text-red-800 font-medium">
                            <i class="fas fa-times-circle mr-1"></i>
                            Hết hạn
                        </span>
                        <span class="font-bold text-red-700 text-base"><?php echo number_format($tbhtHetHan); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. GIAO NHẬN THIẾT BỊ -->
        <div class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-shadow border-l-4 border-blue-500">
            <div class="px-5 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-white">
                <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-exchange-alt text-blue-600"></i>
                    Giao Nhận Thiết Bị
                </h2>
            </div>
            <div class="p-5 space-y-3">
                <!-- Đang kiểm định -->
                <?php if ($dangKiemDinh > 0): ?>
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border-2 border-blue-300 rounded-lg p-4 shadow-md hover:shadow-lg transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <span class="text-xs font-medium text-blue-700 uppercase tracking-wide">Đang xử lý</span>
                            <p class="text-sm font-semibold text-blue-900 mt-1">Đang kiểm định</p>
                        </div>
                        <div class="bg-blue-600 text-white rounded-full w-14 h-14 flex items-center justify-center shadow-lg">
                            <span class="text-2xl font-bold"><?php echo $dangKiemDinh; ?></span>
                        </div>
                    </div>
                    <a href="/iso2/giaonhanthietbi.php?trangthai=dang_kiem_dinh" 
                       class="inline-flex items-center text-xs bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md font-medium transition-colors shadow">
                        <i class="fas fa-arrow-right mr-1"></i> Xem chi tiết
                    </a>
                    
                    <?php if (count($danhSachDangKiemDinh) > 0): ?>
                    <div class="mt-4 pt-3 border-t-2 border-blue-300 space-y-2 max-h-48 overflow-y-auto">
                        <?php foreach ($danhSachDangKiemDinh as $phieu): ?>
                        <a href="/iso2/giaonhanthietbi.php?action=view&id=<?php echo $phieu['id']; ?>" 
                           class="block text-xs bg-white rounded-md p-3 border border-blue-200 shadow hover:shadow-md hover:border-blue-400 transition-all">
                            <div class="font-semibold text-gray-800">
                                <i class="fas fa-file-alt text-blue-600 mr-1"></i>
                                Phiếu #<?php echo $phieu['id']; ?> - <?php echo htmlspecialchars($phieu['nguoi_giao']); ?>
                            </div>
                            <div class="text-gray-500 mt-1.5"><?php echo htmlspecialchars($phieu['ten_donvi_giao'] ?? 'N/A'); ?></div>
                            <div class="flex justify-between items-center mt-2 pt-2 border-t border-blue-100">
                                <span class="text-gray-500 text-[10px]">
                                    <i class="far fa-calendar mr-1"></i>
                                    <?php echo date('d/m/Y', strtotime($phieu['ngay_giao'])); ?>
                                </span>
                                <span class="bg-blue-600 text-white px-2 py-0.5 rounded-full font-bold text-[10px]">
                                    <?php echo $phieu['tong_thietbi'] ?? 0; ?> TB
                                </span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Đã nhận -->
                <?php if ($daNhan > 0): ?>
                <div class="bg-gradient-to-br from-green-50 to-emerald-50 border-2 border-green-300 rounded-lg p-4 shadow-md hover:shadow-lg transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <span class="text-xs font-medium text-green-700 uppercase tracking-wide">Chờ xử lý</span>
                            <p class="text-sm font-semibold text-green-900 mt-1">Đã nhận (chưa gửi KĐ)</p>
                        </div>
                        <div class="bg-green-600 text-white rounded-full w-14 h-14 flex items-center justify-center shadow-lg">
                            <span class="text-2xl font-bold"><?php echo $daNhan; ?></span>
                        </div>
                    </div>
                    <a href="/iso2/giaonhanthietbi.php?trangthai=da_nhan" 
                       class="inline-flex items-center text-xs bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-md font-medium transition-colors shadow">
                        <i class="fas fa-arrow-right mr-1"></i> Xem chi tiết
                    </a>
                    
                    <?php if (count($danhSachDaNhan) > 0): ?>
                    <div class="mt-4 pt-3 border-t-2 border-green-300 space-y-2 max-h-48 overflow-y-auto">
                        <?php foreach ($danhSachDaNhan as $phieu): ?>
                        <a href="/iso2/giaonhanthietbi.php?action=view&id=<?php echo $phieu['id']; ?>" 
                           class="block text-xs bg-white rounded-md p-3 border border-green-200 shadow hover:shadow-md hover:border-green-400 transition-all">
                            <div class="font-semibold text-gray-800">
                                <i class="fas fa-file-alt text-green-600 mr-1"></i>
                                Phiếu #<?php echo $phieu['id']; ?> - <?php echo htmlspecialchars($phieu['nguoi_giao']); ?>
                            </div>
                            <div class="text-gray-500 mt-1.5"><?php echo htmlspecialchars($phieu['ten_donvi_giao'] ?? 'N/A'); ?></div>
                            <div class="flex justify-between items-center mt-2 pt-2 border-t border-green-100">
                                <span class="text-gray-500 text-[10px]">
                                    <i class="far fa-calendar mr-1"></i>
                                    <?php echo date('d/m/Y', strtotime($phieu['ngay_giao'])); ?>
                                </span>
                                <span class="bg-green-600 text-white px-2 py-0.5 rounded-full font-bold text-[10px]">
                                    <?php echo $phieu['tong_thietbi'] ?? 0; ?> TB
                                </span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Stats -->
                <div class="space-y-2">
                    <div class="flex justify-between items-center py-2.5 px-3 bg-gray-50 rounded-md border border-gray-200">
                        <span class="text-sm text-gray-700 font-medium">
                            <i class="fas fa-check-double mr-1"></i>
                            Đã giao (hoàn thành)
                        </span>
                        <span class="font-bold text-gray-800 text-base"><?php echo number_format($daGiao); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. HỒ SƠ SCBĐ -->
        <div class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-shadow border-l-4 border-red-500">
            <div class="px-5 py-4 border-b border-gray-200 bg-gradient-to-r from-red-50 to-white">
                <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-folder-open text-red-600"></i>
                    Hồ Sơ SCBĐ
                </h2>
            </div>
            <div class="p-5 space-y-3">
                <!-- Stats Grid -->
                <div class="space-y-3">
                    <?php if ($chuaThucHien > 0): ?>
                    <a href="/iso2/hososcbd.php?trangthai=chuath" 
                       class="flex justify-between items-center py-4 px-4 bg-gradient-to-r from-gray-50 to-slate-50 border-2 border-gray-300 rounded-lg hover:shadow-md transition-all">
                        <div>
                            <span class="text-xs font-medium text-gray-600 uppercase tracking-wide block">Chờ thực hiện</span>
                            <span class="text-sm font-semibold text-gray-900 mt-0.5 block">Chưa thực hiện</span>
                        </div>
                        <div class="bg-gray-600 text-white rounded-full w-12 h-12 flex items-center justify-center shadow-md">
                            <span class="text-xl font-bold"><?php echo $chuaThucHien; ?></span>
                        </div>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($dangLam > 0): ?>
                    <a href="/iso2/hososcbd.php?trangthai=danglam" 
                       class="flex justify-between items-center py-4 px-4 bg-gradient-to-r from-yellow-50 to-amber-50 border-2 border-yellow-300 rounded-lg hover:shadow-md transition-all">
                        <div>
                            <span class="text-xs font-medium text-yellow-700 uppercase tracking-wide block">Đang xử lý</span>
                            <span class="text-sm font-semibold text-yellow-900 mt-0.5 block">Đang làm</span>
                        </div>
                        <div class="bg-yellow-600 text-white rounded-full w-12 h-12 flex items-center justify-center shadow-md">
                            <span class="text-xl font-bold"><?php echo $dangLam; ?></span>
                        </div>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($chuaBanGiao > 0): ?>
                    <a href="/iso2/hososcbd.php?trangthai=chuabg" 
                       class="flex justify-between items-center py-4 px-4 bg-gradient-to-r from-orange-50 to-red-50 border-2 border-orange-300 rounded-lg hover:shadow-md transition-all">
                        <div>
                            <span class="text-xs font-medium text-orange-700 uppercase tracking-wide block">Cần bàn giao</span>
                            <span class="text-sm font-semibold text-orange-900 mt-0.5 block">Chưa bàn giao</span>
                        </div>
                        <div class="bg-orange-600 text-white rounded-full w-12 h-12 flex items-center justify-center shadow-md">
                            <span class="text-xl font-bold"><?php echo $chuaBanGiao; ?></span>
                        </div>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="mt-8 text-center">
        <p class="text-xs text-gray-400">Dữ liệu được cập nhật tự động</p>
    </div>
</div>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
