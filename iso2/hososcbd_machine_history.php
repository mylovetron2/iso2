<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/includes/permissions.php';

requireAuth();
requirePermission('hososcbd.view');

require_once __DIR__ . '/models/HoSoSCBD.php';
require_once __DIR__ . '/includes/functions.php';

$stt = max(0, (int)($_GET['id'] ?? 0));
$model = new HoSoSCBD();
$item = $stt > 0 ? $model->findById($stt) : false;

if (empty($_SESSION['machine_history_token'])) {
    $_SESSION['machine_history_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hasPermission('hososcbd.edit')) {
        http_response_code(403);
        exit('Bạn không có quyền sửa nội dung kiểm tra.');
    }

    $token = $_POST['machine_history_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['machine_history_token'], $token)) {
        http_response_code(403);
        exit('Phiên xác nhận không hợp lệ. Vui lòng tải lại trang.');
    }

    $contents = $_POST['noidung'] ?? [];
    if (!is_array($contents)) {
        http_response_code(400);
        exit('Dữ liệu nội dung kiểm tra không hợp lệ.');
    }

    $savedCount = 0;
    foreach ($contents as $recordIdValue => $content) {
        $recordId = (int)$recordIdValue;
        if ($recordId <= 0 || !is_string($content)) {
            continue;
        }
        if (!$model->findById($recordId)) {
            continue;
        }
        $model->update($recordId, ['noidung' => trim($content)]);
        $savedCount++;
    }

    $redirectParams = $_GET;
    unset($redirectParams['history_saved']);
    $redirectParams['history_saved'] = (string)$savedCount;
    $scrollTop = (int)($_POST['scroll_top'] ?? 0);
    if ($scrollTop > 0) {
        $redirectParams['scroll_top'] = $scrollTop;
    }
    header('Location: hososcbd_machine_history.php?' . http_build_query($redirectParams));
    exit;
}

$historyPage = max(1, (int)($_GET['history_page'] ?? 1));
$limit = 20;
$offset = ($historyPage - 1) * $limit;
$ngayThFrom = trim((string)($_GET['ngayth_from'] ?? ''));
$ngayThTo = trim((string)($_GET['ngayth_to'] ?? ''));
$ngayThFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', $ngayThFrom) ? $ngayThFrom : '';
$ngayThTo = preg_match('/^\d{4}-\d{2}-\d{2}$/', $ngayThTo) ? $ngayThTo : '';

$historyWhere = 'WHERE 1=1';
$historyParams = [];
$historyCountWhere = $historyWhere;
if ($ngayThFrom !== '') {
    $historyWhere .= ' AND ngayth >= ?';
    $historyCountWhere .= ' AND ngayth >= ?';
    $historyParams[] = $ngayThFrom;
}
if ($ngayThTo !== '') {
    $historyWhere .= ' AND ngayth <= ?';
    $historyCountWhere .= ' AND ngayth <= ?';
    $historyParams[] = $ngayThTo;
}
$historyWhere .= ' ORDER BY ngayth DESC, stt DESC';

$historyItems = $model->getAll($historyWhere, $historyParams, $limit, $offset);
$historyTotal = $model->count($historyCountWhere, $historyParams);
$historyTotalPages = max(1, (int)ceil($historyTotal / $limit));

if ($historyTotal > 0 && $historyPage > $historyTotalPages) {
    $historyPage = $historyTotalPages;
    $offset = ($historyPage - 1) * $limit;
    $historyItems = $model->getAll($historyWhere, $historyParams, $limit, $offset);
}

require_once __DIR__ . '/views/hososcbd/machine_history.php';
