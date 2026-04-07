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
        .status-complete { color: #16a34a; font-weight: bold; }
        .status-incomplete { color: #dc2626; font-weight: bold; }
        .status-partial { color: #f59e0b; font-weight: bold; }
        .section-title { font-size: 13pt; font-weight: bold; margin: 20px 0 10px 0; color: #1e40af; border-bottom: 2px solid #2563eb; padding-bottom: 5px; }
    </style>
</head>
<body>
    <!-- Detailed Tables -->
    <h3 style="color: #1e40af;">CHI TIẾT THIẾT BỊ THEO TRẠNG THÁI</h3>
    <?php 
    // Xác định danh sách trạng thái dựa trên việc có chọn quý hay không
    if (!empty($statistics['summary']['selected_qui'])) {
        // Khi chọn quý: 4 trạng thái
        $statusData = [
            'da_hoan_thanh' => ['title' => 'CHI TIẾT - HOÀN THÀNH ĐÚNG HẠN', 'class' => 'status-complete'],
            'truoc_han' => ['title' => 'CHI TIẾT - HOÀN THÀNH TRƯỚC HẠN', 'class' => 'status-complete'],
            'sau_han' => ['title' => 'CHI TIẾT - HOÀN THÀNH SAU HẠN', 'class' => 'status-partial'],
            'chua_hoan_thanh' => ['title' => 'CHI TIẾT - CHƯA HOÀN THÀNH', 'class' => 'status-incomplete']
        ];
    } else {
        // Khi không chọn quý: 3 trạng thái
        $statusData = [
            'da_hoan_thanh' => ['title' => 'CHI TIẾT - ĐÃ HOÀN THÀNH', 'class' => 'status-complete'],
            'hoan_thanh_mot_phan' => ['title' => 'CHI TIẾT - HOÀN THÀNH MỘT PHẦN', 'class' => 'status-partial'],
            'chua_hoan_thanh' => ['title' => 'CHI TIẾT - CHƯA HOÀN THÀNH', 'class' => 'status-incomplete']
        ];
    }
    
    foreach ($statusData as $status => $info):
        // Kiểm tra kỹ hơn: phải có dữ liệu và count > 0
        if (!isset($statistics['details'][$status]) || 
            !is_array($statistics['details'][$status]) ||
            count($statistics['details'][$status]) == 0) {
            continue;
        }
    ?>
    
    <h4 style="color: #2563eb; margin-top: 20px;"><?php echo $info['title']; ?> (<?php echo count($statistics['details'][$status]); ?> thiết bị)</h4>
    
    <table border="1" cellpadding="4" cellspacing="0" style="width: 100%; font-size: 9pt;">
        <thead>
            <tr style="background-color: #2563eb; color: white;">
                <th width="5%" style="text-align: center;"><b>STT</b></th>
                <th width="35%"><b>Tên thiết bị</b></th>
                <th width="15%" style="text-align: center;"><b>Số S/N</b></th>
                <th width="9%" style="text-align: center;"><b>Nhóm</b></th>
                <th width="9%" style="text-align: center;"><b>Quý 1</b></th>
                <th width="9%" style="text-align: center;"><b>Quý 2</b></th>
                <th width="9%" style="text-align: center;"><b>Quý 3</b></th>
                <th width="9%" style="text-align: center;"><b>Quý 4</b></th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $rowNum = 0;
            foreach ($statistics['details'][$status] as $idx => $plan): 
                $rowNum++;
            ?>
            <tr>
                <td style="text-align: center;"><?php echo $rowNum; ?></td>
                <td><?php echo htmlspecialchars($plan['ten_thietbi'] ?? '-'); ?></td>
                <td style="text-align: center;"><?php echo htmlspecialchars($plan['so_serial'] ?? '-'); ?></td>
                <td style="text-align: center;"><?php echo htmlspecialchars($plan['nhomsc'] ?? '-'); ?></td>
                
                <?php 
                // Display quarters
                for ($q = 1; $q <= 4; $q++):
                    $quiField = 'qui_' . $q;
                    $quiHoanTat = 'qui_' . $q . '_hoantat';
                    $hasContent = !empty($plan[$quiField]) && trim($plan[$quiField]) !== '';
                    $isCompleted = !empty($plan[$quiHoanTat]);
                    
                    if (!$hasContent):
                        echo '<td style="text-align: center; background-color: #f3f4f6;">-</td>';
                    else:
                        if ($isCompleted):
                            echo '<td style="text-align: center; background-color: #d1fae5; color: #16a34a;"><b>&#10003;</b></td>';
                        else:
                            echo '<td style="text-align: center; background-color: #fee2e2; color: #dc2626;"><b>&#10007;</b></td>';
                        endif;
                    endif;
                endfor;
                ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <br/>
    <?php endforeach; ?>

    <!-- Footer -->
    <p style="text-align: right; font-style: italic; font-size: 10pt; margin-top: 20px;">
        Ngày xuất báo cáo: <?php echo date('d/m/Y H:i'); ?>
    </p>
</body>
</html>
