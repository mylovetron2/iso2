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
        .status-truoc { color: #0d9488; font-weight: bold; }
        .status-dung  { color: #16a34a; font-weight: bold; }
        .status-sau   { color: #0891b2; font-weight: bold; }
        .status-chua  { color: #dc2626; font-weight: bold; }
    </style>
</head>
<body>
    <h3 style="color: #1e40af;">CHI TIẾT THIẾT BỊ THEO TRẠNG THÁI - QUÝ <?php echo (int)$qui; ?></h3>
    <?php
    $statusData = [
        'truoc_han'      => ['title' => 'CHI TIẾT - HOÀN THÀNH TRƯỚC HẠN', 'class' => 'status-truoc'],
        'dung_han'       => ['title' => 'CHI TIẾT - HOÀN THÀNH ĐÚNG HẠN',  'class' => 'status-dung'],
        'sau_han'        => ['title' => 'CHI TIẾT - HOÀN THÀNH SAU HẠN',   'class' => 'status-sau'],
        'chua_thuc_hien' => ['title' => 'CHI TIẾT - CHƯA THỰC HIỆN',       'class' => 'status-chua'],
    ];

    foreach ($statusData as $status => $info):
        if (empty($statistics['details'][$status]) || !is_array($statistics['details'][$status])) {
            continue;
        }
    ?>

    <h4 class="<?php echo $info['class']; ?>" style="margin-top: 20px;">
        <?php echo $info['title']; ?> (<?php echo count($statistics['details'][$status]); ?> thiết bị)
    </h4>

    <table border="1" cellpadding="4" cellspacing="0" style="width: 100%; font-size: 9pt;">
        <thead>
            <tr style="background-color: #2563eb; color: white;">
                <th width="5%" style="text-align: center;"><b>STT</b></th>
                <th width="44%"><b>Tên thiết bị</b></th>
                <th width="15%" style="text-align: center;"><b>Số S/N</b></th>
                <th width="9%" style="text-align: center;"><b>Quý 1</b></th>
                <th width="9%" style="text-align: center;"><b>Quý 2</b></th>
                <th width="9%" style="text-align: center;"><b>Quý 3</b></th>
                <th width="9%" style="text-align: center;"><b>Quý 4</b></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $rowNum = 0;
            foreach ($statistics['details'][$status] as $plan):
                $rowNum++;
            ?>
            <tr>
                <td width="5%" style="text-align: center;"><?php echo $rowNum; ?></td>
                <td width="44%"><?php echo htmlspecialchars($plan['ten_thietbi'] ?? '-'); ?></td>
                <td width="15%" style="text-align: center;"><?php echo htmlspecialchars($plan['so_serial'] ?? '-'); ?></td>

                <?php
                for ($q = 1; $q <= 4; $q++):
                    $quiField = 'qui_' . $q;
                    $quiHoanTat = 'qui_' . $q . '_hoantat';
                    $hasContent = !empty($plan[$quiField]) && trim($plan[$quiField]) !== '';
                    $isCompleted = !empty($plan[$quiHoanTat]);
                    $isTO = $hasContent && strtoupper(trim($plan[$quiField])) === 'TO';
                    $highlight = ($q === (int)$qui) ? '; border: 2px solid #f59e0b;' : '';

                    if (!$hasContent && !$isCompleted) {
                        echo '<td width="9%" style="text-align: center; background-color: #f3f4f6' . $highlight . '">-</td>';
                    } else {
                        $bgColor = $isTO ? '#d1fae5' : '#f9fafb';
                        $content = $isCompleted
                            ? '<b style="color:#16a34a;">&#10003;</b>'
                            : ($isTO ? '' : '<span style="color:#6b7280;">' . htmlspecialchars($plan[$quiField]) . '</span>');
                        echo '<td width="9%" style="text-align: center; background-color: ' . $bgColor . $highlight . '">' . $content . '</td>';
                    }
                endfor;
                ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <br/>
    <?php endforeach; ?>

    <p style="text-align: right; font-style: italic; font-size: 10pt; margin-top: 20px;">
        Ngày xuất báo cáo: <?php echo date('d/m/Y H:i'); ?>
    </p>
</body>
</html>
