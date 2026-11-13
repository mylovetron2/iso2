<?php
declare(strict_types=1);

/**
 * Import kế hoạch bảo trì/bảo dưỡng thiết bị (5 nhóm: điện, trạm tời, máy công cụ, máy nén khí, nâng hạ) từ bảng kế hoạch vào:
 *   1. thietbi_iso                        (danh mục thiết bị - trang thietbi.php)
 *   2. ke_hoach_bao_duong_dinh_ky_iso     (kế hoạch bảo dưỡng - trang kehoachbaoduongdinhky.php)
 *
 * Chạy trên hosting: đăng nhập admin rồi mở /iso2/import_kehoach_khi_2026.php
 *   - "Chạy thử": hiển thị kết quả, không ghi DB.
 *   - "Ghi vào DB": thực hiện import (một transaction).
 * Chạy bằng dòng lệnh: php import_kehoach_khi_2026.php [--apply] [--nam=2026]
 *
 * Quy tắc:
 *   - Cột "Phụ trợ" là đơn vị sở hữu thiết bị: madv của thiết bị lấy theo cột này
 *     (Đội Thử vỉa -> TV; Đội/BP ĐVL tổng hợp, Đội ĐVLTH -> ĐVLTH; Đội KTKT -> KTKT; Ban Vật tư Hậu cần -> PVT;
 *     Đội CRTTH - Chuẩn máy GK 200 -> KHI; để trống -> XSCTBĐVL).
 *   - Thiết bị: mavt = Số S/N trong bảng, somay = mavt, model = ''.
 *   - Kế hoạch: đơn vị chính luôn là XSCTBĐVL, đơn vị phụ = mã đơn vị ở cột Phụ trợ (trống nếu không có), nhomsc để trống.
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

const UNIT_NAME = 'XSCTBĐVL';
const MAIN_UNIT = 'XSCTBĐVL';
const CREATED_BY = 'import_kehoach_khi_2026';

/** Đổi nội dung cột "Phụ trợ" sang mã đơn vị (madv); để trống -> XSCTBĐVL. */
function auxToMadv(string $aux): string
{
    $key = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $aux)));
    if ($key === '') {
        return MAIN_UNIT;
    }
    $map = [
        'đội thử vỉa' => 'TV',
        'đội đvl tổng hợp' => 'ĐVLTH',
        'bp đvl tổng hợp' => 'ĐVLTH',
        'đội đvlth' => 'ĐVLTH',
        'đội ktkt' => 'KTKT',
        'ban vật tư hậu cần' => 'PVT',
        'đội crtth - chuẩn máy gk 200' => 'KHI',
    ];
    if (!isset($map[$key])) {
        throw new RuntimeException("Không có quy tắc đổi cột Phụ trợ \"$aux\" sang mã đơn vị.");
    }
    return $map[$key];
}

/**
 * Các dòng đọc từ bảng: [STT, tên thiết bị, Số S/N, các tháng đại diện cho quí được tô màu (1=Q1, 4=Q2, 7=Q3, 10=Q4), cột Phụ trợ]
 *
 * @return array<int, array{stt: string, ten: string, code: string, sn: string, months: int[], note: string, main: string, aux: string, madv: string}>
 */
function planData(): array
{
    $raw = [
        // 1. THIẾT BỊ & HỆ THỐNG ĐIỆN
        ['1.1', 'Máy mài 2 đá', 'MM2Đ-01', [7], ''],
        ['1.2', 'Máy mài 2 đá', 'MM2Đ-02', [7], ''],
        ['1.3', 'Máy mài 2 đá', 'MM2Đ-03', [7], ''],
        ['1.4', 'Máy cưa sắt', 'MCS-01', [7], ''],
        ['1.5', 'Máy cắt đá 350', 'MCĐ-01', [7], ''],
        ['1.6', 'Máy cắt Plasma', 'MCP-01', [7], ''],
        ['1.7', 'Máy hàn', 'ВД – 306', [4], ''],
        ['1.8', 'Máy hàn', 'HK-01', [4], ''],
        ['1.9', 'Máy hàn', 'WELDCOM', [4], ''],
        ['1.10', 'Máy bơm nước cao áp', 'BNCA-01', [10], ''],
        ['1.11', 'Lò gia nhiệt', 'WT-STG-01', [4], 'Đội Thử vỉa'],
        ['1.12', 'Lò gia nhiệt', 'WT-STG-03', [1], 'Đội Thử vỉa'],
        ['1.13', 'Lò gia nhiệt', 'WT-STG-04', [4], 'Đội Thử vỉa'],
        ['1.14', 'Máy xạc ắc qui', 'NAQ-02', [7], ''],
        ['1.15', 'Máy xạc ắc qui', 'NAQ-04', [7], ''],
        ['1.16', 'Hệ thống tiếp địa & chống sét XN Địa vật lý', 'TĐ-CS.ĐVL', [1], ''],
        ['1.17', 'Hệ thống cáp động lực và các tủ điện XN Địa vật lý', 'HTĐ.ĐVL', [1, 4, 7, 10], ''],
        ['1.18', 'Máy bóp ống thủy lực', 'BTL-01', [7], ''],
        ['1.19', 'Máy bóp ống thủy lực', 'FINN-POWER 32', [7], ''],
        ['1.20', 'Barie đóng cổng', 'BR-01', [10], ''],
        ['1.21', 'Barie đóng cổng', 'BR-02', [10], ''],
        ['1.22', 'Bồn điều áp', 'WT-STK-03', [4], 'Đội Thử vỉa'],
        // 2. TRẠM TỜI
        ['2.1', 'Computalog/ Cummins', 'WLU-10', [1], 'Đội ĐVL tổng hợp'],
        ['2.2', 'Computalog/ Caterpillar', 'WLU-01', [1], 'Đội KTKT'],
        ['2.3', 'Dynawinch III/ Perkins', 'WLU-22', [4], 'Đội ĐVL tổng hợp'],
        ['2.4', 'Computalog/ Cummins', 'WLU-12', [4], 'Đội ĐVL tổng hợp'],
        ['2.5', 'Asep Zone 2/Caterpillar', 'WLU-23', [4], 'Đội ĐVL tổng hợp'],
        ['2.6', 'Asep II/ Iveco', 'WLU-04', [7], 'Đội KTKT'],
        ['2.7', 'Dynawinch I/ Perkins', 'WLU-21', [7], 'Đội ĐVL tổng hợp'],
        ['2.8', 'Asep Zone 2/Caterpillar', 'WLU-24', [7], 'Đội KTKT'],
        ['2.9', 'Computalog/ Cummins', 'WLU-11', [7], 'Đội ĐVL tổng hợp'],
        ['2.10', 'Dynawinch II/ Perkins', 'WLU-15', [7], 'Đội KTKT'],
        ['2.11', 'Asep IV/Caterpillar C4.4', 'WLU-02', [7], 'Đội KTKT'],
        ['2.12', 'Asep I/ Iveco', 'WLU-03', [10], 'Đội KTKT'],
        ['2.13', 'Halliburton II/ Perkins', 'WLU-17', [10], 'Đội ĐVL tổng hợp'],
        ['2.14', 'Computalog/ Cummins', 'WLU-09', [10], 'BP ĐVL tổng hợp'],
        ['2.15', 'Asep III/ Iveco', 'WLU-05', [10], 'Đội KTKT'],
        ['2.16', 'Halliburton III/ Caterpillar', 'WLU-18', [10], 'Đội ĐVL tổng hợp'],
        ['2.17', 'Gulf Coast/Iveco', 'WLU-25', [10], 'Đội ĐVL tổng hợp'],
        ['2.18', 'Halliburton I/ Caterpillar', 'WLU-16', [10], 'Đội ĐVL tổng hợp'],
        // 3. MÁY CÔNG CỤ VÀ THIẾT BỊ KHÁC
        ['3.1', 'Máy tiện 16K20/Energo-7.5kw', '16K20', [1], ''],
        ['3.2', 'Bộ tời quấn cáp', 'SO-01, B-01', [1], ''],
        ['3.3', 'Máy tiện Ajax/Efacec-7.5kw', 'Ajax', [4], ''],
        ['3.4', 'Máy nén khí VSP03/Hatz', 'OH-VSP-03', [4], 'Đội ĐVLTH'],
        ['3.5', 'Máy nén khí tự hành/Cat', 'WT-CP-03', [4], 'Đội Thử vỉa'],
        ['3.6', 'Máy nén khí tự hành Twin Air/Cat', 'WT-CP-04', [4], 'Đội Thử vỉa'],
        ['3.7', 'Máy nén khí tự hành/Mercedes', 'WT-CP-01', [4], 'Đội Thử vỉa'],
        ['3.8', 'Wireline Mast/CAT (Power pack)', 'CH-MAST-P', [4], 'Đội KTKT'],
        ['3.9', 'Máy khoan đứng/ Energo - 4Kw', '2H135T', [4], ''],
        ['3.10', 'Bộ gia tải cáp địa vật lý (Capstan)', 'C-30', [4], ''],
        ['3.11', 'Máy chấn 60 tấn', 'MC-60', [7], ''],
        ['3.12', 'Máy phun sơn Graco', 'X70', [7], ''],
        ['3.13', 'Máy cưa sắt', 'YX4', [7], ''],
        ['3.14', 'Máy phay/ Energo - 3Kw', '675П', [7], ''],
        ['3.15', 'Máy nén khí tự hành/Mercedes', 'WT-CP-02', [7], 'Đội Thử vỉa'],
        ['3.16', 'Máy nén khí VSP02/Hatz', 'OH-VSP-02', [10], 'Đội ĐVLTH'],
        ['3.17', 'Hệ thống test nhiệt độ áp suất cao', 'LN T-P - 01', [7], ''],
        ['3.18', 'Wireline Mast/CAT (Power pack)', 'CH-MAST-P2', [7], 'Đội KTKT'],
        // 4. MÁY NÉN KHÍ DI ĐỘNG
        ['4.1', 'MÁY NÉN KHÍ PONYCON', 'CP-01/ ATK-CP-01', [1], 'Đội ĐVLTH'],
        ['4.2', 'MÁY NÉN KHÍ BOGE', 'CP-02/ ATK-CP-02&ATK-CP-02.1', [1], ''],
        ['4.3', 'MÁY NÉN KHÍ INGERSOLL RAND 120 GALLON', 'CP-05/ ATK-CP-05', [1], 'Đội Thử vỉa'],
        ['4.4', 'MÁY NÉN KHÍ INGERSOLL RAND 85L (070038BL)', 'CP-06/ ATK-CP-06', [1], ''],
        ['4.5', 'MÁY NÉN KHÍ INGERSOLL RAND 80L S1B1S', 'CP-14/ ATK-CP-14', [1], ''],
        ['4.6', 'MÁY NÉN KHÍ Fusheng 305L', 'CP-15/ ATK-CP-15', [1], ''],
        ['4.7', 'MÁY NÉN KHÍ Fusheng 305L', 'CP-16/ ATK-CP-16', [1], ''],
        ['4.8', 'MÁY NÉN KHÍ EV7508', 'CP-07/ ATK-CP-07', [7], 'Đội ĐVLTH'],
        ['4.9', 'MÁY NÉN KHÍ INGERSOLL RAND 85L (0810018BL)', 'CP-08/ ATK-CP-08', [7], ''],
        ['4.10', 'MÁY NÉN KHÍ FUSHENG VA-80(T0201114)', 'CP-09/ ATK-CP-09', [7], 'Đội KTKT'],
        ['4.11', 'MÁY NÉN KHÍ FUSHENG FVA – 30II (245L)', 'CP-10/ ATK-CP-10', [7], ''],
        ['4.12', 'MÁY NÉN KHÍ PORTER (2961763761)', 'CP-11/ ATK-CP-11', [7], ''],
        ['4.13', 'MÁY NÉN KHÍ PORTER (2967648501)', 'CP-12/ ATK-CP-12', [7], ''],
        ['4.14', 'MÁY NÉN KHÍ AIRCOM CPRD 12070 NS59SMT', 'CP-13/ATK-CP-13', [7], ''],
        // 5. CÁC THIẾT BỊ NÂNG HẠ
        ['5.1', 'Cầu trục khu vực thử nhiệt độ, áp suất cao', 'ĐVL-TD-01', [7], ''],
        ['5.2', 'Cầu trục khu vực thử nhiệt độ, áp suất cao', 'ĐVL-TD-02', [7], ''],
        ['5.3', 'Cầu trục khu vực SC&CC ĐT', 'ĐVL-TD-06', [7], ''],
        ['5.4', 'Cầu trục khu vực chống ăn mòn', 'ĐVL-TD-03', [7], ''],
        ['5.5', 'Cầu trục kho nguồn phóng xạ', 'ĐVL-TD-07', [7], 'Ban Vật tư Hậu cần'],
        ['5.6', 'Cầu trục kho nguồn phóng xạ', 'ĐVL-TD-08', [7], 'Ban Vật tư Hậu cần'],
        ['5.7', 'Pa lăng điện', 'ĐVL-TD-10', [7], ''],
        ['5.8', 'Cầu trục giếng chuẩn 200m', 'ĐVL-TD-11', [7], 'Đội CRTTH - Chuẩn máy GK 200'],
        ['5.9', 'Cầu trục giếng chuẩn đất đá', 'ĐVL-TD-12', [7], ''],
        ['5.10', 'Cầu trục 2 dầm 10T', 'ĐVL-TD-14', [7], ''],
        ['5.11', 'Cầu trục 2 dầm 10T', 'ĐVL-TD-15', [7], 'Đội Thử vỉa'],
        ['5.12', 'Cầu trục 2 dầm 05T', 'ĐVL-TD-16', [7], 'Đội Thử vỉa'],
        ['5.13', 'Cầu trục 2 dầm 3T', 'ĐVL-TD-17', [7], 'Ban Vật tư Hậu cần'],
        ['5.14', 'Cầu trục 2 dầm 5T', 'ĐVL-TD-18', [7], 'Ban Vật tư Hậu cần'],
        ['5.15', 'Cầu trục di động Wireline Mast', 'CH-WM-80Ft', [7], 'Đội KTKT'],
        ['5.16', 'Cầu trục di động Wireline Mast', 'CH-WM-70Ft', [7], 'Đội KTKT'],
    ];
    $rows = [];
    foreach ($raw as [$stt, $ten, $code, $months, $aux]) {
        $madv = auxToMadv($aux);
        $rows[] = [
            'stt' => $stt, 'ten' => $ten, 'code' => $code, 'sn' => '',
            'months' => $months, 'note' => '',
            'main' => MAIN_UNIT, 'aux' => $madv === MAIN_UNIT ? '' : $madv, 'madv' => $madv,
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

    $db = getDBConnection();
    $db->exec('SET NAMES latin1');

    $warnings = [];
    $checkUnit = $db->prepare('SELECT COUNT(*) FROM donvi_iso WHERE TRIM(madv) = ?');
    foreach (array_unique(array_column($rows, 'madv')) as $unit) {
        $checkUnit->execute([$unit]);
        if ((int)$checkUnit->fetchColumn() === 0) {
            $warnings[] = "madv \"$unit\" chưa có trong donvi_iso";
        }
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
    $out[] = "Số dòng: " . count($rows) . " | Năm kế hoạch: $nam | " . UNIT_NAME . " | madv thiết bị theo cột Phụ trợ | đơn vị chính: " . MAIN_UNIT;

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
            $madv = $r['madv'];
            $tenvt = $r['ten'];
            if ($r['sn'] !== '' && mb_stripos($tenvt, $r['sn']) === false) {
                $tenvt .= ' ' . $r['sn'];
            }
            $tenvt = fitLength($tenvt, $len['tenvt']);
            $somay = fitLength($r['sn'] !== '' ? $r['sn'] : ($r['code'] !== '' ? $r['code'] : 'N/A'), $len['somay']);
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
                || ($existing && ($existing['tenvt'] !== $tenvt || trim((string)$existing['madv']) !== $madv));
            if ($conflict) {
                // Trùng khóa với thiết bị khác (VD cùng tên với thiết bị của đơn vị khác) -> thêm hậu tố STT
                $mavt = ($len['mavt'] > 0 ? mb_strcut($base, 0, $len['mavt'] - strlen($suffix)) : $base) . $suffix;
                $existing = $find($mavt);
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
                $findPlans->execute([$nam, $stt]);
                $existingPlans = $findPlans->fetchAll();
            }

            // Mỗi quí là một kế hoạch riêng: [quí, tham số hiển thị, ghi chú kết quả]
            $planOut = [];
            $covered = [];
            foreach ($existingPlans as $p) {
                $planned = plannedQuarters($p);
                $shown = fn(array $row, ?int $q): array => array_merge(
                    $planBase, array_intersect_key($row, $planBase), $quiBlank, $q ? ["qui_$q" => (string)$row["qui_$q"]] : []
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
$totalRows = count(planData());
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
  <title>Import kế hoạch bảo dưỡng - Xưởng sửa chữa thiết bị ĐVL</title>
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
  <h1>Import thiết bị &amp; kế hoạch bảo dưỡng định kỳ - XSCTBĐVL</h1>
  <p class="note">Nguồn: <?= $totalRows ?> dòng từ bảng kế hoạch (thiết bị &amp; hệ thống điện, trạm tời, máy công cụ, máy nén khí di động, thiết bị nâng hạ).
     Thiết bị &rarr; thietbi_iso (madv theo cột Phụ trợ: Thử vỉa &rarr; TV, ĐVL tổng hợp &rarr; ĐVLTH, KTKT &rarr; KTKT, Ban Vật tư Hậu cần &rarr; PVT, CRTTH - Chuẩn máy GK 200 &rarr; KHI, trống &rarr; XSCTBĐVL);
     kế hoạch &rarr; ke_hoach_bao_duong_dinh_ky_iso (ĐV chính XSCTBĐVL, ĐV phụ theo cột Phụ trợ; TO được nhập theo quí). Nhập một dòng mới tại import_kehoach_khi_mot_dong.php.</p>

  <?php if ($error !== ''): ?><div class="err"><?= $h($error) ?></div><?php endif; ?>

  <form method="post">
    <input type="hidden" name="csrf" value="<?= $h($_SESSION['import_khi_csrf']) ?>">
    <label for="nam">Năm kế hoạch</label>
    <input type="number" id="nam" name="nam" value="<?= $h((string)($_POST['nam'] ?? 2026)) ?>">
    <p class="note">Mã đơn vị thiết bị lấy theo cột Phụ trợ của từng dòng.</p>
    <button class="preview" type="submit" name="mode" value="preview">Chạy thử <?= $totalRows ?> dòng</button>
    <button class="apply" type="submit" name="mode" value="apply"
            onclick="return confirm('Ghi <?= $totalRows ?> dòng vào DB? Bạn đã xem kết quả chạy thử chưa?');">Ghi <?= $totalRows ?> dòng vào DB</button>
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
