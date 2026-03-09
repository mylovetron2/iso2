<?php
// Export Word document for Phiếu Kiểm Soát Vật Tư
// Set headers first
$filename = "PhieuKiemSoatVatTu-{$phieu['so_phieu']}.doc";
header("Content-Type: application/msword; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"{$filename}\""); 
header("Pragma: no-cache");
header("Expires: 0");

// Output UTF-8 BOM for proper encoding detection by Word
echo "\xEF\xBB\xBF";

/**
 * Escape and encode text for Word XML
 */
function escapeWordText($text) {
    if ($text === null || $text === '') {
        return '';
    }
    
    if (!mb_check_encoding($text, 'UTF-8')) {
        $text = mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
    }
    
    $text = str_replace('&', '&amp;', $text);
    $text = str_replace('<', '&lt;', $text);
    $text = str_replace('>', '&gt;', $text);
    $text = str_replace('"', '&quot;', $text);
    $text = str_replace("'", '&#39;', $text);
    
    return $text;
}
?>
<!DOCTYPE html>
<html xmlns:v="urn:schemas-microsoft-com:vml"
xmlns:o="urn:schemas-microsoft-com:office:office"
xmlns:w="urn:schemas-microsoft-com:office:word"
xmlns:m="http://schemas.microsoft.com/office/2004/12/omml"
xmlns="http://www.w3.org/TR/REC-html40">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<style>
@page Section1 {
    size: 595.3pt 841.9pt; /* A4 */
    margin: 1.0cm 1.5cm 1.0cm 2.0cm;
}
div.Section1 { page: Section1; }

body {
    font-family: 'Times New Roman';
    font-size: 13pt;
}

table {
    border-collapse: collapse;
    width: 100%;
    font-size: 13pt;
}

table, th, td {
    border: 1px solid black;
}

th, td {
    padding: 4px;
}

.header-left {
    float: left;
    width: 40%;
    text-align: center;
    font-size: 13pt;
    font-weight: bold;
}

.header-center {
    text-align: center;
    margin-top: 20px;
    margin-bottom: 20px;
}

.title {
    font-size: 16pt;
    font-weight: bold;
}

.info-row {
    margin: 8px 0;
    font-size: 13pt;
}

.info-label {
    font-weight: normal;
}

.signature-section {
    margin-top: 30px;
    width: 100%;
}

.signature-box {
    display: inline-block;
    width: 32%;
    text-align: center;
    vertical-align: top;
}

.signature-title {
    font-weight: bold;
    margin-bottom: 60px;
}

.center {
    text-align: center;
}

.right {
    text-align: right;
}

.checkbox {
    display: inline-block;
    width: 22px;
    height: 22px;
    border: 2px solid black;
    vertical-align: middle;
    text-align: center;
    line-height: 20px;
    font-size: 16pt;
    font-weight: bold;
    margin-left: 5px;
    margin-right: 2px;
}

th {
    background-color: #f0f0f0;
    font-weight: bold;
    text-align: center;
}
</style>
</head>

<body>
<div class="Section1">

<!-- Header -->
<div style="margin-bottom: 10px;">
    <div style="width: 100%; text-align: left;">
        <div style="font-weight: bold; font-size: 13pt; line-height: 1.3;">
            XN DIA VẬT LÝ GK<br/>
            XƯỞNG SCTBĐVL
        </div>
    </div>
</div>

<div class="header-center">
    <div class="title">PHIẾU KIỂM SOÁT VẬT TƯ</div>
</div>

<!-- Information Section -->
<div class="info-row">
    <span class="info-label">1- <strong>Loại công việc:</strong></span> 
    BD theo kế hoạch 
    <span class="checkbox"><?php echo (strpos(strtolower($phieu['loai_congviec'] ?? ''), 'kế hoạch') !== false) ? '&nbsp;x&nbsp;' : '&nbsp;&nbsp;&nbsp;&nbsp;'; ?></span>
    &nbsp;&nbsp;
    KT, BD, SC, gia công đột xuất: 
    <span class="checkbox"><?php echo (strpos(strtolower($phieu['loai_congviec'] ?? ''), 'đột xuất') !== false || strpos(strtolower($phieu['loai_congviec'] ?? ''), 'gia công') !== false) ? '&nbsp;x&nbsp;' : '&nbsp;&nbsp;&nbsp;&nbsp;'; ?></span>
</div>

<div class="info-row">
    <span class="info-label">2- <strong>Bộ phận đặt hàng:</strong></span> 
    <?php echo escapeWordText($phieu['bophan_dathang'] ?? ''); ?>
</div>

<div class="info-row">
    <span class="info-label">3- <strong>Tên TB:</strong></span> 
    <?php echo escapeWordText($phieu['ten_thietbi'] ?? 'Thiết bị Karat'); ?>
    &nbsp;&nbsp;&nbsp;&nbsp;
    <span class="info-label">Ký mã hiệu:</span> 
    <?php echo escapeWordText($phieu['ky_mahieu'] ?? ''); ?>
</div>

<div class="info-row">
    <span class="info-label">4- <strong>Người lập phiếu:</strong></span> 
    <?php echo escapeWordText($phieu['nguoi_lap_phieu'] ?? ''); ?>
    &nbsp;&nbsp;&nbsp;&nbsp;
    <span class="info-label">Bộ phận:</span> 
    <?php echo escapeWordText($phieu['bophan_nguoilap'] ?? 'Xưởng SCTBĐVL'); ?>
</div>

<div class="info-row">
    <span class="info-label">5- <strong>Phiếu xuất kho số:</strong></span> 
    .......................................................... 
    Ngày ...... tháng ...... năm 20....
</div>

<div class="info-row">
    <span class="info-label"><strong>6- Danh mục vật tư:</strong></span>
</div>

<table>
    <thead>
        <tr>
            <th style="width: 5%;">STT</th>
            <th style="width: 15%;">Mã vật tư</th>
            <th style="width: 35%;">Tên vật tư</th>
            <th style="width: 8%;">ĐVT</th>
            <th style="width: 10%;">Nhận</th>
            <th style="width: 12%;">Tiêu hao</th>
            <th style="width: 15%;">Ghi chú</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($chitiets)): ?>
        <tr>
            <td colspan="7" class="center">Chưa có vật tư nào</td>
        </tr>
        <?php else: ?>
        <?php foreach ($chitiets as $index => $item): ?>
        <tr>
            <td class="center"><?php echo $index + 1; ?></td>
            <td><?php echo escapeWordText($item['mavattu']); ?></td>
            <td><?php echo escapeWordText($item['ten_vattu']); ?></td>
            <td class="center"><?php echo escapeWordText($item['donvi']); ?></td>
            <td class="right"><?php echo number_format($item['soluong_nhan'], 0, ',', '.'); ?></td>
            <td class="right"><?php echo number_format($item['soluong_tieuhao'], 0, ',', '.'); ?></td>
            <td style="font-size: 11pt;">
                <?php 
                $ghichu = escapeWordText($item['ghichu']); 
                // Format ghi chú với xuống dòng nếu cần
                echo nl2br($ghichu);
                ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<!-- Signature Section -->
<div style="margin-top: 30px;">
    <div style="text-align: right; margin-bottom: 30px;">
        Ngày ...... tháng ...... năm ......
    </div>
</div>

<table style="border: none;">
    <tr style="border: none;">
        <td style="border: none; width: 33%; text-align: center; vertical-align: top;">
            <div class="signature-title">Lãnh đạo xưởng SCTBĐVL</div>
            <div style="margin-top: 80px;"></div>
        </td>
        <td style="border: none; width: 33%; text-align: center; vertical-align: top;">
            <div class="signature-title">Người lập phiếu</div>
            <div style="margin-top: 80px;"></div>
        </td>
        <td style="border: none; width: 34%; text-align: center; vertical-align: top;">
            <div class="signature-title">Bộ phận quản lý, sử dụng TB</div>
            <div style="margin-top: 80px;"></div>
        </td>
    </tr>
</table>

</div>
</body>
</html>
