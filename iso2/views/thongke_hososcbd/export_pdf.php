<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; }
        h2 { text-align: center; color: #1e40af; margin-bottom: 5px; }
        h3 { text-align: center; color: #1e40af; font-size: 11pt; margin-top: 5px; }
        .summary-box { border: 2px solid #f97316; padding: 10px; margin: 15px 0; background-color: #fff7ed; }
        .summary-row { padding: 5px 0; border-bottom: 1px solid #ddd; }
        .summary-label { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 9pt; }
        th, td { border: 1px solid #333; padding: 6px 4px; }
        th { background-color: #2563eb; color: white; font-weight: bold; text-align: center; vertical-align: middle; }
        td { vertical-align: middle; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .badge-red { background-color: #fecaca; color: #991b1b; padding: 2px 6px; border-radius: 3px; font-weight: bold; }
        .badge-orange { background-color: #fed7aa; color: #9a3412; padding: 2px 6px; border-radius: 3px; font-weight: bold; }
        .badge-yellow { background-color: #fef08a; color: #854d0e; padding: 2px 6px; border-radius: 3px; font-weight: bold; }
    </style>
</head>
<body>
    <h2>BÁO CÁO HỒ SƠ SCBD</h2>
    <h3>(Ngày KT - Ngày TH >= <?php echo isset($_GET['min_days']) ? (int)$_GET['min_days'] : 30; ?> ngày)</h3>
    
    <!-- Summary -->
    <div class="summary-box">
        <h3 style="margin-top: 0; color: #f97316; font-size: 11pt;">TỔNG QUAN</h3>
        <div class="summary-row">
            <span class="summary-label">Tổng số hồ sơ:</span>
            <span style="font-weight: bold; color: #f97316;"><?php echo $statistics['summary']['total']; ?> hồ sơ</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Số ngày trễ nhiều nhất:</span>
            <span style="font-weight: bold; color: #dc2626;"><?php echo $statistics['summary']['max_days']; ?> ngày</span>
        </div>
        <div class="summary-row" style="border-bottom: none;">
            <span class="summary-label">Số ngày trễ ít nhất:</span>
            <span style="font-weight: bold; color: #eab308;"><?php echo $statistics['summary']['min_days']; ?> ngày</span>
        </div>
    </div>

    <!-- Details Table -->
    <h3 style="color: #1e40af; font-size: 11pt; margin-top: 20px; border-bottom: 2px solid #2563eb; padding-bottom: 5px;">
        CHI TIẾT HỒ SƠ SCBD
    </h3>
    
    <?php if (empty($statistics['records'])): ?>
        <p style="text-align: center; color: #16a34a; font-size: 12pt; margin: 30px 0;">
            ✓ Không có hồ sơ nào trễ hạn >= 30 ngày
        </p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th style="width: 3%;">STT</th>
                    <th style="width: 9%;">Số phiếu</th>
                    <th style="width: 24%;">Thiết bị</th>
                    <th style="width: 11%;">Mã hiệu</th>
                    <th style="width: 7%;">Số máy</th>
                    <th style="width: 18%;">Đơn vị</th>
                    <th style="width: 9%;">Ngày TH</th>
                    <th style="width: 9%;">Ngày KT</th>
                    <th style="width: 10%;">Số ngày trễ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($statistics['records'] as $index => $record): ?>
                    <tr>
                        <td class="text-center"><?php echo $index + 1; ?></td>
                        <td style="font-weight: bold;"><?php echo htmlspecialchars($record['phieu'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($record['tentb'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($record['mahieu'] ?? '-'); ?></td>
                        <td class="text-center" style="font-weight: bold;"><?php echo htmlspecialchars($record['somay'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($record['tendv'] ?? '-'); ?></td>
                        <td class="text-center">
                            <?php echo $record['ngayth'] ? date('d/m/Y', strtotime($record['ngayth'])) : '-'; ?>
                        </td>
                        <td class="text-center">
                            <?php echo $record['ngaykt'] ? date('d/m/Y', strtotime($record['ngaykt'])) : '-'; ?>
                        </td>
                        <td class="text-center">
                            <span class="<?php 
                                if ($record['so_ngay_tre'] >= 90) {
                                    echo 'badge-red';
                                } elseif ($record['so_ngay_tre'] >= 60) {
                                    echo 'badge-orange';
                                } else {
                                    echo 'badge-yellow';
                                }
                            ?>">
                                <?php echo $record['so_ngay_tre']; ?> ngày
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Footer -->
    <p style="text-align: right; font-style: italic; font-size: 8pt; margin-top: 20px;">
        Ngày xuất báo cáo: <?php echo date('d/m/Y H:i'); ?>
    </p>
</body>
</html>
