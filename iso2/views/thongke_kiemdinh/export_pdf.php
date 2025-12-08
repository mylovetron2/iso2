<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid black; padding: 6px; }
        th { background-color: #2563eb; color: white; font-weight: bold; text-align: center; }
        .text-center { text-align: center; }
        .status-dung { color: #16a34a; font-weight: bold; }
        .status-chua { color: #dc2626; font-weight: bold; }
        .status-truoc { color: #0d9488; font-weight: bold; }
        .status-sau { color: #0891b2; font-weight: bold; }
        .section-title { font-size: 13pt; font-weight: bold; margin: 20px 0 10px 0; color: #1e40af; border-bottom: 2px solid #2563eb; padding-bottom: 5px; }
        .list-item { line-height: 1.6; margin: 5px 0; }
    </style>
</head>
<body>
    <!-- Detailed Tables -->
    <div class="section-title">CHI TIẾT THIẾT BỊ THEO TRẠNG THÁI</div>
    <?php 
    $statusData = [
        'dung_ke_hoach' => ['title' => 'CHI TIẾT - ĐÚNG KẾ HOẠCH', 'class' => 'status-dung'],
        'chua_kiem_dinh' => ['title' => 'CHI TIẾT - CHƯA KIỂM ĐỊNH', 'class' => 'status-chua'],
        'truoc_han' => ['title' => 'CHI TIẾT - TRƯỚC HẠN', 'class' => 'status-truoc'],
        'sau_han' => ['title' => 'CHI TIẾT - SAU HẠN', 'class' => 'status-sau']
    ];
    
    foreach ($statusData as $status => $info):
        if (empty($statistics['details'][$status])) continue;
    ?>
    
    <div style="page-break-inside: avoid; margin-top: 15px;">
    <div class="section-title"><?php echo $info['title']; ?> (<?php echo count($statistics['details'][$status]); ?> thiết bị)</div>
    
    <table cellpadding="3" cellspacing="0" border="1" style="width: 100%; font-size: 9pt; border-collapse: collapse;">
        <colgroup>
            <col style="width: 5%;">
            <col style="width: 28%;">
            <col style="width: 15%;">
            <col style="width: 10%;">
            <col style="width: 17%;">
            <col style="width: 12%;">
            <col style="width: 13%;">
        </colgroup>
        <thead>
            <tr>
                <th style="background-color: #2563eb; color: white; font-weight: bold; text-align: center;">STT</th>
                <th style="background-color: #2563eb; color: white; font-weight: bold;">Tên thiết bị</th>
                <th style="background-color: #2563eb; color: white; font-weight: bold;">Mã hiệu</th>
                <th style="background-color: #2563eb; color: white; font-weight: bold; text-align: center;">Số máy</th>
                <th style="background-color: #2563eb; color: white; font-weight: bold;">Hãng SX</th>
                <th style="background-color: #2563eb; color: white; font-weight: bold; text-align: center;">Tháng KH</th>
                <th style="background-color: #2563eb; color: white; font-weight: bold; text-align: center;">Ngày KĐ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($statistics['details'][$status] as $idx => $item): 
                $plan = $item['plan'];
                $inspection = $item['inspection'];
            ?>
            <tr>
                <td style="text-align: center;"><?php echo $idx + 1; ?></td>
                <td><?php echo htmlspecialchars($plan['tenthietbi'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($plan['mahieu'] ?? '-'); ?></td>
                <td style="text-align: center;"><strong><?php echo htmlspecialchars($plan['somay']); ?></strong></td>
                <td><?php echo htmlspecialchars($plan['hangsx'] ?? '-'); ?></td>
                <td style="text-align: center;">Tháng <?php echo $plan['thang']; ?></td>
                <td style="text-align: center;" class="<?php echo $info['class']; ?>">
                    <?php 
                    if ($inspection && !empty($inspection['ngayhc'])) {
                        echo date('d/m/Y', strtotime($inspection['ngayhc']));
                    } else {
                        echo '-';
                    }
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endforeach; ?>

    <!-- Footer -->
    <p style="text-align: right; font-style: italic; font-size: 10pt; margin-top: 20px;">
        Ngày xuất báo cáo: <?php echo date('d/m/Y H:i'); ?>
    </p>
</body>
</html>
