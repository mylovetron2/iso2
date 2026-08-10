<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

// Get record ID from URL
$stt = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Capture filter params from URL to preserve them
$filterParams = [];
foreach (['search', 'madv', 'nhomsc', 'trangthai', 'page'] as $key) {
    if (isset($_GET[$key]) && $_GET[$key] !== '') {
        $filterParams[$key] = $_GET[$key];
    }
}

// Load models
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/HoSoSCBD.php';
require_once __DIR__ . '/../../models/ThietBiHoTro.php';
$model = new HoSoSCBD();
$thietBiHoTroModel = new ThietBiHoTro();
$thietBiHoTroList = $thietBiHoTroModel->getAllSimple();

// If no ID, redirect
if (!$stt) {
    header("Location: hososcbd.php");
    exit;
}

// Load the record
$item = $model->findById($stt);

if (!$item) {
    header("Location: hososcbd.php");
    exit;
}

// Load thông tin bảo dưỡng định kỳ (BDDK) nếu có
$bddkInfo = null;
$thietbi = null;
try {
    if (!empty($item['mavt']) && !empty($item['somay'])) {
        $db = getDBConnection();
        
        // Bước 1: Tìm thietbi_id từ bảng thietbi_iso
        $sqlThietBi = "SELECT stt as thietbi_id FROM thietbi_iso 
                       WHERE mavt = :mavt AND somay = :somay
                       LIMIT 1";
        $stmtThietBi = $db->prepare($sqlThietBi);
        $stmtThietBi->execute([
            ':mavt' => $item['mavt'],
            ':somay' => $item['somay']
        ]);
        $thietbi = $stmtThietBi->fetch(PDO::FETCH_ASSOC);
        
        if ($thietbi && !empty($thietbi['thietbi_id'])) {
            $thietbi_id = $thietbi['thietbi_id'];
            
            // Bước 2: Tìm kế hoạch BDDK theo thietbi_id
            $sql = "SELECT * FROM ke_hoach_bao_duong_dinh_ky_iso 
                    WHERE thietbi_id = :thietbi_id
                    ORDER BY nam DESC 
                    LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([':thietbi_id' => $thietbi_id]);
            $bddkInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
} catch (Exception $e) {
    error_log("Error loading BDDK info: " . $e->getMessage());
}

// Load danh sách người thực hiện
$nguoiThucHienList = [];
if (!empty($item['hoso'])) {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("
            SELECT stt, mahoso, mamay, somay, hoten, giolv, ngayth, ngaykt,
                   giolv1, giolv2, giolv3, giolv4, giolv5, giolv6,
                   giolv7, giolv8, giolv9, giolv10, giolv11, giolv12
            FROM ngthuchien_iso 
            WHERE mahoso = :mahoso 
            ORDER BY stt ASC
        ");
        $stmt->execute([':mahoso' => $item['hoso']]);
        $nguoiThucHienList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching nguoi thuc hien: " . $e->getMessage());
    }
}

// Load danh sách tên người thực hiện để gợi ý từ bảng resume
$nguoiThucHienAutocomplete = [];
try {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        SELECT DISTINCT hoten 
        FROM resume 
        WHERE hoten IS NOT NULL 
          AND hoten != '' 
          AND nghiviec != 'yes'
          AND donvi LIKE :donvi
        ORDER BY hoten ASC
    ");
    $stmt->execute([':donvi' => '%chuẩn chỉnh máy địa vật lý%']);
    $nguoiThucHienAutocomplete = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Error fetching nguoi thuc hien autocomplete from resume: " . $e->getMessage());
}

// Kiểm tra quyền sửa: hồ sơ đã có ngày kết thúc thì chỉ admin mới lưu thẳng
require_once __DIR__ . '/../../config/constants.php';
$isAdmin = hasRole(ROLE_ADMIN);
$hasNgaykt = !empty($item['ngaykt']) && $item['ngaykt'] !== '0000-00-00';
$needsApproval = $hasNgaykt && !$isAdmin;

// Định mức KPI (kiểm tra/BD cấp 1-2-3/hiệu chuẩn) gắn cho hồ sơ này
$canViewDinhMuc = hasPermission('hososcbd_dinhmuc.view') || hasPermission('hososcbd.view');
$canEditDinhMuc = hasPermission('hososcbd_dinhmuc.edit');
require_once __DIR__ . '/../../models/HoSoSCBDDinhMuc.php';
$dinhMucModel = new HoSoSCBDDinhMuc();
$dinhMucInfo = $canViewDinhMuc ? $dinhMucModel->layTheoHoSo($stt) : false;
$deviceKpiLink = null;
$deviceKpiLabel = '';
$deviceKpiDetails = null;
$kpiThietBiList = [];
if ($canEditDinhMuc) {
    try {
        $dinhMucDb = getDBConnection();
        $kpiThietBiList = $dinhMucDb->query(
            "SELECT id, ten_thiet_bi, kiem_tra_so_gio, bd_cap_1_so_gio, bd_cap_2_so_gio, bd_cap_3_so_gio, hieu_chuan_so_gio
             FROM kpi_baoduong_thietbi_iso
             ORDER BY ten_thiet_bi ASC"
        )->fetchAll(PDO::FETCH_ASSOC);

        if ($thietbi && !empty($thietbi['thietbi_id'])) {
            $deviceKpiStmt = $dinhMucDb->prepare(
                "SELECT l.kpi_baoduong_stt, k.ten_thiet_bi, k.kiem_tra_so_gio, k.bd_cap_1_so_gio, k.bd_cap_2_so_gio, k.bd_cap_3_so_gio, k.hieu_chuan_so_gio
                 FROM thietbi_kpi_baoduong_iso l
                 LEFT JOIN kpi_baoduong_thietbi_iso k ON k.id = l.kpi_baoduong_stt
                 WHERE l.thietbi_stt = :thietbi_stt
                 LIMIT 1"
            );
            $deviceKpiStmt->execute([':thietbi_stt' => (int)$thietbi['thietbi_id']]);
            $deviceKpiLink = $deviceKpiStmt->fetch(PDO::FETCH_ASSOC);
            $deviceKpiLabel = (string)($deviceKpiLink['ten_thiet_bi'] ?? '');
            $deviceKpiDetails = $deviceKpiLink;
        }
    } catch (PDOException $e) {
        error_log('Error loading kpi_baoduong_thietbi_iso list: ' . $e->getMessage());
    }
}
$dinhMucError = '';
$dinhMucSuccess = '';
$kpiHourPreviewMap = [];
if (!empty($kpiThietBiList)) {
    foreach ($kpiThietBiList as $kpiRow) {
        $kpiHourPreviewMap[(int)$kpiRow['id']] = [
            'kiem_tra' => $kpiRow['kiem_tra_so_gio'] !== null ? (float)$kpiRow['kiem_tra_so_gio'] : null,
            'bd_cap_1' => $kpiRow['bd_cap_1_so_gio'] !== null ? (float)$kpiRow['bd_cap_1_so_gio'] : null,
            'bd_cap_2' => $kpiRow['bd_cap_2_so_gio'] !== null ? (float)$kpiRow['bd_cap_2_so_gio'] : null,
            'bd_cap_3' => $kpiRow['bd_cap_3_so_gio'] !== null ? (float)$kpiRow['bd_cap_3_so_gio'] : null,
            'hieu_chuan' => $kpiRow['hieu_chuan_so_gio'] !== null ? (float)$kpiRow['hieu_chuan_so_gio'] : null,
        ];
    }
}

// Xử lý lưu định mức KPI (form riêng, tách khỏi form sửa hồ sơ chính)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dinhmuc_action'])) {
    if (!$canEditDinhMuc) {
        http_response_code(403);
        die('Không có quyền gán định mức KPI');
    }
    $kpiBaoDuongStt = (int)($_POST['kpi_baoduong_stt'] ?? 0);
    $loaiCongViec = trim((string)($_POST['loai_congviec'] ?? ''));
    if ($kpiBaoDuongStt <= 0 || $loaiCongViec === '') {
        $dinhMucError = 'Vui lòng chọn đầy đủ thiết bị KPI và loại công việc';
    } else {
        $createdBy = $_SESSION['username'] ?? null;
        if ($dinhMucModel->luuDinhMuc($stt, $kpiBaoDuongStt, $loaiCongViec, $createdBy)) {
            header("Location: hososcbd_repair_details.php?id={$stt}");
            exit;
        }
        $dinhMucError = 'Có lỗi xảy ra khi lưu định mức KPI';
    }
    $dinhMucInfo = $dinhMucModel->layTheoHoSo($stt);
}

// Handle form submission BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['dinhmuc_action'])) {
    try {
        $data = [
            'cv' => trim($_POST['cv'] ?? 'SC'),
            'nhomsc' => trim($_POST['nhomsc'] ?? ''),
            'ngaybdtt' => !empty(trim($_POST['ngaybdtt'] ?? '')) ? trim($_POST['ngaybdtt']) : '0000-00-00',
            'ngayth' => !empty(trim($_POST['ngayth'] ?? '')) ? trim($_POST['ngayth']) : '0000-00-00',
            'ngaykt' => empty(trim($_POST['ngaykt'] ?? '')) ? '0000-00-00' : trim($_POST['ngaykt']),
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

        // --- Nếu là user thường và hồ sơ đã có ngày kết thúc: lưu vào bảng pending ---
        if ($needsApproval) {
            $nguoiThucHienPost = [];
            if (isset($_POST['nguoi_hoten'])) {
                $hoten_arr = $_POST['nguoi_hoten'] ?? [];
                $gio_arr   = $_POST['nguoi_gio'] ?? [];
                foreach ($hoten_arr as $i => $ht) {
                    if (trim($ht) !== '') {
                        $nguoiThucHienPost[] = [
                            'hoten' => trim($ht),
                            'giolv' => floatval($gio_arr[$i] ?? 0)
                        ];
                    }
                }
            }

            $db = getDBConnection();
            $userInfo = $_SESSION['user_id'] ?? 0;
            $username = $_SESSION['username'] ?? '';

            // Xóa pending cũ của cùng user+hồ sơ nếu còn pending
            $db->prepare("DELETE FROM hososcbd_pending_edits WHERE hososcbd_stt = :stt AND user_id = :uid AND status = 'pending'")
               ->execute([':stt' => $stt, ':uid' => $userInfo]);

            $stmt = $db->prepare("INSERT INTO hososcbd_pending_edits
                (hososcbd_stt, user_id, username, data_json, nguoi_thuchien_json, bddk_hoantat, status)
                VALUES (:stt, :uid, :uname, :data, :nguoi, :bddk, 'pending')");
            $stmt->execute([
                ':stt'   => $stt,
                ':uid'   => $userInfo,
                ':uname' => $username,
                ':data'  => json_encode($data, JSON_UNESCAPED_UNICODE),
                ':nguoi' => json_encode($nguoiThucHienPost, JSON_UNESCAPED_UNICODE),
                ':bddk'  => (isset($_POST['bddk_hoantat']) && $_POST['bddk_hoantat'] === '1') ? 1 : 0,
            ]);

            $pendingMessage = 'Hồ sơ đã có ngày kết thúc. Yêu cầu sửa đổi của bạn đã được gửi đến admin để duyệt.';
        } else {
            // --- Lưu thẳng vào DB (admin hoặc hồ sơ chưa có ngày kết thúc) ---
            $success = $model->update($stt, $data);
            if ($success !== false) {
                // Xử lý cập nhật BDDK nếu checkbox được chọn
                if (isset($_POST['bddk_hoantat']) && $_POST['bddk_hoantat'] === '1') {
                    $ngayth = $data['ngayth'];
                    if ($ngayth !== '0000-00-00' && $thietbi && !empty($thietbi['thietbi_id'])) {
                        try {
                            // Tính quý từ tháng
                            $month = (int)date('n', strtotime($ngayth));
                            $quarter = ceil($month / 3); // 1-3 => 1, 4-6 => 2, 7-9 => 3, 10-12 => 4
                            
                            // Lấy năm hiện tại
                            $nam = (int)date('Y', strtotime($ngayth));
                            
                            // Update bảng BDDK
                            $db = getDBConnection();
                            $hoantat_field = "qui_{$quarter}_hoantat";
                            
                            $sqlUpdate = "UPDATE ke_hoach_bao_duong_dinh_ky_iso 
                                          SET $hoantat_field = 1
                                          WHERE thietbi_id = :thietbi_id 
                                            AND nam = :nam";
                            $stmtUpdate = $db->prepare($sqlUpdate);
                            $stmtUpdate->execute([
                                ':thietbi_id' => $thietbi['thietbi_id'],
                                ':nam' => $nam
                            ]);
                            
                            error_log("BDDK Updated: thietbi_id={$thietbi['thietbi_id']}, nam=$nam, quy=$quarter, ngayth=$ngayth");
                        } catch (Exception $e) {
                            error_log("Error updating BDDK: " . $e->getMessage());
                        }
                    }
                }
                
                // Xử lý lưu người thực hiện
                if (!empty($item['hoso']) && isset($_POST['nguoi_hoten'])) {
                    try {
                        $db = getDBConnection();
                        $mahoso = $item['hoso'];
                        $mavt = $item['mavt'];
                        $somay = $item['somay'];
                        $ngayth = $data['ngayth'];
                        $ngaykt = $data['ngaykt'];
                        $currentMonth = (int)date('n'); // Tháng hiện tại (1-12)
                        $giolv_field = "giolv{$currentMonth}";
                        
                        $hoten_array = $_POST['nguoi_hoten'] ?? [];
                        $gio_array = $_POST['nguoi_gio'] ?? [];
                        
                        // Lấy danh sách người thực hiện hiện có
                        $stmtExisting = $db->prepare("SELECT stt FROM ngthuchien_iso WHERE mahoso = :mahoso ORDER BY stt ASC");
                        $stmtExisting->execute([':mahoso' => $mahoso]);
                        $existingList = $stmtExisting->fetchAll(PDO::FETCH_COLUMN);
                        
                        // Xác định STT mới cho người mới thêm vào
                        $stmtMaxStt = $db->prepare("SELECT COALESCE(MAX(stt), 0) as max_stt FROM ngthuchien_iso");
                        $stmtMaxStt->execute();
                        $maxStt = (int)$stmtMaxStt->fetch(PDO::FETCH_ASSOC)['max_stt'];
                        $nextStt = $maxStt + 1;
                        
                        $processedIndices = [];
                        
                        // Bước 1: Cập nhật hoặc xóa các bản ghi hiện có
                        foreach ($existingList as $idx => $existingStt) {
                            $formIndex = $idx; // Index trong form tương ứng với vị trí trong DB
                            
                            if (isset($hoten_array[$formIndex]) && trim($hoten_array[$formIndex]) !== '') {
                                // Cập nhật bản ghi
                                $hoten = trim($hoten_array[$formIndex]);
                                $gio = floatval($gio_array[$formIndex] ?? 0);
                                
                                $sqlUpdate = "UPDATE ngthuchien_iso SET 
                                    hoten = :hoten,
                                    giolv = :giolv,
                                    mamay = :mamay,
                                    somay = :somay,
                                    ngayth = :ngayth,
                                    ngaykt = :ngaykt,
                                    {$giolv_field} = :giolv_month
                                    WHERE stt = :stt";
                                $stmtUpdate = $db->prepare($sqlUpdate);
                                $stmtUpdate->execute([
                                    ':hoten' => $hoten,
                                    ':giolv' => $gio,
                                    ':mamay' => $mavt,
                                    ':somay' => $somay,
                                    ':ngayth' => $ngayth,
                                    ':ngaykt' => $ngaykt,
                                    ':giolv_month' => $gio,
                                    ':stt' => $existingStt
                                ]);
                                $processedIndices[] = $formIndex;
                            } else {
                                // Xóa bản ghi nếu tên bị xóa
                                $sqlDelete = "DELETE FROM ngthuchien_iso WHERE stt = :stt";
                                $stmtDelete = $db->prepare($sqlDelete);
                                $stmtDelete->execute([':stt' => $existingStt]);
                            }
                        }
                        
                        // Bước 2: Thêm người mới (từ index sau số lượng bản ghi cũ)
                        for ($i = count($existingList); $i < 8; $i++) {
                            if (isset($hoten_array[$i]) && trim($hoten_array[$i]) !== '') {
                                $hoten = trim($hoten_array[$i]);
                                $gio = floatval($gio_array[$i] ?? 0);
                                
                                $sqlInsert = "INSERT INTO ngthuchien_iso (
                                    stt, mahoso, mamay, somay, hoten, giolv, ngayth, ngaykt, {$giolv_field}
                                ) VALUES (
                                    :stt, :mahoso, :mamay, :somay, :hoten, :giolv, :ngayth, :ngaykt, :giolv_month
                                )";
                                $stmtInsert = $db->prepare($sqlInsert);
                                $stmtInsert->execute([
                                    ':stt' => $nextStt,
                                    ':mahoso' => $mahoso,
                                    ':mamay' => $mavt,
                                    ':somay' => $somay,
                                    ':hoten' => $hoten,
                                    ':giolv' => $gio,
                                    ':ngayth' => $ngayth,
                                    ':ngaykt' => $ngaykt,
                                    ':giolv_month' => $gio
                                ]);
                                $nextStt++;
                            }
                        }
                        
                    } catch (Exception $e) {
                        error_log("Error saving nguoi thuc hien: " . $e->getMessage());
                    }
                }
                
                // Build redirect URL with preserved filters
                $redirectUrl = '/iso2/hososcbd.php';
                // Get filter params from POST (hidden inputs) or from initial GET
                $postFilters = [];
                foreach (['search', 'madv', 'nhomsc', 'trangthai', 'page'] as $key) {
                    if (isset($_POST['filter_' . $key]) && $_POST['filter_' . $key] !== '') {
                        $postFilters[$key] = $_POST['filter_' . $key];
                    }
                }
                $params = !empty($postFilters) ? $postFilters : $filterParams;
                if (!empty($params)) {
                    $redirectUrl .= '?' . http_build_query($params);
                }
                header("Location: $redirectUrl");
                exit;
            } else {
                $errorMessage = 'Có lỗi xảy ra khi cập nhật';
            }
        }
    } catch (Exception $e) {
        error_log("Error updating repair details: " . $e->getMessage());
        $errorMessage = 'Lỗi: ' . $e->getMessage();
    }
}

// Now include header after all logic
$title = 'Thông tin sửa chữa & Thiết bị đo';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-6xl mx-auto bg-white rounded-lg shadow-md p-4 md:p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl md:text-2xl font-bold flex items-center">
            <i class="fas fa-wrench mr-2 text-orange-600"></i> Thông tin sửa chữa & Thiết bị đo
        </h1>
        <div class="flex items-center space-x-2">
            <?php
            // Build back URL with filter params
            $backUrl = 'hososcbd.php';
            if (!empty($filterParams)) {
                $backUrl .= '?' . http_build_query($filterParams);
            }
            $filterQuery = !empty($filterParams) ? '&' . http_build_query($filterParams) : '';
            ?>
            <a href="hososcbd_congviec.php?id=<?= $stt ?><?= $filterQuery ?>" 
               class="inline-flex items-center px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded text-sm">
                <i class="fas fa-tasks mr-1"></i>
                <span class="hidden sm:inline">Công việc</span>
                <span class="sm:hidden">CV</span>
            </a>
            <?php /* Ẩn nút bàn giao
            <a href="hososcbd_handover_details.php?id=<?= $stt ?><?= $filterQuery ?>" 
               class="inline-flex items-center px-3 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded text-sm">
                <i class="fas fa-handshake mr-1"></i>
                <span class="hidden sm:inline">Bàn giao</span>
                <span class="sm:hidden">BG</span>
            </a>
            */ ?>
            <a href="<?php echo htmlspecialchars($backUrl); ?>" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm">
                <i class="fas fa-arrow-left mr-2"></i>Quay lại
            </a>
        </div>
    </div>
    
    <!-- Record Info - Sticky Header -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 sticky top-0 z-10 shadow-md">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-base md:text-lg">
            <div class="bg-indigo-100 p-3 rounded-lg border-l-4 border-indigo-500">
                <span class="font-semibold text-indigo-700">Số phiếu:</span>
                <span class="ml-2 font-bold text-indigo-900"><?php echo htmlspecialchars($item['phieu']); ?></span>
            </div>
            <div class="bg-green-100 p-3 rounded-lg border-l-4 border-green-500">
                <span class="font-semibold text-green-700">Thiết bị:</span>
                <span class="ml-2 font-bold text-green-900"><?php echo htmlspecialchars($item['mavt'] . ' - ' . $item['somay']); ?></span>
            </div>
        </div>
    </div>

    <?php if (isset($errorMessage)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <i class="fas fa-times-circle mr-2"></i><?php echo $errorMessage; ?>
    </div>
    <?php endif; ?>

    <?php if (isset($pendingMessage)): ?>
    <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded mb-4">
        <i class="fas fa-clock mr-2"></i><?php echo htmlspecialchars($pendingMessage); ?>
    </div>
    <?php endif; ?>

    <?php if ($needsApproval): ?>
    <div class="bg-orange-50 border-l-4 border-orange-400 text-orange-800 px-4 py-3 rounded mb-4">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <strong>Lưu ý:</strong> Hồ sơ này đã có ngày kết thúc. Thay đổi của bạn sẽ được gửi cho admin duyệt trước khi cập nhật vào hệ thống.
    </div>
    <?php endif; ?>

    <!-- Thông tin Bảo dưỡng định kỳ (BDDK) -->
    <?php if ($bddkInfo): ?>
    <div class="bg-gradient-to-r from-purple-50 to-indigo-50 border-2 border-purple-300 rounded-lg p-3 mb-6 shadow-md">
        <div class="flex flex-wrap items-center gap-3 text-sm">
            <!-- BDDK Badge -->
            <div class="flex items-center gap-2">
                <i class="fas fa-calendar-check text-purple-600"></i>
                <span class="font-bold text-purple-700">BDDK</span>
            </div>
            <div class="text-gray-400">|</div>
            <!-- Năm và Nhóm -->
            <div class="flex items-center gap-2">
                <span class="font-semibold text-purple-700">Năm:</span>
                <span class="font-bold text-purple-900 bg-white px-2 py-1 rounded"><?php echo htmlspecialchars($bddkInfo['nam'] ?? ''); ?></span>
            </div>
            <div class="text-gray-400">|</div>
            <div class="flex items-center gap-2">
                <span class="font-semibold text-purple-700">Nhóm SC:</span>
                <span class="font-bold text-purple-900 bg-white px-2 py-1 rounded"><?php echo htmlspecialchars($bddkInfo['nhomsc'] ?? ''); ?></span>
            </div>
            <div class="text-gray-400">|</div>
            
            <!-- 4 Quý -->
            <?php for ($q = 1; $q <= 4; $q++): 
                $qui_field = "qui_$q";
                $hoantat_field = "qui_{$q}_hoantat";
                $qui_value = trim($bddkInfo[$qui_field] ?? '');
                $is_hoantat = !empty($bddkInfo[$hoantat_field]);
                $has_plan = !empty($qui_value);
            ?>
            <div class="flex items-center gap-1">
                <span class="font-semibold text-blue-700">Q<?php echo $q; ?>:</span>
                <?php if ($is_hoantat): ?>
                    <span class="bg-green-500 text-white px-3 py-1 rounded font-bold text-xs">✓</span>
                <?php elseif ($has_plan): ?>
                    <span class="bg-orange-400 px-3 py-1 rounded">&nbsp;</span>
                <?php else: ?>
                    <span class="bg-gray-200 px-3 py-1 rounded">&nbsp;</span>
                <?php endif; ?>
            </div>
            <?php endfor; ?>
            
            <?php if (!empty($bddkInfo['ghi_chu'])): ?>
            <div class="text-gray-400">|</div>
            <div class="flex items-center gap-2">
                <i class="fas fa-sticky-note text-yellow-600"></i>
                <span class="text-gray-700 italic"><?php echo htmlspecialchars($bddkInfo['ghi_chu']); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
    <!-- Hiển thị thông báo nếu không có dữ liệu BDDK -->
    <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-3 mb-6">
        <p class="text-sm text-yellow-700">
            <i class="fas fa-info-circle mr-2"></i>
            <strong>Thông tin BDDK:</strong> Không tìm thấy kế hoạch bảo dưỡng định kỳ cho thiết bị <strong><?php echo htmlspecialchars($item['mavt'] ?? ''); ?></strong> (Số máy: <strong><?php echo htmlspecialchars($item['somay'] ?? ''); ?></strong>)
        </p>
    </div>
    <?php endif; ?>

    <!-- Checkbox BDDK hoàn tất -->
    <?php if ($bddkInfo): 
        // Tính quý từ ngày thực hiện
        $ngayth = $item['ngayth'];
        $currentQuarter = 0;
        $isAlreadyCompleted = false;
        
        if ($ngayth && $ngayth !== '0000-00-00') {
            $month = (int)date('n', strtotime($ngayth));
            $currentQuarter = ceil($month / 3);
            $hoantat_field = "qui_{$currentQuarter}_hoantat";
            $isAlreadyCompleted = !empty($bddkInfo[$hoantat_field]);
        }
    ?>
    <div class="bg-purple-50 p-4 rounded-lg border-2 border-purple-300 mb-6">
        <label class="flex items-center gap-3 <?php echo $isAlreadyCompleted ? 'opacity-75' : 'cursor-pointer'; ?>">
            <input type="checkbox" name="bddk_hoantat" value="1" 
                   <?php echo $isAlreadyCompleted ? 'checked disabled' : ''; ?>
                   form="repair-form"
                   class="w-5 h-5 text-purple-600 bg-white border-purple-300 rounded focus:ring-purple-500">
            <div class="flex-1">
                <span class="font-bold text-purple-700 text-base">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php if ($isAlreadyCompleted): ?>
                        ✓ Đã hoàn thành BDDK (Quý <?php echo $currentQuarter; ?>)
                    <?php else: ?>
                        Đánh dấu hoàn thành BDDK
                    <?php endif; ?>
                </span>
                <p class="text-sm text-purple-600 mt-1">
                    <?php if ($isAlreadyCompleted): ?>
                        Quý <?php echo $currentQuarter; ?> của năm <?php echo $bddkInfo['nam']; ?> đã được đánh dấu hoàn tất
                    <?php elseif ($currentQuarter > 0): ?>
                        Khi chọn, Quý <?php echo $currentQuarter; ?> (từ ngày thực hiện) sẽ được đánh dấu hoàn tất trong kế hoạch BDDK
                    <?php else: ?>
                        Vui lòng nhập ngày thực hiện để xác định quý cần hoàn thành
                    <?php endif; ?>
                </p>
            </div>
        </label>
    </div>
    <?php endif; ?>

    <!-- Định mức KPI & Kết luận Đạt/Không đạt -->
    <?php if ($canViewDinhMuc): ?>
    <?php
        $loaiCongViecLabels = [
            'kiem_tra'   => 'Kiểm tra',
            'bd_cap_1'   => 'BD cấp 1',
            'bd_cap_2'   => 'BD cấp 2',
            'bd_cap_3'   => 'BD cấp 3',
            'hieu_chuan' => 'Hiệu chuẩn',
        ];
    ?>
    <div class="bg-teal-50 border-2 border-teal-300 rounded-lg p-4 mb-6">
        <h2 class="text-lg font-bold text-teal-700 mb-3">
            <i class="fas fa-bullseye mr-2"></i>Định mức KPI
        </h2>

        <?php if ($dinhMucError): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded mb-3 text-sm">
            <?php echo htmlspecialchars($dinhMucError); ?>
        </div>
        <?php endif; ?>

        <?php if ($deviceKpiLink): ?>
            <?php
                $selectedLoaiCongViec = '';
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['loai_congviec'])) {
                    $selectedLoaiCongViec = trim((string)$_POST['loai_congviec']);
                }
                if ($selectedLoaiCongViec === '' && $dinhMucInfo) {
                    $selectedLoaiCongViec = (string)($dinhMucInfo['loai_congviec'] ?? '');
                }
                if ($selectedLoaiCongViec === '') {
                    $selectedLoaiCongViec = 'kiem_tra';
                }
                $hourFieldMap = [
                    'kiem_tra' => 'kiem_tra_so_gio',
                    'bd_cap_1' => 'bd_cap_1_so_gio',
                    'bd_cap_2' => 'bd_cap_2_so_gio',
                    'bd_cap_3' => 'bd_cap_3_so_gio',
                    'hieu_chuan' => 'hieu_chuan_so_gio',
                ];
                $effectiveKpiHour = null;
                $effectiveKpiLabel = $deviceKpiLabel !== '' ? $deviceKpiLabel : '—';
                $hourField = $hourFieldMap[$selectedLoaiCongViec] ?? 'kiem_tra_so_gio';
                if (isset($deviceKpiDetails[$hourField]) && $deviceKpiDetails[$hourField] !== null) {
                    $effectiveKpiHour = (float)$deviceKpiDetails[$hourField];
                }
                $ketLuan = $dinhMucInfo['ket_luan_kpi'] ?? 'chua_du_du_lieu';
                $ketLuanBadge = [
                    'dat' => ['bg-green-500 text-white', 'Đạt KPI'],
                    'khong_dat' => ['bg-red-500 text-white', 'Không đạt KPI'],
                    'chua_du_du_lieu' => ['bg-gray-300 text-gray-700', 'Chưa đủ dữ liệu'],
                ][$ketLuan] ?? ['bg-gray-300 text-gray-700', 'Chưa đủ dữ liệu'];
            ?>
            <div class="bg-white border border-teal-300 rounded px-3 py-2 text-sm text-teal-700 mb-3">
                <div class="font-semibold mb-1">Thiết bị này đã có định mức KPI gắn sẵn</div>
                <div><strong>Thiết bị KPI:</strong> <?php echo htmlspecialchars($effectiveKpiLabel); ?></div>
                <div><strong>Định mức giờ:</strong> <span id="kpi-hour-preview-value"><?php echo $effectiveKpiHour !== null ? htmlspecialchars((string)$effectiveKpiHour) : '—'; ?></span></div>
                <div class="mt-2">
                    <span class="px-3 py-1 rounded font-bold <?php echo $ketLuanBadge[0]; ?>"><?php echo $ketLuanBadge[1]; ?></span>
                </div>
            </div>
        <?php elseif ($dinhMucInfo): ?>
            <?php
                $ketLuan = $dinhMucInfo['ket_luan_kpi'] ?? 'chua_du_du_lieu';
                $ketLuanBadge = [
                    'dat' => ['bg-green-500 text-white', 'Đạt KPI'],
                    'khong_dat' => ['bg-red-500 text-white', 'Không đạt KPI'],
                    'chua_du_du_lieu' => ['bg-gray-300 text-gray-700', 'Chưa đủ dữ liệu'],
                ][$ketLuan] ?? ['bg-gray-300 text-gray-700', 'Chưa đủ dữ liệu'];
            ?>
            <div class="flex flex-wrap items-center gap-3 text-sm mb-3">
                <span class="font-semibold text-teal-700">Thiết bị:</span>
                <span class="font-bold bg-white px-2 py-1 rounded"><?php echo htmlspecialchars($dinhMucInfo['ten_thiet_bi'] ?? ''); ?></span>
                <span class="font-semibold text-teal-700">Loại:</span>
                <span class="font-bold bg-white px-2 py-1 rounded"><?php echo htmlspecialchars($loaiCongViecLabels[$dinhMucInfo['loai_congviec']] ?? $dinhMucInfo['loai_congviec']); ?></span>
                <span class="font-semibold text-teal-700">Định mức giờ:</span>
                <span class="font-bold bg-white px-2 py-1 rounded"><?php echo $dinhMucInfo['dinh_muc_so_gio'] !== null ? htmlspecialchars((string)$dinhMucInfo['dinh_muc_so_gio']) : '—'; ?></span>
                <span class="font-semibold text-teal-700">Giờ thực tế:</span>
                <span class="font-bold bg-white px-2 py-1 rounded"><?php echo $dinhMucInfo['gio_thuc_te'] !== null ? htmlspecialchars((string)$dinhMucInfo['gio_thuc_te']) : '—'; ?></span>
                <span class="px-3 py-1 rounded font-bold <?php echo $ketLuanBadge[0]; ?>"><?php echo $ketLuanBadge[1]; ?></span>
            </div>
        <?php else: ?>
            <p class="text-sm text-teal-700 mb-3">Hồ sơ này chưa được gán định mức KPI.</p>
        <?php endif; ?>

        <?php if ($canEditDinhMuc): ?>
        <form method="POST" class="flex flex-wrap items-end gap-3">
            <input type="hidden" name="dinhmuc_action" value="1">
            <div>
                <label class="block text-xs font-semibold text-teal-700 mb-1">Thiết bị KPI</label>
                <select id="kpi-baoduong-select" name="kpi_baoduong_stt" class="border border-teal-300 rounded px-2 py-1 text-sm">
                    <option value="">-- Chọn thiết bị --</option>
                    <?php foreach ($kpiThietBiList as $kpiRow): ?>
                    <?php
                        $selectedKpi = false;
                        $selectedKpiValue = (int)($dinhMucInfo['kpi_baoduong_stt'] ?? 0);
                        if ($selectedKpiValue > 0 && (int)$kpiRow['id'] === $selectedKpiValue) {
                            $selectedKpi = true;
                        } elseif (!$dinhMucInfo && $deviceKpiLink && (int)$deviceKpiLink['kpi_baoduong_stt'] === (int)$kpiRow['id']) {
                            $selectedKpi = true;
                        }
                    ?>
                    <option value="<?php echo (int)$kpiRow['id']; ?>" <?php echo $selectedKpi ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($kpiRow['ten_thiet_bi']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-teal-700 mb-1">Loại công việc</label>
                <select id="loai-congviec-select" name="loai_congviec" class="border border-teal-300 rounded px-2 py-1 text-sm">
                    <?php foreach ($loaiCongViecLabels as $key => $label): ?>
                    <?php $selectedLoai = ($dinhMucInfo && $dinhMucInfo['loai_congviec'] === $key) || (!$dinhMucInfo && $key === 'kiem_tra'); ?>
                    <option value="<?php echo $key; ?>" <?php echo $selectedLoai ? 'selected' : ''; ?>>
                        <?php echo $label; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="w-full text-sm text-teal-700">
                <span class="font-semibold">Định mức giờ hiện tại:</span>
                <span id="kpi-hour-preview-inline"><?php echo $effectiveKpiHour !== null ? htmlspecialchars((string)$effectiveKpiHour) : '—'; ?></span>
            </div>
            <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white px-3 py-1.5 rounded text-sm">
                <i class="fas fa-save mr-1"></i><?php echo $dinhMucInfo ? 'Cập nhật định mức' : 'Gán định mức'; ?>
            </button>
        </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <script>
    (function () {
        const hourMap = <?php echo json_encode($kpiHourPreviewMap, JSON_UNESCAPED_UNICODE); ?>;
        const typeSelect = document.getElementById('loai-congviec-select');
        const kpiSelect = document.getElementById('kpi-baoduong-select');
        const hourValue = document.getElementById('kpi-hour-preview-value');
        const hourInline = document.getElementById('kpi-hour-preview-inline');

        function formatHour(value) {
            return value === null || value === undefined || value === '' ? '—' : String(value);
        }

        function updatePreview() {
            if (!typeSelect || !kpiSelect) {
                return;
            }
            const type = typeSelect.value || 'kiem_tra';
            const kpiId = parseInt(kpiSelect.value, 10) || 0;
            const entry = hourMap[kpiId] || {};
            const value = entry[type];
            const display = formatHour(value);
            if (hourValue) {
                hourValue.textContent = display;
            }
            if (hourInline) {
                hourInline.textContent = display;
            }
        }

        if (typeSelect) {
            typeSelect.addEventListener('change', updatePreview);
        }
        if (kpiSelect) {
            kpiSelect.addEventListener('change', updatePreview);
        }
        document.addEventListener('DOMContentLoaded', updatePreview);
        updatePreview();
    })();
    </script>

    <!-- Người thực hiện -->
    <div class="border-l-4 border-indigo-500 pl-4 mb-6">
        <div class="flex justify-between items-center mb-3">
            <h2 class="text-lg font-bold text-indigo-700">
                <i class="fas fa-users mr-2"></i>Người thực hiện
            </h2>
            <button type="button" onclick="addPersonRow()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded text-sm">
                <i class="fas fa-user-plus mr-1"></i> Thêm người
            </button>
        </div>
        <div id="personList" class="space-y-2">
            <?php 
            // Chuẩn bị danh sách người thực hiện (tối đa 8)
            $persons = [];
            for ($i = 0; $i < 8; $i++) {
                if (isset($nguoiThucHienList[$i])) {
                    $persons[] = [
                        'hoten' => $nguoiThucHienList[$i]['hoten'] ?? '',
                        'giolv' => $nguoiThucHienList[$i]['giolv'] ?? ''
                    ];
                } else {
                    $persons[] = ['hoten' => '', 'giolv' => ''];
                }
            }
            
            // Hiển thị ít nhất 2 dòng, hoặc số dòng đã có + 1
            $displayCount = max(2, count($nguoiThucHienList) > 0 ? count($nguoiThucHienList) : 1);
            
            for ($idx = 0; $idx < $displayCount && $idx < 8; $idx++): 
                $person = $persons[$idx];
            ?>
            <div class="person-row flex gap-2 items-start bg-indigo-50 p-2 rounded">
                <div class="flex-1 relative">
                    <input type="text" 
                           name="nguoi_hoten[<?php echo $idx; ?>]" 
                           list="nguoiThList" 
                           placeholder="Chọn từ danh sách..." 
                           value="<?php echo htmlspecialchars($person['hoten']); ?>"
                           class="person-name-input w-full px-2 py-1 text-sm border rounded focus:outline-none focus:ring focus:border-indigo-500"
                           onblur="validatePersonNameStrict(this)"
                           autocomplete="off"
                           form="repair-form">
                    <span class="warning-icon absolute right-2 top-1/2 transform -translate-y-1/2 text-red-500" 
                          style="display:none;" 
                          title="❌ Tên không hợp lệ">
                        <i class="fas fa-times-circle"></i>
                    </span>
                </div>
                <div class="w-24">
                    <input type="number" name="nguoi_gio[<?php echo $idx; ?>]" placeholder="Giờ" 
                           value="<?php echo htmlspecialchars($person['giolv']); ?>"
                           step="0.5" min="0"
                           class="w-full px-2 py-1 text-sm border rounded focus:outline-none focus:ring focus:border-indigo-500"
                           form="repair-form">
                </div>
                <button type="button" onclick="removePersonRow(this)" class="text-red-600 hover:text-red-800 px-2 py-1" title="Xóa">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <?php endfor; ?>
        </div>
    </div>

    <script>
    let personIndex = <?php echo $displayCount; ?>;
    
    // Danh sách người thực hiện hợp lệ (chỉ chấp nhận tên trong danh sách này)
    const validPersonList = <?php echo json_encode($nguoiThucHienAutocomplete); ?>;
    
    function validatePersonNameStrict(input) {
        const value = input.value.trim();
        const row = input.closest('.person-row');
        const warningIcon = row.querySelector('.warning-icon');
        
        if (value === '') {
            // Tên rỗng - OK, bỏ cảnh báo
            input.classList.remove('border-red-500', 'border-2', 'bg-red-50');
            if (warningIcon) warningIcon.style.display = 'none';
            return;
        }
        
        // Kiểm tra tên CÓ trong danh sách không
        const isValid = validPersonList.includes(value);
        
        if (!isValid) {
            // Tên KHÔNG hợp lệ - BẮT BUỘC xóa
            input.classList.add('border-red-500', 'border-2', 'bg-red-50');
            if (warningIcon) warningIcon.style.display = 'block';
            
            // Thông báo lỗi
            alert(`❌ TÊN KHÔNG HỢP LỆ\n\nTên "${value}" không có trong danh sách nhân viên.\n\nVui lòng chọn tên từ danh sách gợi ý.`);
            
            // Xóa tên và focus lại
            input.value = '';
            input.classList.remove('border-red-500', 'border-2', 'bg-red-50');
            if (warningIcon) warningIcon.style.display = 'none';
            input.focus();
        } else {
            // Tên hợp lệ - bỏ cảnh báo
            input.classList.remove('border-red-500', 'border-2', 'bg-red-50');
            if (warningIcon) warningIcon.style.display = 'none';
        }
    }
    
    function validateFormBeforeSubmit() {
        // Kiểm tra tất cả tên người thực hiện trước khi submit
        const personInputs = document.querySelectorAll('.person-name-input');
        const invalidNames = [];
        
        personInputs.forEach((input, index) => {
            const value = input.value.trim();
            if (value !== '' && !validPersonList.includes(value)) {
                invalidNames.push({
                    index: index + 1,
                    name: value
                });
            }
        });
        
        if (invalidNames.length > 0) {
            let errorMsg = '❌ KHÔNG THỂ LƯU\n\nCác tên sau KHÔNG có trong danh sách nhân viên:\n\n';
            invalidNames.forEach(item => {
                errorMsg += `• Người ${item.index}: "${item.name}"\n`;
            });
            errorMsg += '\nVui lòng chọn tên từ danh sách hoặc xóa các tên không hợp lệ.';
            
            alert(errorMsg);
            
            // Focus vào input đầu tiên bị lỗi
            if (personInputs[invalidNames[0].index - 1]) {
                personInputs[invalidNames[0].index - 1].focus();
                personInputs[invalidNames[0].index - 1].classList.add('border-red-500', 'border-2', 'bg-red-50');
            }
            
            return false; // Ngăn submit
        }
        
        return true; // Cho phép submit
    }
    
    function addPersonRow() {
        if (personIndex >= 8) {
            alert('Tối đa 8 người thực hiện!');
            return;
        }
        
        const container = document.getElementById('personList');
        const row = document.createElement('div');
        row.className = 'person-row flex gap-2 items-start bg-indigo-50 p-2 rounded';
        row.innerHTML = `
            <div class="flex-1 relative">
                <input type="text" 
                       name="nguoi_hoten[${personIndex}]" 
                       list="nguoiThList" 
                       placeholder="Chọn từ danh sách..." 
                       class="person-name-input w-full px-2 py-1 text-sm border rounded focus:outline-none focus:ring focus:border-indigo-500"
                       onblur="validatePersonNameStrict(this)"
                       autocomplete="off"
                       form="repair-form">
                <span class="warning-icon absolute right-2 top-1/2 transform -translate-y-1/2 text-red-500" 
                      style="display:none;" 
                      title="❌ Tên không hợp lệ">
                    <i class="fas fa-times-circle"></i>
                </span>
            </div>
            <div class="w-24">
                <input type="number" name="nguoi_gio[${personIndex}]" placeholder="Giờ" 
                       step="0.5" min="0"
                       class="w-full px-2 py-1 text-sm border rounded focus:outline-none focus:ring focus:border-indigo-500"
                       form="repair-form">
            </div>
            <button type="button" onclick="removePersonRow(this)" class="text-red-600 hover:text-red-800 px-2 py-1" title="Xóa">
                <i class="fas fa-times"></i>
            </button>
        `;
        container.appendChild(row);
        personIndex++;
    }
    
    function removePersonRow(button) {
        const row = button.closest('.person-row');
        const personRows = document.querySelectorAll('.person-row');
        
        if (personRows.length > 1) {
            row.remove();
            // Re-index remaining rows
            const remainingRows = document.querySelectorAll('.person-row');
            remainingRows.forEach((r, idx) => {
                const hotenInput = r.querySelector('input[name^="nguoi_hoten"]');
                const gioInput = r.querySelector('input[name^="nguoi_gio"]');
                if (hotenInput) hotenInput.name = `nguoi_hoten[${idx}]`;
                if (gioInput) gioInput.name = `nguoi_gio[${idx}]`;
            });
            personIndex = remainingRows.length;
        } else {
            // Clear inputs and reset state
            const nameInput = row.querySelector('.person-name-input');
            const gioInput = row.querySelector('input[type="number"]');
            const warningIcon = row.querySelector('.warning-icon');
            
            if (nameInput) {
                nameInput.value = '';
                nameInput.classList.remove('border-red-500', 'border-2', 'bg-red-50');
            }
            if (gioInput) gioInput.value = '';
            if (warningIcon) warningIcon.style.display = 'none';
        }
    }
    </script>

    <form method="POST" class="space-y-6" id="repair-form" onsubmit="return validateFormBeforeSubmit()">
        <!-- Hidden inputs to preserve filters -->
        <?php foreach ($filterParams as $key => $value): ?>
            <input type="hidden" name="filter_<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
        <?php endforeach; ?>
        
        <!-- Thông tin sửa chữa -->
        <div class="border-l-4 border-orange-500 pl-4">
            <h2 class="text-lg font-bold mb-3 text-orange-700">
                <i class="fas fa-wrench mr-2"></i>Thông tin sửa chữa
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Loại công việc <span class="text-red-500">*</span></label>
                    <select name="cv" required class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                        <option value="SC" <?php echo ($item['cv'] ?? 'SC') === 'SC' ? 'selected' : ''; ?>>SC - Sửa chữa</option>
                        <option value="BD" <?php echo ($item['cv'] ?? '') === 'BD' ? 'selected' : ''; ?>>BD - Bảo dưỡng</option>
                        <option value="KT" <?php echo ($item['cv'] ?? '') === 'KT' ? 'selected' : ''; ?>>KT - Kiểm tra</option>
                        <option value="BDDK" <?php echo ($item['cv'] ?? '') === 'BDDK' ? 'selected' : ''; ?>>BDDK - Bảo dưỡng định kỳ</option>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Nhóm SC <span class="text-red-500">*</span></label>
                    <select name="nhomsc" required class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                        <?php $currentNhomsc = $item['nhomsc'] ?? getUserNhom(); ?>
                        <option value="RDNGA" <?php echo ($currentNhomsc === 'RDNGA') ? 'selected' : ''; ?>>RDNGA</option>
                        <option value="CNC" <?php echo ($currentNhomsc === 'CNC') ? 'selected' : ''; ?>>CNC</option>
                    </select>
                </div>
                <div class="hidden">
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
                    <label class="block text-gray-700 font-semibold mb-2">Ngày kết thúc</label>
                    <input type="date" name="ngaykt" value="<?php echo $item['ngaykt']; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div style="display: none;">
                    <input type="hidden" name="solg" value="<?php echo $item['solg']; ?>">
                </div>
                <div class="md:col-span-3" style="display: none;">
                    <label class="block text-gray-700 font-semibold mb-2">Tình trạng kỹ thuật trước khi SC/BĐ</label>
                    <textarea name="ttktbefore" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo displayText($item['ttktbefore']); ?></textarea>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Hỏng hóc (trước khi sửa chữa)</label>
                    <textarea name="honghoc" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo displayText($item['honghoc']); ?></textarea>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Khắc phục hỏng hóc</label>
                    <textarea name="khacphuc" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo displayText($item['khacphuc']); ?></textarea>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Nội dung sửa chữa</label>
                    <textarea name="noidung" rows="4" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo displayText($item['noidung']); ?></textarea>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Tình trạng kỹ thuật sau khi SC/BĐ</label>
                    <select name="ttktafter" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                        <option value="">-- Chọn trạng thái --</option>
                        <option value="Đạt" <?php echo ($item['ttktafter'] ?? '') === 'Đạt' ? 'selected' : ''; ?>>Đạt</option>
                        <option value="Hỏng" <?php echo ($item['ttktafter'] ?? '') === 'Hỏng' ? 'selected' : ''; ?>>Hỏng (Không khắc phục được)</option>
                        <option value="Chờ vật tư thay thế" <?php echo ($item['ttktafter'] ?? '') === 'Chờ vật tư thay thế' ? 'selected' : ''; ?>>Chờ vật tư thay thế</option>
                        <option value="Chưa kết luận" <?php echo ($item['ttktafter'] ?? '') === 'Chưa kết luận' ? 'selected' : ''; ?>>Chưa kết luận</option>
                        <option value="Đang sửa chữa" <?php echo ($item['ttktafter'] ?? '') === 'Đang sửa chữa' ? 'selected' : ''; ?>>Đang sửa chữa</option>
                        <option value="TTKTDB" <?php echo ($item['ttktafter'] ?? '') === 'TTKTDB' ? 'selected' : ''; ?>>TTKT Đặc biệt</option>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Kết luận</label>
                    <textarea name="ketluan" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo displayText($item['ketluan']); ?></textarea>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Xem xét xưởng</label>
                    <textarea name="xemxetxuong" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo displayText($item['xemxetxuong']); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Thiết bị đo SC -->
        <div class="border-l-4 border-teal-500 pl-4">
            <div class="flex justify-between items-center mb-3">
                <h2 class="text-lg font-bold text-teal-700">
                    <i class="fas fa-tools mr-2"></i>Thiết bị hỗ trợ
                </h2>
                <button type="button" onclick="addDeviceRow()" class="bg-teal-600 hover:bg-teal-700 text-white px-3 py-1 rounded text-sm">
                    <i class="fas fa-plus mr-1"></i> Thêm thiết bị
                </button>
            </div>
            <div id="deviceList" class="space-y-2">
                <?php 
                // Collect existing devices
                $devices = [];
                for ($i = 0; $i <= 4; $i++) {
                    $tbField = $i == 0 ? 'tbdosc' : "tbdosc$i";
                    $serialField = $i == 0 ? 'serialtbdosc' : "serialtbdosc$i";
                    if (!empty($item[$tbField]) || !empty($item[$serialField])) {
                        $devices[] = [
                            'tb' => $item[$tbField],
                            'serial' => $item[$serialField],
                            'tbField' => $tbField,
                            'serialField' => $serialField
                        ];
                    }
                }
                // If no devices, show at least one empty row
                if (empty($devices)) {
                    $devices[] = ['tb' => '', 'serial' => '', 'tbField' => 'tbdosc', 'serialField' => 'serialtbdosc'];
                }
                
                foreach ($devices as $idx => $device): 
                ?>
                <div class="device-row flex gap-2 items-start bg-teal-50 p-2 rounded">
                    <div class="flex-1">
                        <input type="text" name="<?php echo $device['tbField']; ?>" list="tbhtList" placeholder="Tên thiết bị hỗ trợ" 
                               value="<?php echo htmlspecialchars($device['tb']); ?>"
                               class="w-full px-2 py-1 text-sm border rounded focus:outline-none focus:ring focus:border-teal-500"
                               onchange="fillSerial(this)" oninput="fillSerial(this)">
                    </div>
                    <div class="flex-1">
                        <input type="text" name="<?php echo $device['serialField']; ?>" placeholder="Serial/Mã số" 
                               value="<?php echo htmlspecialchars($device['serial']); ?>"
                               class="w-full px-2 py-1 text-sm border rounded focus:outline-none focus:ring focus:border-teal-500">
                    </div>
                    <button type="button" onclick="removeDeviceRow(this)" class="text-red-600 hover:text-red-800 px-2 py-1" title="Xóa">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Datalist for thiết bị hỗ trợ -->
        <datalist id="tbhtList">
            <?php foreach ($thietBiHoTroList as $tb): ?>
                <option value="<?php echo htmlspecialchars($tb['tenthietbi']); ?>" 
                        data-serial="<?php echo htmlspecialchars($tb['serialnumber']); ?>"
                        data-tenvt="<?php echo htmlspecialchars($tb['tenvt']); ?>"
                        data-chusohuu="<?php echo htmlspecialchars($tb['chusohuu']); ?>">
                    <?php echo htmlspecialchars($tb['tenthietbi'] . ' - ' . $tb['serialnumber'] . (!empty($tb['chusohuu']) ? ' (' . $tb['chusohuu'] . ')' : '')); ?>
                </option>
            <?php endforeach; ?>
        </datalist>

        <!-- Datalist for người thực hiện -->
        <datalist id="nguoiThList">
            <?php foreach ($nguoiThucHienAutocomplete as $tenNguoi): ?>
                <option value="<?php echo htmlspecialchars($tenNguoi); ?>">
            <?php endforeach; ?>
        </datalist>

        <script>
        let deviceIndex = <?php echo count($devices); ?>;
        
        // Auto-fill serial when device is selected
        function fillSerial(input) {
            const selectedValue = input.value;
            const datalist = document.getElementById('tbhtList');
            const options = datalist.querySelectorAll('option');
            const serialInput = input.closest('.device-row').querySelector('input[name*="serial"]');
            
            let found = false;
            for (let option of options) {
                if (option.value === selectedValue) {
                    if (serialInput && option.dataset.serial) {
                        serialInput.value = option.dataset.serial;
                        serialInput.readOnly = true;
                        serialInput.classList.add('bg-gray-100', 'cursor-not-allowed');
                    }
                    found = true;
                    break;
                }
            }
            
            // Nếu không tìm thấy trong datalist hoặc ô bị xóa trống, mở khóa
            if (!found || !selectedValue) {
                if (serialInput) {
                    serialInput.readOnly = false;
                    serialInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
                }
            }
        }
        
        function addDeviceRow() {
            const container = document.getElementById('deviceList');
            const fieldName = deviceIndex === 0 ? 'tbdosc' : `tbdosc${deviceIndex}`;
            const serialName = deviceIndex === 0 ? 'serialtbdosc' : `serialtbdosc${deviceIndex}`;
            
            const row = document.createElement('div');
            row.className = 'device-row flex gap-2 items-start bg-teal-50 p-2 rounded';
            row.innerHTML = `
                <div class="flex-1">
                    <input type="text" name="${fieldName}" list="tbhtList" placeholder="Tên thiết bị hỗ trợ" 
                           class="w-full px-2 py-1 text-sm border rounded focus:outline-none focus:ring focus:border-teal-500"
                           onchange="fillSerial(this)" oninput="fillSerial(this)">
                </div>
                <div class="flex-1">
                    <input type="text" name="${serialName}" placeholder="Serial/Mã số" 
                           class="w-full px-2 py-1 text-sm border rounded focus:outline-none focus:ring focus:border-teal-500">
                </div>
                <button type="button" onclick="removeDeviceRow(this)" class="text-red-600 hover:text-red-800 px-2 py-1" title="Xóa">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(row);
            deviceIndex++;
        }
        
        function removeDeviceRow(button) {
            const row = button.closest('.device-row');
            if (document.querySelectorAll('.device-row').length > 1) {
                row.remove();
            } else {
                alert('Phải có ít nhất 1 dòng thiết bị');
            }
        }
        
        // Khóa các ô serial đã có khi load trang
        document.addEventListener('DOMContentLoaded', function() {
            const deviceInputs = document.querySelectorAll('input[list="tbhtList"]');
            deviceInputs.forEach(input => {
                if (input.value) {
                    fillSerial(input);
                }
            });
        });
        </script>

        <!-- Buttons -->
        <div class="flex flex-col md:flex-row gap-2 pt-4 border-t">
            <button type="submit" class="<?php echo $needsApproval ? 'bg-yellow-500 hover:bg-yellow-600' : 'bg-green-600 hover:bg-green-700'; ?> text-white px-6 py-3 rounded text-base font-semibold w-full md:w-auto">
                <?php if ($needsApproval): ?>
                    <i class="fas fa-paper-plane mr-2"></i> Gửi yêu cầu sửa (chờ admin duyệt)
                <?php else: ?>
                    <i class="fas fa-save mr-2"></i> Lưu thông tin
                <?php endif; ?>
            </button>
            <a href="<?php echo htmlspecialchars($backUrl); ?>" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded text-base font-semibold text-center w-full md:w-auto">
                <i class="fas fa-arrow-left mr-2"></i> Quay lại danh sách
            </a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
