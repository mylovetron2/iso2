<?php
declare(strict_types=1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/ActivityLogger.php';

requireAuth();

// Check permissions
if (!hasPermission('thietbi.view')) {
    header('Location: /iso2/index.php?error=no_permission');
    exit;
}

$db = getDBConnection();
$logger = new ActivityLogger($db);
$success = '';
$error = '';

// Xử lý AJAX - Lưu từng thiết bị
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_save'])) {
    header('Content-Type: application/json');
    
    try {
        $stt = $_POST['stt'] ?? '';
        $selectedMonth = $_POST['thang_thuchien'] ?? '';
        $donviThuchien = $_POST['donvi_thuchien'] ?? '';
        
        if (empty($stt)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu STT thiết bị']);
            exit;
        }
        
        // Lấy thông tin thiết bị từ master table
        $stmtTB = $db->prepare("SELECT * FROM thietbihckd_iso WHERE stt = :stt LIMIT 1");
        $stmtTB->execute([':stt' => $stt]);
        $thietbi = $stmtTB->fetch(PDO::FETCH_ASSOC);
        
        if (!$thietbi) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy thiết bị']);
            exit;
        }
        
        $db->beginTransaction();
        
        // Xóa kế hoạch cũ của thiết bị này trong năm 2026 (join qua stt)
        $stmtDelete = $db->prepare("DELETE FROM kehoach_kiemdinh_2026_iso WHERE stt = :stt AND nam_kehoach = 2026");
        $stmtDelete->execute([':stt' => $stt]);
        
        // Nếu có chọn tháng, thêm kế hoạch mới
        if (!empty($selectedMonth)) {
            $stmt = $db->prepare("
                INSERT INTO kehoach_kiemdinh_2026_iso 
                (stt, ten_thietbi, ky_hieu, hang_sanxuat, so_may, thang_thuchien, donvi_thuchien, ghichu, nam_kehoach)
                VALUES (:stt, :ten_thietbi, :ky_hieu, :hang_sanxuat, :so_may, :thang_thuchien, :donvi_thuchien, :ghichu, 2026)
            ");
            
            $stmt->execute([
                ':stt' => $stt,
                ':ten_thietbi' => $thietbi['tenthietbi'],
                ':ky_hieu' => $thietbi['tenviettat'] ?? '',
                ':hang_sanxuat' => $thietbi['hangsx'] ?? '',
                ':so_may' => $thietbi['somay'] ?? '',
                ':thang_thuchien' => (int)$selectedMonth,
                ':donvi_thuchien' => $donviThuchien,
                ':ghichu' => 'Mã vật tư: ' . ($thietbi['mavattu'] ?? '') . ' - Chủ sở hữu: ' . ($thietbi['chusohuu'] ?? '')
            ]);
            
            // Log activity
            $logger->log(
                'kehoach_kiemdinh_2026_iso',
                'INSERT',
                null,
                null,
                [
                    'stt' => $stt,
                    'ten_thietbi' => $thietbi['tenthietbi'],
                    'mavattu' => $thietbi['mavattu'] ?? '',
                    'thang_thuchien' => (int)$selectedMonth,
                    'nam_kehoach' => 2026
                ]
            );
        }
        
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Đã lưu thành công!']);
        
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
    exit;
}

// Xử lý POST - Lưu kế hoạch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_plan'])) {
    try {
        $db->beginTransaction();
        
        // Xóa kế hoạch cũ của năm 2026 (tùy chọn)
        if (isset($_POST['clear_old'])) {
            $db->exec("DELETE FROM kehoach_kiemdinh_2026_iso WHERE nam_kehoach = 2026");
        }
        
        $stmt = $db->prepare("
            INSERT INTO kehoach_kiemdinh_2026_iso 
            (stt, ten_thietbi, ky_hieu, hang_sanxuat, so_may, thang_thuchien, donvi_thuchien, ghichu, nam_kehoach)
            VALUES (:stt, :ten_thietbi, :ky_hieu, :hang_sanxuat, :so_may, :thang_thuchien, :donvi_thuchien, :ghichu, 2026)
        ");
        
        $count = 0;
        foreach ($_POST['thietbi'] ?? [] as $stt => $selectedMonth) {
            // Lấy thông tin thiết bị từ master table (join qua stt)
            $stmtTB = $db->prepare("SELECT * FROM thietbihckd_iso WHERE stt = :stt LIMIT 1");
            $stmtTB->execute([':stt' => $stt]);
            $thietbi = $stmtTB->fetch(PDO::FETCH_ASSOC);
            
            if (!$thietbi || empty($selectedMonth)) continue;
            
            // Lưu tháng được chọn
            $stmt->execute([
                ':stt' => $stt,
                ':ten_thietbi' => $thietbi['tenthietbi'],
                ':ky_hieu' => $thietbi['tenviettat'] ?? '',
                ':hang_sanxuat' => $thietbi['hangsx'] ?? '',
                ':so_may' => $thietbi['somay'] ?? '',
                ':thang_thuchien' => (int)$selectedMonth,
                ':donvi_thuchien' => $_POST['donvi_thuchien'][$stt] ?? '',
                ':ghichu' => 'Mã vật tư: ' . ($thietbi['mavattu'] ?? '') . ' - Chủ sở hữu: ' . ($thietbi['chusohuu'] ?? '')
            ]);
            
            // Log activity
            $logger->log(
                'kehoach_kiemdinh_2026_iso',
                'INSERT',
                null,
                null,
                [
                    'stt' => $stt,
                    'ten_thietbi' => $thietbi['tenthietbi'],
                    'mavattu' => $thietbi['mavattu'] ?? '',
                    'thang_thuchien' => (int)$selectedMonth,
                    'nam_kehoach' => 2026
                ]
            );
            
            $count++;
        }
        
        $db->commit();
        $success = "Đã lưu thành công {$count} kế hoạch kiểm định năm 2026!";
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Lỗi khi lưu dữ liệu: " . $e->getMessage();
        error_log("Error saving plan: " . $e->getMessage());
    }
}

// Lấy danh sách thiết bị
$search = $_GET['search'] ?? '';
$loaitb = $_GET['loaitb'] ?? '';
$bophansh = $_GET['bophansh'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

$where = ['1=1'];
$params = [];

if ($search) {
    $where[] = "(mavattu LIKE :search OR tenthietbi LIKE :search2 OR somay LIKE :search3)";
    $params[':search'] = "%$search%";
    $params[':search2'] = "%$search%";
    $params[':search3'] = "%$search%";
}

if ($loaitb) {
    $where[] = "loaitb = :loaitb";
    $params[':loaitb'] = $loaitb;
}

if ($bophansh) {
    $where[] = "bophansh = :bophansh";
    $params[':bophansh'] = $bophansh;
}

$whereClause = implode(' AND ', $where);

// Đếm tổng số thiết bị
$countSql = "SELECT COUNT(DISTINCT t.stt) as total
             FROM thietbihckd_iso t
             WHERE $whereClause";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRecords / $limit);

// Nếu có search thì không phân trang (hiển thị tất cả)
$limitClause = $search ? '' : "LIMIT $limit OFFSET $offset";

$sql = "SELECT t.*, 
        GROUP_CONCAT(DISTINCT k.thang_thuchien ORDER BY k.thang_thuchien) as planned_months
        FROM thietbihckd_iso t
        LEFT JOIN kehoach_kiemdinh_2026_iso k ON t.stt = k.stt AND k.nam_kehoach = 2026
        WHERE $whereClause
        GROUP BY t.stt
        ORDER BY t.loaitb, t.tenthietbi
        $limitClause";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$thietbiList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách loại TB và bộ phận
$loaiTBList = $db->query("SELECT DISTINCT loaitb FROM thietbihckd_iso WHERE loaitb != '' ORDER BY loaitb")->fetchAll(PDO::FETCH_COLUMN);
$boPhanList = $db->query("SELECT DISTINCT bophansh FROM thietbihckd_iso WHERE bophansh != '' ORDER BY bophansh")->fetchAll(PDO::FETCH_COLUMN);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kế hoạch Kiểm định Thiết bị 2026</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 100%; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 20px; font-size: 24px; }
        .alert { padding: 12px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .filters { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .filters input, .filters select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .filters button { padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .filters button:hover { background: #0056b3; }
        .table-wrapper { overflow-x: auto; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; min-width: 1400px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 13px; }
        th { background: #4CAF50; color: white; position: sticky; top: 0; z-index: 10; }
        .month-cell { text-align: center; width: 50px; cursor: pointer; user-select: none; }
        .month-header { text-align: center; background: #2196F3; }
        .month-selected { background: #4CAF50 !important; }
        input[type="checkbox"] { cursor: pointer; transform: scale(1.2); }
        input[type="radio"] { opacity: 0; position: absolute; pointer-events: none; }
        .btn-save-single { 
            padding: 6px 12px; 
            background: #28a745; 
            color: white; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            font-size: 16px;
            transition: background 0.3s;
        }
        .btn-save-single:hover { background: #218838; }
        .btn-save-single:disabled { background: #6c757d; cursor: not-allowed; }
        input[type="text"].small { width: 150px; padding: 4px; font-size: 12px; }
        .btn-save { padding: 12px 24px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; }
        .btn-save:hover { background: #218838; }
        .back-link { display: inline-block; margin-bottom: 15px; color: #007bff; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
        tr:nth-child(even) { background: #f9f9f9; }
        tr:hover { background: #f0f0f0; }
        .selected { background: #e3f2fd !important; }
        .action-buttons { margin-top: 20px; display: flex; gap: 10px; }
        .checkbox-label { display: flex; align-items: center; gap: 5px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="/iso2/thietbihckd.php" class="back-link">← Quay lại danh sách thiết bị</a>
        
        <h1>� Kế hoạch Kiểm định Thiết bị Năm 2026</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">✗ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- Bộ lọc -->
        <form method="GET" class="filters">
            <input type="text" name="search" placeholder="Tìm kiếm thiết bị, số máy..." value="<?= htmlspecialchars($search) ?>">
            
            <select name="loaitb">
                <option value="">-- Tất cả loại TB --</option>
                <?php foreach ($loaiTBList as $loai): ?>
                    <option value="<?= htmlspecialchars($loai) ?>" <?= $loaitb === $loai ? 'selected' : '' ?>>
                        <?= htmlspecialchars($loai) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="bophansh">
                <option value="">-- Tất cả bộ phận --</option>
                <?php foreach ($boPhanList as $bp): ?>
                    <option value="<?= htmlspecialchars($bp) ?>" <?= $bophansh === $bp ? 'selected' : '' ?>>
                        <?= htmlspecialchars($bp) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit">Lọc</button>
            <a href="kehoach_thietbi_2026.php" style="padding: 8px 16px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px;">Reset</a>
        </form>
        
        <!-- Form nhập kế hoạch -->
        <form method="POST">
            <label class="checkbox-label">
                <input type="checkbox" name="clear_old" value="1">
                <span>Xóa kế hoạch cũ năm 2026 trước khi lưu</span>
            </label>
            
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th rowspan="2" style="width: 40px;">STT</th>
                            <th rowspan="2" style="min-width: 200px;">Tên thiết bị, mẫu chuẩn/Vật chuẩn</th>
                            <th rowspan="2" style="width: 100px;">Ký/Mã hiệu</th>
                            <th rowspan="2" style="width: 100px;">Số máy</th>
                            <th rowspan="2" style="width: 80px;">Nước/Hãng SX</th>
                            <th rowspan="2" style="min-width: 150px;">Nơi thực hiện</th>
                            <th colspan="12" class="month-header">THÁNG</th>
                            <th rowspan="2" style="width: 100px;">Chủ sở hữu</th>
                            <th rowspan="2" style="width: 80px;">Lưu</th>
                        </tr>
                        <tr>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <th class="month-header"><?= $i ?></th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($thietbiList)): ?>
                            <tr>
                                <td colspan="19" style="text-align: center; padding: 20px;">
                                    Không có thiết bị nào. Vui lòng thêm thiết bị vào hệ thống trước.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $displaySTT = 1;
                            foreach ($thietbiList as $tb): 
                                $plannedMonths = $tb['planned_months'] ? explode(',', $tb['planned_months']) : [];
                            ?>
                            <tr>
                                <td><?= $displaySTT++ ?></td>
                                <td><?= htmlspecialchars($tb['tenthietbi']) ?></td>
                                <td><?= htmlspecialchars($tb['tenviettat'] ?? '') ?></td>
                                <td><?= htmlspecialchars($tb['somay'] ?? '') ?></td>
                                <td><?= htmlspecialchars($tb['hangsx'] ?? '') ?></td>
                                <td>
                                    <input type="text" 
                                           name="donvi_thuchien[<?= (int)$tb['stt'] ?>]" 
                                           class="small" 
                                           placeholder="Nơi thực hiện"
                                           value="">
                                </td>
                                <?php for ($month = 1; $month <= 12; $month++): ?>
                                    <td class="month-cell">
                                        <input type="radio" 
                                               name="thietbi[<?= (int)$tb['stt'] ?>]" 
                                               value="<?= $month ?>"
                                               <?= in_array((string)$month, $plannedMonths) ? 'checked' : '' ?>>
                                    </td>
                                <?php endfor; ?>
                                <td><?= htmlspecialchars($tb['chusohuu'] ?? '') ?></td>
                                <td style="text-align: center;">
                                    <button type="button" 
                                            class="btn-save-single" 
                                            data-stt="<?= (int)$tb['stt'] ?>"
                                            onclick="saveSingleEquipment(this)">
                                        💾
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="action-buttons">
                <button type="submit" name="save_plan" class="btn-save">💾 Lưu Kế hoạch 2026</button>
                <a href="/iso2/thietbihckd.php" style="padding: 12px 24px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px;">Hủy</a>
            </div>
        </form>
        
        <div style="margin-top: 20px; padding: 15px; background: #e7f3fe; border-left: 4px solid #2196F3;">
            <strong>Tổng số thiết bị: <?= $totalRecords ?></strong>
            <?php if (!$search && $totalPages > 1): ?>
                (Hiển thị: <?= count($thietbiList) ?> - Trang <?= $page ?>/<?= $totalPages ?>)
            <?php else: ?>
                (Hiển thị: <?= count($thietbiList) ?>)
            <?php endif; ?>
        </div>
        
        <?php if (!$search && $totalPages > 1): ?>
        <div style="margin-top: 20px; padding: 15px; text-align: center;">
            <div style="display: inline-flex; gap: 5px; align-items: center;">
                <?php if ($page > 1): ?>
                    <a href="?page=1<?= $loaitb ? '&loaitb='.urlencode($loaitb) : '' ?><?= $bophansh ? '&bophansh='.urlencode($bophansh) : '' ?>" 
                       style="padding: 8px 12px; background: #007bff; color: white; text-decoration: none; border-radius: 4px;">« Đầu</a>
                    <a href="?page=<?= $page - 1 ?><?= $loaitb ? '&loaitb='.urlencode($loaitb) : '' ?><?= $bophansh ? '&bophansh='.urlencode($bophansh) : '' ?>" 
                       style="padding: 8px 12px; background: #007bff; color: white; text-decoration: none; border-radius: 4px;">‹ Trước</a>
                <?php endif; ?>
                
                <span style="padding: 8px 16px; background: #e9ecef; border-radius: 4px; font-weight: bold;">
                    Trang <?= $page ?> / <?= $totalPages ?>
                </span>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?><?= $loaitb ? '&loaitb='.urlencode($loaitb) : '' ?><?= $bophansh ? '&bophansh='.urlencode($bophansh) : '' ?>" 
                       style="padding: 8px 12px; background: #007bff; color: white; text-decoration: none; border-radius: 4px;">Sau ›</a>
                    <a href="?page=<?= $totalPages ?><?= $loaitb ? '&loaitb='.urlencode($loaitb) : '' ?><?= $bophansh ? '&bophansh='.urlencode($bophansh) : '' ?>" 
                       style="padding: 8px 12px; background: #007bff; color: white; text-decoration: none; border-radius: 4px;">Cuối »</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Save single equipment via AJAX
        function saveSingleEquipment(button) {
            const stt = button.getAttribute('data-stt');
            const row = button.closest('tr');
            const donviInput = row.querySelector('input[name="donvi_thuchien[' + stt + ']"]');
            const selectedRadio = row.querySelector('input[name="thietbi[' + stt + ']"]:checked');
            
            const donviThuchien = donviInput ? donviInput.value : '';
            const thangThuchien = selectedRadio ? selectedRadio.value : '';
            
            // Disable button
            button.disabled = true;
            const originalText = button.innerHTML;
            button.innerHTML = '⏳';
            
            // Send AJAX request
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'ajax_save=1&stt=' + encodeURIComponent(stt) + 
                      '&thang_thuchien=' + encodeURIComponent(thangThuchien) + 
                      '&donvi_thuchien=' + encodeURIComponent(donviThuchien)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    button.innerHTML = '✓';
                    button.style.background = '#28a745';
                    setTimeout(() => {
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }, 1500);
                    
                    // Optional: Show toast notification
                    showToast(data.message, 'success');
                } else {
                    button.innerHTML = '✗';
                    button.style.background = '#dc3545';
                    alert('Lỗi: ' + data.message);
                    setTimeout(() => {
                        button.innerHTML = originalText;
                        button.disabled = false;
                        button.style.background = '#28a745';
                    }, 2000);
                }
            })
            .catch(error => {
                button.innerHTML = '✗';
                button.style.background = '#dc3545';
                alert('Lỗi kết nối: ' + error);
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                    button.style.background = '#28a745';
                }, 2000);
            });
        }
        
        // Simple toast notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#28a745' : '#dc3545'};
                color: white;
                padding: 15px 20px;
                border-radius: 4px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                z-index: 9999;
                animation: slideIn 0.3s ease-out;
            `;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        // Allow clicking on cell to select/deselect month
        let lastChecked = {};
        
        // Initialize - highlight already selected cells
        document.querySelectorAll('input[type="radio"]').forEach(radio => {
            const name = radio.name;
            if (radio.checked) {
                lastChecked[name] = radio;
                radio.closest('td').classList.add('month-selected');
                radio.closest('tr').classList.add('selected');
            }
        });
        
        // Handle cell clicks
        document.querySelectorAll('.month-cell').forEach(cell => {
            cell.addEventListener('click', function() {
                const radio = this.querySelector('input[type="radio"]');
                if (!radio) return;
                
                const name = radio.name;
                const row = this.closest('tr');
                
                // If clicking the same cell, uncheck it
                if (lastChecked[name] === radio) {
                    radio.checked = false;
                    this.classList.remove('month-selected');
                    lastChecked[name] = null;
                    row.classList.remove('selected');
                } else {
                    // Remove highlight from previous selection
                    if (lastChecked[name]) {
                        lastChecked[name].closest('td').classList.remove('month-selected');
                    }
                    
                    // Highlight new selection
                    radio.checked = true;
                    this.classList.add('month-selected');
                    lastChecked[name] = radio;
                    row.classList.add('selected');
                }
            });
        });
    </script>
</body>
</html>
