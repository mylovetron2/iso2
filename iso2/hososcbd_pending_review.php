<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/includes/permissions.php';

// Chỉ admin được truy cập
if (!hasRole(ROLE_ADMIN)) {
    http_response_code(403);
    header('Location: /iso2/hososcbd.php');
    exit;
}

$db = getDBConnection();

// Xử lý hành động duyệt / từ chối
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action'] ?? '';
    $pendingId = (int)($_POST['pending_id'] ?? 0);
    $note      = trim($_POST['admin_note'] ?? '');

    if ($pendingId > 0 && in_array($action, ['approve', 'reject'], true)) {
        // Lấy bản ghi pending
        $stmtGet = $db->prepare("SELECT * FROM hososcbd_pending_edits WHERE id = :id AND status = 'pending'");
        $stmtGet->execute([':id' => $pendingId]);
        $pending = $stmtGet->fetch(PDO::FETCH_ASSOC);

        if ($pending) {
            if ($action === 'approve') {
                // Áp dụng thay đổi vào DB
                $data = json_decode($pending['data_json'], true);
                $hososcbd_stt = (int)$pending['hososcbd_stt'];

                require_once __DIR__ . '/models/HoSoSCBD.php';
                $model = new HoSoSCBD();
                $model->update($hososcbd_stt, $data);

                // Người thực hiện
                $nguoiList = json_decode($pending['nguoi_thuchien_json'] ?? '[]', true);
                if (!empty($nguoiList)) {
                    // Lấy thông tin hồ sơ gốc
                    $itemStmt = $db->prepare("SELECT hoso, mavt, somay FROM hososcbd_iso WHERE stt = :stt");
                    $itemStmt->execute([':stt' => $hososcbd_stt]);
                    $itemInfo = $itemStmt->fetch(PDO::FETCH_ASSOC);

                    if ($itemInfo) {
                        $mahoso = $itemInfo['hoso'];
                        $mavt   = $itemInfo['mavt'];
                        $somay  = $itemInfo['somay'];
                        $ngayth = $data['ngayth'] ?? '0000-00-00';
                        $ngaykt = $data['ngaykt'] ?? '0000-00-00';
                        $currentMonth = (int)date('n');
                        $giolv_field  = "giolv{$currentMonth}";

                        // Xóa người thực hiện cũ
                        $db->prepare("DELETE FROM ngthuchien_iso WHERE mahoso = :mahoso")
                           ->execute([':mahoso' => $mahoso]);

                        // Thêm mới
                        $stmtMaxStt = $db->prepare("SELECT COALESCE(MAX(stt),0) as m FROM ngthuchien_iso");
                        $stmtMaxStt->execute();
                        $nextStt = (int)$stmtMaxStt->fetch(PDO::FETCH_ASSOC)['m'] + 1;

                        foreach ($nguoiList as $ng) {
                            $sqlIns = "INSERT INTO ngthuchien_iso
                                (stt, mahoso, mamay, somay, hoten, giolv, ngayth, ngaykt, {$giolv_field})
                                VALUES (:stt, :mahoso, :mamay, :somay, :hoten, :giolv, :ngayth, :ngaykt, :gm)";
                            $db->prepare($sqlIns)->execute([
                                ':stt'    => $nextStt++,
                                ':mahoso' => $mahoso,
                                ':mamay'  => $mavt,
                                ':somay'  => $somay,
                                ':hoten'  => $ng['hoten'],
                                ':giolv'  => $ng['giolv'],
                                ':ngayth' => $ngayth,
                                ':ngaykt' => $ngaykt,
                                ':gm'     => $ng['giolv'],
                            ]);
                        }
                    }
                }

                // BDDK
                if (!empty($pending['bddk_hoantat'])) {
                    $ngayth = $data['ngayth'] ?? '';
                    if ($ngayth && $ngayth !== '0000-00-00') {
                        // Tìm thietbi_id
                        $itemStmt2 = $db->prepare("SELECT mavt, somay FROM hososcbd_iso WHERE stt = :stt");
                        $itemStmt2->execute([':stt' => $hososcbd_stt]);
                        $ii = $itemStmt2->fetch(PDO::FETCH_ASSOC);
                        if ($ii) {
                            $tbStmt = $db->prepare("SELECT stt as thietbi_id FROM thietbi_iso WHERE mavt=:mavt AND somay=:somay LIMIT 1");
                            $tbStmt->execute([':mavt' => $ii['mavt'], ':somay' => $ii['somay']]);
                            $tb = $tbStmt->fetch(PDO::FETCH_ASSOC);
                            if ($tb) {
                                $month   = (int)date('n', strtotime($ngayth));
                                $quarter = (int)ceil($month / 3);
                                $nam     = (int)date('Y', strtotime($ngayth));
                                $hf      = "qui_{$quarter}_hoantat";
                                $db->prepare("UPDATE ke_hoach_bao_duong_dinh_ky_iso SET $hf=1 WHERE thietbi_id=:tid AND nam=:nam")
                                   ->execute([':tid' => $tb['thietbi_id'], ':nam' => $nam]);
                            }
                        }
                    }
                }
            }

            // Cập nhật trạng thái pending
            $newStatus = ($action === 'approve') ? 'approved' : 'rejected';
            $db->prepare("UPDATE hososcbd_pending_edits SET status=:s, admin_note=:n, reviewed_by=:rb, reviewed_at=NOW() WHERE id=:id")
               ->execute([
                   ':s'  => $newStatus,
                   ':n'  => $note,
                   ':rb' => $_SESSION['user_id'],
                   ':id' => $pendingId
               ]);

            $flashMessage = ($action === 'approve') ? 'Đã duyệt và áp dụng thay đổi.' : 'Đã từ chối yêu cầu.';
            $flashType    = ($action === 'approve') ? 'green' : 'red';
        }
    }
}

// Load danh sách pending
$filter = $_GET['filter'] ?? 'pending';
$validFilters = ['pending', 'approved', 'rejected', 'all'];
if (!in_array($filter, $validFilters, true)) $filter = 'pending';

$whereClause = ($filter === 'all') ? '' : "WHERE pe.status = :status";
$sql = "SELECT pe.*, h.phieu, h.mavt, h.somay, h.ngaykt
        FROM hososcbd_pending_edits pe
        LEFT JOIN hososcbd_iso h ON h.stt = pe.hososcbd_stt
        $whereClause
        ORDER BY pe.created_at DESC
        LIMIT 200";
$stmtList = $db->prepare($sql);
if ($filter !== 'all') {
    $stmtList->execute([':status' => $filter]);
} else {
    $stmtList->execute();
}
$pendingList = $stmtList->fetchAll(PDO::FETCH_ASSOC);

// Đếm pending
$countStmt = $db->prepare("SELECT COUNT(*) FROM hososcbd_pending_edits WHERE status='pending'");
$countStmt->execute();
$pendingCount = (int)$countStmt->fetchColumn();

$title = 'Duyệt yêu cầu sửa hồ sơ SCBD';
require_once __DIR__ . '/views/layouts/header.php';
?>
<div class="max-w-7xl mx-auto bg-white rounded-lg shadow-md p-4 md:p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl md:text-2xl font-bold flex items-center">
            <i class="fas fa-clipboard-check mr-2 text-blue-600"></i> Duyệt yêu cầu sửa hồ sơ SCBD
            <?php if ($pendingCount > 0): ?>
            <span class="ml-2 bg-red-500 text-white text-sm px-2 py-0.5 rounded-full"><?= $pendingCount ?></span>
            <?php endif; ?>
        </h1>
        <a href="/iso2/hososcbd.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Quay lại
        </a>
    </div>

    <?php if (isset($flashMessage)): ?>
    <div class="bg-<?= $flashType ?>-100 border border-<?= $flashType ?>-400 text-<?= $flashType ?>-800 px-4 py-3 rounded mb-4">
        <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($flashMessage) ?>
    </div>
    <?php endif; ?>

    <!-- Filter tabs -->
    <div class="flex gap-2 mb-6 border-b pb-2">
        <?php foreach (['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Đã từ chối', 'all' => 'Tất cả'] as $f => $label): ?>
        <a href="?filter=<?= $f ?>" class="px-4 py-2 rounded text-sm font-semibold <?= $filter === $f ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
            <?= $label ?>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($pendingList)): ?>
    <div class="text-center py-12 text-gray-500">
        <i class="fas fa-inbox text-4xl mb-3"></i>
        <p>Không có yêu cầu nào.</p>
    </div>
    <?php else: ?>
    <div class="space-y-6">
        <?php foreach ($pendingList as $row):
            $rowData = json_decode($row['data_json'] ?? '{}', true) ?: [];
            $nguoiList = json_decode($row['nguoi_thuchien_json'] ?? '[]', true) ?: [];
            $statusColors = ['pending' => 'yellow', 'approved' => 'green', 'rejected' => 'red'];
            $statusLabels = ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Đã từ chối'];
            $sc = $statusColors[$row['status']] ?? 'gray';
        ?>
        <div class="border border-gray-200 rounded-lg overflow-hidden shadow-sm">
            <!-- Header -->
            <div class="bg-gray-50 px-4 py-3 flex flex-wrap items-center justify-between gap-2 border-b">
                <div class="flex items-center gap-3 flex-wrap">
                    <span class="font-bold text-gray-800">
                        <i class="fas fa-file-alt mr-1 text-blue-500"></i>
                        Phiếu: <strong><?= htmlspecialchars($row['phieu'] ?? 'N/A') ?></strong>
                    </span>
                    <span class="text-gray-600 text-sm">
                        <?= htmlspecialchars($row['mavt'] ?? '') ?> – <?= htmlspecialchars($row['somay'] ?? '') ?>
                    </span>
                    <span class="bg-<?= $sc ?>-100 text-<?= $sc ?>-800 text-xs font-semibold px-2 py-0.5 rounded-full">
                        <?= $statusLabels[$row['status']] ?? $row['status'] ?>
                    </span>
                </div>
                <div class="text-sm text-gray-500">
                    Người gửi: <strong><?= htmlspecialchars($row['username']) ?></strong>
                    &nbsp;|&nbsp; <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?>
                </div>
            </div>

            <!-- Data comparison -->
            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h3 class="font-semibold text-gray-700 mb-2 text-sm">Dữ liệu đề xuất sửa</h3>
                    <table class="w-full text-xs border-collapse">
                        <?php
                        $fieldLabels = [
                            'cv'          => 'Loại công việc',
                            'nhomsc'      => 'Nhóm SC',
                            'ngayth'      => 'Ngày thực hiện',
                            'ngaykt'      => 'Ngày kết thúc',
                            'honghoc'     => 'Hỏng hóc',
                            'khacphuc'    => 'Khắc phục',
                            'noidung'     => 'Nội dung SC',
                            'ttktafter'   => 'Tình trạng KT sau',
                            'ketluan'     => 'Kết luận',
                            'xemxetxuong' => 'Xem xét xưởng',
                        ];
                        foreach ($fieldLabels as $field => $label):
                            $val = $rowData[$field] ?? '';
                            if ($val === '0000-00-00') $val = '';
                        ?>
                        <tr class="border-b border-gray-100">
                            <td class="py-1 pr-2 font-semibold text-gray-600 w-32 align-top"><?= $label ?></td>
                            <td class="py-1 text-gray-800 align-top whitespace-pre-wrap"><?= htmlspecialchars($val) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!empty($nguoiList)): ?>
                        <tr class="border-b border-gray-100">
                            <td class="py-1 pr-2 font-semibold text-gray-600 align-top">Người thực hiện</td>
                            <td class="py-1 text-gray-800 align-top">
                                <?php foreach ($nguoiList as $ng): ?>
                                <div><?= htmlspecialchars($ng['hoten']) ?> (<?= $ng['giolv'] ?> giờ)</div>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($row['bddk_hoantat'])): ?>
                        <tr>
                            <td class="py-1 pr-2 font-semibold text-purple-600 align-top">BDDK</td>
                            <td class="py-1 text-purple-700 font-semibold align-top">✓ Đánh dấu hoàn thành</td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>

                <!-- Action panel -->
                <div>
                    <?php if ($row['status'] === 'pending'): ?>
                    <h3 class="font-semibold text-gray-700 mb-2 text-sm">Hành động</h3>
                    <form method="POST" class="space-y-3" onsubmit="return confirm('Xác nhận hành động này?')">
                        <input type="hidden" name="pending_id" value="<?= $row['id'] ?>">
                        <div>
                            <label class="block text-sm font-semibold text-gray-600 mb-1">Ghi chú (tuỳ chọn)</label>
                            <textarea name="admin_note" rows="2" class="w-full px-2 py-1 text-sm border rounded focus:outline-none focus:ring focus:border-blue-400" placeholder="Lý do từ chối hoặc ghi chú..."></textarea>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" name="action" value="approve"
                                    class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm font-semibold">
                                <i class="fas fa-check mr-1"></i> Duyệt & Áp dụng
                            </button>
                            <button type="submit" name="action" value="reject"
                                    class="flex-1 bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded text-sm font-semibold">
                                <i class="fas fa-times mr-1"></i> Từ chối
                            </button>
                        </div>
                    </form>
                    <?php else: ?>
                    <div class="bg-gray-50 rounded p-3 text-sm">
                        <p class="font-semibold text-gray-700 mb-1">Đã xử lý bởi admin</p>
                        <?php if (!empty($row['admin_note'])): ?>
                        <p class="text-gray-600 italic">"<?= htmlspecialchars($row['admin_note']) ?>"</p>
                        <?php endif; ?>
                        <?php if (!empty($row['reviewed_at'])): ?>
                        <p class="text-gray-500 text-xs mt-1"><?= date('d/m/Y H:i', strtotime($row['reviewed_at'])) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="mt-3">
                        <a href="/iso2/hososcbd_repair_details.php?id=<?= $row['hososcbd_stt'] ?>"
                           class="text-blue-600 hover:underline text-sm">
                            <i class="fas fa-external-link-alt mr-1"></i> Xem hồ sơ gốc
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
