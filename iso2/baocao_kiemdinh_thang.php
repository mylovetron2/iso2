<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Get filter parameters
$nam = isset($_GET['nam']) ? (int)$_GET['nam'] : (int)date('Y');
$madv = isset($_GET['madv']) ? trim($_GET['madv']) : '';
$chuaKD = isset($_GET['chuakd']) && $_GET['chuakd'] == '1';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch device inspection data from kehoach_iso and hosohckd_iso
try {
    $db = getDBConnection();
    
    // Get list of units for filter
    $stmtDV = $db->query("SELECT DISTINCT madv, tendv FROM donvi_iso ORDER BY madv");
    $donViList = $stmtDV->fetchAll(PDO::FETCH_ASSOC);
    
    // Build query to get devices from kehoach_iso with inspection data
    $sql = "
        SELECT 
            k.mahieu,
            k.somay,
            k.tenthietbi,
            k.hangsx,
            k.thang,
            h.ngayhc,
            MONTH(h.ngayhc) as thangkd,
            YEAR(h.ngayhc) as namkd
        FROM kehoach_iso k
        LEFT JOIN hosohckd_iso h ON k.mahieu = h.tenmay 
            AND YEAR(h.ngayhc) = ?
        WHERE k.namkh = ?
        ORDER BY k.hangsx, k.tenthietbi, k.somay
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$nam, $nam]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group devices and collect inspection months
    $devices = [];
    foreach ($results as $row) {
        $mahieu = $row['mahieu'];
        
        if (!isset($devices[$mahieu])) {
            $devices[$mahieu] = [
                'mahieu' => $row['mahieu'],
                'somay' => $row['somay'],
                'tenvt' => $row['tenthietbi'],
                'nuocsx' => $row['hangsx'],
                'months' => array_fill(1, 12, false), // Initialize 12 months as false
                'has_inspection' => false
            ];
        }
        
        // Mark the month as inspected
        if (!empty($row['thangkd'])) {
            $thang = (int)$row['thangkd'];
            if ($thang >= 1 && $thang <= 12) {
                $devices[$mahieu]['months'][$thang] = true;
                $devices[$mahieu]['has_inspection'] = true;
            }
        }
    }
    
    // Filter for "chưa kiểm định" if requested
    if ($chuaKD) {
        $devices = array_filter($devices, function($device) {
            return !$device['has_inspection'];
        });
    }
    
    // Filter by search keyword
    if (!empty($search)) {
        $searchLower = mb_strtolower($search, 'UTF-8');
        $devices = array_filter($devices, function($device) use ($searchLower) {
            $searchFields = [
                mb_strtolower($device['tenvt'] ?? '', 'UTF-8'),
                mb_strtolower($device['mahieu'] ?? '', 'UTF-8'),
                mb_strtolower($device['somay'] ?? '', 'UTF-8'),
                mb_strtolower($device['nuocsx'] ?? '', 'UTF-8')
            ];
            $searchText = implode(' ', $searchFields);
            return strpos($searchText, $searchLower) !== false;
        });
    }
    
    // Custom sort order based on device name and serial number
    $sortOrder = [
        'Bàn chuẩn máy độ lệch' => 1,
        'La bàn' => 2,
        'Lò điện tử chuẩn' => 3,
        'Đồng hồ đa năng số' => 4,
        'Nhiệt ẩm kế' => 11,
        'Máy hiện sóng' => 12,
        'Máy phát tần số' => 17,
        'Máy đo tần số' => 18,
        'Hộp trở MMЭC' => 19,
        'Bộ chuẩn máy cảm ứng' => 34,
        'Bộ chuẩn cho máy đo đường kính' => 35,
        'Bộ chuẩn cho vi hệ cực' => 36,
        'Đồng hồ sức căng' => 37,
        'Thước cặp' => 39,
        'Áp kế' => 40,
        'Đồng hồ Megaohm' => 42,
        'Đồng hồ kim simpson' => 44,
        'Lò chuẩn nhiệt độ' => 46
    ];
    
    // Sort devices by custom order
    uasort($devices, function($a, $b) use ($sortOrder) {
        $orderA = 999;
        $orderB = 999;
        
        foreach ($sortOrder as $keyword => $order) {
            if (stripos($a['tenvt'], $keyword) !== false) {
                $orderA = $order;
                break;
            }
        }
        
        foreach ($sortOrder as $keyword => $order) {
            if (stripos($b['tenvt'], $keyword) !== false) {
                $orderB = $order;
                break;
            }
        }
        
        if ($orderA != $orderB) {
            return $orderA - $orderB;
        }
        
        // If same type, sort by serial number
        return strcmp($a['somay'] ?? '', $b['somay'] ?? '');
    });
    
    // Don't group by country - display as flat list
    $groupedDevices = ['Tất cả thiết bị' => array_values($devices)];
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo "<h1 style='color:red;'>Database Error</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    $devices = [];
    $groupedDevices = [];
    $donViList = [];
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    echo "<h1 style='color:red;'>Error</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    $devices = [];
    $groupedDevices = [];
    $donViList = [];
}

$title = 'Báo cáo thống kê kiểm định theo tháng';
require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="max-w-full mx-auto bg-white rounded-lg shadow-md p-4 md:p-6">
    <h1 class="text-xl md:text-2xl font-bold mb-4 text-center">
        BÁO CÁO THỐNG KÊ KIỂM ĐỊNH THEO THÁNG
    </h1>
    <p class="text-center text-gray-600 mb-6">Năm: <?php echo $nam; ?></p>
    
    <!-- Filters -->
    <form method="GET" class="mb-6 bg-gray-50 p-4 rounded-lg">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Năm</label>
                <select name="nam" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                    <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y == $nam ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Tìm kiếm</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Tên TB, số máy, mã hiệu..." 
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>
            
            <div class="flex items-end">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="chuakd" value="1" <?php echo $chuaKD ? 'checked' : ''; ?> 
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="ml-2 text-gray-700 font-semibold">Chưa kiểm định</span>
                </label>
            </div>
            
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded font-semibold flex-1">
                    <i class="fas fa-filter mr-2"></i>Lọc
                </button>
                <a href="baocao_kiemdinh_thang.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded font-semibold">
                    <i class="fas fa-redo mr-2"></i>Reset
                </a>
            </div>
        </div>
    </form>
    
    <!-- Report Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full border-collapse border border-gray-400 text-sm">
            <thead>
                <tr class="bg-cyan-200">
                    <th rowspan="2" class="border border-gray-400 px-2 py-2">Stt</th>
                    <th rowspan="2" class="border border-gray-400 px-2 py-2">Tên thiết bị,mẫu<br>chuẩn/vật chuẩn</th>
                    <th rowspan="2" class="border border-gray-400 px-2 py-2">Ký/Mã hiệu</th>
                    <th rowspan="2" class="border border-gray-400 px-2 py-2">Số máy</th>
                    <th rowspan="2" class="border border-gray-400 px-2 py-2">Nước/Hãng<br>SX</th>
                    <th colspan="12" class="border border-gray-400 px-2 py-2">THÁNG</th>
                    <th rowspan="2" class="border border-gray-400 px-2 py-2">Chú số<br>hữu</th>
                </tr>
                <tr class="bg-cyan-200">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <th class="border border-gray-400 px-2 py-1"><?php echo $m; ?></th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                <?php 
                $stt = 1;
                foreach ($groupedDevices as $group => $groupDevices): 
                ?>
                    
                    <?php foreach ($groupDevices as $device): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="border border-gray-400 px-2 py-1 text-center"><?php echo $stt++; ?></td>
                        <td class="border border-gray-400 px-2 py-1"><?php echo htmlspecialchars($device['tenvt']); ?></td>
                        <td class="border border-gray-400 px-2 py-1 text-center"><?php echo htmlspecialchars($device['mahieu']); ?></td>
                        <td class="border border-gray-400 px-2 py-1 text-center"><?php echo htmlspecialchars($device['somay']); ?></td>
                        <td class="border border-gray-400 px-2 py-1 text-center"><?php echo htmlspecialchars($device['nuocsx']); ?></td>
                        
                        <!-- 12 month columns -->
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <td class="border border-gray-400 px-1 py-1 text-center <?php echo $device['months'][$m] ? 'bg-blue-400' : ''; ?>">
                                <?php echo $device['months'][$m] ? '✓' : ''; ?>
                            </td>
                        <?php endfor; ?>
                        
                        <td class="border border-gray-400 px-2 py-1 text-center"></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                
                <?php if (empty($groupedDevices)): ?>
                <tr>
                    <td colspan="16" class="border border-gray-400 px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>Không có dữ liệu</p>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Print Button -->
    <div class="mt-6 flex justify-center gap-4">
        <button onclick="window.print()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded font-semibold">
            <i class="fas fa-print mr-2"></i>In báo cáo
        </button>
        <a href="hososcbd.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded font-semibold inline-block">
            <i class="fas fa-arrow-left mr-2"></i>Quay lại
        </a>
    </div>
</div>

<style>
@media print {
    .no-print {
        display: none !important;
    }
    
    body {
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }
    
    table {
        page-break-inside: auto;
    }
    
    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }
}
</style>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
