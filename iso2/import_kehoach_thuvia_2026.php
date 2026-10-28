<?php
declare(strict_types=1);

/**
 * Import dữ liệu từ html/kehoach_baotri_baoduong_2026_doi_thuvia.html vào:
 *   1. thietbi_iso                        (danh mục thiết bị - trang thietbi.php)
 *   2. ke_hoach_bao_duong_dinh_ky_iso     (kế hoạch bảo dưỡng - trang kehoachbaoduongdinhky.php)
 *
 * Chạy trên hosting: đăng nhập admin rồi mở /iso2/import_kehoach_thuvia_2026.php
 *   - "Chạy thử": hiển thị kết quả, không ghi DB.
 *   - "Ghi vào DB": thực hiện import (một transaction).
 * Chạy bằng dòng lệnh: php import_kehoach_thuvia_2026.php [--apply] [--nam=2026] [--html=<file>] [--madv-tv=<madv>]
 *
 * Quy tắc:
 *   - Tất cả thiết bị thuộc donvi_iso.tendv = "Đội Thử vỉa".
 *   - Thiết bị: mavt = Mã tài sản (thiếu thì S/N, thiếu nữa thì tên), somay = S/N, model = ''.
 *   - Kế hoạch: TO và TP đều lưu 'TO' ở cột quí tương ứng; Đội TV -> 'TV',
 *     XSCTBĐVL / Dịch vụ giữ nguyên; nhomsc để trống.
 *   - Chạy lại an toàn: thiết bị đã có thì dùng lại, dòng kế hoạch đã có (cùng năm + thiết bị) thì bỏ qua.
 *
 * Xóa file này khỏi hosting sau khi import xong.
 */

$isCli = PHP_SAPI === 'cli';

if (!$isCli && session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';
if (!$isCli) {
    require_once __DIR__ . '/config/constants.php';
    require_once __DIR__ . '/includes/auth.php';
    require_once __DIR__ . '/includes/permissions.php';
    requireAuth();
    if (!hasRole(ROLE_ADMIN)) {
        http_response_code(403);
        exit('Chỉ admin mới được chạy import này.');
    }
    header('Content-Type: text/html; charset=UTF-8');
}

mb_internal_encoding('UTF-8');

const UNIT_TV = 'Đội Thử vỉa';
const DEFAULT_HTML = __DIR__ . '/html/kehoach_baotri_baoduong_2026_doi_thuvia.html';
const EXEC_LABELS = ['a' => ['Đội TV', 'XSCTBĐVL'], 'b' => ['XSCTBĐVL', 'Đội TV'], 'd' => ['Dịch vụ', '']];


// ---------------------------------------------------------------- parse html
function parseHtml(string $html): array
{
    $quarterOf = [];
    if (!preg_match('/const QUARTERS = \{(.*?)\n\};/s', $html, $block)) {
        throw new RuntimeException('Không đọc được khối QUARTERS trong file html');
    }
    preg_match_all('/(\d):\s*\[(.*?)\]/s', $block[1], $qs, PREG_SET_ORDER);
    foreach ($qs as $q) {
        preg_match_all("/'([^']+)'/", $q[2], $items);
        foreach ($items[1] as $item) {
            [$from, $to] = array_pad(explode('-', $item), 2, null);
            [$sec, $a] = explode('.', $from);
            $b = $to !== null ? explode('.', $to)[1] : $a;
            for ($i = (int)$a; $i <= (int)$b; $i++) {
                $quarterOf["$sec.$i"] = (int)$q[1];
            }
        }
    }

    $rows = [];
    foreach (['RAW1', 'RAW2'] as $name) {
        if (!preg_match('/const ' . $name . ' = `(.*?)`;/s', $html, $m)) {
            throw new RuntimeException("Không đọc được $name trong file html");
        }
        foreach (preg_split('/\R/', trim($m[1])) as $line) {
            $f = explode('|', $line);
            if (count($f) < 7) {
                throw new RuntimeException("Dòng dữ liệu không hợp lệ: $line");
            }
            [$stt, $ten, $code, $sn, $year, $ex, $type] = array_map('trim', $f);
            [$main, $aux] = EXEC_LABELS[$ex] ?? ['', ''];
            $rows[] = [
                'stt' => $stt, 'ten' => $ten, 'code' => $code,
                'sn' => strtoupper($sn) === 'N/A' ? '' : $sn,
                'year' => $year, 'main' => $main, 'aux' => $aux,
                'quarter' => $type !== '' ? ($quarterOf[$stt] ?? 0) : 0,
            ];
        }
    }
    return $rows;
}

function parseNgayKtsd(string $s): string
{
    if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $s, $m)) {
        return "$m[3]-$m[2]-$m[1]";
    }
    if (preg_match('/^(\d{4})/', $s, $m)) {
        return "$m[1]-01-01";
    }
    throw new RuntimeException("Không đọc được năm sản xuất: $s");
}

function normalize(string $s): string
{
    return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $s)));
}

function fitLength(string $value, int $max): string
{
    return ($max > 0 && strlen($value) > $max) ? mb_strcut($value, 0, $max) : $value;
}

// ---------------------------------------------------------------- db helpers
function findMadv(PDO $db, string $tendv, string $override): string
{
    if ($override !== '') {
        return $override;
    }
    $target = normalize($tendv);
    foreach ($db->query('SELECT madv, tendv FROM donvi_iso')->fetchAll() as $dv) {
        $name = (string)$dv['tendv'];
        // DB lưu latin1 nhưng dữ liệu là byte UTF-8 nên so sánh trực tiếp; thử thêm bản giải mã phòng khi khác
        if (normalize($name) === $target || normalize(mb_convert_encoding($name, 'UTF-8', 'ISO-8859-1')) === $target) {
            return (string)$dv['madv'];
        }
    }
    throw new RuntimeException("Không tìm thấy đơn vị \"$tendv\" trong donvi_iso.");
}

function columnLength(PDO $db, string $table, string $column): int
{
    $stmt = $db->prepare('SELECT CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS
                          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->execute([$table, $column]);
    return (int)($stmt->fetchColumn() ?: 0);
}

// ---------------------------------------------------------------- import
/**
 * @param array{apply: bool, nam: int, madv-tv: string, html: string} $opts
 * @return array{before: string[], tables: array<string, array{head: string[], rows: array<int, array>}>, after: string[]}
 */
function runImport(array $opts): array
{
    $out = [];
    $tables = [];
    $rows = parseHtml($opts['html']);
    $nam = (int)$opts['nam'];
    $apply = (bool)$opts['apply'];

    $db = getDBConnection();
    $db->exec('SET NAMES latin1');

    $madv = findMadv($db, UNIT_TV, (string)$opts['madv-tv']);

    $len = [];
    foreach (['mavt', 'tenvt', 'somay', 'model', 'mamay'] as $c) {
        $len[$c] = columnLength($db, 'thietbi_iso', $c);
    }
    $planLen = [
        'ten_thietbi' => columnLength($db, 'ke_hoach_bao_duong_dinh_ky_iso', 'ten_thietbi'),
        'so_serial' => columnLength($db, 'ke_hoach_bao_duong_dinh_ky_iso', 'so_serial'),
    ];

    $out[] = ($apply ? '=== GHI DỮ LIỆU ===' : '=== CHẠY THỬ (không ghi DB) ===');
    $out[] = "Năm kế hoạch: $nam | madv Đội Thử vỉa: $madv";

    $findDevice = $db->prepare('SELECT stt, tenvt FROM thietbi_iso WHERE mavt = ? AND somay = ? AND model = ?');
    $insertDevice = $db->prepare(
        'INSERT INTO thietbi_iso (mavt, tenvt, somay, model, homay, dienap, thongtincb, loaidau, mucdau, madv, bdtime, ngayktsd, tlkt, hosomay, mamay)
         VALUES (:mavt, :tenvt, :somay, :model, :homay, :dienap, :thongtincb, :loaidau, :mucdau, :madv, :bdtime, :ngayktsd, :tlkt, :hosomay, :mamay)'
    );
    $findPlan = $db->prepare('SELECT id FROM ke_hoach_bao_duong_dinh_ky_iso WHERE nam = ? AND thietbi_id = ?');
    $insertPlan = $db->prepare(
        'INSERT INTO ke_hoach_bao_duong_dinh_ky_iso
         (thietbi_id, nam, ten_thietbi, so_serial, nhomsc, qui_1, qui_2, qui_3, qui_4, donvi_lam_chinh, donvi_lam_phu, ghi_chu, created_by)
         VALUES (:thietbi_id, :nam, :ten_thietbi, :so_serial, :nhomsc, :qui_1, :qui_2, :qui_3, :qui_4, :donvi_lam_chinh, :donvi_lam_phu, :ghi_chu, :created_by)'
    );

    $stat = ['device_new' => 0, 'device_reuse' => 0, 'plan_new' => 0, 'plan_skip' => 0];
    $usedKeys = [];
    $warnings = [];
    $label = fn(string $v): string => $v === 'Đội TV' ? 'TV' : $v;

    $db->beginTransaction();
    try {
        foreach ($rows as $r) {
            $tenvt = $r['ten'];
            if ($r['sn'] !== '' && mb_stripos($tenvt, $r['sn']) === false) {
                $tenvt .= ' ' . $r['sn'];
            }
            $tenvt = fitLength($tenvt, $len['tenvt']);
            $somay = fitLength($r['sn'], $len['somay']);
            $model = '';

            $base = $r['code'] !== '' ? $r['code'] : ($r['sn'] !== '' ? $r['sn'] : $r['ten']);
            $suffix = '-' . $r['stt'];
            $mavt = strlen($base) > $len['mavt'] && $len['mavt'] > 0
                ? mb_strcut($base, 0, $len['mavt'] - strlen($suffix)) . $suffix
                : $base;

            $find = function (string $key) use ($findDevice, $somay, $model) {
                $findDevice->execute([$key, $somay, $model]);
                return $findDevice->fetch();
            };
            $existing = $find($mavt);
            $conflict = (isset($usedKeys["$mavt|$somay|$model"]) && $usedKeys["$mavt|$somay|$model"] !== $tenvt)
                || ($existing && $existing['tenvt'] !== $tenvt);
            if ($conflict) {
                // Trùng khóa với thiết bị khác (VD khung dầm dùng chung mã tài sản) -> thêm hậu tố STT
                $mavt = ($len['mavt'] > 0 ? mb_strcut($base, 0, $len['mavt'] - strlen($suffix)) : $base) . $suffix;
                $existing = $find($mavt);
                $warnings[] = "{$r['stt']}: trùng khóa thiết bị, dùng mavt = $mavt";
            }
            $usedKeys["$mavt|$somay|$model"] = $tenvt;

            $mamay = fitLength(implode('-', array_filter([$mavt, $model, $somay], fn($p) => $p !== '')), $len['mamay']);
            $deviceParams = [
                'mavt' => $mavt, 'tenvt' => $tenvt, 'somay' => $somay, 'model' => $model,
                'homay' => '', 'dienap' => '', 'thongtincb' => '', 'loaidau' => '', 'mucdau' => '',
                'madv' => $madv, 'bdtime' => 0, 'ngayktsd' => parseNgayKtsd($r['year']),
                'tlkt' => '', 'hosomay' => '', 'mamay' => $mamay,
            ];

            if ($existing) {
                $stt = (int)$existing['stt'];
                $stat['device_reuse']++;
                $deviceNote = "dùng lại (stt=$stt), không ghi";
            } else {
                $stt = 0;
                if ($apply) {
                    $insertDevice->execute(array_combine(array_map(fn($k) => ":$k", array_keys($deviceParams)), $deviceParams));
                    $stt = (int)$db->lastInsertId();
                }
                $stat['device_new']++;
                $deviceNote = $apply ? "thêm mới (stt=$stt)" : 'sẽ thêm mới';
            }

            $qui = ['', '', '', ''];
            if ($r['quarter'] >= 1 && $r['quarter'] <= 4) {
                $qui[$r['quarter'] - 1] = 'TO';
            }
            $planParams = [
                'thietbi_id' => $stt, 'nam' => $nam,
                'ten_thietbi' => fitLength($tenvt, $planLen['ten_thietbi']),
                'so_serial' => fitLength($somay, $planLen['so_serial']),
                'nhomsc' => '',
                'qui_1' => $qui[0], 'qui_2' => $qui[1], 'qui_3' => $qui[2], 'qui_4' => $qui[3],
                'donvi_lam_chinh' => $label($r['main']), 'donvi_lam_phu' => $label($r['aux']),
                'ghi_chu' => '', 'created_by' => 'import_kehoach_thuvia_2026',
            ];
            $planExists = false;
            if ($stt > 0) {
                $findPlan->execute([$nam, $stt]);
                $planExists = (bool)$findPlan->fetchColumn();
            }
            if ($planExists) {
                $stat['plan_skip']++;
                $planNote = 'đã có trong kế hoạch, bỏ qua';
            } else {
                if ($apply) {
                    $insertPlan->execute(array_combine(array_map(fn($k) => ":$k", array_keys($planParams)), $planParams));
                }
                $stat['plan_new']++;
                $planNote = $apply ? 'đã thêm' : 'sẽ thêm mới';
            }

            // Khóa liên kết: ke_hoach_bao_duong_dinh_ky_iso.thietbi_id = thietbi_iso.stt (thiết bị mới chưa có stt khi chạy thử)
            $link = $stt > 0 ? (string)$stt : 'mới #' . $r['stt'];
            $tables['thietbi_iso']['rows'][] = array_merge([$r['stt'], $link], array_values($deviceParams), [$deviceNote]);
            $planParams['thietbi_id'] = $link;
            $tables['ke_hoach_bao_duong_dinh_ky_iso']['rows'][] = array_merge([$r['stt']], array_values($planParams), [$planNote]);
            $tables['thietbi_iso']['head'] ??= array_merge(['STT (html)', 'stt'], array_keys($deviceParams), ['Kết quả']);
            $tables['ke_hoach_bao_duong_dinh_ky_iso']['head'] ??= array_merge(['STT (html)'], array_keys($planParams), ['Kết quả']);
        }

        $apply ? $db->commit() : $db->rollBack();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }

    $after = [];
    $after[] = 'Tổng: ' . count($rows) . " dòng | thiết bị mới: {$stat['device_new']}, dùng lại: {$stat['device_reuse']}"
        . " | kế hoạch mới: {$stat['plan_new']}, bỏ qua: {$stat['plan_skip']}";
    foreach ($warnings as $w) {
        $after[] = "Cảnh báo - $w";
    }
    $after[] = $apply ? 'ĐÃ GHI VÀO DB. Hãy xóa file import_kehoach_thuvia_2026.php khỏi hosting.' : 'Chưa ghi gì vào DB.';
    return ['before' => $out, 'tables' => $tables, 'after' => $after];
}

// ---------------------------------------------------------------- CLI
if ($isCli) {
    $opts = ['apply' => false, 'html' => DEFAULT_HTML, 'nam' => 2026, 'madv-tv' => ''];
    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--apply') {
            $opts['apply'] = true;
        } elseif (preg_match('/^--(html|nam|madv-tv)=(.+)$/', $arg, $m)) {
            $opts[$m[1]] = $m[1] === 'nam' ? (int)$m[2] : $m[2];
        } else {
            fwrite(STDERR, "Tham số không hợp lệ: $arg\n");
            exit(2);
        }
    }
    try {
        $path = (string)$opts['html'];
        if (!is_file($path)) {
            throw new RuntimeException("Không tìm thấy file: $path");
        }
        $opts['html'] = (string)file_get_contents($path);
        $res = runImport($opts);
        echo implode("\n", $res['before']), "\n";
        foreach ($res['tables'] as $name => $t) {
            echo "\n--- $name ---\n", implode("\t", $t['head']), "\n";
            foreach ($t['rows'] as $row) {
                echo implode("\t", $row), "\n";
            }
        }
        echo "\n", implode("\n", $res['after']), "\n";
    } catch (Throwable $e) {
        fwrite(STDERR, 'Lỗi: ' . $e->getMessage() . "\n");
        exit(1);
    }
    exit(0);
}

// ---------------------------------------------------------------- Web
$h = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
if (empty($_SESSION['import_thuvia_csrf'])) {
    $_SESSION['import_thuvia_csrf'] = bin2hex(random_bytes(16));
}

$result = null;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!hash_equals($_SESSION['import_thuvia_csrf'], (string)($_POST['csrf'] ?? ''))) {
            throw new RuntimeException('Phiên không hợp lệ, hãy tải lại trang.');
        }
        set_time_limit(180);

        $html = '';
        if (isset($_FILES['html_file']) && $_FILES['html_file']['error'] === UPLOAD_ERR_OK) {
            $html = (string)file_get_contents($_FILES['html_file']['tmp_name']);
        } elseif (is_file(DEFAULT_HTML)) {
            $html = (string)file_get_contents(DEFAULT_HTML);
        } else {
            throw new RuntimeException('Không thấy html/kehoach_baotri_baoduong_2026_doi_thuvia.html trên hosting. Hãy chọn file để tải lên.');
        }

        $nam = (int)($_POST['nam'] ?? 2026);
        if ($nam < 2000 || $nam > 2100) {
            throw new RuntimeException('Năm không hợp lệ.');
        }
        $result = runImport([
            'apply' => ($_POST['mode'] ?? '') === 'apply',
            'nam' => $nam,
            'madv-tv' => trim((string)($_POST['madv_tv'] ?? '')),
            'html' => $html,
        ]);
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
  <title>Import kế hoạch bảo dưỡng 2026 - Đội Thử vỉa</title>
  <style>
    body { font-family: "Segoe UI", Arial, sans-serif; background: #f1f5f9; color: #1e293b; margin: 0; padding: 24px 16px; }
    main { max-width: 1100px; margin: 0 auto; background: #fff; padding: 20px 24px; border-radius: 10px; border: 1px solid #e2e8f0; }
    h1 { margin-top: 0; font-size: 1.3rem; color: #312e81; }
    label { display: block; margin: 12px 0 4px; font-weight: 600; }
    input[type=number], input[type=text] { padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; }
    button { padding: 8px 18px; border: 0; border-radius: 6px; color: #fff; font-weight: 600; cursor: pointer; margin-right: 8px; }
    .preview { background: #4338ca; } .apply { background: #dc2626; }
    pre { background: #0f172a; color: #e2e8f0; padding: 14px; border-radius: 8px; overflow-x: auto; font-size: .8rem; line-height: 1.5; }
    .err { padding: 10px 14px; background: #fef2f2; border-left: 4px solid #dc2626; color: #991b1b; margin: 12px 0; }
    .note { color: #475569; font-size: .9rem; }
    .tbl { overflow-x: auto; margin: 12px 0; }
    h2 { font-size: 1.05rem; color: #4338ca; margin: 20px 0 4px; }
    th.lnk, td.lnk { background: #fef08a; font-weight: 700; }
    table { border-collapse: collapse; font-size: .78rem; white-space: nowrap; }
    th, td { border: 1px solid #cbd5e1; padding: 4px 8px; text-align: left; }
    th { background: #e0e7ff; color: #312e81; position: sticky; top: 0; }
    main { max-width: 98vw; }
  </style>
</head>
<body>
<main>
  <h1>Import thiết bị &amp; kế hoạch bảo dưỡng định kỳ - Đội Thử vỉa</h1>
  <p class="note">Nguồn: html/kehoach_baotri_baoduong_2026_doi_thuvia.html. Thiết bị &rarr; thietbi_iso (đơn vị Đội Thử vỉa); kế hoạch &rarr; ke_hoach_bao_duong_dinh_ky_iso (TP lưu thành TO, Đội TV &rarr; TV).
     Hãy bấm "Chạy thử" trước để xem kết quả.</p>

  <?php if ($error !== ''): ?><div class="err"><?= $h($error) ?></div><?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= $h($_SESSION['import_thuvia_csrf']) ?>">
    <label for="nam">Năm kế hoạch</label>
    <input type="number" id="nam" name="nam" value="<?= $h((string)($_POST['nam'] ?? 2026)) ?>">
    <label for="html_file">File HTML (chỉ cần nếu hosting chưa có thư mục html/)</label>
    <input type="file" id="html_file" name="html_file" accept=".html,.htm">
    <label for="madv_tv">Mã đơn vị Đội Thử vỉa (để trống = tự tra theo tên trong donvi_iso)</label>
    <input type="text" id="madv_tv" name="madv_tv" value="<?= $h((string)($_POST['madv_tv'] ?? '')) ?>">
    <p>
      <button class="preview" type="submit" name="mode" value="preview">Chạy thử</button>
      <button class="apply" type="submit" name="mode" value="apply"
              onclick="return confirm('Ghi dữ liệu vào DB? Bạn đã xem kết quả chạy thử chưa?');">Ghi vào DB</button>
    </p>
  </form>

  <?php if ($result !== null): ?>
    <pre><?= $h(implode("\n", $result['before'])) ?></pre>
    <p class="note">Liên kết giữa hai bảng: <b>ke_hoach_bao_duong_dinh_ky_iso.thietbi_id</b> = <b>thietbi_iso.stt</b> (hai cột được tô vàng). Thiết bị mới hiện "mới #STT" khi chạy thử, sẽ được thay bằng stt thật khi ghi.</p>
    <?php foreach ($result['tables'] as $name => $t): ?>
      <?php $linkCol = array_search($name === 'thietbi_iso' ? 'stt' : 'thietbi_id', $t['head'], true); ?>
      <h2><?= $h($name) ?> (<?= count($t['rows']) ?> dòng)</h2>
      <div class="tbl"><table>
        <thead><tr><?php foreach ($t['head'] as $i => $col): ?><th<?= $i === $linkCol ? ' class="lnk"' : '' ?>><?= $h($col) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
          <?php foreach ($t['rows'] as $row): ?>
            <tr><?php foreach ($row as $i => $cell): ?><td<?= $i === $linkCol ? ' class="lnk"' : '' ?>><?= $h((string)$cell) ?></td><?php endforeach; ?></tr>
          <?php endforeach; ?>
        </tbody>
      </table></div>
    <?php endforeach; ?>
    <pre><?= $h(implode("\n", $result['after'])) ?></pre>
  <?php endif; ?>
</main>
</body>
</html>
