<?php
declare(strict_types=1);

/**
 * Xóa thông tin bảo dưỡng hồ sơ đội của một kế hoạch bảo dưỡng định kỳ theo ID kế hoạch.
 *
 * Mở /iso2/reset_hoso_doi_kehoach.php (đăng nhập admin), nhập ID kế hoạch, xem trước rồi xác nhận.
 * Khi xác nhận (một transaction):
 *   1. Xóa các dòng chi tiết baoduong_dinhky_chitiet_iso của kế hoạch.
 *   2. Xóa các yêu cầu chờ duyệt hososcbd_pending_edits của hồ sơ đội.
 *   3. Gỡ liên kết kế hoạch (hososcbd_doi_id, nguon_hoso = NULL) và (tùy chọn) đặt lại cờ hoàn tất qui_1..4_hoantat = 0.
 *   4. Xóa hồ sơ đội hososcbd_doi_iso đã liên kết.
 * Không đụng tới kế hoạch liên kết hồ sơ cá nhân (hososcbd_iso). Ghi activity log.
 *
 * Xóa file này khỏi hosting khi không còn dùng.
 */

session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/includes/bao_duong_activity.php';

requireAuth();
if (!hasRole(ROLE_ADMIN)) {
    http_response_code(403);
    exit('Chỉ admin mới được dùng chức năng này.');
}

header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

$h = fn($s): string => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

if (empty($_SESSION['reset_hoso_doi_csrf'])) {
    $_SESSION['reset_hoso_doi_csrf'] = bin2hex(random_bytes(16));
}

/**
 * Lấy kế hoạch kèm hồ sơ đội và số bản ghi liên quan.
 */
function loadPlanInfo(PDO $db, int $planId, bool $lock = false): array
{
    $stmt = $db->prepare('SELECT * FROM ke_hoach_bao_duong_dinh_ky_iso WHERE id = :id' . ($lock ? ' FOR UPDATE' : ''));
    $stmt->execute([':id' => $planId]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$plan) {
        throw new RuntimeException("Không tìm thấy kế hoạch ID $planId.");
    }
    if ($plan['hososcbd_id'] !== null) {
        throw new RuntimeException('Kế hoạch này liên kết hồ sơ cá nhân (hososcbd_iso), không phải hồ sơ đội.');
    }

    $dossier = null;
    if ($plan['hososcbd_doi_id'] !== null) {
        $stmt = $db->prepare('SELECT * FROM hososcbd_doi_iso WHERE stt = :stt');
        $stmt->execute([':stt' => $plan['hososcbd_doi_id']]);
        $dossier = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    $stmt = $db->prepare('SELECT COUNT(*) FROM baoduong_dinhky_chitiet_iso WHERE kehoach_id = :id');
    $stmt->execute([':id' => $planId]);
    $detailCount = (int)$stmt->fetchColumn();

    $pendingCount = 0;
    if ($dossier !== null) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM hososcbd_pending_edits
            WHERE hososcbd_stt = :stt AND record_type = 'hososcbd_doi_iso'");
        $stmt->execute([':stt' => $plan['hososcbd_doi_id']]);
        $pendingCount = (int)$stmt->fetchColumn();
    }

    return ['plan' => $plan, 'dossier' => $dossier, 'detailCount' => $detailCount, 'pendingCount' => $pendingCount];
}

function resetDoiExecution(PDO $db, int $planId, bool $resetFlags): array
{
    $db->beginTransaction();
    try {
        $info = loadPlanInfo($db, $planId, true);
        $plan = $info['plan'];
        if ($plan['hososcbd_doi_id'] === null && $info['detailCount'] === 0) {
            throw new RuntimeException('Kế hoạch này không có thông tin bảo dưỡng hồ sơ đội để xóa.');
        }
        $dossierId = $plan['hososcbd_doi_id'];

        $stmt = $db->prepare('DELETE FROM baoduong_dinhky_chitiet_iso WHERE kehoach_id = :id');
        $stmt->execute([':id' => $planId]);
        $deletedDetails = $stmt->rowCount();

        $deletedPending = 0;
        $deletedDossier = 0;
        if ($dossierId !== null) {
            $stmt = $db->prepare("DELETE FROM hososcbd_pending_edits
                WHERE hososcbd_stt = :stt AND record_type = 'hososcbd_doi_iso'");
            $stmt->execute([':stt' => $dossierId]);
            $deletedPending = $stmt->rowCount();
        }

        $sets = ['hososcbd_doi_id = NULL', 'nguon_hoso = NULL'];
        if ($resetFlags) {
            for ($q = 1; $q <= 4; $q++) {
                $sets[] = "qui_{$q}_hoantat = 0";
            }
        }
        $stmt = $db->prepare('UPDATE ke_hoach_bao_duong_dinh_ky_iso SET ' . implode(', ', $sets) . ' WHERE id = :id');
        $stmt->execute([':id' => $planId]);

        if ($dossierId !== null) {
            $stmt = $db->prepare('DELETE FROM hososcbd_doi_iso WHERE stt = :stt');
            $stmt->execute([':stt' => $dossierId]);
            $deletedDossier = $stmt->rowCount();
        }

        $old = [
            'hososcbd_doi_id' => $dossierId,
            'nguon_hoso' => $plan['nguon_hoso'],
            'qui_1_hoantat' => $plan['qui_1_hoantat'], 'qui_2_hoantat' => $plan['qui_2_hoantat'],
            'qui_3_hoantat' => $plan['qui_3_hoantat'], 'qui_4_hoantat' => $plan['qui_4_hoantat'],
            'dossier' => $info['dossier'],
        ];
        $new = [
            'operation' => 'reset_doi_execution', 'hososcbd_doi_id' => null, 'nguon_hoso' => null,
            'reset_flags' => $resetFlags,
            'deleted_details' => $deletedDetails, 'deleted_pending' => $deletedPending,
            'deleted_dossier' => $deletedDossier,
        ];
        logBaoDuongActivity($db, 'UPDATE', $planId, $old, $new);

        $db->commit();
        return $new;
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
}

$planIdInput = trim((string)($_POST['plan_id'] ?? $_GET['plan_id'] ?? ''));
$resetFlags = ($_SERVER['REQUEST_METHOD'] !== 'POST') || isset($_POST['reset_flags']);
$info = null;
$result = null;
$error = '';

if ($planIdInput !== '' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($_SERVER['REQUEST_METHOD'] === 'POST'
            && !hash_equals($_SESSION['reset_hoso_doi_csrf'], (string)($_POST['csrf'] ?? ''))) {
            throw new RuntimeException('Phiên không hợp lệ, hãy tải lại trang.');
        }
        if (!ctype_digit($planIdInput) || (int)$planIdInput <= 0) {
            throw new RuntimeException('ID kế hoạch phải là số nguyên dương.');
        }
        $planId = (int)$planIdInput;
        $db = getDBConnection();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['mode'] ?? '') === 'apply') {
            $result = resetDoiExecution($db, $planId, $resetFlags);
            $planIdInput = '';
        } else {
            $info = loadPlanInfo($db, $planId);
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Xóa thông tin bảo dưỡng hồ sơ đội</title>
  <style>
    body { font-family: "Segoe UI", Arial, sans-serif; background: #f1f5f9; color: #1e293b; margin: 0; padding: 24px 16px; }
    main { max-width: 820px; margin: 0 auto; background: #fff; padding: 20px 24px; border-radius: 10px; border: 1px solid #e2e8f0; }
    h1 { margin-top: 0; font-size: 1.3rem; color: #991b1b; }
    label { display: block; margin: 12px 0 4px; font-weight: 600; }
    input[type=text] { padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; width: 160px; }
    button { padding: 8px 18px; border: 0; border-radius: 6px; color: #fff; font-weight: 600; cursor: pointer; margin-right: 8px; }
    .find { background: #4338ca; } .apply { background: #dc2626; }
    .err { padding: 10px 14px; background: #fef2f2; border-left: 4px solid #dc2626; color: #991b1b; margin: 12px 0; }
    .ok { padding: 10px 14px; background: #f0fdf4; border-left: 4px solid #16a34a; color: #166534; margin: 12px 0; }
    .note { color: #475569; font-size: .9rem; }
    table { border-collapse: collapse; font-size: .85rem; margin: 10px 0; width: 100%; }
    th, td { border: 1px solid #cbd5e1; padding: 4px 8px; text-align: left; }
    th { background: #e0e7ff; color: #312e81; width: 220px; }
    h2 { font-size: 1.05rem; color: #4338ca; margin: 20px 0 4px; }
  </style>
</head>
<body>
<main>
  <h1>Xóa thông tin bảo dưỡng hồ sơ đội theo ID kế hoạch</h1>
  <p class="note">Nhập ID kế hoạch (cột ID trên trang kế hoạch bảo dưỡng định kỳ) để xem trước dữ liệu sẽ bị xóa. Thao tác không hoàn tác được &mdash; hãy sao lưu DB trước.</p>

  <?php if ($error !== ''): ?><div class="err"><?= $h($error) ?></div><?php endif; ?>
  <?php if ($result !== null): ?>
    <div class="ok">
      Đã xóa thông tin bảo dưỡng hồ sơ đội: hồ sơ đội <?= $h($result['deleted_dossier']) ?>,
      chi tiết bảo dưỡng <?= $h($result['deleted_details']) ?>,
      yêu cầu chờ duyệt <?= $h($result['deleted_pending']) ?>.
      <?= $result['reset_flags'] ? 'Đã đặt lại cờ hoàn tất các quý.' : 'Giữ nguyên cờ hoàn tất các quý.' ?>
    </div>
  <?php endif; ?>

  <form method="post">
    <input type="hidden" name="csrf" value="<?= $h($_SESSION['reset_hoso_doi_csrf']) ?>">
    <label for="plan_id">ID kế hoạch</label>
    <input type="text" id="plan_id" name="plan_id" inputmode="numeric" value="<?= $h($planIdInput) ?>" autofocus>
    <p><button class="find" type="submit" name="mode" value="preview">Xem trước</button></p>
  </form>

  <?php if ($info !== null): ?>
    <?php $plan = $info['plan']; $dossier = $info['dossier']; ?>
    <h2>Kế hoạch #<?= $h($plan['id']) ?></h2>
    <table>
      <tr><th>Năm</th><td><?= $h($plan['nam']) ?></td></tr>
      <tr><th>Thiết bị</th><td><?= $h($plan['ten_thietbi']) ?> (S/N: <?= $h($plan['so_serial']) ?>; thietbi_id: <?= $h($plan['thietbi_id']) ?>)</td></tr>
      <tr><th>ĐV chính / ĐV phụ</th><td><?= $h($plan['donvi_lam_chinh']) ?> / <?= $h($plan['donvi_lam_phu']) ?></td></tr>
      <tr><th>Quý</th><td><?= $h($plan['qui_1']) ?> | <?= $h($plan['qui_2']) ?> | <?= $h($plan['qui_3']) ?> | <?= $h($plan['qui_4']) ?></td></tr>
      <tr><th>Hoàn tất Q1/Q2/Q3/Q4</th><td><?= $h((int)$plan['qui_1_hoantat']) ?> / <?= $h((int)$plan['qui_2_hoantat']) ?> / <?= $h((int)$plan['qui_3_hoantat']) ?> / <?= $h((int)$plan['qui_4_hoantat']) ?></td></tr>
      <tr><th>nguon_hoso / hososcbd_doi_id</th><td><?= $h($plan['nguon_hoso']) ?> / <?= $h($plan['hososcbd_doi_id']) ?></td></tr>
    </table>

    <?php if ($dossier !== null): ?>
      <h2>Hồ sơ đội sẽ bị xóa (hososcbd_doi_iso #<?= $h($dossier['stt']) ?>)</h2>
      <table>
        <tr><th>Phiếu / Hồ sơ</th><td><?= $h($dossier['phieu']) ?> / <?= $h($dossier['hoso']) ?></td></tr>
        <tr><th>Ngày thực hiện &rarr; kết thúc</th><td><?= $h($dossier['ngayth']) ?> &rarr; <?= $h($dossier['ngaykt']) ?></td></tr>
        <tr><th>Người thực hiện</th><td><?= $h($dossier['nguoi_thuc_hien_iso'] ?? '') ?></td></tr>
      </table>
    <?php endif; ?>
    <p>Chi tiết bảo dưỡng sẽ xóa: <b><?= $h($info['detailCount']) ?></b>; yêu cầu chờ duyệt sẽ xóa: <b><?= $h($info['pendingCount']) ?></b>.</p>

    <?php if ($plan['hososcbd_doi_id'] === null && $info['detailCount'] === 0): ?>
      <div class="err">Kế hoạch này không có thông tin bảo dưỡng hồ sơ đội để xóa.</div>
    <?php else: ?>
      <form method="post" onsubmit="return confirm('Xóa vĩnh viễn thông tin bảo dưỡng hồ sơ đội của kế hoạch #<?= $h($plan['id']) ?>?');">
        <input type="hidden" name="csrf" value="<?= $h($_SESSION['reset_hoso_doi_csrf']) ?>">
        <input type="hidden" name="plan_id" value="<?= $h($plan['id']) ?>">
        <label style="font-weight:normal"><input type="checkbox" name="reset_flags" value="1" <?= $resetFlags ? 'checked' : '' ?>> Đặt lại cờ hoàn tất các quý (qui_1..4_hoantat = 0)</label>
        <p><button class="apply" type="submit" name="mode" value="apply">Xóa thông tin bảo dưỡng</button></p>
      </form>
    <?php endif; ?>
  <?php endif; ?>
</main>
</body>
</html>
