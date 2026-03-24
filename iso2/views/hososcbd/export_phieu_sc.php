<?php
/**
 * PHIẾU THỰC HIỆN CÔNG VIỆC SC/BD/KT
 * Dựa theo template từ DOCUMENTATION_IN_PHIEU_SC.md
 */

if (!isset($item) || empty($item)) {
    http_response_code(404);
    die('Record not found');
}

// Lấy danh sách người thực hiện
require_once __DIR__ . '/../../config/database.php';
$db = getDBConnection();
$nguoiThucHienList = [];
if (!empty($item['hoso'])) {
    try {
        $stmt = $db->prepare("
            SELECT hoten, giolv, stt
            FROM ngthuchien_iso 
            WHERE mahoso = :mahoso 
            ORDER BY stt ASC
        ");
        $stmt->execute([':mahoso' => $item['hoso']]);
        $nguoiThucHienList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching nguoi thuc hien: " . $e->getMessage());
    }
}

// Khởi tạo mảng người thực hiện (tối đa 8 người)
$hoten = array_fill(1, 8, '');
$giolv = array_fill(1, 8, '');
foreach ($nguoiThucHienList as $index => $nguoi) {
    if ($index < 8) {
        $hoten[$index + 1] = $nguoi['hoten'] ?? '';
        $giolv[$index + 1] = $nguoi['giolv'] ?? '';
    }
}

// Khởi tạo mảng thiết bị phụ trợ (tối đa 5)
$tbdosc = array_fill(1, 5, '');
$serialtbdosc = array_fill(1, 5, '');

// Lấy thiết bị phụ trợ từ item
$tbdosc[1] = $item['tbdosc'] ?? '';
$serialtbdosc[1] = $item['serialtbdosc'] ?? '';
$tbdosc[2] = $item['tbdosc1'] ?? '';
$serialtbdosc[2] = $item['serialtbdosc1'] ?? '';
$tbdosc[3] = $item['tbdosc2'] ?? '';
$serialtbdosc[3] = $item['serialtbdosc2'] ?? '';
$tbdosc[4] = $item['tbdosc3'] ?? '';
$serialtbdosc[4] = $item['serialtbdosc3'] ?? '';
$tbdosc[5] = $item['tbdosc4'] ?? '';
$serialtbdosc[5] = $item['serialtbdosc4'] ?? '';

// Parse ngày tháng
function parseDate($dateString) {
    if (empty($dateString) || $dateString == '0000-00-00') {
        return array('day' => '', 'month' => '', 'year' => '');
    }
    
    // Detect format
    if (strpos($dateString, '/') !== false) {
        // Format DD/MM/YYYY
        $parts = explode('/', $dateString);
        return array('day' => $parts[0], 'month' => $parts[1], 'year' => $parts[2]);
    } elseif (strpos($dateString, '-') !== false) {
        // Format YYYY-MM-DD
        $parts = explode('-', $dateString);
        return array('day' => $parts[2], 'month' => $parts[1], 'year' => $parts[0]);
    }
    
    return array('day' => '', 'month' => '', 'year' => '');
}

$dateStart = parseDate($item['ngayth'] ?? '');
$ngays = $dateStart['day'];
$thangs = $dateStart['month'];
$nams = $dateStart['year'];

$dateEnd = parseDate($item['ngaykt'] ?? '');
$ngayt = $dateEnd['day'];
$thangt = $dateEnd['month'];
$namt = $dateEnd['year'];

// Data fields
$hosomay = $item['hoso'] ?? '';
$mavtu = $item['mavt'] ?? '';
$somay = $item['somay'] ?? '';
$model = $item['model'] ?? '';
$cv = $item['cv'] ?? '';
$noidung = $item['noidung'] ?? '';
$honghoc = $item['honghoc'] ?? '';
$khacphuc = $item['khacphuc'] ?? '';
$ketluan = $item['ketluan'] ?? '';
$ttktbefore = $item['ttktbefore'] ?? '';
$ttktafter = $item['ttktafter'] ?? '';
$ghichufinal = $item['ghichu'] ?? '';

// Filename
$phieuClean = preg_replace('/[^a-zA-Z0-9_-]/', '_', $item['hoso'] ?? 'unknown');
$filename = 'PhieuThucHienCongViec_' . $phieuClean . '_' . date('YmdHis') . '.doc';

header('Content-Type: application/vnd.ms-word; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

function dText($text) {
    if (empty($text)) return '';
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
?>
<html xmlns:v="urn:schemas-microsoft-com:vml"
xmlns:o="urn:schemas-microsoft-com:office:office"
xmlns:w="urn:schemas-microsoft-com:office:word"
xmlns:m="http://schemas.microsoft.com/office/2004/12/omml"
xmlns="http://www.w3.org/TR/REC-html40">

<head>
<meta http-equiv=Content-Type content="text/html; charset=unicode">
<meta name=ProgId content=Word.Document>
<meta name=Generator content="Microsoft Word 14">
<title>PHIẾU THỰC HIỆN CÔNG VIỆC</title>

<!--[if gte mso 9]><xml>
 <o:DocumentProperties>
  <o:Author>System</o:Author>
  <o:Created><?php echo date('Y-m-d\TH:i:s\Z'); ?></o:Created>
  <o:Pages>1</o:Pages>
 </o:DocumentProperties>
</xml><![endif]-->

<style>
/* CSS cho Microsoft Word */
@page Section1 {
    size: 595.3pt 841.9pt; /* A4 */
    margin: 1.0cm 1.5cm 1.0cm 2.0cm;
    mso-footer-margin: .5in;
    mso-footer: f1;
}
div.Section1 { page: Section1; }

@font-face {
    font-family: 'Times New Roman';
}

body {
    font-family: 'Times New Roman', serif;
    font-size: 12pt;
}

table.MsoNormalTable {
    border-collapse: collapse;
    border: solid windowtext 1.0pt;
    width: 100%;
}

table.MsoNormalTable td {
    border: solid windowtext 1.0pt;
    padding: 0in 5.4pt 0in 5.4pt;
}

p.MsoNormal {
    margin: 0in;
    margin-bottom: .0001pt;
    font-size: 12.0pt;
    font-family: 'Times New Roman', serif;
}

.header-row {
    text-align: center;
    font-weight: bold;
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
<div class=Section1>

<!-- Header -->
<table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0 width="100%" style='width:100.0%;border:none'>
    <tr>
        <td width="25%" style='width:25%;padding:0;border:none;vertical-align:top'>
            <p class=MsoNormal>
                <span style='font-size:12.0pt'>
                    <b>XN Địa vật lý GK</b><br>
                    <b>Xưởng SCTBĐVL</b>
                </span>
            </p>
        </td>
        <td width="75%" style='width:75%;padding:0;border:none;vertical-align:top'>
            <p class=MsoNormal align=center style='text-align:center'>
                <span style='font-size:12.0pt'>
                    <b>PHIẾU THỰC HIỆN CÔNG VIỆC</b>
                </span>
            </p>
        </td>
    </tr>
</table>

<table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0 width="100%" style='width:100.0%;border:none;margin-top:5pt'>
    <tr>
        <td width="50%" style='width:50%;padding:0;border:none'>
            <p class=MsoNormal>
                <span style='font-size:12.0pt'>Số hồ sơ: <?php echo dText($hosomay); ?></span>
            </p>
        </td>
        <td width="50%" style='width:50%;padding:0;border:none'>
            <p class=MsoNormal>
                <span style='font-size:12.0pt'>Ngày bắt đầu: &nbsp;<?php echo $ngays; ?>/<?php echo $thangs; ?>/<?php echo $nams; ?></span>
            </p>
        </td>
    </tr>
    <tr>
        <td width="50%" style='width:50%;padding:0;border:none'>
            <p class=MsoNormal><span style='font-size:12.0pt'>&nbsp;</span></p>
        </td>
        <td width="50%" style='width:50%;padding:0;border:none'>
            <p class=MsoNormal>
                <span style='font-size:12.0pt'>Ngày kết thúc: <?php echo $ngayt; ?>/<?php echo $thangt; ?>/<?php echo $namt; ?></span>
            </p>
        </td>
    </tr>
</table>

<br>

<!-- 1. Thông tin thiết bị -->
<p class=MsoNormal>
    <span style='font-size:12.0pt'>
        1. Tên thiết bị: <?php echo dText($mavtu); ?><?php if(!empty($model)) echo '-'.dText($model); ?>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        Số máy: &nbsp;&nbsp;&nbsp;&nbsp;<?php echo dText($somay); ?>
    </span>
</p>

<br>

<!-- 2. Người tham gia thực hiện công việc -->
<p class=MsoNormal>
    <span style='font-size:12.0pt'>
        2. Người tham gia thực hiện công việc:
    </span>
</p>

<br>

<table class=MsoNormalTable border=1 cellspacing=0 cellpadding=0>
    <tr>
        <td style='width:173.15pt'>
            <p class=MsoNormal align=center><b>Họ và tên</b></p>
        </td>
        <td style='width:85.05pt'>
            <p class=MsoNormal align=center><b>Số giờ tham gia</b></p>
        </td>
        <td style='width:173.15pt'>
            <p class=MsoNormal align=center><b>Họ và tên</b></p>
        </td>
        <td style='width:89.05pt'>
            <p class=MsoNormal align=center><b>Số giờ tham gia</b></p>
        </td>
    </tr>
    <?php for($i=1; $i<=4; $i++): 
        $index1 = ($i-1) * 2 + 1;
        $index2 = ($i-1) * 2 + 2;
    ?>
    <tr>
        <td>
            <p class=MsoNormal><?php echo $index1; ?>. <?php echo dText($hoten[$index1]); ?></p>
        </td>
        <td>
            <p class=MsoNormal><?php echo $giolv[$index1]; ?></p>
        </td>
        <td>
            <p class=MsoNormal><?php echo $index2; ?>. <?php echo dText($hoten[$index2]); ?></p>
        </td>
        <td>
            <p class=MsoNormal><?php echo $giolv[$index2]; ?></p>
        </td>
    </tr>
    <?php endfor; ?>
</table>

<br>

<!-- 3. Thiết bị và phần mềm phụ trợ -->
<p class=MsoNormal>
    <span style='font-size:12.0pt'>
        3. Các thiết bị và phần mềm phụ trợ:
    </span>
</p>

<br>

<table class=MsoNormalTable border=1 cellspacing=0 cellpadding=0>
    <tr>
        <td style='width:40.0pt'>
            <p class=MsoNormal align=center><b>STT</b></p>
        </td>
        <td style='width:239.0pt'>
            <p class=MsoNormal align=center><b>Tên viết tắt</b></p>
        </td>
        <td style='width:230.95pt'>
            <p class=MsoNormal align=center><b>Số serial</b></p>
        </td>
    </tr>
    <?php for($i=1; $i<=5; $i++): 
        if(!empty($tbdosc[$i]) || !empty($serialtbdosc[$i])):
    ?>
    <tr>
        <td>
            <p class=MsoNormal><?php echo $i; ?></p>
        </td>
        <td>
            <p class=MsoNormal><?php echo dText($tbdosc[$i]); ?></p>
        </td>
        <td>
            <p class=MsoNormal><?php echo dText($serialtbdosc[$i]); ?></p>
        </td>
    </tr>
    <?php 
        endif;
    endfor; 
    ?>
</table>

<br>

<!-- 4. Nội dung công việc -->
<p class=MsoNormal>
    <span style='font-size:12.0pt'>
        4. Nội dung công việc: 
        <?php if($cv == "KT"): ?>
            KT <input type="checkbox" checked> &nbsp;&nbsp; BD <input type="checkbox"> &nbsp;&nbsp; SC <input type="checkbox">
        <?php elseif($cv == "BD"): ?>
            KT <input type="checkbox"> &nbsp;&nbsp; BD <input type="checkbox" checked> &nbsp;&nbsp; SC <input type="checkbox">
        <?php elseif($cv == "SC"): ?>
            KT <input type="checkbox"> &nbsp;&nbsp; BD <input type="checkbox"> &nbsp;&nbsp; SC <input type="checkbox" checked>
        <?php else: ?>
            KT <input type="checkbox"> &nbsp;&nbsp; BD <input type="checkbox"> &nbsp;&nbsp; SC <input type="checkbox">
        <?php endif; ?>
    </span>
</p>

<br>

<!-- Bảng mô tả tình trạng và công việc thực hiện -->
<table class=MsoNormalTable border=1 cellspacing=0 cellpadding=0 style='width:100%'>
    <tr>
        <td style='width:40%;padding:5.4pt;vertical-align:top'>
            <p class=MsoNormal style='text-align:center'>
                <b>Mô tả chi tiết tình trạng của thiết bị, các hỏng hóc (nếu có) do nhân viên Xưởng kiểm tra, phát hiện</b>
            </p>
        </td>
        <td style='width:60%;padding:5.4pt;vertical-align:top'>
            <p class=MsoNormal>
                <?php 
                $moTaTinhTrang = '';
                if (!empty($ttktbefore)) {
                    $moTaTinhTrang .= dText($ttktbefore);
                }
                if (!empty($honghoc)) {
                    if (!empty($moTaTinhTrang)) $moTaTinhTrang .= "\n\n";
                    $moTaTinhTrang .= dText($honghoc);
                }
                echo nl2br($moTaTinhTrang);
                ?>
            </p>
        </td>
    </tr>
    <tr>
        <td style='width:40%;padding:5.4pt;vertical-align:top'>
            <p class=MsoNormal style='text-align:center'>
                <b>Mô tả công việc thực hiện hoặc cách khắc phục hỏng hóc</b>
            </p>
        </td>
        <td style='width:60%;padding:5.4pt;vertical-align:top'>
            <p class=MsoNormal>
                <?php 
                $moTaCongViec = '';
                if (!empty($noidung)) {
                    $moTaCongViec .= dText($noidung);
                }
                if (!empty($khacphuc)) {
                    if (!empty($moTaCongViec)) $moTaCongViec .= "\n\n";
                    $moTaCongViec .= dText($khacphuc);
                }
                echo nl2br($moTaCongViec);
                ?>
            </p>
        </td>
    </tr>
</table>

<br/>

<!-- Footer -->
<table id='hrdftrtbl' border='0' cellspacing='0' cellpadding='0'>
    <tr>
        <td>
            <div style='mso-element:footer' id="f1">
                <p class="MsoFooter">
                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td class="footer">
                                <span lang=VI style='mso-ansi-language:VI'>BM.25.03<br/>
	01/01/2024 <o:p></o:p></span>
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
