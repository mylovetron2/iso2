<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

requireAuth();

$from = isset($_GET['from']) && $_GET['from'] !== '' ? $_GET['from'] : date('Y-m-01');
$to = isset($_GET['to']) && $_GET['to'] !== '' ? $_GET['to'] : date('Y-m-t');
$benYeuCau = isset($_GET['benyeucau']) ? strtoupper(trim((string)$_GET['benyeucau'])) : '';
if (!in_array($benYeuCau, ['TH', 'CNC'], true)) {
    $benYeuCau = '';
}

$fromDate = DateTime::createFromFormat('Y-m-d', $from);
$toDate = DateTime::createFromFormat('Y-m-d', $to);

if (!$fromDate || !$toDate) {
    $fromDate = new DateTime(date('Y-m-01'));
    $toDate = new DateTime(date('Y-m-t'));
}

if ($fromDate > $toDate) {
    [$fromDate, $toDate] = [$toDate, $fromDate];
}

$from = $fromDate->format('Y-m-d');
$to = $toDate->format('Y-m-d');

$fromStart = $fromDate->format('Y-m-d') . ' 00:00:00';
$toEnd = $toDate->format('Y-m-d') . ' 23:59:59';

$legacyFrom = $fromDate->format('d/m/Y');
$legacyTo = $toDate->format('d/m/Y');

$rows = [];
$hckdRows = [];
$errorMessage = '';

try {
    $db = getDBConnection();

    $excludeTamDungSql = '';
    $checkTamDungTable = $db->query("SHOW TABLES LIKE 'hososcbd_tamdung'");
    if ($checkTamDungTable && $checkTamDungTable->fetch()) {
        $excludeTamDungSql = "
        AND h.hoso NOT IN (
            SELECT t.hoso
            FROM hososcbd_tamdung t
            WHERE t.trangthai = 'dang_tam_dung'
            AND t.id IN (
                SELECT MAX(t2.id)
                FROM hososcbd_tamdung t2
                GROUP BY t2.hoso
            )
        )";
    }

    $madvFilterSql = '';
    if ($benYeuCau !== '') {
        $madvFilterSql = " AND h.madv = :madvFilter ";
    }

    $sql = "
        SELECT
            h.maql,
            h.hoso,
            h.mavt,
            h.somay,
            h.cv,
            h.madv,
            h.honghoc,
            h.ttktafter,
            h.ghichufinal,
            h.model,
            DATE_FORMAT(h.ngayth, '%d/%m/%Y') AS ngayth_fmt,
            DATE_FORMAT(h.ngaykt, '%d/%m/%Y') AS ngaykt_fmt,
            COALESCE(tb.tenvt, '') AS tenvt
        FROM hososcbd_iso h
        LEFT JOIN thietbi_iso tb
            ON tb.mavt = h.mavt
            AND tb.somay = h.somay
            AND tb.model = h.model
        WHERE (
            h.ngaykt BETWEEN :fromStart1 AND :toEnd1
            OR h.ngayth BETWEEN :fromStart2 AND :toEnd2
        )
        {$madvFilterSql}
        {$excludeTamDungSql}
        ORDER BY h.maql DESC, h.hoso DESC
    ";

    $stmt = $db->prepare($sql);
    $params = [
        ':fromStart1' => $fromStart,
        ':toEnd1' => $toEnd,
        ':fromStart2' => $fromStart,
        ':toEnd2' => $toEnd,
    ];

    if ($benYeuCau !== '') {
        $params[':madvFilter'] = $benYeuCau;
    }

    $stmt->execute($params);

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmtWorkers = $db->prepare(
        "SELECT hoten, COALESCE(giolv, 0) AS giolv FROM ngthuchien_iso WHERE mahoso = :hoso"
    );

    $formatWorker = static function (string $fullName): string {
        $fullName = trim($fullName);
        if ($fullName === '') {
            return '';
        }

        $parts = preg_split('/\s+/u', $fullName);
        if (!$parts || count($parts) === 0) {
            return $fullName;
        }

        $last = mb_convert_case((string)end($parts), MB_CASE_TITLE, 'UTF-8');

        if ($fullName === 'VŨ ANH ĐỨC') {
            return 'A.Đức';
        }

        if ($fullName === 'ĐOÀN MINH ĐỨC') {
            return 'M.Đức';
        }

        return $last;
    };

    $normalizeText = static function ($value): string {
        $text = (string)($value ?? '');
        if ($text === '') {
            return '';
        }

        // Convert HTML entities (e.g. &iacute;) and remove markup (e.g. <p>...</p>).
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strip_tags($text);
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim((string)$text);
    };

    foreach ($result as $item) {
        $stmtWorkers->execute([':hoso' => $item['hoso']]);
        $workerRows = $stmtWorkers->fetchAll(PDO::FETCH_ASSOC);

        $workerNames = [];
        $hours = 0.0;

        foreach ($workerRows as $worker) {
            $name = $formatWorker((string)($worker['hoten'] ?? ''));
            if ($name !== '') {
                $workerNames[] = $name;
            }
            $hours += (float)($worker['giolv'] ?? 0);
        }

        $statusAfter = $normalizeText($item['ttktafter'] ?? '');
        if ($statusAfter === '') {
            $statusAfter = 'Đang sửa chữa';
        }
        if ($statusAfter === 'Hỏng') {
            $statusAfter = 'Hỏng - Không khắc phục được';
        }
        if ($statusAfter === 'Chưa kết luận') {
            $statusAfter = $normalizeText($item['ghichufinal'] ?? '');
        }

        $ngayKt = (string)($item['ngaykt_fmt'] ?? '');
        if ($ngayKt === '' || $ngayKt === '00/00/0000') {
            $ngayKt = 'Đang TH';
        }

        $rows[] = [
            'maql' => $normalizeText($item['maql'] ?? ''),
            'hoso' => $normalizeText($item['hoso'] ?? ''),
            'mavt' => $normalizeText($item['mavt'] ?? ''),
            'tenvt' => $normalizeText($item['tenvt'] ?? ''),
            'somay' => $normalizeText($item['somay'] ?? ''),
            'cv' => $normalizeText($item['cv'] ?? ''),
            'ngayth' => $normalizeText($item['ngayth_fmt'] ?? ''),
            'ngaykt' => $ngayKt,
            'nhanvien' => implode(', ', array_values(array_unique($workerNames))),
            'ttktafter' => $statusAfter,
            'ttktbefore' => ($normalizeText($item['cv'] ?? '') === 'SC') ? $normalizeText($item['honghoc'] ?? '') : '',
            'madv' => $normalizeText($item['madv'] ?? ''),
            'ghichu' => $normalizeText($item['ghichufinal'] ?? ''),
            'sogio' => $hours,
        ];
    }

    $sqlHckd = "
        SELECT
            hk.sohs,
            hk.tenmay,
            hk.congviec,
            DATE_FORMAT(hk.ngayhc, '%d/%m/%Y') AS ngayhc_fmt,
            hk.nhanvien,
            hk.noithuchien,
            hk.ttkt,
            tb_map.tenviettat,
            tb_map.somay,
            tb_map.bophansh,
            tb_map.chusohuu
        FROM hosohckd_iso hk
        LEFT JOIN thietbihckd_iso tb_map ON tb_map.stt = COALESCE(
            NULLIF(hk.thietbi_stt, 0),
            (
                SELECT t2.stt
                FROM thietbihckd_iso t2
                WHERE t2.mavattu = hk.tenmay
                   OR t2.somay = hk.tenmay
                   OR (
                        (
                            REPLACE(REPLACE(REPLACE(UPPER(t2.mavattu), ' ', ''), '-', ''), '_', '') = REPLACE(REPLACE(REPLACE(UPPER(hk.tenmay), ' ', ''), '-', ''), '_', '')
                            OR REPLACE(REPLACE(REPLACE(UPPER(t2.somay), ' ', ''), '-', ''), '_', '') = REPLACE(REPLACE(REPLACE(UPPER(hk.tenmay), ' ', ''), '-', ''), '_', '')
                        )
                        AND (
                            SELECT COUNT(*)
                            FROM thietbihckd_iso tx
                            WHERE REPLACE(REPLACE(REPLACE(UPPER(tx.mavattu), ' ', ''), '-', ''), '_', '') = REPLACE(REPLACE(REPLACE(UPPER(hk.tenmay), ' ', ''), '-', ''), '_', '')
                               OR REPLACE(REPLACE(REPLACE(UPPER(tx.somay), ' ', ''), '-', ''), '_', '') = REPLACE(REPLACE(REPLACE(UPPER(hk.tenmay), ' ', ''), '-', ''), '_', '')
                        ) = 1
                   )
                ORDER BY CASE
                            WHEN t2.mavattu = hk.tenmay THEN 0
                            WHEN t2.somay = hk.tenmay THEN 1
                            ELSE 2
                         END,
                         t2.stt
                LIMIT 1
            )
        )
        WHERE hk.ngayhc >= :fromDateHckd
            AND hk.ngayhc < DATE_ADD(:toDateHckd, INTERVAL 1 DAY)
            AND hk.ttkt = 'Tốt'
        ORDER BY hk.noithuchien ASC, hk.ttkt DESC, hk.ngayhc ASC, hk.sohs ASC
    ";

    $stmtHckd = $db->prepare($sqlHckd);
    $stmtHckd->execute([
        ':fromDateHckd' => $fromDate->format('Y-m-d'),
        ':toDateHckd' => $toDate->format('Y-m-d'),
    ]);

    $hckdResult = $stmtHckd->fetchAll(PDO::FETCH_ASSOC);
    foreach ($hckdResult as $item) {
        $tenMayRaw = $normalizeText($item['tenmay'] ?? '');
        $thietBi = $item;

        $ttkt = $normalizeText($item['ttkt'] ?? '');
        if ($ttkt !== 'Tốt') {
            continue;
        }

        $boPhan = $normalizeText($thietBi['bophansh'] ?? '');
        $chuSoHuu = $normalizeText($thietBi['chusohuu'] ?? '');
        $benYeuCauRow = $boPhan === 'XDT' ? $chuSoHuu : $boPhan;

        $sohs = $ttkt === 'Tốt' ? $normalizeText($item['sohs'] ?? '') : '';
        $ngayHc = $ttkt === 'Tốt' ? $normalizeText($item['ngayhc_fmt'] ?? '') : '';
        $nhanVien = $ttkt === 'Tốt'
            ? mb_convert_case($normalizeText($item['nhanvien'] ?? ''), MB_CASE_TITLE, 'UTF-8')
            : '';

        $hckdRows[] = [
            'sohs' => $sohs,
            'tenmay' => $normalizeText($thietBi['tenviettat'] ?? $tenMayRaw),
            'somay' => $normalizeText($thietBi['somay'] ?? ''),
            'congviec' => $normalizeText($item['congviec'] ?? ''),
            'ngayhc' => $ngayHc,
            'nhanvien' => $nhanVien,
            'ttkt' => $ttkt,
            'benyeucau' => $normalizeText($benYeuCauRow),
            'sogio' => '1',
        ];
    }
} catch (Throwable $e) {
    error_log('Error in baocaothang01_print.php: ' . $e->getMessage());
    $errorMessage = 'Không thể tải dữ liệu báo cáo. Vui lòng thử lại.';
}

$groupedRows = [];
foreach ($rows as $row) {
    $maql = $row['maql'] !== '' ? $row['maql'] : 'Không có mã quản lý';
    if (!isset($groupedRows[$maql])) {
        $groupedRows[$maql] = [
            'totalHours' => 0.0,
            'items' => [],
        ];
    }
    $groupedRows[$maql]['items'][] = $row;
    $groupedRows[$maql]['totalHours'] += (float)$row['sogio'];
}

$compareHoso = static function (array $a, array $b): int {
    $hosoA = trim((string)($a['hoso'] ?? ''));
    $hosoB = trim((string)($b['hoso'] ?? ''));

    $parse = static function (string $hoso): array {
        if ($hoso === '') {
            return [PHP_INT_MAX, PHP_INT_MAX, ''];
        }

        if (preg_match('/^(\d+)-(\d+)$/', $hoso, $m)) {
            return [(int)$m[1], (int)$m[2], $hoso];
        }

        if (preg_match('/^(\d+)/', $hoso, $m)) {
            return [(int)$m[1], PHP_INT_MAX, $hoso];
        }

        return [PHP_INT_MAX, PHP_INT_MAX, $hoso];
    };

    [$prefixA, $suffixA, $rawA] = $parse($hosoA);
    [$prefixB, $suffixB, $rawB] = $parse($hosoB);

    if ($prefixA !== $prefixB) {
        return $prefixA <=> $prefixB;
    }

    if ($suffixA !== $suffixB) {
        return $suffixA <=> $suffixB;
    }

    return strcasecmp($rawA, $rawB);
};

foreach ($groupedRows as $maql => $group) {
    usort($group['items'], $compareHoso);
    $groupedRows[$maql] = $group;
}

$summaryStats = [
    'totalOrders' => count($groupedRows),
    'totalMachines' => count($rows),
    'totalPassed' => 0,
    'totalBroken' => 0,
    'totalSpecial' => 0,
    'totalWaitingParts' => 0,
    'totalRepairing' => 0,
];

foreach ($rows as $row) {
    $status = trim((string)($row['ttktafter'] ?? ''));
    $statusLower = mb_strtolower($status, 'UTF-8');

    if ($statusLower === 'đạt' || $statusLower === 'tốt') {
        $summaryStats['totalPassed']++;
    } elseif ($statusLower === 'hỏng - không khắc phục được' || $statusLower === 'hỏng-không khắc phục được') {
        $summaryStats['totalBroken']++;
    } elseif ($statusLower === 'ttktdb') {
        $summaryStats['totalSpecial']++;
    } elseif ($statusLower === 'chờ vật tư thay thế') {
        $summaryStats['totalWaitingParts']++;
    } elseif ($statusLower === 'đang sửa chữa') {
        $summaryStats['totalRepairing']++;
    }
}

$exportMode = strtolower(trim((string)($_GET['export'] ?? '')));

if ($exportMode === 'excel') {
    $exportMode = 'kpi';
}

$isExportExcel = in_array($exportMode, ['excel1', 'excel2', 'kpi'], true);
if ($isExportExcel) {
    $exportLabel = $exportMode;
    if ($exportMode === 'excel1') {
        // BCKT naming: BCSX-mm-YYYY-KT
        $fileName = sprintf('BCSX-%s-%s-KT.xls', $toDate->format('m'), $toDate->format('Y'));
    } elseif ($exportMode === 'excel2') {
        // Keep legacy naming equivalent to baocaothang02.php (BCSX-month-year)
        $fileName = sprintf('BCSX-%s-%s.xls', $toDate->format('m'), $toDate->format('Y'));
    } else {
        $fileName = sprintf(
            'baocao_scbd_cc_%s_%s_%s_%s.xls',
            $exportLabel,
            $fromDate->format('Ymd'),
            $toDate->format('Ymd'),
            date('His')
        );
    }

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('X-Export-Mode: ' . $exportLabel);
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: 0');

    $excelText = static function ($value): string {
        $text = trim((string)($value ?? ''));
        return $text;
    };

    $excelHours = static function ($value): string {
        $hours = (float)($value ?? 0);
        return $hours > 0 ? number_format($hours, 2) : '';
    };

    echo "\xEF\xBB\xBF";
    ?>
    <?php if ($exportMode === 'excel1'): ?>
        <table border="0" style="border-collapse: collapse; width: 1100px; font-family: 'Times New Roman', serif; font-size: 11pt;">
            <tr>
                <td colspan="3" style="text-align: left; font-weight: bold;">XN ĐỊA VẬT LÝ GK</td>
                <td colspan="9" style="text-align: center; font-weight: bold;">LIỆT KÊ CÔNG TÁC BẢO DƯỠNG, SỬA CHỮA, CHUẨN CHỈNH THIẾT BỊ</td>
            </tr>
            <tr>
                <td colspan="3" style="text-align: left; font-weight: bold;">XƯỞNG SCTBĐVL</td>
                <td colspan="9" style="text-align: center; font-weight: bold;">Từ <?php echo htmlspecialchars($fromDate->format('d/m/Y')); ?> đến <?php echo htmlspecialchars($toDate->format('d/m/Y')); ?></td>
            </tr>
        </table>
        <br>
        <table border="1" style="border-collapse: collapse; width: 1100px; font-family: 'Times New Roman', serif; font-size: 11pt;">
            <colgroup>
                <col style="width: 35px;">
                <col style="width: 60px;">
                <col style="width: 75px;">
                <col style="width: 185px;">
                <col style="width: 83px;">
                <col style="width: 58px;">
                <col style="width: 88px;">
                <col style="width: 100px;">
                <col style="width: 90px;">
                <col style="width: 170px;">
                <col style="width: 55px;">
                <col style="width: 90px;">
            </colgroup>
            <thead>
                <tr>
                    <th style="background-color: #87CEEB;"></th>
                    <th style="background-color: #87CEEB;">№ Yêu cầu DV</th>
                    <th style="background-color: #87CEEB;">Số Hồ Sơ</th>
                    <th style="background-color: #87CEEB;">Tên TB, công việc</th>
                    <th style="background-color: #87CEEB;">Số máy</th>
                    <th style="background-color: #87CEEB;">C.Việc</th>
                    <th style="background-color: #87CEEB;">Ngày hoàn thành</th>
                    <th style="background-color: #87CEEB;">Nhân viên thực hiện</th>
                    <th style="background-color: #87CEEB;">Tình trạng KT sau khi SC, BD</th>
                    <th style="background-color: #87CEEB; width: 170px;">Bên yêu cầu</th>
                    <th style="background-color: #87CEEB; width: 55px; text-align: center;">Số giờ</th>
                    <th style="background-color: #87CEEB; width: 90px; text-align: center;">Ghi chú</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($groupedRows)): ?>
                    <tr><td colspan="12" style="text-align: center;">Không có dữ liệu trong khoảng thời gian đã chọn.</td></tr>
                <?php else: ?>
                    <?php $sttGroup = 1; ?>
                    <?php foreach ($groupedRows as $maql => $group): ?>
                        <?php $groupMadv = isset($group['items'][0]['madv']) ? $excelText($group['items'][0]['madv']) : ''; ?>
                        <tr>
                            <td style="text-align: center; font-weight: bold;"><?php echo $sttGroup++; ?></td>
                            <td colspan="8" style="font-weight: bold;"><?php echo htmlspecialchars($maql === 'Không có mã quản lý' ? '' : $maql); ?></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($groupMadv); ?></td>
                            <td style="text-align: center; font-weight: bold;"><?php echo htmlspecialchars($excelHours($group['totalHours'])); ?></td>
                            <td></td>
                        </tr>
                        <?php $sttRow = 1; ?>
                        <?php foreach ($group['items'] as $row): ?>
                            <?php
                                $mavt = $excelText($row['mavt'] ?? '');
                                $tenvt = $excelText($row['tenvt'] ?? '');
                                $tenTbCv = $mavt !== '' && $tenvt !== '' ? ($mavt . '-' . $tenvt) : ($mavt !== '' ? $mavt : $tenvt);
                                $ghiChu = $excelText($row['ghichu'] ?? '');
                            ?>
                            <tr>
                                <td colspan="2" style="text-align: right;"><?php echo $sttRow++; ?>&nbsp;&nbsp;&nbsp;</td>
                                <td><?php echo htmlspecialchars($excelText($row['hoso'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($tenTbCv); ?></td>
                                <td><?php echo htmlspecialchars($excelText($row['somay'] ?? '')); ?></td>
                                <td style="text-align: center;"><?php echo htmlspecialchars($excelText($row['cv'] ?? '')); ?></td>
                                <td style="text-align: center;"><?php echo htmlspecialchars($excelText($row['ngaykt'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($excelText($row['nhanvien'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($excelText($row['ttktafter'] ?? '')); ?></td>
                                <td style="text-align: center;"><?php echo $ghiChu !== '' ? htmlspecialchars($ghiChu) : '&nbsp;'; ?></td>
                                <td style="text-align: center;"></td>
                                <td style="text-align: center;"><?php echo htmlspecialchars($excelHours($row['sogio'] ?? 0)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <br>
        <p style="font-family: 'Times New Roman', serif; font-size: 11pt; font-weight: bold;">&nbsp;3. Công tác Hiệu chuẩn/ Kiểm định thiết bị</p>
        <table border="1" style="border-collapse: collapse; width: 1100px; font-family: 'Times New Roman', serif; font-size: 11pt;">
            <colgroup>
                <col style="width: 10px;">
                <col style="width: 60px;">
                <col style="width: 75px;">
                <col style="width: 185px;">
                <col style="width: 120px;">
                <col style="width: 83px;">
                <col style="width: 58px;">
                <col style="width: 88px;">
                <col style="width: 75px;">
                <col style="width: 90px;">
                <col style="width: 100px;">
            </colgroup>
            <thead>
                <tr>
                    <th style="background-color: #87CEEB;"></th>
                    <th style="background-color: #87CEEB;">STT</th>
                    <th style="background-color: #87CEEB;">SỐ HỒ SƠ</th>
                    <th style="background-color: #87CEEB;">TÊN MÁY</th>
                    <th style="background-color: #87CEEB;">SỐ MÁY</th>
                    <th style="background-color: #87CEEB;">C.VIỆC</th>
                    <th style="background-color: #87CEEB;">Ngày TH</th>
                    <th style="background-color: #87CEEB;">Nhân viên thực hiện</th>
                    <th style="background-color: #87CEEB;">Tình trạng KT</th>
                    <th style="background-color: #87CEEB;">Bên yêu cầu</th>
                    <th style="background-color: #87CEEB;">Số giờ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($hckdRows)): ?>
                    <tr><td colspan="11" style="text-align: center;">Không có dữ liệu hiệu chuẩn/kiểm định trong khoảng thời gian đã chọn.</td></tr>
                <?php else: ?>
                    <?php $hckdStt = 1; ?>
                    <?php foreach ($hckdRows as $item): ?>
                        <tr>
                            <td></td>
                            <td style="text-align: center;"><?php echo $hckdStt++; ?></td>
                            <td style="text-align: left; padding-left: 8px;"><?php echo htmlspecialchars($excelText($item['sohs'])); ?></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($excelText($item['tenmay'])); ?></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($excelText($item['somay'])); ?></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($excelText($item['congviec'])); ?></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($excelText($item['ngayhc'])); ?></td>
                            <td style="text-align: left; padding-left: 8px;"><?php echo htmlspecialchars($excelText($item['nhanvien'])); ?></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($excelText($item['ttkt'])); ?></td>
                            <td style="text-align: left; padding-left: 8px;"><?php echo htmlspecialchars($excelText($item['benyeucau'])); ?></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($excelText($item['sogio'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php elseif ($exportMode === 'excel2'): ?>
        <table border="1" style="border-collapse: collapse; width: 100%; font-family: 'Times New Roman', serif; font-size: 11pt;">
            <thead>
                <tr>
                    <th colspan="3" style="text-align: left; font-weight: bold; border: 0;">XN ĐỊA VẬT LÝ GK</th>
                    <th colspan="8" style="text-align: center; font-weight: bold; border: 0; color: #1f4e78;">LIỆT KÊ CÔNG TÁC BẢO DƯỠNG, SỬA CHỮA, CHUẨN CHỈNH THIẾT BỊ</th>
                </tr>
                <tr>
                    <th colspan="3" style="text-align: left; font-weight: bold; border: 0;">XƯỞNG SCTBĐVL</th>
                    <th colspan="8" style="text-align: center; font-weight: bold; border: 0; color: #1f4e78;">Từ <?php echo htmlspecialchars($fromDate->format('d/m/Y')); ?> đến <?php echo htmlspecialchars($toDate->format('d/m/Y')); ?></th>
                </tr>
                <tr>
                    <th colspan="11" style="background-color: #ffffff; border: 0; height: 14px;"></th>
                </tr>
                <tr>
                    <th style="width: 5%; background-color: #87CEEB;">&nbsp;</th>
                    <th style="width: 10%; background-color: #87CEEB;">№ Yêu cầu DV</th>
                    <th style="width: 10%; background-color: #87CEEB;">Số Hồ Sơ</th>
                    <th style="width: 22%; background-color: #87CEEB;">Tên TB, công việc</th>
                    <th style="width: 8%; background-color: #87CEEB;">Số máy</th>
                    <th style="width: 7%; background-color: #87CEEB;">C.Việc</th>
                    <th style="width: 10%; background-color: #87CEEB;">Ngày bắt đầu</th>
                    <th style="width: 10%; background-color: #87CEEB;">Ngày hoàn thành</th>
                    <th style="width: 14%; background-color: #87CEEB;">Nhân viên thực hiện</th>
                    <th style="width: 12%; background-color: #87CEEB;">Tình trạng KT sau khi SC, BD</th>
                    <th style="width: 12%; background-color: #87CEEB;">Tình trạng KT trước khi SC, BD</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($groupedRows)): ?>
                    <tr><td colspan="11" style="text-align: center;">Không có dữ liệu trong khoảng thời gian đã chọn.</td></tr>
                <?php else: ?>
                    <?php $stt = 1; ?>
                    <?php foreach ($groupedRows as $maql => $group): ?>
                        <tr>
                            <td style="text-align: center;">&nbsp;<?php echo $stt++; ?></td>
                            <td style="text-align: left; padding-left: 8px;"><?php echo htmlspecialchars($excelText($maql)); ?></td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                        </tr>
                        <?php $detailStt = 1; ?>
                        <?php foreach ($group['items'] as $row): ?>
                            <?php
                                $mavt = $excelText($row['mavt'] ?? '');
                                $tenvt = $excelText($row['tenvt'] ?? '');
                                $tenTbCv = $mavt !== '' && $tenvt !== '' ? ($mavt . '-' . $tenvt) : ($mavt !== '' ? $mavt : $tenvt);
                            ?>
                            <tr>
                                <td colspan="2" style="text-align: right;"><?php echo $detailStt++; ?>&nbsp;&nbsp;&nbsp;</td>
                                <td><?php echo htmlspecialchars($excelText($row['hoso'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($tenTbCv); ?></td>
                                <td style="text-align: center;"><?php echo htmlspecialchars($excelText($row['somay'] ?? '')); ?></td>
                                <td style="text-align: center;"><?php echo htmlspecialchars($excelText($row['cv'] ?? '')); ?></td>
                                <td style="text-align: center;"><?php echo htmlspecialchars($excelText($row['ngayth'] ?? '')); ?></td>
                                <td style="text-align: center;"><?php echo htmlspecialchars($excelText($row['ngaykt'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($excelText($row['nhanvien'] ?? '')); ?></td>
                                <td style="text-align: center;"><?php echo htmlspecialchars($excelText($row['ttktafter'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($excelText($row['ttktbefore'] ?? '')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <table border="1" style="border-collapse: collapse; width: 100%; font-family: 'Times New Roman', serif; font-size: 11pt; margin-top: 2px;">
            <tbody>
                <tr><td colspan="11" style="height: 12px;">&nbsp;</td></tr>
                <tr><td colspan="11" style="font-weight: bold;">Tổng số đơn hàng : <?php echo (int)$summaryStats['totalOrders']; ?></td></tr>
                <tr><td colspan="11" style="font-weight: bold;">Tổng số máy : <?php echo (int)$summaryStats['totalMachines']; ?></td></tr>
                <tr><td colspan="11" style="font-weight: bold;">Tổng số máy đạt : <?php echo (int)$summaryStats['totalPassed']; ?></td></tr>
                <tr><td colspan="11" style="font-weight: bold;">Tổng số máy hỏng (Không khắc phục được) : <?php echo (int)$summaryStats['totalBroken']; ?></td></tr>
                <tr><td colspan="11" style="font-weight: bold;">Tổng số máy TTKTĐB : <?php echo (int)$summaryStats['totalSpecial']; ?></td></tr>
                <tr><td colspan="11" style="font-weight: bold;">Tổng số máy chờ vật tư thay thế : <?php echo (int)$summaryStats['totalWaitingParts']; ?></td></tr>
                <tr><td colspan="11" style="font-weight: bold;">Tổng số máy đang sửa chữa : <?php echo (int)$summaryStats['totalRepairing']; ?></td></tr>
            </tbody>
        </table>

        <p>&nbsp;<b> 3. Công tác Hiệu chuẩn/ Kiểm định thiết bị </b></p>
        <table border="1" style="border-collapse: collapse; width: 100%; font-family: 'Times New Roman', serif; font-size: 11pt;">
            <thead>
                <tr>
                    <th style="width: 8%; background-color: #87CEEB;">STT</th>
                    <th style="width: 12%; background-color: #87CEEB;">SỐ HỒ SƠ</th>
                    <th style="width: 20%; background-color: #87CEEB;">TÊN MÁY</th>
                    <th style="width: 12%; background-color: #87CEEB;">SỐ MÁY</th>
                    <th style="width: 8%; background-color: #87CEEB;">C.VIỆC</th>
                    <th style="width: 10%; background-color: #87CEEB;">Ngày TH</th>
                    <th style="width: 15%; background-color: #87CEEB;">Nhân viên thực hiện</th>
                    <th style="width: 10%; background-color: #87CEEB;">Tình trạng KT</th>
                    <th style="width: 15%; background-color: #87CEEB;">Bên yêu cầu</th>
                    <th style="width: 10%; background-color: #87CEEB;">Số giờ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($hckdRows)): ?>
                    <tr>
                        <td colspan="10" style="text-align: center;">Không có dữ liệu hiệu chuẩn/kiểm định trong khoảng thời gian đã chọn.</td>
                    </tr>
                <?php else: ?>
                    <?php $hckdStt = 1; ?>
                    <?php foreach ($hckdRows as $item): ?>
                        <tr>
                            <td style="text-align: center;"><?php echo $hckdStt++; ?></td>
                            <td style="text-align: left; padding-left: 8px;"><?php echo htmlspecialchars($excelText($item['sohs'] ?? '')); ?></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($excelText($item['tenmay'] ?? '')); ?></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($excelText($item['somay'] ?? '')); ?></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($excelText($item['congviec'] ?? '')); ?></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($excelText($item['ngayhc'] ?? '')); ?></td>
                            <td style="text-align: left; padding-left: 8px;"><?php echo htmlspecialchars($excelText($item['nhanvien'] ?? '')); ?></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($excelText($item['ttkt'] ?? '')); ?></td>
                            <td style="text-align: left; padding-left: 8px;"><?php echo htmlspecialchars($excelText($item['benyeucau'] ?? '')); ?></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($excelText($item['sogio'] ?? '1')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <p>&nbsp;<b> 4. Công việc khác </b></p>
        <table border="1" style="border-collapse: collapse; width: 100%; font-family: 'Times New Roman', serif; font-size: 11pt;">
            <thead>
                <tr>
                    <th style="width: 8%; background-color: #87CEEB;">STT</th>
                    <th style="width: 46%; background-color: #87CEEB;">Công việc</th>
                    <th style="width: 30%; background-color: #87CEEB;">Tiến độ thực hiện</th>
                    <th style="width: 16%; background-color: #87CEEB;">Nhân viên thực hiện</th>
                </tr>
            </thead>
            <tbody>
                <?php for ($j = 1; $j <= 5; $j++): ?>
                    <tr>
                        <td style="text-align: center;"><?php echo $j; ?></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    <?php elseif ($exportMode === 'kpi'): ?>
        <table border="1" style="border-collapse: collapse; width: 100%; font-family: 'Times New Roman', serif; font-size: 11pt;">
            <thead>
                <tr>
                    <th colspan="11" style="text-align: left; font-weight: bold;">XN ĐỊA VẬT LÝ GK</th>
                </tr>
                <tr>
                    <th colspan="11" style="text-align: center; font-weight: bold;">BÁO CÁO KPI CÔNG TÁC BẢO DƯỠNG, SỬA CHỮA, CHUẨN CHỈNH THIẾT BỊ</th>
                </tr>
                <tr>
                    <th colspan="11" style="text-align: center; font-weight: bold; color: #0f766e;">KPI - MẪU MỚI</th>
                </tr>
                <tr>
                    <th colspan="11" style="text-align: center; font-weight: bold;">Từ <?php echo htmlspecialchars($fromDate->format('d/m/Y')); ?> đến <?php echo htmlspecialchars($toDate->format('d/m/Y')); ?></th>
                </tr>
                <tr>
                    <th style="width: 5%;">STT</th>
                    <th style="width: 10%;">Số hồ sơ</th>
                    <th style="width: 18%;">Tên TB, công việc</th>
                    <th style="width: 8%;">Số máy</th>
                    <th style="width: 8%;">Công việc</th>
                    <th style="width: 10%;">Ngày hoàn thành</th>
                    <th style="width: 15%;">Nhân viên thực hiện</th>
                    <th style="width: 12%;">Tình trạng KT sau SC/BD</th>
                    <th style="width: 9%;">Bên yêu cầu</th>
                    <th style="width: 8%;">Số giờ</th>
                    <th style="width: 10%;">Ghi chú</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($groupedRows)): ?>
                    <tr><td colspan="11" style="text-align: center;">Không có dữ liệu trong khoảng thời gian đã chọn.</td></tr>
                <?php else: ?>
                    <?php $stt = 1; ?>
                    <?php foreach ($groupedRows as $maql => $group): ?>
                        <tr style="background-color: #eaf2ff; font-weight: bold;">
                            <td style="text-align: center;"></td>
                            <td colspan="7"><?php echo htmlspecialchars($maql); ?></td>
                            <td style="text-align: center;"></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($excelHours($group['totalHours'])); ?></td>
                            <td></td>
                        </tr>
                        <?php foreach ($group['items'] as $row): ?>
                            <?php
                                $mavt = $excelText($row['mavt'] ?? '');
                                $tenvt = $excelText($row['tenvt'] ?? '');
                                $tenTbCv = $mavt !== '' && $tenvt !== '' ? ($mavt . '-' . $tenvt) : ($mavt !== '' ? $mavt : $tenvt);
                            ?>
                            <tr>
                                <td style="text-align: center;"><?php echo $stt++; ?></td>
                                <td><?php echo htmlspecialchars($excelText($row['hoso'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($tenTbCv); ?></td>
                                <td><?php echo htmlspecialchars($excelText($row['somay'] ?? '')); ?></td>
                                <td style="text-align: center;"><?php echo htmlspecialchars($excelText($row['cv'] ?? '')); ?></td>
                                <td style="text-align: center;"><?php echo htmlspecialchars($excelText($row['ngaykt'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($excelText($row['nhanvien'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($excelText($row['ttktafter'] ?? '')); ?></td>
                                <td style="text-align: center;"><?php echo htmlspecialchars($excelText($row['madv'] ?? '')); ?></td>
                                <td style="text-align: right;"><?php echo htmlspecialchars($excelHours($row['sogio'] ?? 0)); ?></td>
                                <td><?php echo htmlspecialchars($excelText($row['ghichu'] ?? '')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php else: ?>
        <table border="1" style="border-collapse: collapse; width: 100%; font-family: 'Times New Roman', serif; font-size: 11pt;">
            <tbody>
                <tr>
                    <td style="color: #b91c1c; font-weight: bold;">Export mode không hợp lệ: <?php echo htmlspecialchars($exportMode); ?></td>
                </tr>
            </tbody>
        </table>
    <?php endif; ?>
    <?php
    exit;
}

$title = 'In báo cáo công tác SC/BD/CC thiết bị';
require_once __DIR__ . '/views/layouts/header.php';
?>

<script>
function openLegacyPrint(url) {
    const newWindow = window.open(url, '_blank');
    if (!newWindow) {
        window.location.href = url;
        return;
    }
    setTimeout(function () {
        try {
            newWindow.focus();
            newWindow.print();
        } catch (e) {
            console.log('Print fail for legacy report', e);
        }
    }, 800);
}
</script>

<script>
function openExport(type) {
    const url = '/iso2/baocaothang01_print.php?from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>&benyeucau=<?php echo urlencode($benYeuCau); ?>&export=' + type;
    const win = window.open(url, '_blank');
    if (win) {
        setTimeout(function () {
            try {
                win.focus();
                win.print();
            } catch (e) {
                console.log('Print export failed', e);
            }
        }, 500);
    } else {
        window.location.href = url;
    }
}
</script>

<div class="max-w-full mx-auto bg-white rounded-lg shadow-md p-4 md:p-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4 no-print">
        <h1 class="text-xl md:text-2xl font-bold text-blue-900">
            BÁO CÁO CÔNG TÁC BẢO DƯỠNG, SỬA CHỮA, CHUẨN CHỈNH THIẾT BỊ
        </h1>
        <div class="flex flex-wrap gap-2">
            <button onclick="window.print()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded font-semibold">
                <i class="fas fa-print mr-2"></i>In báo cáo
            </button>
            <button type="button"
                    onclick="openExport('excel1')"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded font-semibold"
                    title="BCKT theo baocaothang01.php">
                <i class="fas fa-file-excel mr-2"></i>BCKT
            </button>
            <button type="button"
                    onclick="openExport('excel2')"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-semibold"
                    title="BCSX theo baocaothang02.php">
                <i class="fas fa-file-excel mr-2"></i>BCSX
            </button>
            <button type="button"
                    class="bg-gray-400 text-white px-4 py-2 rounded font-semibold cursor-not-allowed opacity-70"
                    title="Tạm thời khóa"
                    disabled>
                <i class="fas fa-file-export mr-2"></i>KPI – mới
            </button>
            <a href="/iso2/baocao_giolamviec.php?from_month=<?php echo urlencode($fromDate->format('n')); ?>&amp;from_year=<?php echo urlencode($fromDate->format('Y')); ?>&amp;to_month=<?php echo urlencode($toDate->format('n')); ?>&amp;to_year=<?php echo urlencode($toDate->format('Y')); ?>"
               class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded font-semibold"
               title="Mẫu cũ">
                <i class="fas fa-history mr-2"></i>Mẫu cũ
            </a>
        </div>
    </div>

    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3 mb-5 bg-gray-50 border border-gray-200 rounded p-3 no-print">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Từ ngày</label>
            <input type="date" name="from" value="<?php echo htmlspecialchars($from); ?>" class="w-full border rounded px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Đến ngày</label>
            <input type="date" name="to" value="<?php echo htmlspecialchars($to); ?>" class="w-full border rounded px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Bên yêu cầu</label>
            <select name="benyeucau" class="w-full border rounded px-3 py-2">
                <option value="" <?php echo $benYeuCau === '' ? 'selected' : ''; ?>>Tất cả</option>
                <option value="TH" <?php echo $benYeuCau === 'TH' ? 'selected' : ''; ?>>TH</option>
                <option value="CNC" <?php echo $benYeuCau === 'CNC' ? 'selected' : ''; ?>>CNC</option>
            </select>
        </div>
        <div class="md:col-span-2 flex items-end gap-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded font-semibold">
                <i class="fas fa-filter mr-2"></i>Lọc dữ liệu
            </button>
            <a href="/iso2/baocaothang01_print.php" class="bg-gray-500 hover:bg-gray-600 text-white px-5 py-2 rounded font-semibold">
                <i class="fas fa-undo mr-2"></i>Đặt lại
            </a>
        </div>
    </form>

    <p class="text-center text-gray-600 mb-4">
        Từ <?php echo htmlspecialchars($fromDate->format('d/m/Y')); ?> đến <?php echo htmlspecialchars($toDate->format('d/m/Y')); ?>
    </p>

    <?php if ($errorMessage !== ''): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>
    <?php endif; ?>

    <div class="overflow-x-auto">
        <table class="min-w-full border-collapse border border-gray-400 text-sm">
            <thead>
                <tr class="bg-blue-500 text-white">
                    <th class="border border-gray-400 px-2 py-2 text-center">STT</th>
                    <th class="border border-gray-400 px-2 py-2 text-center">Số hồ sơ</th>
                    <th class="border border-gray-400 px-2 py-2 text-center">Tên TB, công việc</th>
                    <th class="border border-gray-400 px-2 py-2 text-center">Số máy</th>
                    <th class="border border-gray-400 px-2 py-2 text-center">Công việc</th>
                    <th class="border border-gray-400 px-2 py-2 text-center">Ngày hoàn thành</th>
                    <th class="border border-gray-400 px-2 py-2 text-center">Nhân viên thực hiện</th>
                    <th class="border border-gray-400 px-2 py-2 text-center">Tình trạng KT sau SC/BD</th>
                    <th class="border border-gray-400 px-2 py-2 text-center">Bên yêu cầu</th>
                    <th class="border border-gray-400 px-2 py-2 text-center">Số giờ</th>
                    <th class="border border-gray-400 px-2 py-2 text-center">Ghi chú</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($groupedRows)): ?>
                    <tr>
                        <td colspan="11" class="border border-gray-400 px-4 py-8 text-center text-gray-500">
                            Không có dữ liệu trong khoảng thời gian đã chọn.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $stt = 1; ?>
                    <?php foreach ($groupedRows as $maql => $group): ?>
                        <tr class="bg-gray-100 font-semibold">
                            <td class="border border-gray-400 px-2 py-2 text-center"></td>
                            <td colspan="7" class="border border-gray-400 px-2 py-2"><?php echo htmlspecialchars($maql); ?></td>
                            <td class="border border-gray-400 px-2 py-2 text-center"></td>
                            <td class="border border-gray-400 px-2 py-2 text-center"><?php echo number_format($group['totalHours'], 2); ?></td>
                            <td class="border border-gray-400 px-2 py-2"></td>
                        </tr>
                        <?php foreach ($group['items'] as $row): ?>
                            <tr>
                                <td class="border border-gray-400 px-2 py-1 text-center"><?php echo $stt++; ?></td>
                                <td class="border border-gray-400 px-2 py-1"><?php echo htmlspecialchars($row['hoso']); ?></td>
                                <td class="border border-gray-400 px-2 py-1"><?php echo htmlspecialchars($row['mavt'] . '-' . $row['tenvt']); ?></td>
                                <td class="border border-gray-400 px-2 py-1"><?php echo htmlspecialchars($row['somay']); ?></td>
                                <td class="border border-gray-400 px-2 py-1 text-center"><?php echo htmlspecialchars($row['cv']); ?></td>
                                <td class="border border-gray-400 px-2 py-1 text-center"><?php echo htmlspecialchars($row['ngaykt']); ?></td>
                                <td class="border border-gray-400 px-2 py-1"><?php echo htmlspecialchars($row['nhanvien']); ?></td>
                                <td class="border border-gray-400 px-2 py-1"><?php echo htmlspecialchars($row['ttktafter']); ?></td>
                                <td class="border border-gray-400 px-2 py-1 text-center"><?php echo htmlspecialchars($row['madv']); ?></td>
                                <td class="border border-gray-400 px-2 py-1 text-right"><?php echo number_format((float)$row['sogio'], 2); ?></td>
                                <td class="border border-gray-400 px-2 py-1"><?php echo htmlspecialchars($row['ghichu']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
@media print {
    .no-print,
    header,
    nav,
    aside,
    .sidebar,
    #menu-toggle,
    #mobileMenuBtn,
    [id*="MenuBtn"],
    [id*="menuBtn"] {
        display: none !important;
    }

    body {
        margin: 0;
        padding: 8px;
        background: #fff !important;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    .max-w-full,
    .rounded-lg,
    .shadow-md {
        box-shadow: none !important;
        border-radius: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    table {
        width: 100%;
        font-size: 9pt;
    }

    th,
    td {
        padding: 3px 4px !important;
    }
}
</style>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
