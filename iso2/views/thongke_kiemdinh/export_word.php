<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta charset="UTF-8">
    <title>Báo cáo Thống kê Kiểm định <?php echo $namkh; ?></title>
    <style>
        @page {
            size: A4;
            margin: 2cm 2cm 2cm 2cm;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 13pt;
            line-height: 1.5;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .company-name {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .department {
            font-size: 10pt;
            font-style: italic;
        }
        .title {
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 20px 0;
        }
        .subtitle {
            font-size: 13pt;
            font-style: italic;
            margin-bottom: 30px;
        }
        .summary-box {
            border: 2px solid #2563eb;
            padding: 15px;
            margin: 20px 0;
            background-color: #eff6ff;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #ddd;
        }
        .summary-label {
            font-weight: bold;
        }
        .summary-value {
            font-weight: bold;
            color: #1e40af;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #dbeafe;
            font-weight: bold;
            text-align: center;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .status-dung {
            color: #16a34a;
            font-weight: bold;
        }
        .status-chua {
            color: #dc2626;
            font-weight: bold;
        }
        .status-truoc {
            color: #0d9488;
            font-weight: bold;
        }
        .status-sau {
            color: #0891b2;
            font-weight: bold;
        }
        .section-title {
            font-size: 14pt;
            font-weight: bold;
            margin: 25px 0 10px 0;
            color: #1e40af;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 5px;
        }
        .footer {
            margin-top: 40px;
            text-align: right;
        }
        .signature {
            margin-top: 10px;
            font-style: italic;
        }
        .chart-text {
            font-size: 11pt;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="title">BÁO CÁO THỐNG KÊ KIỂM ĐỊNH THIẾT BỊ</div>
        <div class="subtitle">Năm <?php echo $namkh; ?></div>
    </div>

    <!-- Summary Section -->
    <div class="summary-box">
        <h3 style="margin-top: 0; color: #1e40af;">TỔNG QUAN</h3>
        <div class="summary-row">
            <span class="summary-label">Tổng số kế hoạch kiểm định:</span>
            <span class="summary-value"><?php echo $statistics['summary']['total_plans']; ?> thiết bị</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Đã kiểm định đúng kế hoạch:</span>
            <span class="summary-value" style="color: #16a34a;"><?php echo $statistics['summary']['dung_ke_hoach']; ?> thiết bị</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Chưa kiểm định:</span>
            <span class="summary-value" style="color: #dc2626;"><?php echo $statistics['summary']['chua_kiem_dinh']; ?> thiết bị</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Kiểm định trước hạn:</span>
            <span class="summary-value" style="color: #0d9488;"><?php echo $statistics['summary']['truoc_han']; ?> thiết bị</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Kiểm định sau hạn:</span>
            <span class="summary-value" style="color: #0891b2;"><?php echo $statistics['summary']['sau_han']; ?> thiết bị</span>
        </div>
        <div class="summary-row" style="border-bottom: none; margin-top: 10px; background-color: white; padding: 10px;">
            <span class="summary-label" style="font-size: 14pt;">TỶ LỆ HOÀN THÀNH:</span>
            <span class="summary-value" style="font-size: 18pt; color: #16a34a;"><?php echo $statistics['summary']['tyle_hoan_thanh']; ?>%</span>
        </div>
    </div>

    <!-- Chart Section -->
    <div style="margin: 30px 0; text-align: center;">
        <h3 style="color: #1e40af; margin-bottom: 20px;">BIỂU ĐỒ PHÂN BỔ TRẠNG THÁI</h3>
        
        <?php
        $total = $statistics['summary']['total_plans'];
        $pct_dung = $total > 0 ? round(($statistics['summary']['dung_ke_hoach']/$total)*100, 1) : 0;
        $pct_chua = $total > 0 ? round(($statistics['summary']['chua_kiem_dinh']/$total)*100, 1) : 0;
        $pct_truoc = $total > 0 ? round(($statistics['summary']['truoc_han']/$total)*100, 1) : 0;
        $pct_sau = $total > 0 ? round(($statistics['summary']['sau_han']/$total)*100, 1) : 0;
        ?>
        
        <table style="width: 100%; margin: 20px auto; border: none;">
            <tr>
                <!-- Pie Chart using table cells -->
                <td style="width: 45%; vertical-align: top; text-align: center;">
                    <table style="width: 280px; height: 280px; margin: 0 auto; border-collapse: collapse; border: 3px solid #1e40af;">
                        <tr style="height: 50%;">
                            <td style="width: 50%; background-color: #16a34a; border: 1px solid white;"></td>
                            <td style="width: 50%; background-color: <?php echo $pct_chua > $pct_dung ? '#dc2626' : '#16a34a'; ?>; border: 1px solid white;"></td>
                        </tr>
                        <tr style="height: 50%;">
                            <td style="width: 50%; background-color: <?php echo $pct_sau > 0 ? '#0891b2' : '#0d9488'; ?>; border: 1px solid white;"></td>
                            <td style="width: 50%; background-color: <?php echo ($pct_truoc + $pct_sau) > ($pct_dung + $pct_chua) ? '#0d9488' : '#dc2626'; ?>; border: 1px solid white;"></td>
                        </tr>
                    </table>
                    <p style="font-size: 10pt; font-style: italic; margin-top: 10px;">Biểu đồ tròn phân bổ 4 trạng thái</p>
                </td>
                
                <!-- Legend -->
                <td style="width: 55%; vertical-align: middle; padding-left: 30px;">
                    <table style="width: 100%; border: none;">
                        <tr>
                            <td style="border: none; padding: 12px;">
                                <table style="border: none; width: 100%;">
                                    <tr>
                                        <td style="border: none; width: 50px;">
                                            <div style="width: 45px; height: 45px; background-color: #16a34a; border: 2px solid #1e40af;"></div>
                                        </td>
                                        <td style="border: none; text-align: left;">
                                            <div style="font-size: 12pt; font-weight: bold;">Đúng kế hoạch</div>
                                            <div style="font-size: 14pt; color: #16a34a; font-weight: bold;">
                                                <?php echo $statistics['summary']['dung_ke_hoach']; ?> thiết bị (<?php echo $pct_dung; ?>%)
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 12px;">
                                <table style="border: none; width: 100%;">
                                    <tr>
                                        <td style="border: none; width: 50px;">
                                            <div style="width: 45px; height: 45px; background-color: #dc2626; border: 2px solid #1e40af;"></div>
                                        </td>
                                        <td style="border: none; text-align: left;">
                                            <div style="font-size: 12pt; font-weight: bold;">Chưa kiểm định</div>
                                            <div style="font-size: 14pt; color: #dc2626; font-weight: bold;">
                                                <?php echo $statistics['summary']['chua_kiem_dinh']; ?> thiết bị (<?php echo $pct_chua; ?>%)
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 12px;">
                                <table style="border: none; width: 100%;">
                                    <tr>
                                        <td style="border: none; width: 50px;">
                                            <div style="width: 45px; height: 45px; background-color: #0d9488; border: 2px solid #1e40af;"></div>
                                        </td>
                                        <td style="border: none; text-align: left;">
                                            <div style="font-size: 12pt; font-weight: bold;">Trước hạn</div>
                                            <div style="font-size: 14pt; color: #0d9488; font-weight: bold;">
                                                <?php echo $statistics['summary']['truoc_han']; ?> thiết bị (<?php echo $pct_truoc; ?>%)
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 12px;">
                                <table style="border: none; width: 100%;">
                                    <tr>
                                        <td style="border: none; width: 50px;">
                                            <div style="width: 45px; height: 45px; background-color: #0891b2; border: 2px solid #1e40af;"></div>
                                        </td>
                                        <td style="border: none; text-align: left;">
                                            <div style="font-size: 12pt; font-weight: bold;">Sau hạn</div>
                                            <div style="font-size: 14pt; color: #0891b2; font-weight: bold;">
                                                <?php echo $statistics['summary']['sau_han']; ?> thiết bị (<?php echo $pct_sau; ?>%)
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- Device Lists -->
    <div style="page-break-before: always;"></div>
    <div class="section-title">DANH SÁCH THIẾT BỊ THEO TRẠNG THÁI</div>
    
    <?php 
    // Simple lists for all statuses including dung_ke_hoach
    $listStatuses = [
        'dung_ke_hoach' => ['label' => 'ĐÚNG KẾ HOẠCH', 'color' => '#16a34a'],
        'chua_kiem_dinh' => ['label' => 'CHƯA KIỂM ĐỊNH', 'color' => '#dc2626'],
        'truoc_han' => ['label' => 'TRƯỚC HẠN', 'color' => '#0d9488'],
        'sau_han' => ['label' => 'SAU HẠN', 'color' => '#0891b2']
    ];
    
    foreach ($listStatuses as $status => $info):
        if (empty($statistics['details'][$status])) continue;
    ?>
    <div style="margin: 20px 0;">
        <h4 style="color: <?php echo $info['color']; ?>; border-left: 4px solid; padding-left: 10px;">
            <?php echo $info['label']; ?> (<?php echo count($statistics['details'][$status]); ?> thiết bị)
        </h4>
        <ol style="line-height: 1.8;">
            <?php foreach ($statistics['details'][$status] as $item): 
                $plan = $item['plan'];
                $inspection = $item['inspection'];
            ?>
            <li>
                <strong><?php echo htmlspecialchars($plan['tenthietbi'] ?? $plan['mahieu']); ?></strong> - 
                Số máy: <?php echo htmlspecialchars($plan['somay']); ?> - 
                KH: Tháng <?php echo $plan['thang']; ?>
                <?php if ($inspection && !empty($inspection['ngayhc'])): ?>
                 - KĐ: <?php echo date('d/m/Y', strtotime($inspection['ngayhc'])); ?>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ol>
    </div>
    <?php endforeach; ?>

    <!-- Detailed Tables -->
    <div style="page-break-before: always;"></div>
    <?php 
    $statusData = [
        'dung_ke_hoach' => ['title' => 'THIẾT BỊ KIỂM ĐỊNH ĐÚNG KẾ HOẠCH', 'class' => 'status-dung'],
        'chua_kiem_dinh' => ['title' => 'THIẾT BỊ CHƯA KIỂM ĐỊNH', 'class' => 'status-chua'],
        'truoc_han' => ['title' => 'THIẾT BỊ KIỂM ĐỊNH TRƯỚC HẠN', 'class' => 'status-truoc'],
        'sau_han' => ['title' => 'THIẾT BỊ KIỂM ĐỊNH SAU HẠN', 'class' => 'status-sau']
    ];
    
    foreach ($statusData as $status => $info):
        if (empty($statistics['details'][$status])) continue;
    ?>
    
    <div style="page-break-before: always;"></div>
    <div class="section-title"><?php echo $info['title']; ?> (<?php echo count($statistics['details'][$status]); ?> thiết bị)</div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">STT</th>
                <th style="width: 20%;">Tên thiết bị</th>
                <th style="width: 15%;">Mã hiệu</th>
                <th style="width: 10%;">Số máy</th>
                <th style="width: 10%;">Hãng SX</th>
                <th style="width: 10%;">Tháng KH</th>
                <th style="width: 15%;">Khoảng cho phép</th>
                <th style="width: 15%;">Ngày kiểm định</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($statistics['details'][$status] as $idx => $item): 
                $plan = $item['plan'];
                $inspection = $item['inspection'];
                $dateRange = $item['date_range'];
            ?>
            <tr>
                <td class="text-center"><?php echo $idx + 1; ?></td>
                <td><?php echo htmlspecialchars($plan['tenthietbi'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($plan['mahieu'] ?? '-'); ?></td>
                <td class="text-center"><strong><?php echo htmlspecialchars($plan['somay']); ?></strong></td>
                <td><?php echo htmlspecialchars($plan['hangsx'] ?? '-'); ?></td>
                <td class="text-center">Tháng <?php echo $plan['thang']; ?></td>
                <td class="text-center" style="font-size: 10pt;">
                    <?php echo date('d/m/Y', strtotime($dateRange['start'])); ?><br>đến<br><?php echo date('d/m/Y', strtotime($dateRange['end'])); ?>
                </td>
                <td class="text-center <?php echo $info['class']; ?>">
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
    <?php endforeach; ?>

    <!-- Footer -->
    <div style="page-break-before: always;"></div>
    <div class="footer">
        <p style="margin: 5px 0;"><i>Ngày xuất báo cáo: <?php echo date('d/m/Y H:i'); ?></i></p>
    </div>
</body>
</html>
