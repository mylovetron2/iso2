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

        $statusAfter = trim((string)($item['ttktafter'] ?? ''));
        if ($statusAfter === '') {
            $statusAfter = 'Đang sửa chữa';
        }
        if ($statusAfter === 'Hỏng') {
            $statusAfter = 'Hỏng - Không khắc phục được';
        }
        if ($statusAfter === 'Chưa kết luận') {
            $statusAfter = (string)($item['ghichufinal'] ?? '');
        }

        $ngayKt = (string)($item['ngaykt_fmt'] ?? '');
        if ($ngayKt === '' || $ngayKt === '00/00/0000') {
            $ngayKt = 'Đang TH';
        }

        $rows[] = [
            'maql' => (string)($item['maql'] ?? ''),
            'hoso' => (string)($item['hoso'] ?? ''),
            'mavt' => (string)($item['mavt'] ?? ''),
            'tenvt' => (string)($item['tenvt'] ?? ''),
            'somay' => (string)($item['somay'] ?? ''),
            'cv' => (string)($item['cv'] ?? ''),
            'ngaykt' => $ngayKt,
            'nhanvien' => implode(', ', array_values(array_unique($workerNames))),
            'ttktafter' => $statusAfter,
            'madv' => (string)($item['madv'] ?? ''),
            'ghichu' => (string)($item['ghichufinal'] ?? ''),
            'sogio' => $hours,
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

$isExportExcel = isset($_GET['export']) && $_GET['export'] === 'excel';
if ($isExportExcel) {
    $fileName = sprintf(
        'baocao_scbd_cc_%s_%s_%s.xls',
        $fromDate->format('Ymd'),
        $toDate->format('Ymd'),
        date('His')
    );

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
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
    <table border="1" style="border-collapse: collapse; width: 100%; font-family: 'Times New Roman', serif; font-size: 11pt;">
        <thead>
            <tr>
                <th colspan="3" style="text-align: left; font-weight: bold;">XN ĐỊA VẬT LÝ GK</th>
                <th colspan="10" style="text-align: center; font-weight: bold;">LIỆT KÊ CÔNG TÁC BẢO DƯỠNG, SỬA CHỮA, CHUẨN CHỈNH THIẾT BỊ</th>
            </tr>
            <tr>
                <th colspan="3" style="text-align: left; font-weight: bold;">XƯỞNG SCTBĐVL</th>
                <th colspan="10" style="text-align: center; font-weight: bold;">Từ <?php echo htmlspecialchars($fromDate->format('d/m/Y')); ?> đến <?php echo htmlspecialchars($toDate->format('d/m/Y')); ?></th>
            </tr>
            <tr>
                <th colspan="13" style="text-align: left; font-weight: bold;">I. Bảo dưỡng, SC máy giếng</th>
            </tr>
            <tr>
                <th>STT</th>
                <th>№ Yêu cầu DV</th>
                <th>Số Hồ Sơ</th>
                <th>Tên TB, công việc</th>
                <th>Số máy</th>
                <th>Công việc</th>
                <th>Ngày hoàn thành</th>
                <th>Nhân viên thực hiện</th>
                <th>Giờ định mức/YC khách hàng</th>
                <th>Số giờ thực tế</th>
                <th>Tổng số yêu cầu</th>
                <th>Số lượng đạt KPI</th>
                <th>Ghi chú</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($groupedRows)): ?>
                <tr>
                    <td colspan="13" style="text-align: center;">Không có dữ liệu trong khoảng thời gian đã chọn.</td>
                </tr>
            <?php else: ?>
                <?php $sttGroup = 1; ?>
                <?php foreach ($groupedRows as $maql => $group): ?>
                    <tr>
                        <td style="text-align: center; font-weight: bold;"><?php echo $sttGroup++; ?></td>
                        <td style="font-weight: bold;"><?php echo htmlspecialchars($maql === 'Không có mã quản lý' ? '' : $maql); ?></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td style="text-align: right; font-weight: bold;"><?php echo htmlspecialchars($excelHours($group['totalHours'])); ?></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <?php $sttRow = 1; ?>
                    <?php foreach ($group['items'] as $row): ?>
                        <?php
                            $mavt = $excelText($row['mavt'] ?? '');
                            $tenvt = $excelText($row['tenvt'] ?? '');
                            $tenTbCv = $mavt !== '' && $tenvt !== '' ? ($mavt . '-' . $tenvt) : ($mavt !== '' ? $mavt : $tenvt);
                        ?>
                        <tr>
                            <td style="text-align: center;"><?php echo $sttRow++; ?></td>
                            <td></td>
                            <td><?php echo htmlspecialchars($excelText($row['hoso'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars($tenTbCv); ?></td>
                            <td><?php echo htmlspecialchars($excelText($row['somay'] ?? '')); ?></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($excelText($row['cv'] ?? '')); ?></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($excelText($row['ngaykt'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars($excelText($row['nhanvien'] ?? '')); ?></td>
                            <td></td>
                            <td style="text-align: right;"><?php echo htmlspecialchars($excelHours($row['sogio'] ?? 0)); ?></td>
                            <td></td>
                            <td></td>
                            <td><?php echo htmlspecialchars($excelText($row['ghichu'] ?? '')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php
    exit;
}

$title = 'In báo cáo công tác SC/BD/CC thiết bị';
require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="max-w-full mx-auto bg-white rounded-lg shadow-md p-4 md:p-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4 no-print">
        <h1 class="text-xl md:text-2xl font-bold text-blue-900">
            BÁO CÁO CÔNG TÁC BẢO DƯỠNG, SỬA CHỮA, CHUẨN CHỈNH THIẾT BỊ
        </h1>
        <div class="flex gap-2">
            <button onclick="window.print()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded font-semibold">
                <i class="fas fa-print mr-2"></i>In báo cáo
            </button>
            <a href="/iso2/baocaothang01_print.php?from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>&benyeucau=<?php echo urlencode($benYeuCau); ?>&export=excel"
               class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded font-semibold">
                <i class="fas fa-file-excel mr-2"></i>Xuất Excel (mới)
            </a>
            <a href="/iso2/baocaothang01.php?from=<?php echo urlencode($legacyFrom); ?>&to=<?php echo urlencode($legacyTo); ?>"
               target="_blank"
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded font-semibold"
               title="Bản cũ từ baocaothang01.php, chưa áp dụng định dạng mới">
                <i class="fas fa-file-excel mr-2"></i>Excel cũ (legacy)
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
