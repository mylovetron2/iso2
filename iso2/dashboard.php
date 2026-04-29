<?php
declare(strict_types=1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/HoSoScBdTamDung.php';

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
    // ===== NHÓM RDNGA =====
    // Chưa thực hiện
    $sql = "SELECT COUNT(*) as count 
            FROM hososcbd_iso 
            WHERE nhomsc = 'RDNGA'
              AND (ngayth IS NULL OR ngayth = '0000-00-00')";
    $stmt = $db->query($sql);
    $rdnga_chuaThucHien = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Đang làm
    $sql = "SELECT COUNT(*) as count 
            FROM hososcbd_iso 
            WHERE nhomsc = 'RDNGA'
              AND ngayth IS NOT NULL 
              AND ngayth != '0000-00-00' 
              AND (ngaykt IS NULL OR ngaykt = '0000-00-00')";
    $stmt = $db->query($sql);
    $rdnga_dangLam = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Chưa bàn giao
    $sql = "SELECT COUNT(*) as count 
            FROM hososcbd_iso 
            WHERE nhomsc = 'RDNGA'
              AND bg = 0 
              AND ngaykt IS NOT NULL 
              AND ngaykt != '0000-00-00'";
    $stmt = $db->query($sql);
    $rdnga_chuaBanGiao = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // ===== NHÓM CNC =====
    // Chưa thực hiện
    $sql = "SELECT COUNT(*) as count 
            FROM hososcbd_iso 
            WHERE nhomsc = 'CNC'
              AND (ngayth IS NULL OR ngayth = '0000-00-00')";
    $stmt = $db->query($sql);
    $cnc_chuaThucHien = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Đang làm
    $sql = "SELECT COUNT(*) as count 
            FROM hososcbd_iso 
            WHERE nhomsc = 'CNC'
              AND ngayth IS NOT NULL 
              AND ngayth != '0000-00-00' 
              AND (ngaykt IS NULL OR ngaykt = '0000-00-00')";
    $stmt = $db->query($sql);
    $cnc_dangLam = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Chưa bàn giao
    $sql = "SELECT COUNT(*) as count 
            FROM hososcbd_iso 
            WHERE nhomsc = 'CNC'
              AND bg = 0 
              AND ngaykt IS NOT NULL 
              AND ngaykt != '0000-00-00'";
    $stmt = $db->query($sql);
    $cnc_chuaBanGiao = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // ===== ĐANG TẠM DỪNG (TẤT CẢ) =====
    $tamDungModel = new HoSoScBdTamDung();
    $dangTamDung = $tamDungModel->countDanhSachTamDung();
    $danhSachTamDung = $tamDungModel->getDanhSachTamDung('', '', '', '', 0, 9999);
    
} catch (Exception $e) {
    $rdnga_chuaThucHien = 0;
    $rdnga_dangLam = 0;
    $rdnga_chuaBanGiao = 0;
    $cnc_chuaThucHien = 0;
    $cnc_dangLam = 0;
    $cnc_chuaBanGiao = 0;
    $dangTamDung = 0;
    $danhSachTamDung = [];
}

require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="max-w-7xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Dashboard</h1>
        <p class="text-sm text-gray-500 mt-1">Tổng quan quản lý thiết bị và hồ sơ</p>
    </div>

    <!-- Statistics Grid -->
    <!-- Row 1: Hồ Sơ SCBĐ - RDNGA + CNC -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        
        <!-- 1. HỒ SƠ SCBĐ - RDNGA -->
        <div class="bg-white rounded-lg border border-gray-200 hover:border-gray-300 transition-colors">
            <div class="px-4 py-3 border-b border-[#27A4F2] bg-[#27A4F2]">
                <h2 class="text-base font-bold text-white">Hồ Sơ SCBĐ - RDNGA</h2>
            </div>
            <div class="p-4">
                <div class="space-y-2">
                    <?php if ($rdnga_chuaThucHien > 0): ?>
                    <a href="/iso2/hososcbd.php?trangthai=chuath&nhomsc=RDNGA" 
                       class="flex justify-between items-center py-3 px-3 bg-gray-50 hover:bg-gray-100 rounded transition-colors">
                        <span class="text-sm text-gray-700">Chưa thực hiện</span>
                        <span class="text-lg font-bold text-gray-900"><?php echo $rdnga_chuaThucHien; ?></span>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($rdnga_dangLam > 0): ?>
                    <a href="/iso2/hososcbd.php?trangthai=danglam&nhomsc=RDNGA" 
                       class="flex justify-between items-center py-3 px-3 bg-yellow-50 hover:bg-yellow-100 rounded transition-colors">
                        <span class="text-sm text-yellow-800">Đang làm</span>
                        <span class="text-lg font-bold text-yellow-700"><?php echo $rdnga_dangLam; ?></span>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($rdnga_chuaBanGiao > 0): ?>
                    <a href="/iso2/hososcbd.php?trangthai=chuabg&nhomsc=RDNGA" 
                       class="flex justify-between items-center py-3 px-3 bg-orange-50 hover:bg-orange-100 rounded transition-colors">
                        <span class="text-sm text-orange-800">Chưa bàn giao</span>
                        <span class="text-lg font-bold text-orange-700"><?php echo $rdnga_chuaBanGiao; ?></span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 2. HỒ SƠ SCBĐ - CNC -->
        <div class="bg-white rounded-lg border border-gray-200 hover:border-gray-300 transition-colors">
            <div class="px-4 py-3 border-b border-[#27A4F2] bg-[#27A4F2]">
                <h2 class="text-base font-bold text-white">Hồ Sơ SCBĐ - CNC</h2>
            </div>
            <div class="p-4">
                <div class="space-y-2">
                    <?php if ($cnc_chuaThucHien > 0): ?>
                    <a href="/iso2/hososcbd.php?trangthai=chuath&nhomsc=CNC" 
                       class="flex justify-between items-center py-3 px-3 bg-gray-50 hover:bg-gray-100 rounded transition-colors">
                        <span class="text-sm text-gray-700">Chưa thực hiện</span>
                        <span class="text-lg font-bold text-gray-900"><?php echo $cnc_chuaThucHien; ?></span>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($cnc_dangLam > 0): ?>
                    <a href="/iso2/hososcbd.php?trangthai=danglam&nhomsc=CNC" 
                       class="flex justify-between items-center py-3 px-3 bg-yellow-50 hover:bg-yellow-100 rounded transition-colors">
                        <span class="text-sm text-yellow-800">Đang làm</span>
                        <span class="text-lg font-bold text-yellow-700"><?php echo $cnc_dangLam; ?></span>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($cnc_chuaBanGiao > 0): ?>
                    <a href="/iso2/hososcbd.php?trangthai=chuabg&nhomsc=CNC" 
                       class="flex justify-between items-center py-3 px-3 bg-orange-50 hover:bg-orange-100 rounded transition-colors">
                        <span class="text-sm text-orange-800">Chưa bàn giao</span>
                        <span class="text-lg font-bold text-orange-700"><?php echo $cnc_chuaBanGiao; ?></span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Row 2: Thiết Bị Hỗ Trợ + Giao Nhận Thiết Bị -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mt-5">

        <!-- 3. THIẾT BỊ HỖ TRỢ -->
        <div class="bg-white rounded-lg border border-gray-200 hover:border-gray-300 transition-colors">
            <div class="px-4 py-3 border-b border-[#27A4F2] bg-[#27A4F2]">
                <h2 class="text-base font-bold text-white">Thiết Bị Hỗ Trợ</h2>
            </div>
            <div class="p-4 space-y-2">
                <!-- Sắp hết hạn -->
                <?php if ($sapHetHan30Ngay > 0): ?>
                <div class="bg-orange-50 border border-orange-200 rounded p-2">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-orange-600 font-medium">Sắp hết hạn (30 ngày)</span>
                        <span class="text-xl font-bold text-orange-700"><?php echo $sapHetHan30Ngay; ?></span>
                    </div>
                    <a href="/iso2/thietbihotro.php?trangthai=saphethan" 
                       class="text-[10px] text-orange-700 hover:text-orange-900 font-medium">
                        Xem tất cả →
                    </a>
                    
                    <?php if (count($danhSachSapHetHan) > 0): ?>
                    <div class="mt-2 space-y-1.5 max-h-32 overflow-y-auto">
                        <?php foreach ($danhSachSapHetHan as $tb): ?>
                        <div class="text-[11px] bg-white rounded p-1.5 border border-orange-100">
                            <div class="font-medium text-gray-800 truncate"><?php echo htmlspecialchars($tb['tenthietbi']); ?></div>
                            <div class="flex justify-between items-center mt-0.5">
                                <span class="text-gray-500">SN: <?php echo htmlspecialchars($tb['serialnumber'] ?? 'N/A'); ?></span>
                                <span class="text-orange-600 font-semibold"><?php echo $tb['so_ngay_con_lai']; ?> ngày</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Stats -->
                <div class="space-y-1 text-xs">
                    <div class="flex justify-between items-center py-1.5 px-2 bg-gray-50 rounded">
                        <span class="text-gray-600">Còn hạn</span>
                        <span class="font-semibold text-gray-900"><?php echo number_format($tbhtConHan); ?></span>
                    </div>
                    <div class="flex justify-between items-center py-1.5 px-2 bg-gray-50 rounded">
                        <span class="text-gray-600">Hết hạn</span>
                        <span class="font-semibold text-red-600"><?php echo number_format($tbhtHetHan); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. GIAO NHẬN THIẾT BỊ -->
        <div class="bg-white rounded-lg border border-gray-200 hover:border-gray-300 transition-colors">
            <div class="px-4 py-3 border-b border-[#27A4F2] bg-[#27A4F2]">
                <h2 class="text-base font-bold text-white">Giao Nhận Thiết Bị</h2>
            </div>
            <div class="p-4 space-y-2">
                <!-- Đang kiểm định -->
                <?php if ($dangKiemDinh > 0): ?>
                <div class="bg-blue-50 border border-blue-200 rounded p-2">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-blue-600 font-medium">Đang kiểm định</span>
                        <span class="text-xl font-bold text-blue-700"><?php echo $dangKiemDinh; ?></span>
                    </div>
                    <a href="/iso2/giaonhanthietbi.php?trangthai=dang_kiem_dinh" 
                       class="text-[10px] text-blue-700 hover:text-blue-900 font-medium">
                        Xem tất cả →
                    </a>
                    
                    <?php if (count($danhSachDangKiemDinh) > 0): ?>
                    <div class="mt-2 space-y-1.5 max-h-32 overflow-y-auto">
                        <?php foreach ($danhSachDangKiemDinh as $phieu): ?>
                        <a href="/iso2/giaonhanthietbi.php?action=view&id=<?php echo $phieu['id']; ?>" 
                           class="block text-[11px] bg-white rounded p-1.5 border border-blue-100 hover:border-blue-300 transition-colors">
                            <div class="font-medium text-gray-800">Phiếu #<?php echo $phieu['id']; ?> - <?php echo htmlspecialchars($phieu['nguoi_giao']); ?></div>
                            <div class="flex justify-between items-center mt-0.5">
                                <span class="text-gray-500 truncate"><?php echo htmlspecialchars($phieu['ten_donvi_giao'] ?? 'N/A'); ?></span>
                                <span class="text-blue-600 font-semibold ml-2"><?php echo $phieu['tong_thietbi'] ?? 0; ?> TB</span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Đã nhận -->
                <?php if ($daNhan > 0): ?>
                <div class="bg-green-50 border border-green-200 rounded p-2">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-green-600 font-medium">Đã nhận (chưa gửi KĐ)</span>
                        <span class="text-xl font-bold text-green-700"><?php echo $daNhan; ?></span>
                    </div>
                    <a href="/iso2/giaonhanthietbi.php?trangthai=da_nhan" 
                       class="text-[10px] text-green-700 hover:text-green-900 font-medium">
                        Xem tất cả →
                    </a>
                    
                    <?php if (count($danhSachDaNhan) > 0): ?>
                    <div class="mt-2 space-y-1.5 max-h-32 overflow-y-auto">
                        <?php foreach ($danhSachDaNhan as $phieu): ?>
                        <a href="/iso2/giaonhanthietbi.php?action=view&id=<?php echo $phieu['id']; ?>" 
                           class="block text-[11px] bg-white rounded p-1.5 border border-green-100 hover:border-green-300 transition-colors">
                            <div class="font-medium text-gray-800">Phiếu #<?php echo $phieu['id']; ?> - <?php echo htmlspecialchars($phieu['nguoi_giao']); ?></div>
                            <div class="flex justify-between items-center mt-0.5">
                                <span class="text-gray-500 truncate"><?php echo htmlspecialchars($phieu['ten_donvi_giao'] ?? 'N/A'); ?></span>
                                <span class="text-green-600 font-semibold ml-2"><?php echo $phieu['tong_thietbi'] ?? 0; ?> TB</span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Stats -->
                <div class="text-xs">
                    <div class="flex justify-between items-center py-1.5 px-2 bg-gray-50 rounded">
                        <span class="text-gray-600">Đã giao (hoàn thành)</span>
                        <span class="font-semibold text-gray-900"><?php echo number_format($daGiao); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Row 3: Tạm dừng (Full width) -->
    <?php if ($dangTamDung > 0): ?>
    <div class="grid grid-cols-1 gap-5 mt-5">
        <div class="bg-white rounded-lg border border-gray-200 hover:border-gray-300 transition-colors">
            <div class="px-4 py-3 border-b border-[#27A4F2] bg-[#27A4F2]">
                <h2 class="text-base font-bold text-white">Hồ Sơ SCBĐ - Đang Tạm Dừng</h2>
            </div>
            <div class="p-4">
                <div class="bg-red-50 border border-red-200 rounded p-2">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-red-600 font-medium">Đang tạm dừng</span>
                        <span class="text-xl font-bold text-red-700"><?php echo $dangTamDung; ?></span>
                    </div>
                    <a href="/iso2/baocao_hososcbd_tamdung.php?trangthai=dang_tam_dung" 
                       class="text-[10px] text-red-700 hover:text-red-900 font-medium">
                        Xem tất cả →
                    </a>
                    
                    <?php if (count($danhSachTamDung) > 0): ?>
                    <div class="mt-2 space-y-1.5 max-h-32 overflow-y-auto">
                        <?php foreach ($danhSachTamDung as $hs): ?>
                        <a href="/iso2/hososcbd.php?action=view&hoso=<?php echo urlencode($hs['hoso']); ?>" 
                           class="block text-[11px] bg-white rounded p-1.5 border border-red-100 hover:border-red-300 transition-colors">
                            <div class="font-medium text-gray-800">HS: <?php echo htmlspecialchars($hs['hoso']); ?></div>
                            <div class="flex justify-between items-center mt-0.5">
                                <span class="text-gray-500 truncate">
                                    <?php if (!empty($hs['phieu'])): ?>
                                        Phiếu: <?php echo htmlspecialchars($hs['phieu']); ?>
                                    <?php endif; ?>
                                    <?php if (!empty($hs['mavt'])): ?>
                                        <?php echo !empty($hs['phieu']) ? ' | ' : ''; ?>TB: <?php echo htmlspecialchars($hs['mavt']); ?>
                                    <?php endif; ?>
                                </span>
                                <?php if (!empty($hs['tendv'])): ?>
                                <span class="text-gray-500 ml-2"><?php echo htmlspecialchars($hs['tendv']); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($hs['lydo_tamdung'])): ?>
                            <div class="mt-0.5 text-[10px] text-red-600 italic truncate">
                                Lý do: <?php echo htmlspecialchars($hs['lydo_tamdung']); ?>
                            </div>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="mt-6 text-center">
        <p class="text-xs text-gray-400">Cập nhật tự động</p>
    </div>
</div>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
