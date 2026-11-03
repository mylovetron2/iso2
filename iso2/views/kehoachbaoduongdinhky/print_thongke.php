<?php
$escape = static function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$formatDate = static function ($value): string {
    $value = (string)($value ?? '');
    $datePart = substr($value, 0, 10);
    if ($value === '' || $datePart === '0000-00-00') {
        return '—';
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $datePart);
    return $date && $date->format('Y-m-d') === $datePart ? $date->format('d/m/Y') : $value;
};
$formatPercent = static function (int $count, int $total): string {
    return number_format($total > 0 ? $count * 100 / $total : 0, 2, ',', '.') . '%';
};
$displayDonVi = static function ($value): string {
    $value = trim((string)$value);
    return in_array(mb_strtoupper($value, 'UTF-8'), ['CNM', 'RDNGA'], true) ? 'XSCTBĐVL' : $value;
};
$statusInfo = [
    'truoc_han' => ['label' => 'Trước kế hoạch', 'color' => '#0d756b'],
    'dung_han' => ['label' => 'Đúng kế hoạch', 'color' => '#287c38'],
    'sau_han' => ['label' => 'Sau kế hoạch', 'color' => '#a65b00'],
    'chua_thuc_hien' => ['label' => 'Chưa thực hiện', 'color' => '#b52a31'],
];
$summary = $report['summary'];
$total = $summary['total_plans'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Báo cáo kết quả bảo dưỡng định kỳ năm <?php echo (int)$nam; ?></title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; color: #000; font: 11pt "Times New Roman", Times, serif; }
        .toolbar { padding: 12px; text-align: center; background: #e5e7eb; font: 14px Arial, sans-serif; }
        .toolbar button { padding: 8px 16px; margin: 0 4px; cursor: pointer; }
        .sheet { width: 279mm; max-width: 100%; margin: 12px auto; padding: 8mm; background: #fff; }
        h1 { margin: 0 0 5px; text-align: center; font-size: 19pt; }
        .report-header p { margin: 3px 0; text-align: center; font-size: 12pt; }
        h2 { margin: 12px 0 6px; font-size: 13pt; }
        .overview { display: flex; gap: 18mm; align-items: center; }
        .overview-data { flex: 1 1 60%; }
        .overview-data table { width: 100%; border-collapse: collapse; }
        .overview-data td { padding: 2px 6px 2px 0; vertical-align: top; }
        .overview-data td:nth-child(2) { min-width: 35px; text-align: right; font-weight: bold; }
        .overview-data td:nth-child(3) { padding-left: 12px; white-space: nowrap; }
        .chart { flex: 0 1 40%; display: flex; align-items: center; gap: 10px; }
        .chart svg { width: 35mm; height: 35mm; flex: none; }
        .legend { margin: 0; padding: 0; list-style: none; font-size: 9pt; }
        .legend li { display: flex; align-items: center; gap: 5px; margin: 3px 0; }
        .legend svg { width: 10px; height: 10px; }
        .details { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 9pt; }
        .details th, .details td { border: 1px solid #000; padding: 4px; vertical-align: middle; overflow-wrap: anywhere; }
        .details th { text-align: center; }
        .details td.center { text-align: center; }
        .details .date { white-space: nowrap; }
        .details small { display: block; font-size: 8pt; }
        .report-note { margin: 6px 0 0; font-size: 11pt; font-style: italic; }
        .signature { margin: 10px 0 0 45%; width: 30%; text-align: center; font-size: 12pt; break-inside: avoid; page-break-inside: avoid; }
        .signature p { margin: 0; }
        .signature .signer { margin-top: 22mm; }
        .page-wrap { width: 100%; border-collapse: collapse; }
        .page-wrap > thead > tr > td, .page-wrap > tbody > tr > td, .page-wrap > tfoot > tr > td { padding: 0; border: 0; }
        .page-gap { height: 0; }
        @media screen {
            body { background: #f3f4f6; }
            .sheet { max-width: none; box-shadow: 0 1px 8px #bbb; }
        }
        @media print {
            /* Lề trang bằng 0 để trình duyệt không in ngày giờ, tiêu đề và URL; lề thật do .page-gap và .sheet tạo */
            @page { size: A4 landscape; margin: 0; }
            .toolbar { display: none; }
            .page-wrap > thead { display: table-header-group; }
            .page-wrap > tfoot { display: table-footer-group; }
            .page-gap { height: 9mm; }
            .sheet { width: auto; max-width: none; margin: 0; padding: 0 9mm; }
            .overview { break-inside: avoid; page-break-inside: avoid; }
            .details thead { display: table-header-group; }
            .details tr { break-inside: avoid; page-break-inside: avoid; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <button type="button" onclick="window.print()">In báo cáo</button>
    <button type="button" onclick="window.close()">Đóng</button>
</div>
<table class="page-wrap">
<thead><tr><td><div class="page-gap"></div></td></tr></thead>
<tfoot><tr><td><div class="page-gap"></div></td></tr></tfoot>
<tbody><tr><td>
<main class="sheet">
    <header class="report-header">
        <h1>BÁO CÁO KẾT QUẢ THỰC HIỆN BẢO DƯỠNG ĐỊNH KỲ</h1>
        <p>Đơn vị: <?php echo $escape($reportUnit); ?></p>
        <p>Kỳ báo cáo: Từ đầu năm đến hết Quý <?php echo (int)$qui; ?> năm <?php echo (int)$nam; ?></p>
    </header>

    <h2>I. TỔNG QUAN</h2>
    <div class="overview">
        <div class="overview-data">
            <table>
                <tr><td>Tổng số thiết bị theo kế hoạch:</td><td><?php echo $total; ?></td><td></td></tr>
                <tr><td>Số thiết bị thực hiện trước kế hoạch:</td><td><?php echo $summary['truoc_han']; ?></td><td>chiếm tỷ lệ: <?php echo $formatPercent($summary['truoc_han'], $total); ?></td></tr>
                <tr><td>Số thiết bị thực hiện đúng kế hoạch:</td><td><?php echo $summary['dung_han']; ?></td><td>chiếm tỷ lệ: <?php echo $formatPercent($summary['dung_han'], $total); ?></td></tr>
                <tr><td>Số thiết bị thực hiện sau kế hoạch:</td><td><?php echo $summary['sau_han']; ?></td><td>chiếm tỷ lệ: <?php echo $formatPercent($summary['sau_han'], $total); ?></td></tr>
                <tr><td>Số thiết bị chưa thực hiện:</td><td><?php echo $summary['chua_thuc_hien']; ?></td><td>chiếm tỷ lệ: <?php echo $formatPercent($summary['chua_thuc_hien'], $total); ?></td></tr>
                <tr><td>Tỷ lệ hoàn thành:</td><td><?php echo number_format($summary['tyle_hoan_thanh'], 2, ',', '.'); ?>%</td><td></td></tr>
            </table>
        </div>
        <div class="chart">
            <svg viewBox="0 0 120 120" role="img" aria-label="Biểu đồ tỷ lệ thực hiện bảo dưỡng">
                <title>Biểu đồ tỷ lệ thực hiện bảo dưỡng</title>
                <circle cx="60" cy="60" r="48" fill="none" stroke="#dedede" stroke-width="20"></circle>
                <?php $offset = 0.0; foreach ($statusInfo as $key => $info): ?>
                    <?php
                    $segment = $total > 0 ? $summary[$key] * 100 / $total : 0;
                    if ($segment <= 0) {
                        continue;
                    }
                    ?>
                    <circle cx="60" cy="60" r="48" fill="none" stroke="<?php echo $info['color']; ?>"
                            stroke-width="20" pathLength="100"
                            stroke-dasharray="<?php echo sprintf('%.6F %.6F', $segment, 100 - $segment); ?>"
                            stroke-dashoffset="<?php echo sprintf('%.6F', -$offset); ?>"
                            transform="rotate(-90 60 60)"></circle>
                    <?php $offset += $segment; ?>
                <?php endforeach; ?>
            </svg>
            <ul class="legend">
                <?php foreach ($statusInfo as $key => $info): ?>
                    <li>
                        <svg viewBox="0 0 10 10" aria-hidden="true"><rect width="10" height="10" fill="<?php echo $info['color']; ?>"></rect></svg>
                        <span><?php echo $info['label']; ?>: <?php echo $summary[$key]; ?> (<?php echo $formatPercent($summary[$key], $total); ?>)</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <h2>II. CHI TIẾT THỰC HIỆN</h2>
    <table class="details">
        <colgroup>
            <col style="width: 4%"><col style="width: 12%"><col style="width: 8%"><col style="width: 12%">
            <col style="width: 8%"><col style="width: 8%"><col style="width: 8%"><col style="width: 10%">
            <col style="width: 15%"><col style="width: 15%">
        </colgroup>
        <thead>
            <tr>
                <th scope="col">STT</th>
                <th scope="col">Tên thiết bị</th>
                <th scope="col">Số S/N</th>
                <th scope="col">Bộ phận thực hiện chính</th>
                <th scope="col">Kế hoạch</th>
                <th scope="col">Ngày bắt đầu</th>
                <th scope="col">Ngày kết thúc</th>
                <th scope="col">Đáp ứng kế hoạch</th>
                <th scope="col">Người thực hiện</th>
                <th scope="col">Ghi chú</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($report['items'] === []): ?>
            <tr><td colspan="10" class="center">Không có thiết bị phù hợp với bộ lọc.</td></tr>
        <?php else: ?>
            <?php foreach ($report['items'] as $index => $item): ?>
                <?php
                $plannedLabels = array_map(static function (int $quarter): string {
                    return 'Quý ' . $quarter;
                }, $item['report_planned_quarters']);
                ?>
                <tr>
                    <td class="center"><?php echo $index + 1; ?></td>
                    <td><?php echo $escape($item['ten_thietbi'] ?? ''); ?></td>
                    <td class="center"><?php echo $escape($item['so_serial'] ?? ''); ?></td>
                    <td class="center"><?php echo $escape($displayDonVi($item['donvi_lam_chinh'] ?? '')); ?></td>
                    <td class="center">
                        <?php echo $escape(implode(', ', $plannedLabels)); ?>
                        <?php if (count($plannedLabels) > 1 && $item['report_reference_quarter'] !== null): ?>
                            <small>(đối chiếu Quý <?php echo $item['report_reference_quarter']; ?>)</small>
                        <?php endif; ?>
                    </td>
                    <td class="center date"><?php echo $escape($formatDate($item['ngay_thuc_hien'] ?? null)); ?></td>
                    <td class="center date"><?php echo $escape($formatDate($item['ngay_ket_thuc'] ?? null)); ?></td>
                    <td class="center"><?php echo $statusInfo[$item['report_status']]['label']; ?></td>
                    <td><?php echo nl2br($escape(implode("\n", $item['cac_nguoi_thuc_hien'] ?? []))); ?></td>
                    <td><?php echo nl2br($escape($item['ghi_chu'] ?? '')); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
    <p class="report-note">Lưu ý: Mốc thời gian để tính là ngày bắt đầu thực hiện</p>
    <div class="signature">
        <p>Xưởng trưởng Xưởng SCTBĐVL</p>
        <p class="signer">Đặng Văn Tuệ</p>
    </div>
</main>
</td></tr></tbody>
</table>
</body>
</html>
