<?php
if (!isset($item) || empty($item)) {
    http_response_code(404);
    die('Record not found');
}

require_once __DIR__ . '/../../config/database.php';
$db = getDBConnection();
$nguoiThucHienList = [];
if (!empty($item['hoso'])) {
    try {
        $stmt = $db->prepare('SELECT hoten FROM ngthuchien_iso WHERE mahoso = :mahoso ORDER BY stt ASC');
        $stmt->execute([':mahoso' => $item['hoso']]);
        $nguoiThucHienList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error fetching nguoi thuc hien: ' . $e->getMessage());
    }
}

function kiemTraText($value): string
{
    $value = html_entity_decode(strip_tags((string)$value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function kiemTraDate($value): string
{
    if (empty($value) || $value === '0000-00-00') {
        return '';
    }
    $timestamp = strtotime((string)$value);
    return $timestamp ? date('d/m/Y', $timestamp) : kiemTraText($value);
}

$toolRows = [];
for ($index = 0; $index < 5; $index++) {
    $toolField = $index === 0 ? 'tbdosc' : 'tbdosc' . $index;
    $serialField = $index === 0 ? 'serialtbdosc' : 'serialtbdosc' . $index;
    if (!empty($item[$toolField]) || !empty($item[$serialField])) {
        $toolRows[] = [
            'name' => $item[$toolField] ?? '',
            'serial' => $item[$serialField] ?? '',
        ];
    }
}

$filename = 'phieu_kiem_tra_sau_bd_sc_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', (string)($item['hoso'] ?? $item['stt'])) . '.doc';
header('Content-Type: application/msword; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');
?>
<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="ProgId" content="Word.Document">
<title>Phiếu kiểm tra tình trạng kỹ thuật</title>
<style>
@page Section1 { size: 21cm 29.7cm; margin: 1.5cm 1.5cm 1.5cm 1.5cm; mso-footer-margin: .5in; mso-footer: f1; }
div.Section1 { page: Section1; }
body { font-family: "Times New Roman", serif; font-size: 11pt; }
p { margin: 0; }
table { border-collapse: collapse; }
.header td { padding: 0 5pt; vertical-align: top; }
.header .unit { width: 45%; font-weight: bold; }
.header .title { width: 55%; text-align: center; font-weight: bold; }
.header .file-number { display: block; margin-left: 18.18%; text-align: left; font-weight: normal; }
.info td { padding: 3pt 5pt; vertical-align: top; }
.grid { width: 100%; border: 1pt solid windowtext; }
.grid th, .grid td { border: 1pt solid windowtext; padding: 5pt; vertical-align: top; }
.grid th { text-align: center; font-weight: normal; }
.footer { font-size: 10pt; }
p.MsoFooter { margin: 0; margin-bottom: .0001pt; mso-pagination: widow-orphan; font-size: 11pt; }
table#hrdftrtbl { margin: 0in 0in 0in 9in; }
</style>
</head>
<body>
<div class="Section1">
<table class="header" width="100%">
<tr>
<td class="unit">XN Địa vật lý GK<br>Xưởng SCTBĐVL</td>
<td class="title">PHIẾU KIỂM TRA TÌNH TRẠNG KỸ<br>THUẬT THIẾT BỊ SAU KHI BD/SC<br><br><span class="file-number">Số hồ sơ: <b><?php echo kiemTraText($item['hoso'] ?? $item['phieu'] ?? ''); ?></b></span></td>
</tr>
</table>

<table class="info" width="100%">
<tr>
<td width="55%">1. Tên máy: <b><?php echo kiemTraText($item['mavt'] ?? ''); ?></b></td>
<td>Số máy: <b><?php echo kiemTraText($item['somay'] ?? ''); ?></b></td>
</tr>
<tr>
<td>2. Họ máy: <b><?php echo kiemTraText($item['model'] ?? ''); ?></b></td>
<td>Model: <b><?php echo kiemTraText($item['model'] ?? ''); ?></b></td>
</tr>
<tr>
<td>3. Điện áp nuôi: <b></b></td>
<td>Dòng tiêu thụ: <b></b></td>
</tr>
<tr><td colspan="2">4. Các thiết bị và phần mềm phụ trợ:</td></tr>
</table>

<table class="grid" width="100%">
<tr><th width="10%">STT</th><th width="55%">Tên thiết bị hoặc phần mềm</th><th width="35%">Số thiết bị</th></tr>
<?php if ($toolRows): ?>
<?php foreach ($toolRows as $index => $tool): ?>
<tr><td align="center"><?php echo $index + 1; ?></td><td><?php echo kiemTraText($tool['name']); ?></td><td><?php echo kiemTraText($tool['serial']); ?></td></tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td align="center">1</td><td>&nbsp;</td><td>&nbsp;</td></tr>
<?php endif; ?>
</table>

<p style="margin-top:12pt;">5. Nội dung kiểm tra:</p>
<p style="min-height:55pt; white-space:pre-wrap;"><span style="mso-tab-count:1"></span><?php echo kiemTraText($item['ttktafter'] ?? ''); ?></p>
<p>6. Bảng số liệu hoặc file dữ liệu của đường log (Nếu có).</p>
<p style="min-height:55pt; white-space:pre-wrap;"><?php echo kiemTraText($item['ghichu'] ?? ''); ?></p>
<p>7. Kết luận:</p>
<p style="min-height:55pt; white-space:pre-wrap;"><span style="mso-tab-count:1"></span><?php echo kiemTraText($item['ketluan'] ?? ''); ?></p>

<table class="grid" width="100%" style="margin-top:12pt;">
<tr><td width="17%">Người thực hiện</td><td width="38%">Họ và tên: <?php echo kiemTraText(implode(', ', array_column($nguoiThucHienList, 'hoten'))); ?></td><td width="23%">Chữ ký:</td><td width="22%">Ngày: <?php echo kiemTraDate($item['ngaykt'] ?? $item['ngayth'] ?? ''); ?></td></tr>
<tr><td>Người kiểm tra</td><td>Họ và tên:</td><td>Chữ ký:</td><td>Ngày:</td></tr>
</table>

<table id="hrdftrtbl" border="0" cellspacing="0" cellpadding="0">
<tr><td>
<div style="mso-element:footer" id="f1">
<p class="MsoFooter">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr><td class="footer">BM.25.04<br>01/09/2026</td></tr>
</table>
</p>
</div>
</td></tr>
</table>
</div>
</body>
</html>
