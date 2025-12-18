<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; }
        h2 { text-align: center; color: #1e40af; margin-bottom: 5px; }
        h3 { text-align: center; color: #1e40af; font-size: 13pt; margin-top: 5px; }
        .summary-box { border: 2px solid #2563eb; padding: 10px; margin: 15px 0; background-color: #eff6ff; }
        .summary-row { padding: 5px 0; border-bottom: 1px solid #ddd; }
        .summary-label { font-weight: bold; }
        .dept-header { background-color: #2563eb; color: white; padding: 8px; margin-top: 15px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 10pt; }
        th, td { border: 1px solid #333; padding: 5px 3px; }
        th { background-color: #60a5fa; color: white; font-weight: bold; text-align: center; vertical-align: middle; }
        td { vertical-align: middle; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <h2>BÁO CÁO THIẾT BỊ CHƯA KIỂM ĐỊNH</h2>
    <h3>Năm <?php echo isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y'); ?></h3>
    
    <!-- Summary -->
    <div class="summary-box">
        <h3 style="margin-top: 0; color: #2563eb; font-size: 11pt;">TỔNG QUAN</h3>
        <div class="summary-row">
            <span class="summary-label">Tổng số bộ phận:</span>
            <span style="font-weight: bold; color: #2563eb;"><?php echo $statistics['summary']['total_departments']; ?> bộ phận</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Tổng số thiết bị:</span>
            <span style="font-weight: bold; color: #16a34a;"><?php echo $statistics['summary']['total_devices']; ?> thiết bị</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Đã kiểm định:</span>
            <span style="font-weight: bold; color: #059669;"><?php echo $statistics['summary']['inspected_devices']; ?> thiết bị</span>
        </div>
        <div class="summary-row" style="border-bottom: none;">
            <span class="summary-label">Chưa kiểm định:</span>
            <span style="font-weight: bold; color: #f97316;"><?php echo $statistics['summary']['not_inspected_devices']; ?> thiết bị</span>
        </div>
    </div>

    <!-- Departments Details -->
    <h3 style="color: #1e40af; font-size: 11pt; margin-top: 20px; border-bottom: 2px solid #2563eb; padding-bottom: 5px;">
        CHI TIẾT THEO BỘ PHẬN
    </h3>
    
    <?php if (empty($statistics['departments'])): ?>
        <p style="text-align: center; color: #16a34a; font-size: 12pt; margin: 30px 0;">
            ✓ Tất cả thiết bị đã được kiểm định
        </p>
    <?php else: ?>
        <?php foreach ($statistics['departments'] as $dept): ?>
            <?php if ($dept['not_inspected_devices'] > 0): ?>
                <div style="margin: 5px 0;"></div>
                
                <!-- Department Header -->
                <div class="dept-header">
                    <?php echo htmlspecialchars($dept['tendv']); ?> (<?php echo htmlspecialchars($dept['madv']); ?>)
                    <br>
                    Chưa KĐ: <?php echo $dept['not_inspected_devices']; ?> / <?php echo $dept['total_devices']; ?> thiết bị
                </div>

                <div style="margin: 5px 0;"></div>

                <!-- Devices Table -->
                <?php if (!empty($dept['devices'])): ?>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 5%; text-align: center; background-color: #f97316; color: white; font-size: 11pt; padding: 8px;">STT</th>
                            <th style="width: 20%; text-align: center; background-color: #f97316; color: white; font-size: 11pt; padding: 8px;">Mã máy</th>
                            <th style="width: 40%; text-align: center; background-color: #f97316; color: white; font-size: 11pt; padding: 8px;">Tên thiết bị</th>
                            <th style="width: 17%; text-align: center; background-color: #f97316; color: white; font-size: 11pt; padding: 8px;">Số máy</th>
                            <th style="width: 18%; text-align: center; background-color: #f97316; color: white; font-size: 11pt; padding: 8px;">Hãng SX</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dept['devices'] as $index => $device): ?>
                        <tr>
                            <td style="width: 5%; text-align: center;"><?php echo $index + 1; ?></td>
                            <td style="width: 20%; font-weight: bold;"><?php echo htmlspecialchars($device['mavattu']); ?></td>
                            <td style="width: 40%;"><?php echo htmlspecialchars($device['tenthietbi']); ?></td>
                            <td style="width: 17%; text-align: center; font-weight: bold;">
                                <?php echo htmlspecialchars($device['somay']); ?>
                            </td>
                            <td style="width: 18%;"><?php echo htmlspecialchars($device['hangsx'] ?? '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Footer -->
    <p style="text-align: right; font-style: italic; font-size: 8pt; margin-top: 20px;">
        Ngày xuất báo cáo: <?php echo date('d/m/Y H:i'); ?>
    </p>
</body>
</html>
