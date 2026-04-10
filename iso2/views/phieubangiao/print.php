<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biên bản bàn giao thiết bị - <?php echo htmlspecialchars($item['sophieu']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 13pt;
            line-height: 150%;
            padding: 28.4pt 36pt 28.4pt 20mm;
            color: #000;
        }
        
        .header-line {
            font-size: 14pt;
            margin-bottom: 0;
        }
        
        .body-text {
            font-size: 13pt;
            line-height: 150%;
            margin-left: 18pt;
            text-indent: -18pt;
        }
        
        .devices-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            margin-left: -12.6pt;
        }
        
        .devices-table th,
        .devices-table td {
            border: solid #000 1pt;
            padding: 0cm 5.4pt;
            text-align: center;
            font-size: 13pt;
        }
        
        .devices-table th {
            font-weight: bold;
            vertical-align: middle;
        }
        
        .note-lines {
            font-size: 12pt;
            margin: 6pt 0 6pt 36pt;
        }
        
        .signature-line {
            font-size: 13pt;
            margin-left: 42.6pt;
            text-indent: -173.45pt;
            margin-bottom: 12pt;
        }
        
        .footer {
            font-size: 10pt;
            margin-top: 40pt;
        }
        
        @media print {
            body {
                padding: 28.4pt 36pt 28.4pt 20mm;
            }
            
            @page {
                size: 21cm 842pt;
                margin: 0;
            }
        }
        
        @media screen {
            body {
                max-width: 210mm;
                margin: 20px auto;
                background: #fff;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
        }
    </style>
</head>
<body>
    <p style="font-size:13pt">&nbsp;</p>
    <p style="font-size:13pt">&nbsp;</p>
    
    <p class="header-line">
        &nbsp;&nbsp;XN Địa vật lý GK&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>BIÊN BẢN BÀN GIAO THIẾT BỊ</b>
    </p>
    
    <p class="header-line">
        &nbsp;&nbsp;<b>Xưởng SCTBĐVL</b><span style="mso-tab-count:11">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span style="font-size:13pt">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>Số hồ sơ: <b>...<?php echo htmlspecialchars($item['sophieu']); ?>...</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Ngày:<b>...<?php echo date('d/m/Y', strtotime($item['ngaybg'])); ?>...</b>
    </p>
    
    <p style="font-size:13pt">&nbsp;</p>
    
    <p class="body-text">
        1.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Bên giao,Сдал<b>...<?php echo htmlspecialchars($item['nguoigiao']); ?>...&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</b>Đơn vị, Подр<b>..<?php echo htmlspecialchars($item['donvigiao_tendv'] ?? $item['donvigiao']); ?>..</b>
    </p>
    
    <p class="body-text">
        2.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Bên nhận,Принял<b>....&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</b><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</b>Đơn vị, Подр<b>..<?php echo htmlspecialchars($item['donvinhan_tendv'] ?? $item['donvinhan']); ?>..</b>
    </p>
    
    <p class="body-text">
        3.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Nội dung: Bên nhận sau khi kiểm tra tình trạng kỹ thuật của thiết bị và đã thống nhất với bên giao cùng nhau giao nhận các thiết bị sau:
    </p>
    
    <p style="font-size:14pt">&nbsp;</p>
    
    <table class="devices-table">
        <tr>
            <td width="36" style="width:36pt">
                <p style="text-align:center;margin:0"><b>STT</b></p>
                <p style="text-align:center;margin:0">П/П</p>
            </td>
            <td width="126" style="width:126pt">
                <p style="text-align:center;margin:0"><b>Tên thiết bị</b></p>
                <p style="text-align:center;margin:0">Наим-е оборудования</p>
            </td>
            <td width="85.5" style="width:85.5pt">
                <p style="text-align:center;margin:0"><b>Số của thiết bị - Serial</b></p>
                <p style="text-align:center;margin:0">Номер</p>
            </td>
            <td width="135" style="width:135pt">
                <p style="text-align:center;margin:0"><b>Tình trạng kỹ thuật của thiết bị</b></p>
                <p style="text-align:center;margin:0">Тех. состояние</p>
            </td>
            <td width="135" style="width:135pt">
                <p style="text-align:center;margin:0"><b>Ghi chú</b></p>
            </td>
        </tr>
        <?php $no = 1; foreach ($devices as $device): ?>
        <tr>
            <td><p style="text-align:center;margin:0"><?php echo $no++; ?></p></td>
            <td><p style="text-align:center;margin:0"><?php echo htmlspecialchars($device['tenvt']); ?></p></td>
            <td><p style="text-align:center;margin:0"><?php echo htmlspecialchars($device['somay'] ?: 'NA'); ?></p></td>
            <td><p style="text-align:center;margin:0"><?php echo htmlspecialchars($device['tinhtrang'] ?: 'Đạt'); ?></p></td>
            <td><p style="text-align:center;margin:0"><?php echo htmlspecialchars($device['ghichu'] ?: ''); ?></p></td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <p style="font-size:14pt">&nbsp;</p>
    
    <p class="body-text">
        4.&nbsp;&nbsp;&nbsp;&nbsp;Ghi chú:
    </p>
    
    <?php if (!empty($item['ghichu'])): ?>
    <p class="note-lines"><?php echo nl2br(htmlspecialchars($item['ghichu'])); ?></p>
    <?php else: ?>
    <p class="note-lines"><sub>………………………………………………………………………………………………………………</sub></p>
    <p class="note-lines"><sub>………………………………………………………………………………………………………………</sub></p>
    <p class="note-lines"><sub>………………………………………………………………………………………………………………</sub></p>
    <p class="note-lines"><sub>………………………………………………………………………………………………………………</sub></p>
    <?php endif; ?>
    
    <p style="font-size:7pt;margin-left:216pt;text-indent:-173.45pt">&nbsp;</p>
    
    <p class="signature-line">
        <b>Bên giao</b> (Сдал /Подпись): <sub>………………………………………………<i>(ký tên)</i></sub>
    </p>
    
    <p class="signature-line">
        <b>Bên nhận</b> (Принял /Подпись): <sub>………………………………………………<i>(ký tên)</i></sub>
    </p>
    
    <p style="font-size:13pt">&nbsp;</p>
    
    <div class="footer">
        BM.25.07<br>
        01/01/2024
    </div>

    <script>
        // Auto print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>

    <script>
        // Auto print when page loads
        window.onload = function() {
            // Small delay to ensure page is fully loaded
            setTimeout(function() {
                window.print();
            }, 500);
        };
        
        // Handle after print
        window.onafterprint = function() {
            // Optional: close window after printing
            // Uncomment if you want to auto-close
            // window.close();
        };
    </script>
</body>
</html>
