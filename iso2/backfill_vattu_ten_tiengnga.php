<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';

requireAuth();
if (!hasPermission('vattu.edit')) {
    http_response_code(403);
    exit('Bạn không có quyền cập nhật vật tư.');
}

function extractRussianName(string $vietnameseName): ?array
{
    $trimUnicode = static function (string $value): string {
        $trimmed = preg_replace('/\A[\p{Z}\s]+|[\p{Z}\s]+\z/u', '', $value);
        return $trimmed ?? trim($value);
    };
    $separateLanguages = static function (string $left, string $right) use ($trimUnicode): ?array {
        $leftLatin = preg_match_all('/\p{Latin}/u', $left);
        $leftCyrillic = preg_match_all('/\p{Cyrillic}/u', $left);
        $rightLatin = preg_match_all('/\p{Latin}/u', $right);
        $rightCyrillic = preg_match_all('/\p{Cyrillic}/u', $right);

        if ($leftLatin === false || $leftCyrillic === false
            || $rightLatin === false || $rightCyrillic === false) {
            return null;
        }

        if ($leftCyrillic > 0 && $rightCyrillic === 0 && $rightLatin > 0) {
            return [$left, $right];
        }
        if ($rightCyrillic > 0 && $leftCyrillic === 0 && $leftLatin > 0) {
            return [$right, $left];
        }
        if ($leftCyrillic > $leftLatin && $rightLatin > $rightCyrillic) {
            return [$left, $right];
        }
        if ($rightCyrillic > $rightLatin && $leftLatin > $leftCyrillic) {
            return [$right, $left];
        }

        return null;
    };
    $vietnameseName = $trimUnicode($vietnameseName);

    if (preg_match('/^(.+?)\s*\/\s*(.+)$/us', $vietnameseName, $matches) === 1) {
        $vietnamese = $trimUnicode($matches[1]);
        $russian = $trimUnicode($matches[2]);
        $parts = $separateLanguages($vietnamese, $russian);
        if ($parts !== null) {
            return $parts;
        }
    }

    if (preg_match('/^(.+?)\s+-\s+(.+)$/us', $vietnameseName, $matches) !== 1) {
        return null;
    }

    $left = $trimUnicode($matches[1]);
    $right = $trimUnicode($matches[2]);
    return $separateLanguages($left, $right);
}

function findRussianNameCandidates(PDO $db): array
{
    $stmt = $db->query("
        SELECT stt, mavattu, ten_tiengviet
        FROM vattu_thanh_ly_iso
        WHERE (ten_tiengnga IS NULL OR TRIM(ten_tiengnga) = '')
          AND ten_tiengviet IS NOT NULL
          AND TRIM(ten_tiengviet) <> ''
        ORDER BY stt
    ");

    $candidates = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $parts = extractRussianName((string)$item['ten_tiengviet']);
        if ($parts === null) {
            continue;
        }

        $item['ten_tiengnga_moi'] = $parts[0];
        $item['ten_tiengviet_moi'] = $parts[1];
        $candidates[] = $item;
    }

    return $candidates;
}

function russianNameCandidatesFingerprint(array $candidates): string
{
    $currentValues = array_map(
        static fn(array $item): array => [$item['stt'], $item['ten_tiengviet']],
        $candidates
    );

    return hash('sha256', json_encode($currentValues, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
}

if (!isset($_SESSION['backfill_vattu_ten_tiengnga_csrf'])) {
    $_SESSION['backfill_vattu_ten_tiengnga_csrf'] = bin2hex(random_bytes(32));
}

$records = [];
$summary = null;
$confirmation = null;
$errorMessage = null;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    http_response_code(405);
    exit('Method not allowed.');
}

if ($method === 'POST') {
    try {
        $csrf = $_POST['csrf'] ?? null;
        if (!is_string($csrf)
            || !hash_equals($_SESSION['backfill_vattu_ten_tiengnga_csrf'], $csrf)) {
            http_response_code(403);
            throw new InvalidArgumentException('Phiên xác nhận không hợp lệ. Hãy tải lại trang.');
        }

        $action = $_POST['action'] ?? null;
        if (!in_array($action, ['preview', 'apply'], true)) {
            throw new InvalidArgumentException('Thao tác không hợp lệ.');
        }

        if ($action === 'apply') {
            $preview = $_SESSION['backfill_vattu_ten_tiengnga_preview'] ?? null;
            $token = $_POST['confirmation'] ?? null;
            if (!is_array($preview)
                || $preview['expires'] < time()
                || !is_string($token)
                || !hash_equals($preview['token'], $token)
                || ($_POST['confirm_backup'] ?? null) !== '1') {
                throw new InvalidArgumentException('Cần xem trước lại và xác nhận đã sao lưu trước khi cập nhật.');
            }
            unset($_SESSION['backfill_vattu_ten_tiengnga_preview']);
        }

        if ($action === 'preview') {
            unset($_SESSION['backfill_vattu_ten_tiengnga_preview']);
        }

        $db = getDBConnection();
        $records = findRussianNameCandidates($db);

        if ($action === 'preview') {
            $summary = ['matched' => count($records), 'updated' => 0];
            if ($records !== []) {
                $confirmation = bin2hex(random_bytes(32));
                $_SESSION['backfill_vattu_ten_tiengnga_preview'] = [
                    'token' => $confirmation,
                    'fingerprint' => russianNameCandidatesFingerprint($records),
                    'expires' => time() + 600,
                ];
            }
        } else {
            $currentFingerprint = russianNameCandidatesFingerprint($records);
            if (!hash_equals($preview['fingerprint'], $currentFingerprint)) {
                throw new InvalidArgumentException('Dữ liệu đã thay đổi từ lúc xem trước. Hãy xem trước lại.');
            }

            $db->beginTransaction();
            $stmtUpdate = $db->prepare("
                UPDATE vattu_thanh_ly_iso
                SET ten_tiengnga = :ten_tiengnga,
                    ten_tiengviet = :ten_tiengviet
                WHERE stt = :stt
                  AND (ten_tiengnga IS NULL OR TRIM(ten_tiengnga) = '')
                  AND ten_tiengviet = :ten_tiengviet_cu
            ");
            $updated = 0;

            foreach ($records as $record) {
                $stmtUpdate->execute([
                    ':ten_tiengnga' => $record['ten_tiengnga_moi'],
                    ':ten_tiengviet' => $record['ten_tiengviet_moi'],
                    ':stt' => $record['stt'],
                    ':ten_tiengviet_cu' => $record['ten_tiengviet'],
                ]);
                $updated += $stmtUpdate->rowCount();
            }

            $db->commit();
            $summary = ['matched' => count($records), 'updated' => $updated];
            error_log('Vat tu Russian-name backfill: ' . json_encode([
                'user_id' => $_SESSION['user_id'],
                'summary' => $summary,
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        }
    } catch (InvalidArgumentException $error) {
        if (http_response_code() !== 403) {
            http_response_code(400);
        }
        $errorMessage = $error->getMessage();
    } catch (Throwable $error) {
        if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
            $db->rollBack();
        }
        http_response_code(500);
        error_log('Vat tu Russian-name backfill failed: ' . $error->getMessage());
        $errorMessage = 'Không hoàn tất xử lý. Hãy kiểm tra PHP error log trước khi chạy lại.';
        $records = [];
    }
}

$escape = static function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tách tên tiếng Nga vật tư</title>
</head>
<body>
<h1>Tách tên tiếng Nga khỏi tên tiếng Việt</h1>
<p>Chỉ xét vật tư có cột tên tiếng Nga đang trống. Chương trình tách theo các dạng:
<code>Tên tiếng Nga - Tên tiếng Việt</code>,
<code>Tên tiếng Việt - Tên tiếng Nga</code> hoặc <code>Tên tiếng Việt / Tên tiếng Nga</code>.
Ví dụ: <code>Стол для заседания - Bàn họp(gỗ dầu gió,căm xe)</code> và
<code>Súng phun sơn cá nhân /Персональный пистолет - Краско испылитель</code>.
Các tên có lẫn chữ Latin/Cyrillic như <code>Блок Эмулятор Blackfin / Bo mạch lập trình Emulator Blackfin để sửa máy</code>
và <code>Вình bột cứu hỏa MFZ-1 - Огнетушитель MFZ-1</code> cũng được hỗ trợ.</p>
<p><strong>Sao lưu cơ sở dữ liệu trước khi cập nhật.</strong> Xem trước không thay đổi dữ liệu.
Sau khi xác nhận, chương trình sẽ đối chiếu dữ liệu hiện tại rồi mới cập nhật.</p>

<?php if ($errorMessage !== null): ?>
    <p role="alert"><?= $escape($errorMessage) ?></p>
<?php endif; ?>

<form method="post">
    <input type="hidden" name="csrf" value="<?= $escape($_SESSION['backfill_vattu_ten_tiengnga_csrf']) ?>">
    <button type="submit" name="action" value="preview">Xem trước danh sách</button>
</form>

<?php if ($summary !== null): ?>
    <h2>Kết quả</h2>
    <p>Số bản ghi phù hợp: <?= $escape($summary['matched']) ?>.
    <?php if (isset($summary['updated'])): ?>
        Số bản ghi đã cập nhật: <?= $escape($summary['updated']) ?>.
    <?php endif; ?>
    </p>
    <?php if ($records !== []): ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Mã vật tư</th>
                    <th>Tên tiếng Nga (sẽ tách)</th>
                    <th>Tên tiếng Việt (sau khi tách)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $record): ?>
                    <tr>
                        <td><?= $escape($record['stt']) ?></td>
                        <td><?= $escape($record['mavattu']) ?></td>
                        <td><?= $escape($record['ten_tiengnga_moi']) ?></td>
                        <td><?= $escape($record['ten_tiengviet_moi']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
<?php endif; ?>

<?php if ($confirmation !== null): ?>
    <h2>Xác nhận cập nhật</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= $escape($_SESSION['backfill_vattu_ten_tiengnga_csrf']) ?>">
        <input type="hidden" name="confirmation" value="<?= $escape($confirmation) ?>">
        <label>
            <input type="checkbox" name="confirm_backup" value="1" required>
            Tôi đã sao lưu cơ sở dữ liệu và kiểm tra danh sách trên.
        </label>
        <button type="submit" name="action" value="apply">Cập nhật dữ liệu</button>
    </form>
<?php endif; ?>

<p><a href="vattuthanhly.php">Quay lại danh sách vật tư</a></p>
</body>
</html>
