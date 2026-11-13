<?php
declare(strict_types=1);

session_start();
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'; style-src 'unsafe-inline'");

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';

requireAuth();
if (!hasRole(ROLE_ADMIN)) {
    http_response_code(403);
    exit('Chỉ quản trị viên được chạy công cụ này.');
}

if (!isset($_SESSION['backfill_thietbi_csrf'])) {
    $_SESSION['backfill_thietbi_csrf'] = bin2hex(random_bytes(32));
}

$escape = static function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$missingCondition = "(mavt IS NULL OR TRIM(mavt) = ''
    OR somay IS NULL OR TRIM(somay) = ''
    OR madv IS NULL OR TRIM(madv) = '')";
$fields = [
    'mavt' => 'Mã vật tư',
    'somay' => 'Số máy',
    'madv' => 'Đơn vị',
];

$errorMessage = null;
$summary = null;
$records = [];
$confirmation = null;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    http_response_code(405);
    exit('Method not allowed.');
}

if ($method === 'POST') {
    try {
        $csrf = $_POST['csrf'] ?? null;
        if (!is_string($csrf) || !hash_equals($_SESSION['backfill_thietbi_csrf'], $csrf)) {
            http_response_code(403);
            throw new InvalidArgumentException('Phiên xác nhận không hợp lệ. Hãy tải lại trang.');
        }

        $action = $_POST['action'] ?? null;
        if (!in_array($action, ['preview', 'apply'], true)) {
            throw new InvalidArgumentException('Thao tác không hợp lệ.');
        }

        $preview = $_SESSION['backfill_thietbi_preview'] ?? null;
        if ($action === 'apply') {
            unset($_SESSION['backfill_thietbi_preview']);
            $token = $_POST['confirmation'] ?? null;
            if (!is_array($preview) || $preview['expires'] < time()
                || !is_string($token) || !hash_equals($preview['token'], $token)
                || ($_POST['confirm_backup'] ?? null) !== '1') {
                throw new InvalidArgumentException('Hãy xem trước lại và xác nhận đã sao lưu trước khi cập nhật.');
            }
        } else {
            unset($_SESSION['backfill_thietbi_preview']);
        }

        $db = getDBConnection();
        if ($action === 'preview') {
            $summary = [
                'missing_rows' => (int)$db->query(
                    "SELECT COUNT(*) FROM thietbi_iso WHERE $missingCondition"
                )->fetchColumn(),
            ];
            $stmt = $db->query(
                "SELECT stt, mavt, somay, madv FROM thietbi_iso
                 WHERE $missingCondition ORDER BY stt LIMIT 100"
            );
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $row['missing_fields'] = [];
                foreach ($fields as $column => $label) {
                    if ($row[$column] === null || trim((string)$row[$column]) === '') {
                        $row['missing_fields'][] = $label;
                    }
                }
                $records[] = $row;
            }
            if ($summary['missing_rows'] > 0) {
                $confirmation = bin2hex(random_bytes(32));
                $_SESSION['backfill_thietbi_preview'] = [
                    'token' => $confirmation,
                    'expires' => time() + 600,
                ];
            }
        } else {
            $db->beginTransaction();
            try {
                $stmt = $db->prepare("UPDATE thietbi_iso
                    SET
                        mavt = CASE WHEN mavt IS NULL OR TRIM(mavt) = '' THEN 'N/A' ELSE mavt END,
                        somay = CASE WHEN somay IS NULL OR TRIM(somay) = '' THEN 'N/A' ELSE somay END,
                        madv = CASE WHEN madv IS NULL OR TRIM(madv) = '' THEN 'N/A' ELSE madv END
                    WHERE $missingCondition");
                $stmt->execute();
                $summary = ['updated_rows' => $stmt->rowCount()];
                $db->commit();
                error_log('Backfill thietbi_iso required fields: ' . json_encode([
                    'user_id' => $_SESSION['user_id'] ?? null,
                    'summary' => $summary,
                ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
            } catch (Throwable $error) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                throw $error;
            }
        }
    } catch (InvalidArgumentException $error) {
        if (http_response_code() !== 403) {
            http_response_code(400);
        }
        $errorMessage = $error->getMessage();
        error_log('Backfill thietbi_iso rejected: ' . $error->getMessage());
    } catch (Throwable $error) {
        http_response_code(500);
        error_log('Backfill thietbi_iso failed: ' . $error->getMessage());
        $errorMessage = 'Không hoàn tất xử lý. Hãy kiểm tra PHP error log trước khi chạy lại.';
        $records = [];
        $summary = null;
        $confirmation = null;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bổ sung định danh thiết bị</title>
    <style>
        body { max-width: 1100px; margin: 32px auto; padding: 0 16px; color: #1f2937; font: 16px Arial, sans-serif; }
        h1, h2 { color: #111827; }
        .notice { margin: 16px 0; padding: 12px 16px; border-radius: 6px; background: #eff6ff; }
        .warning { background: #fff7ed; }
        .error { background: #fef2f2; color: #991b1b; }
        button { margin: 12px 8px 12px 0; padding: 10px 16px; border: 0; border-radius: 5px; background: #2563eb; color: white; cursor: pointer; }
        button.danger { background: #b91c1c; }
        table { width: 100%; margin-top: 16px; border-collapse: collapse; }
        th, td { padding: 8px; border: 1px solid #d1d5db; text-align: left; }
        th { background: #f3f4f6; }
        code { background: #f3f4f6; padding: 2px 4px; }
    </style>
</head>
<body>
    <h1>Bổ sung định danh thiết bị</h1>
    <p>Công cụ áp dụng cho toàn bộ bảng <code>thietbi_iso</code>. Các trường <code>mavt</code>, <code>somay</code>, <code>madv</code> có giá trị NULL hoặc rỗng sẽ được điền thành <code>N/A</code>; giá trị hiện có được giữ nguyên.</p>
    <p class="notice warning"><strong>Sao lưu cơ sở dữ liệu trước khi cập nhật.</strong> Chức năng xem trước không thay đổi dữ liệu. Nên gỡ công cụ khỏi hosting sau khi sử dụng.</p>

    <?php if ($errorMessage !== null): ?>
        <p class="notice error" role="alert"><?php echo $escape($errorMessage); ?></p>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf" value="<?php echo $escape($_SESSION['backfill_thietbi_csrf']); ?>">
        <button type="submit" name="action" value="preview">Xem trước dữ liệu thiếu</button>
    </form>

    <?php if ($summary !== null && isset($summary['missing_rows'])): ?>
        <h2>Kết quả xem trước</h2>
        <p>Có <strong><?php echo (int)$summary['missing_rows']; ?></strong> thiết bị cần bổ sung.</p>
        <?php if ($records !== []): ?>
            <p>Hiển thị tối đa 100 dòng đầu tiên:</p>
            <table>
                <thead><tr><th>ID</th><th>Mã vật tư</th><th>Số máy</th><th>Đơn vị</th><th>Trường sẽ được điền</th></tr></thead>
                <tbody>
                <?php foreach ($records as $record): ?>
                    <tr>
                        <td><?php echo $escape($record['stt']); ?></td>
                        <td><?php echo $escape($record['mavt'] ?? 'NULL'); ?></td>
                        <td><?php echo $escape($record['somay'] ?? 'NULL'); ?></td>
                        <td><?php echo $escape($record['madv'] ?? 'NULL'); ?></td>
                        <td><?php echo $escape(implode(', ', $record['missing_fields'])); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php elseif ($summary !== null && isset($summary['updated_rows'])): ?>
        <p class="notice">Đã cập nhật <strong><?php echo (int)$summary['updated_rows']; ?></strong> thiết bị.</p>
    <?php endif; ?>

    <?php if ($confirmation !== null): ?>
        <h2>Xác nhận cập nhật</h2>
        <form method="post">
            <input type="hidden" name="csrf" value="<?php echo $escape($_SESSION['backfill_thietbi_csrf']); ?>">
            <input type="hidden" name="confirmation" value="<?php echo $escape($confirmation); ?>">
            <label>
                <input type="checkbox" name="confirm_backup" value="1" required>
                Tôi đã sao lưu cơ sở dữ liệu và kiểm tra kết quả xem trước.
            </label>
            <br>
            <button type="submit" name="action" value="apply" class="danger">Xác nhận điền N/A</button>
        </form>
        <p>Xác nhận có hiệu lực trong 10 phút và chỉ sử dụng một lần. Khi cập nhật, hệ thống sẽ xét lại các dòng thiếu theo dữ liệu hiện tại.</p>
    <?php endif; ?>
</body>
</html>
