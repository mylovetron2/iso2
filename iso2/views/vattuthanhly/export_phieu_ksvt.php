<?php
// Export Word document for Phiếu Kiểm Soát Vật Tư - Thống kê thanh lý
// Set headers first
$filename = "PhieuKSVT_ThongKe_" . date('Ymd_His') . ".doc";
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
    
    // First strip any HTML tags
    $text = strip_tags($text);
    
    // Decode HTML entities (e.g., &ocirc; -> ô)
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
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

// Lấy tên bộ phận từ madv
$tenBoPhan = '';
if (!empty($bophan)) {
    if ($bophan === 'DVLTH') {
        $tenBoPhan = 'Đội ĐVL Tổng hợp';
    } else {
        // Query để lấy tên
        $stmtBP = $this->db->prepare("SELECT tendv FROM donvi_iso WHERE madv = :madv");
        $stmtBP->execute([':madv' => $bophan]);
        $bpData = $stmtBP->fetch(PDO::FETCH_ASSOC);
        $tenBoPhan = $bpData['tendv'] ?? '';
    }
}
?>
<html xmlns:v="urn:schemas-microsoft-com:vml"
xmlns:o="urn:schemas-microsoft-com:office:office"
xmlns:w="urn:schemas-microsoft-com:office:word"
xmlns:m="http://schemas.microsoft.com/office/2004/12/omml"
xmlns="http://www.w3.org/TR/REC-html40">

<head>
<meta http-equiv=Content-Type content="text/html; charset=unicode">
<style>
@page Section1 {
    size: 595.3pt 841.9pt; /* A4 */
    margin: 1.0cm 1.5cm 1.0cm 2.0cm;
    mso-footer-margin: .5in;
    mso-footer: f1;
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

/* Remove borders from footer */
table#hrdftrtbl table,
table#hrdftrtbl td {
    border: none !important;
}

p.MsoFooter, li.MsoFooter, div.MsoFooter {
    margin: 0in;
    margin-bottom: .0001pt;
    mso-pagination: widow-orphan;
    tab-stops: center 3.0in right 6.0in;
    font-size: 12.0pt;
}

table#hrdftrtbl {
    margin: 0in 0in 0in 9in;
}
</style>
</head>

<body>
<div class="Section1">

<!-- Header -->
<div style="margin-bottom: 10px;">
    <div style="width: 100%; text-align: left;">
        <div style="font-weight: bold; font-size: 13pt; line-height: 1.3;">
            XN ĐỊA VẬT LÝ GK<br/>
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
    <span class="checkbox">&nbsp;&nbsp;&nbsp;&nbsp;</span>
    &nbsp;&nbsp;
    KT, BD, SC, gia công đột xuất: 
    <span class="checkbox">&nbsp;&nbsp;&nbsp;&nbsp;</span>
</div>

<div class="info-row">
    <span class="info-label">2- <strong>Bộ phận đặt hàng:</strong></span> 
    <?php echo escapeWordText($tenBoPhan); ?>
</div>

<table style="width: 100%; border: none; margin-bottom: 0.5em;" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td style="width: 60%; border: none; padding: 2px 0;">
            <span class="info-label">3- <strong>Tên TB:</strong></span> 
            .......................................................................
        </td>
        <td style="width: 40%; border: none; padding: 2px 0;">
            <span class="info-label">Ký mã hiệu:</span> 
            ...............................
        </td>
    </tr>
</table>

<table style="width: 100%; border: none; margin-bottom: 0.5em;" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td style="width: 60%; border: none; padding: 2px 0;">
            <span class="info-label">4- <strong>Người lập phiếu:</strong></span> 
            ................................
        </td>
        <td style="width: 40%; border: none; padding: 2px 0;">
            <span class="info-label">Bộ phận:</span> 
            ...............................
        </td>
    </tr>
</table>

<table style="width: 100%; border: none; margin-bottom: 0.5em;" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td style="width: 60%; border: none; padding: 2px 0;">
            <span class="info-label">5- <strong>Phiếu xuất kho số:</strong></span> 
            ................................
        </td>
        <td style="width: 40%; border: none; padding: 2px 0;">
            Ngày ...... tháng ...... năm 20....
        </td>
    </tr>
</table>

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
        <?php if (empty($items)): ?>
        <tr>
            <td colspan="7" class="center">Không có vật tư nào</td>
        </tr>
        <?php else: ?>
        <?php foreach ($items as $index => $item): 
            $tenvattu = $item['ten_tiengviet'] ?: $item['ten_tienganh'] ?: $item['ten_tiengnga'];
            $soluong = $item['soluong_thaydoi'];
            $donvi = $item['donvi'] ?: $item['dvt_tiengnga'];
            $ghichu = $item['nguyennhan'] ?? '';
        ?>
        <tr>
            <td class="center"><?php echo $index + 1; ?></td>
            <td><?php echo escapeWordText($item['mavattu']); ?></td>
            <td><?php echo escapeWordText($tenvattu); ?></td>
            <td class="center"><?php echo escapeWordText($donvi); ?></td>
            <td class="center"><?php echo number_format($soluong, 2, ',', '.'); ?></td>
            <td class="center"><?php echo number_format($soluong, 2, ',', '.'); ?></td>
            <td><?php echo escapeWordText($ghichu); ?></td>
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

<table id='hrdftrtbl' border='0' cellspacing='0' cellpadding='0'>
    <tr>
        <td>
            <div style='mso-element:footer' id="f1">
                <p class="MsoFooter">
                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td class="footer">
                                <span lang=VI style='mso-ansi-language:VI'>BM.25.06<br/>
	01/09/2020 <o:p></o:p></span>
                            </td>
                        </tr>
                    </table>
                </p>
            </div>
        </td>
    </tr>
</table>

</div>
</body>
</html>
