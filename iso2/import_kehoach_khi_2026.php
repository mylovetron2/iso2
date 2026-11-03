<?php
declare(strict_types=1);

/**
 * Import 21 dòng kế hoạch bảo trì/bảo dưỡng Đội Carota Khí từ hình bảng kế hoạch vào:
 *   1. thietbi_iso                        (danh mục thiết bị - trang thietbi.php)
 *   2. ke_hoach_bao_duong_dinh_ky_iso     (kế hoạch bảo dưỡng - trang kehoachbaoduongdinhky.php)
 *
 * Chạy trên hosting: đăng nhập admin rồi mở /iso2/import_kehoach_khi_2026.php
 *   - "Chạy thử": hiển thị kết quả, không ghi DB.
 *   - "Ghi vào DB": thực hiện import (một transaction).
 * Chạy bằng dòng lệnh: php import_kehoach_khi_2026.php [--apply] [--nam=2026]
 *
 * Quy tắc:
 *   - Tất cả thiết bị được gán đơn vị quản lý madv = KTKT (thiết bị import cũ madv = KHI sẽ được cập nhật khi chạy lại).
 *   - Thiết bị: mavt = Ký mã hiệu (thiếu thì S/N, thiếu nữa thì tên), somay = S/N (thiếu thì lấy Ký mã hiệu, thiếu nữa thì 'N/A'), model = ''.
 *   - Kế hoạch: đơn vị chính/phụ lấy theo từng dòng trong bảng, nhomsc để trống.
 *     Quí được suy ra từ tháng được tô màu (T1-3 -> qui_1, T4-6 -> qui_2, T7-9 -> qui_3, T10-12 -> qui_4),
 *     mỗi quí có ít nhất một tháng được đánh dấu là một dòng kế hoạch riêng (lưu 'TO'), nên thiết bị có 2 đợt trong
 *     năm sẽ có 2 dòng. Dòng "Kiểm định khi cần thiết" không có quí.
 *   - Chạy lại an toàn: thiết bị đã có thì dùng lại, quí đã có dòng kế hoạch (cùng năm + thiết bị) thì bỏ qua.
 *     Dòng cũ do chính import này tạo mà gộp nhiều quí sẽ được tách thành mỗi quí một dòng.
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

const UNIT_NAME = 'Đội Carota Khí';
const DEFAULT_MADV = 'KTKT';
const LEGACY_MADV = 'KHI';
const CREATED_BY = 'import_kehoach_khi_2026';

/**
 * 21 dòng đọc từ hình: [STT, tên thiết bị, ký mã hiệu, số máy, các tháng được tô màu, ghi chú, ĐV chính, ĐV phụ]
 *
 * @return array<int, array{stt: string, ten: string, code: string, sn: string, months: int[], note: string, main: string, aux: string}>
 */
function planData(): array
{
    $raw = [
        // 2. THIẾT BỊ KHỐNG CHẾ ÁP SUẤT
        ['2.1', 'TBMG', 'CH-PCE-02', '', [4], '', 'XSCTBĐVL', 'KTKT'],
        ['2.2', 'TBMG', 'CH-PCE-07', '', [10], '', 'XSCTBĐVL', 'KTKT'],
        ['2.3', 'Bơm mỡ TBMG', 'CH-GIU-05', '', [7], '', 'XSCTBĐVL', 'KTKT'],
        ['2.4', 'Bộ khống chế áp suất 4" LW Siva', '4" Lightweight Siva', '', [1], 'Kiểm tra MPI test sau 5 năm sử dụng', 'Dịch vụ', 'KTKT'],
        // 3. VỎ TRẠM, KHUNG DẦM
        ['3.1', 'Vỏ trạm đo', 'CH-LGU-02', '', [1], '', 'KTKT', 'XSCTBĐVL'],
        ['3.2', 'Vỏ trạm đo', 'CH-LGU-07', '', [7], '', 'KTKT', 'XSCTBĐVL'],
        ['3.3', 'Vỏ trạm đo', 'CH-LGU-08', '', [10], '', 'KTKT', 'XSCTBĐVL'],
        ['3.4', 'Vỏ trạm đo', 'CH-LGU-10', '', [7], '', 'KTKT', 'XSCTBĐVL'],
        ['3.5', 'Vỏ trạm đo', 'CH-LGU-12', '', [1], '', 'KTKT', 'XSCTBĐVL'],
        // 4. VỎ TRẠM TỜI
        ['4.1', 'Vỏ trạm tời', 'WLU-04', '', [7], '', 'KTKT', 'XSCTBĐVL'],
        ['4.2', 'Vỏ trạm tời', 'WLU-05', '', [10], '', 'KTKT', 'XSCTBĐVL'],
        ['4.3', 'Vỏ trạm tời', 'WLU-15', '', [1], '', 'KTKT', 'XSCTBĐVL'],
        ['4.4', 'Vỏ phụ tùng tời', 'CH-DH-03', '', [1], '', 'KTKT', 'XSCTBĐVL'],
        // 5. GIÁ TANG CÁP
        ['5.1', 'Giá tang cáp', 'CH-DR-01', '', [4], '', 'KTKT', 'XSCTBĐVL'],
        ['5.2', 'Giá tang cáp', 'CH-DR-03', '', [1], '', 'KTKT', 'XSCTBĐVL'],
        ['5.3', 'Giá tang cáp', 'CH-DR-06', '', [1], '', 'KTKT', 'XSCTBĐVL'],
        // 6. CONTAINER THIẾT BỊ
        ['6.1', 'Container', 'CH-CON-02', '', [1], '', 'KTKT', 'XSCTBĐVL'],
        ['6.2', 'Container', 'CH-CON-04', '', [4], '', 'KTKT', 'XSCTBĐVL'],
        ['6.3', 'Container', 'CH-CON-07', '', [1], '', 'KTKT', 'XSCTBĐVL'],
        ['6.4', 'Container', 'CH-CON-11', '', [10], '', 'KTKT', 'XSCTBĐVL'],
        ['6.5', 'Container', 'CH-CON-16', '', [7], '', 'KTKT', 'XSCTBĐVL'],
    ];

    $rows = [];
    foreach ($raw as $item) {
        [$stt, $ten, $code, $sn, $months, $note] = $item;
        $rows[] = [
            'stt' => $stt, 'ten' => $ten, 'code' => $code, 'sn' => $sn,
            'months' => $months, 'note' => $note,
            'main' => $item[6] ?? '', 'aux' => $item[7] ?? '',
        ];
    }
    return $rows;
}

/** Suy ra các quí (1-4) từ danh sách tháng. */
function quartersFromMonths(array $months): array
{
    $quarters = [];
    foreach ($months as $m) {
        if ($m >= 1 && $m <= 12) {
            $quarters[(int)ceil($m / 3)] = true;
        }
    }
    $quarters = array_keys($quarters);
    sort($quarters);
    return $quarters;
}

/** Các quí (1-4) đang có kế hoạch trong một dòng kế hoạch. */
function plannedQuarters(array $plan): array
{
    $quarters = [];
    for ($q = 1; $q <= 4; $q++) {
        if (trim((string)($plan["qui_$q"] ?? '')) !== '') {
            $quarters[] = $q;
        }
    }
    return $quarters;
}

function fitLength(string $value, int $max): string
{
    return ($max > 0 && strlen($value) > $max) ? mb_strcut($value, 0, $max) : $value;
}

// ---------------------------------------------------------------- db helpers
function columnLength(PDO $db, string $table, string $column): int
{
    $stmt = $db->prepare('SELECT CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS
                          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->execute([$table, $column]);
    return (int)($stmt->fetchColumn() ?: 0);
}

// ---------------------------------------------------------------- import
/**
 * @param array{apply: bool, nam: int} $opts
 * @return array{before: string[], tables: array<string, array{head: string[], rows: array<int, array>}>, after: string[]}
 */
function runImport(array $opts): array
{
    $out = [];
    $tables = [];
    $rows = planData();
    $nam = (int)$opts['nam'];
    $apply = (bool)$opts['apply'];
    $madv = DEFAULT_MADV;

    $db = getDBConnection();
    $db->exec('SET NAMES latin1');

    $warnings = [];
    $checkUnit = $db->prepare('SELECT COUNT(*) FROM donvi_iso WHERE TRIM(madv) = ?');
    $checkUnit->execute([$madv]);
    if ((int)$checkUnit->fetchColumn() === 0) {
        $warnings[] = "madv \"$madv\" chưa có trong donvi_iso";
    }

    $len = [];
    foreach (['mavt', 'tenvt', 'somay', 'model', 'mamay'] as $c) {
        $len[$c] = columnLength($db, 'thietbi_iso', $c);
    }
    $planLen = [
        'ten_thietbi' => columnLength($db, 'ke_hoach_bao_duong_dinh_ky_iso', 'ten_thietbi'),
        'so_serial' => columnLength($db, 'ke_hoach_bao_duong_dinh_ky_iso', 'so_serial'),
        'ghi_chu' => columnLength($db, 'ke_hoach_bao_duong_dinh_ky_iso', 'ghi_chu'),
    ];

    $out[] = ($apply ? '=== GHI DỮ LIỆU ===' : '=== CHẠY THỬ (không ghi DB) ===');
    $out[] = "Số dòng: " . count($rows) . " | Năm kế hoạch: $nam | " . UNIT_NAME . " | madv thiết bị: $madv | đơn vị kế hoạch theo từng dòng";

    $findDevice = $db->prepare('SELECT stt, tenvt, madv FROM thietbi_iso WHERE mavt = ? AND somay = ? AND model = ?');
    $insertDevice = $db->prepare(
        'INSERT INTO thietbi_iso (mavt, tenvt, somay, model, homay, dienap, thongtincb, loaidau, mucdau, madv, bdtime, ngayktsd, tlkt, hosomay, mamay)
         VALUES (:mavt, :tenvt, :somay, :model, :homay, :dienap, :thongtincb, :loaidau, :mucdau, :madv, :bdtime, :ngayktsd, :tlkt, :hosomay, :mamay)'
    );
    $findPlans = $db->prepare('SELECT * FROM ke_hoach_bao_duong_dinh_ky_iso WHERE nam = ? AND thietbi_id = ? ORDER BY id');
    $insertPlan = $db->prepare(
        'INSERT INTO ke_hoach_bao_duong_dinh_ky_iso
         (thietbi_id, nam, ten_thietbi, so_serial, nhomsc, qui_1, qui_2, qui_3, qui_4, donvi_lam_chinh, donvi_lam_phu, ghi_chu, created_by)
         VALUES (:thietbi_id, :nam, :ten_thietbi, :so_serial, :nhomsc, :qui_1, :qui_2, :qui_3, :qui_4, :donvi_lam_chinh, :donvi_lam_phu, :ghi_chu, :created_by)'
    );

    $stat = ['device_new' => 0, 'device_reuse' => 0, 'plan_new' => 0, 'plan_skip' => 0, 'plan_split' => 0];
    $usedKeys = [];

    $db->beginTransaction();
    try {
        foreach ($rows as $r) {
            $tenvt = $r['ten'];
            if ($r['sn'] !== '' && mb_stripos($tenvt, $r['sn']) === false) {
                $tenvt .= ' ' . $r['sn'];
            }
            $tenvt = fitLength($tenvt, $len['tenvt']);
            $legacySomay = $r['sn'] === '';
            $somay = fitLength($r['sn'] !== '' ? $r['sn'] : ($r['code'] !== '' ? $r['code'] : 'N/A'), $len['somay']);
            $model = '';

            $base = $r['code'] !== '' ? $r['code'] : ($r['sn'] !== '' ? $r['sn'] : $r['ten']);
            $suffix = '-' . $r['stt'];
            $mavt = strlen($base) > $len['mavt'] && $len['mavt'] > 0
                ? mb_strcut($base, 0, $len['mavt'] - strlen($suffix)) . $suffix
                : $base;

            $find = function (string $key) use ($findDevice, $somay, $model, $legacySomay, $madv) {
                $findDevice->execute([$key, $somay, $model]);
                $row = $findDevice->fetch();
                if (!$row && $legacySomay) {
                    // Bản import trước để trống số máy -> tìm lại để cập nhật
                    $findDevice->execute([$key, '', $model]);
                    $row = $findDevice->fetch();
                    if ($row && in_array(trim((string)$row['madv']), [$madv, LEGACY_MADV], true)) {
                        $row['legacy'] = true;
                    } else {
                        $row = false;
                    }
                }
                return $row;
            };
            $existing = $find($mavt);
            $migrateLegacyUnit = $existing
                && $madv === DEFAULT_MADV
                && trim((string)$existing['madv']) === LEGACY_MADV
                && $existing['tenvt'] === $tenvt;
            $conflict = (isset($usedKeys["$mavt|$somay|$model"]) && $usedKeys["$mavt|$somay|$model"] !== $tenvt)
                || ($existing && ($existing['tenvt'] !== $tenvt
                    || (trim((string)$existing['madv']) !== $madv && !$migrateLegacyUnit)));
            if ($conflict) {
                // Trùng khóa với thiết bị khác (VD cùng tên với thiết bị của đơn vị khác) -> thêm hậu tố STT
                $mavt = ($len['mavt'] > 0 ? mb_strcut($base, 0, $len['mavt'] - strlen($suffix)) : $base) . $suffix;
                $existing = $find($mavt);
                $migrateLegacyUnit = $existing
                    && $madv === DEFAULT_MADV
                    && trim((string)$existing['madv']) === LEGACY_MADV
                    && $existing['tenvt'] === $tenvt;
                $warnings[] = "{$r['stt']}: trùng khóa thiết bị, dùng mavt = $mavt";
            }
            $usedKeys["$mavt|$somay|$model"] = $tenvt;

            $mamay = fitLength(implode('-', array_filter([$mavt, $model, $somay !== $mavt && $somay !== 'N/A' ? $somay : ''], fn($p) => $p !== '')), $len['mamay']);
            $deviceParams = [
                'mavt' => $mavt, 'tenvt' => $tenvt, 'somay' => $somay, 'model' => $model,
                'homay' => '', 'dienap' => '', 'thongtincb' => '', 'loaidau' => '', 'mucdau' => '',
                'madv' => $madv, 'bdtime' => 0, 'ngayktsd' => date('Y-m-d'),
                'tlkt' => '', 'hosomay' => '', 'mamay' => $mamay,
            ];

            $legacy = !empty($existing['legacy']);
            if ($existing) {
                $stt = (int)$existing['stt'];
                $stat['device_reuse']++;
                if ($legacy || $migrateLegacyUnit) {
                    if ($apply) {
                        $db->prepare('UPDATE thietbi_iso SET somay = ?, mamay = ?, madv = ? WHERE stt = ?')
                            ->execute([$somay, $mamay, $madv, $stt]);
                    }
                    $deviceNote = $migrateLegacyUnit
                        ? "cập nhật madv = $madv (stt=$stt)"
                        : "cập nhật somay = $somay (stt=$stt)";
                } else {
                    $deviceNote = "dùng lại (stt=$stt), không ghi";
                }
            } else {
                $stt = 0;
                if ($apply) {
                    $insertDevice->execute(array_combine(array_map(fn($k) => ":$k", array_keys($deviceParams)), $deviceParams));
                    $stt = (int)$db->lastInsertId();
                }
                $stat['device_new']++;
                $deviceNote = $apply ? "thêm mới (stt=$stt)" : 'sẽ thêm mới';
            }

            $quiBlank = ['qui_1' => '', 'qui_2' => '', 'qui_3' => '', 'qui_4' => ''];
            $planBase = array_merge([
                'thietbi_id' => $stt, 'nam' => $nam,
                'ten_thietbi' => fitLength($tenvt, $planLen['ten_thietbi']),
                'so_serial' => fitLength($somay, $planLen['so_serial']),
                'nhomsc' => '',
            ], $quiBlank, [
                'donvi_lam_chinh' => $r['main'] !== '' ? $r['main'] : $madv,
                'donvi_lam_phu' => $r['aux'],
                'ghi_chu' => fitLength($r['note'], $planLen['ghi_chu']), 'created_by' => CREATED_BY,
            ]);
            $bind = fn(array $params): array => array_combine(array_map(fn($k) => ":$k", array_keys($params)), $params);

            $existingPlans = [];
            if ($stt > 0) {
                if ($legacy && $apply) {
                    $db->prepare('UPDATE ke_hoach_bao_duong_dinh_ky_iso SET so_serial = ? WHERE nam = ? AND thietbi_id = ?')
                        ->execute([$planBase['so_serial'], $nam, $stt]);
                }
                $findPlans->execute([$nam, $stt]);
                $existingPlans = $findPlans->fetchAll();
            }

            // Mỗi quí là một kế hoạch riêng: [quí, tham số hiển thị, ghi chú kết quả]
            $planOut = [];
            $covered = [];
            foreach ($existingPlans as $p) {
                $planned = plannedQuarters($p);
                $shown = fn(array $row, ?int $q): array => array_merge(
                    $planBase, array_intersect_key($row, $planBase), $quiBlank, $q ? ["qui_$q" => (string)$row["qui_$q"]] : [],
                    $legacy ? ['so_serial' => $planBase['so_serial']] : []
                );
                if (count($planned) <= 1) {
                    foreach ($planned as $q) {
                        $covered[$q] = true;
                    }
                    $stat['plan_skip']++;
                    $planOut[] = [$planned[0] ?? 0, $shown($p, $planned[0] ?? null), 'đã có trong kế hoạch, bỏ qua'];
                    continue;
                }
                if ($p['created_by'] !== CREATED_BY) {
                    foreach ($planned as $q) {
                        $covered[$q] = true;
                    }
                    $warnings[] = "{$r['stt']}: kế hoạch id={$p['id']} có nhiều quí nhưng không do import này tạo, không tách";
                    continue;
                }

                // Dòng import cũ gộp nhiều quí -> giữ quí đã hoàn tất (hoặc quí nhỏ nhất), tách các quí còn lại thành dòng mới
                $flagged = array_values(array_filter($planned, fn($q) => !empty($p["qui_{$q}_hoantat"])));
                $keep = $flagged[0] ?? $planned[0];
                $covered[$keep] = true;
                $stat['plan_skip']++;
                $planOut[] = [$keep, $shown($p, $keep), "giữ quí $keep (id={$p['id']}), tách các quí còn lại"];
                foreach ($planned as $q) {
                    if ($q === $keep) {
                        continue;
                    }
                    $covered[$q] = true;
                    $split = array_merge($shown($p, $q), ['created_by' => CREATED_BY]);
                    if ($apply) {
                        $db->prepare("UPDATE ke_hoach_bao_duong_dinh_ky_iso SET qui_$q = '', qui_{$q}_hoantat = 0 WHERE id = ?")->execute([$p['id']]);
                        $insertPlan->execute($bind($split));
                        if (!empty($p["qui_{$q}_hoantat"])) {
                            $db->prepare("UPDATE ke_hoach_bao_duong_dinh_ky_iso SET qui_{$q}_hoantat = 1 WHERE id = ?")->execute([(int)$db->lastInsertId()]);
                        }
                    }
                    $stat['plan_split']++;
                    $planOut[] = [$q, $split, $apply ? "đã tách từ id={$p['id']}" : "sẽ tách từ id={$p['id']}"];
                }
            }

            $wanted = quartersFromMonths($r['months']);
            if ($wanted === [] && $existingPlans === []) {
                $wanted = [0];
            }
            foreach ($wanted as $q) {
                if ($q > 0 && isset($covered[$q])) {
                    continue;
                }
                $params = $q > 0 ? array_merge($planBase, ["qui_$q" => 'TO']) : $planBase;
                if ($apply) {
                    $insertPlan->execute($bind($params));
                }
                $stat['plan_new']++;
                $planOut[] = [$q, $params, $apply ? 'đã thêm' : 'sẽ thêm mới'];
            }

            // Khóa liên kết: ke_hoach_bao_duong_dinh_ky_iso.thietbi_id = thietbi_iso.stt (thiết bị mới chưa có stt khi chạy thử)
            $link = $stt > 0 ? (string)$stt : 'mới #' . $r['stt'];
            $tables['thietbi_iso']['rows'][] = array_merge([$r['stt'], $link], array_values($deviceParams), [$deviceNote]);
            $tables['thietbi_iso']['head'] ??= array_merge(['STT (bảng)', 'stt'], array_keys($deviceParams), ['Kết quả']);
            foreach ($planOut as [$q, $params, $note]) {
                $params['thietbi_id'] = $link;
                $months = array_filter($r['months'], fn($m) => $q > 0 && (int)ceil($m / 3) === $q);
                $tables['ke_hoach_bao_duong_dinh_ky_iso']['rows'][] = array_merge(
                    [$r['stt'], $months ? implode(',', $months) : '-'], array_values($params), [$note]
                );
                $tables['ke_hoach_bao_duong_dinh_ky_iso']['head'] ??= array_merge(['STT (bảng)', 'Tháng'], array_keys($params), ['Kết quả']);
            }
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
        . " | kế hoạch mới: {$stat['plan_new']}, tách: {$stat['plan_split']}, bỏ qua: {$stat['plan_skip']}";
    foreach ($warnings as $w) {
        $after[] = "Cảnh báo - $w";
    }
    $after[] = $apply ? 'ĐÃ GHI VÀO DB. Hãy xóa file import_kehoach_khi_2026.php khỏi hosting.' : 'Chưa ghi gì vào DB.';
    return ['before' => $out, 'tables' => $tables, 'after' => $after];
}

// ---------------------------------------------------------------- CLI
if ($isCli) {
    $opts = ['apply' => false, 'nam' => 2026];
    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--apply') {
            $opts['apply'] = true;
        } elseif (preg_match('/^--nam=(.+)$/', $arg, $m)) {
            $opts['nam'] = (int)$m[1];
        } else {
            fwrite(STDERR, "Tham số không hợp lệ: $arg\n");
            exit(2);
        }
    }
    try {
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
if (empty($_SESSION['import_khi_csrf'])) {
    $_SESSION['import_khi_csrf'] = bin2hex(random_bytes(16));
}

$result = null;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!hash_equals($_SESSION['import_khi_csrf'], (string)($_POST['csrf'] ?? ''))) {
            throw new RuntimeException('Phiên không hợp lệ, hãy tải lại trang.');
        }
        set_time_limit(180);

        $nam = (int)($_POST['nam'] ?? 2026);
        if ($nam < 2000 || $nam > 2100) {
            throw new RuntimeException('Năm không hợp lệ.');
        }
        $mode = (string)($_POST['mode'] ?? '');
        $result = runImport([
            'apply' => $mode === 'apply',
            'nam' => $nam,
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
  <title>Import kế hoạch bảo dưỡng - Đội Carota Khí</title>
  <style>
    body { font-family: "Segoe UI", Arial, sans-serif; background: #f1f5f9; color: #1e293b; margin: 0; padding: 24px 16px; }
    main { max-width: 98vw; margin: 0 auto; background: #fff; padding: 20px 24px; border-radius: 10px; border: 1px solid #e2e8f0; }
    h1 { margin-top: 0; font-size: 1.3rem; color: #312e81; }
    label { display: block; margin: 12px 0 4px; font-weight: 600; }
    input[type=number], input[type=text] { padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; }
    button { padding: 8px 18px; border: 0; border-radius: 6px; color: #fff; font-weight: 600; cursor: pointer; margin-right: 8px; }
    .preview { background: #4338ca; } .apply { background: #dc2626; }
    fieldset { margin: 20px 0; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 16px 16px; }
    pre { background: #0f172a; color: #e2e8f0; padding: 14px; border-radius: 8px; overflow-x: auto; font-size: .8rem; line-height: 1.5; }
    .err { padding: 10px 14px; background: #fef2f2; border-left: 4px solid #dc2626; color: #991b1b; margin: 12px 0; }
    .note { color: #475569; font-size: .9rem; }
    .tbl { overflow-x: auto; margin: 12px 0; }
    h2 { font-size: 1.05rem; color: #4338ca; margin: 20px 0 4px; }
    th.lnk, td.lnk { background: #fef08a; font-weight: 700; }
    table { border-collapse: collapse; font-size: .78rem; white-space: nowrap; }
    th, td { border: 1px solid #cbd5e1; padding: 4px 8px; text-align: left; }
    th { background: #e0e7ff; color: #312e81; position: sticky; top: 0; }
  </style>
</head>
<body>
<main>
  <h1>Import thiết bị &amp; kế hoạch bảo dưỡng định kỳ - Đội Carota Khí</h1>
  <p class="note">Nguồn: 17 dòng đã có và 4 dòng bổ sung từ bảng kế hoạch Đội Carota Khí. Thiết bị &rarr; thietbi_iso (madv KTKT); kế hoạch &rarr; ke_hoach_bao_duong_dinh_ky_iso
     (ĐV chính/phụ theo từng dòng; TP được nhập thành TO). Khi ghi lại, thiết bị trùng khớp đã nhập trước đó với madv KHI sẽ được chuyển sang KTKT. Nhập một dòng mới tại import_kehoach_khi_mot_dong.php.</p>

  <?php if ($error !== ''): ?><div class="err"><?= $h($error) ?></div><?php endif; ?>

  <form method="post">
    <input type="hidden" name="csrf" value="<?= $h($_SESSION['import_khi_csrf']) ?>">
    <label for="nam">Năm kế hoạch</label>
    <input type="number" id="nam" name="nam" value="<?= $h((string)($_POST['nam'] ?? 2026)) ?>">
    <p class="note">Mã đơn vị thiết bị cố định: <b><?= $h(DEFAULT_MADV) ?></b></p>
    <button class="preview" type="submit" name="mode" value="preview">Chạy thử 21 dòng</button>
    <button class="apply" type="submit" name="mode" value="apply"
            onclick="return confirm('Ghi 21 dòng vào DB? Bạn đã xem kết quả chạy thử chưa?');">Ghi 21 dòng vào DB</button>
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
