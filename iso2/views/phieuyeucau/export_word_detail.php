<?php
declare(strict_types=1);

/**
 * Export danh sách thiết bị chi tiết với thông tin BDDK ra Word
 */

// Set headers for Word
header("Content-Type: application/vnd.ms-word");
header("Content-Disposition: attachment; filename=\"ChiTiet_Phieu_{$summary['phieu']}_" . date('Ymd_His') . ".doc\""); 
header("Cache-Control: max-age=0");

// Output UTF-8 BOM for proper encoding
echo "\xEF\xBB\xBF";

/**
 * Escape text for HTML/Word
 */
function escapeText($text) {
    if ($text === null || $text === '') {
        return '';
    }
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:w="urn:schemas-microsoft-com:office:word"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<style>
@page {
    size: A4 portrait;
    margin: 2cm 1.5cm;
}
body {
    font-family: 'Times New Roman', Times, serif;
    font-size: 13pt;
}
.header {
    text-align: center;
    font-weight: bold;
    font-size: 16pt;
    margin-bottom: 20pt;
}
.info {
    margin-bottom: 10pt;
}
.info-label {
    font-weight: bold;
    display: inline-block;
    width: 150pt;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15pt;
}
table th {
    background-color: #4472C4;
    color: white;
    font-weight: bold;
    padding: 8pt;
    border: 1pt solid black;
    text-align: center;
}
table td {
    padding: 6pt;
    border: 1pt solid black;
}
.center {
    text-align: center;
}
.bddk-complete {
    background-color: #70AD47;
    color: white;
    font-weight: bold;
    padding: 2pt 6pt;
    display: inline-block;
    margin: 2pt;
    border-radius: 3pt;
}
.bddk-incomplete {
    background-color: #D9D9D9;
    padding: 2pt 6pt;
    display: inline-block;
    margin: 2pt;
    border-radius: 3pt;
}
</style>
</head>
<body>

<div class="header">
    DANH SÁCH THIẾT BỊ CHI TIẾT<br>
    PHIẾU YÊU CẦU DỊCH VỤ
</div>

<div class="info">
    <span class="info-label">Số phiếu:</span>
    <span><?php echo escapeText($summary['phieu']); ?></span>
</div>

<div class="info">
    <span class="info-label">Ngày yêu cầu:</span>
    <span><?php echo date('d/m/Y', strtotime($summary['ngayyc'])); ?></span>
</div>

<div class="info">
    <span class="info-label">Đơn vị:</span>
    <span><?php echo escapeText($summary['tendv']); ?> (<?php echo escapeText($summary['madv']); ?>)</span>
</div>

<?php if (!empty($summary['ngyeucau'])): ?>
<div class="info">
    <span class="info-label">Người yêu cầu:</span>
    <span><?php echo escapeText($summary['ngyeucau']); ?></span>
</div>
<?php endif; ?>

<?php if (!empty($summary['nhomsc'])): ?>
<div class="info">
    <span class="info-label">Nhóm SC:</span>
    <span><?php echo escapeText($summary['nhomsc']); ?></span>
</div>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th style="width: 40pt;">STT</th>
            <th style="width: 100pt;">Mã VT</th>
            <th style="width: 120pt;">Số máy</th>
            <th>BDDK</th>
            <th style="width: 80pt;">HC/KĐ</th>
            <th style="width: 120pt;">Ghi chú</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $stt = 1;
        foreach ($devices as $device): 
            // Xử lý BDDK
            $bddkDisplay = '';
            if (!empty($device['bddk_quarters']) && is_array($device['bddk_quarters'])) {
                $bddkParts = [];
                foreach ($device['bddk_quarters'] as $qData) {
                    $quarter = $qData['quarter'] ?? '';
                    $completed = $qData['completed'] ?? false;
                    
                    if ($completed) {
                        $bddkParts[] = '<span class="bddk-complete">' . escapeText($quarter) . '</span>';
                    } else {
                        $bddkParts[] = '<span class="bddk-incomplete">' . escapeText($quarter) . '</span>';
                    }
                }
                $bddkDisplay = implode(' ', $bddkParts);
            } else {
                $bddkDisplay = '-';
            }
        ?>
        <tr>
            <td class="center"><?php echo $stt; ?></td>
            <td class="center"><?php echo escapeText($device['mavt'] ?? ''); ?></td>
            <td class="center"><?php echo escapeText($device['somay'] ?? ''); ?></td>
            <td class="center"><?php echo $bddkDisplay; ?></td>
            <td class="center"></td>
            <td></td>
        </tr>
        <?php 
            $stt++;
        endforeach; 
        ?>
    </tbody>
</table>

</body>
</html>
