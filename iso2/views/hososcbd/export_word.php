<?php
if (!isset($item) || empty($item)) {
    http_response_code(404);
    die('Record not found');
}

$phieuClean = preg_replace('/[^a-zA-Z0-9_-]/', '_', $item['phieu'] ?? 'unknown');
$filename = 'PhieuYeuCauDichVu_' . $phieuClean . '_' . date('YmdHis') . '.doc';

header('Content-Type: application/vnd.ms-word; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

function dText($text) {
    return !empty($text) ? htmlspecialchars($text, ENT_QUOTES, 'UTF-8') : '';
}

function dDate($date) {
    if (empty($date) || $date == '0000-00-00') return '';
    return date('d/m/Y', strtotime($date));
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
<meta name=Originator content="Microsoft Word 14">
<style>
<!--
v\:* {behavior:url(#default#VML);}
o\:* {behavior:url(#default#VML);}
w\:* {behavior:url(#default#VML);}
.shape {behavior:url(#default#VML);}

 /* Style Definitions */
 p.MsoNormal, li.MsoNormal, div.MsoNormal
	{mso-style-unhide:no;
	mso-style-qformat:yes;
	mso-style-parent:"";
	margin:0cm;
	margin-bottom:.0001pt;
	mso-pagination:widow-orphan;
	font-size:12.0pt;
	font-family:"Times New Roman","serif";
	mso-fareast-font-family:"Times New Roman";
	mso-fareast-theme-font:minor-fareast;}
p.MsoFooter, li.MsoFooter, div.MsoFooter
	{mso-style-priority:99;
	mso-style-link:"Footer Char";
	margin:0cm;
	margin-bottom:.0001pt;
	mso-pagination:widow-orphan;
	tab-stops:center 216.0pt right 432.0pt;
	font-size:12.0pt;
	font-family:"Times New Roman","serif";
	mso-fareast-font-family:"Times New Roman";
	mso-fareast-theme-font:minor-fareast;}
p
	{mso-style-noshow:yes;
	mso-style-priority:99;
	mso-margin-top-alt:auto;
	margin-right:0cm;
	mso-margin-bottom-alt:auto;
	margin-left:0cm;
	mso-pagination:widow-orphan;
	font-size:12.0pt;
	font-family:"Times New Roman","serif";
	mso-fareast-font-family:"Times New Roman";
	mso-fareast-theme-font:minor-fareast;}
span.FooterChar
	{mso-style-name:"Footer Char";
	mso-style-priority:99;
	mso-style-unhide:no;
	mso-style-locked:yes;
	mso-style-link:Footer;
	mso-ansi-font-size:12.0pt;
	mso-bidi-font-size:12.0pt;
	font-family:"Times New Roman","serif";
	mso-fareast-font-family:"Times New Roman";
	mso-fareast-theme-font:minor-fareast;}
.MsoChpDefault
	{mso-style-type:export-only;
	mso-default-props:yes;
	font-size:10.0pt;
	mso-ansi-font-size:10.0pt;
	mso-bidi-font-size:10.0pt;}
@page WordSection1
	{size:21.0cm 841.95pt;
	margin:72.0pt 54.0pt 72.0pt 54.0pt;
	mso-header-margin:36.0pt;
	mso-footer-margin:36.0pt;
	mso-paper-source:0;}
div.WordSection1
	{page:WordSection1;}
table.MsoNormalTable
	{mso-style-name:"Table Normal";
	mso-tstyle-rowband-size:0;
	mso-tstyle-colband-size:0;
	mso-style-noshow:yes;
	mso-style-priority:99;
	mso-style-parent:"";
	mso-padding-alt:0cm 5.4pt 0cm 5.4pt;
	mso-para-margin:0cm;
	mso-para-margin-bottom:.0001pt;
	mso-pagination:widow-orphan;
	font-size:10.0pt;
	font-family:"Times New Roman","serif";}
table.MsoTableGrid
	{mso-style-name:"Table Grid";
	mso-tstyle-rowband-size:0;
	mso-tstyle-colband-size:0;
	mso-style-priority:59;
	mso-style-unhide:no;
	border:solid windowtext 1.0pt;
	mso-border-alt:solid windowtext .5pt;
	mso-padding-alt:0cm 5.4pt 0cm 5.4pt;
	mso-border-insideh:.5pt solid windowtext;
	mso-border-insidev:.5pt solid windowtext;
	mso-para-margin:0cm;
	mso-para-margin-bottom:.0001pt;
	mso-pagination:widow-orphan;
	font-size:10.0pt;
	font-family:"Times New Roman","serif";}
-->
</style>
</head>

<body lang=EN-US style='tab-interval:36.0pt'>

<div class=WordSection1>

<table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0 width="100%"
 style='width:100.0%;mso-cellspacing:0cm;mso-yfti-tbllook:1184;mso-padding-alt:
 0cm 5.4pt 0cm 5.4pt'>
 <tr style='mso-yfti-irow:0;mso-yfti-firstrow:yes;mso-yfti-lastrow:yes'>
  <td style='padding:.75pt .75pt .75pt .75pt'>
  <p class=MsoNormal><span style='mso-fareast-font-family:"Times New Roman"'>XN
  Địa vật lý GK <br>
  Xưởng SCTBĐVL <o:p></o:p></span></p>
  </td>
  <td style='padding:.75pt .75pt .75pt .75pt'>
  <p class=MsoNormal><b><span style='mso-fareast-font-family:"Times New Roman"'>&nbsp;&nbsp;&nbsp;&nbsp;
  PHIẾU YÊU CẦU DỊCH VỤ</span></b><span style='mso-fareast-font-family:"Times New Roman"'><br>
  <br>
  <strong>Số hồ sơ:</strong> <strong><?php echo dText($item['phieu']); ?></strong>
  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>Ngày,
  Дата:<?php echo dDate($item['ngayyc']); ?></strong> <o:p></o:p></span></p>
  </td>
 </tr>
</table>

<p class=MsoNormal><span lang=VI style='mso-fareast-font-family:"Times New Roman";
mso-ansi-language:VI'><o:p>&nbsp;</o:p></span></p>

<table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0 width="100%"
 style='width:100.0%;mso-cellspacing:0cm;mso-yfti-tbllook:1184;mso-padding-alt:
 0cm 5.4pt 0cm 5.4pt'>
 <tr style='mso-yfti-irow:0;mso-yfti-firstrow:yes'>
  <td width="65%" style='width:65.0%;padding:.75pt .75pt .75pt .75pt'>
  <p class=MsoNormal><span style='mso-fareast-font-family:"Times New Roman"'>1.
  Người yêu cầu/bàn giao TB,Сдал:&nbsp; <b><?php echo dText($item['ngyeucau']); ?></b><o:p></o:p></span></p>
  </td>
  <td width="40%" style='width:40.0%;padding:.75pt .75pt .75pt .75pt'>
  <p class=MsoNormal><span style='mso-fareast-font-family:"Times New Roman"'>Ký
  tên(Сдал /Подпись): .........<o:p></o:p></span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:1'>
  <td width="60%" style='width:60.0%;padding:.75pt .75pt .75pt .75pt'>
  <p class=MsoNormal><span style='mso-fareast-font-family:"Times New Roman"'>&nbsp;&nbsp;&nbsp;&nbsp;Đơn
  vị,Подр: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <b><?php echo dText($item['madv']); ?></b> <o:p></o:p></span></p>
  </td>
  <td width="40%" style='width:40.0%;padding:.75pt .75pt .75pt .75pt'>
  <p class=MsoNormal><span style='mso-fareast-font-family:"Times New Roman"'>Điện
  thoại liên lạc (Tel):&nbsp; <?php echo dText($item['dienthoai']); ?><o:p></o:p></span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:2'>
  <td width="60%" style='width:60.0%;padding:.75pt .75pt .75pt .75pt'>
  <p class=MsoNormal><span style='mso-fareast-font-family:"Times New Roman"'>2.
  Người nhận thiết bị, Принял:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <b><?php echo dText($item['ngnhyeucau']); ?></b><o:p></o:p></span></p>
  </td>
  <td width="40%" style='width:40.0%;padding:.75pt .75pt .75pt .75pt'>
  <p class=MsoNormal><span style='mso-fareast-font-family:"Times New Roman"'>Ký
  tên(Принял /Подпись): .........<o:p></o:p></span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:3;mso-yfti-lastrow:yes'>
  <td style='padding:.75pt .75pt .75pt .75pt'>
  <p class=MsoNormal><span style='mso-fareast-font-family:"Times New Roman"'>3.
  Nội dung: <o:p></o:p></span></p>
  </td>
  <td style='padding:.75pt .75pt .75pt .75pt'></td>
 </tr>
</table>

<p class=MsoNormal><span lang=VI style='mso-fareast-font-family:"Times New Roman";
mso-ansi-language:VI'><o:p>&nbsp;</o:p></span></p>

<table class=MsoNormalTable border=1 cellspacing=0 cellpadding=0 width="100%"
 style='width:100.0%;border-collapse:collapse;border:none;
 mso-border-alt:solid windowtext .5pt;mso-yfti-tbllook:1184;mso-padding-alt:
 0cm 5.4pt 0cm 5.4pt;mso-border-insideh:.5pt solid windowtext;mso-border-insidev:
 .5pt solid windowtext'>
 <tr style='mso-yfti-irow:0;mso-yfti-firstrow:yes;height:5.45pt'>
  <td width=62 style='width:30.9pt;border:solid windowtext 1.0pt;mso-border-alt:
  solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:5.45pt'>
  <p class=MsoNormal align=center style='margin-top:0cm;margin-right:-5.4pt;
  margin-bottom:0cm;margin-left:5.4pt;margin-bottom:.0001pt;text-align:center;
  text-indent:-5.4pt'><b style='mso-bidi-font-weight:normal'><span
  style='color:black;mso-themecolor:text1'>STT</span></b></p>
  <p class=MsoNormal align=center style='margin-top:0cm;margin-right:-5.4pt;
  margin-bottom:0cm;margin-left:5.4pt;margin-bottom:.0001pt;text-align:center;
  text-indent:-5.4pt;mso-line-height-alt:5.45pt'><span style='color:black;
  mso-themecolor:text1'>П/П</span></p>
  </td>
  <td width=243 style='width:121.35pt;border:solid windowtext 1.0pt;border-left:
  none;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;
  padding:0cm 5.4pt 0cm 5.4pt;height:5.45pt'>
  <p class=MsoNormal align=center style='mso-margin-top-alt:auto;mso-margin-bottom-alt:
  auto;text-align:center'><b style='mso-bidi-font-weight:normal'><span
  style='color:black;mso-themecolor:text1'>Tên thiết bị - Model</span></b></p>
  <p class=MsoNormal align=center style='mso-margin-top-alt:auto;mso-margin-bottom-alt:
  auto;text-align:center;mso-line-height-alt:5.45pt'><span lang=RU
  style='color:black;mso-themecolor:text1;mso-ansi-language:RU'>Наим-е
  оборудования</span></p>
  </td>
  <td width=142 style='width:70.85pt;border:solid windowtext 1.0pt;border-left:
  none;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;
  padding:0cm 5.4pt 0cm 5.4pt;height:5.45pt'>
  <p class=MsoNormal align=center style='mso-margin-top-alt:auto;mso-margin-bottom-alt:
  auto;text-align:center'><b style='mso-bidi-font-weight:normal'><span
  style='color:black;mso-themecolor:text1'>Số của thiết bị - Serial</span></b></p>
  <p class=MsoNormal align=center style='mso-margin-top-alt:auto;mso-margin-bottom-alt:
  auto;text-align:center;mso-line-height-alt:5.45pt'><span style='color:black;
  mso-themecolor:text1'>Номер</span></p>
  </td>
  <td width=238 style='width:118.8pt;border:solid windowtext 1.0pt;border-left:
  none;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;
  padding:0cm 5.4pt 0cm 5.4pt;height:5.45pt'>
  <p class=MsoNormal align=center style='mso-margin-top-alt:auto;mso-margin-bottom-alt:
  auto;text-align:center'><b style='mso-bidi-font-weight:normal'><span
  style='color:black;mso-themecolor:text1'>Mô tả chi tiết tình trạng kỹ thuật của
  thiết bị trước khi đưa về Xưởng</span></b></p>
  <p class=MsoNormal align=center style='mso-margin-top-alt:auto;mso-margin-bottom-alt:
  auto;text-align:center;mso-line-height-alt:5.45pt'><span lang=RU
  style='color:black;mso-themecolor:text1;mso-ansi-language:RU'>Тех</span><span
  style='color:black;mso-themecolor:text1'>. состояние</span></p>
  </td>
  <td width=145 style='width:72.65pt;border:solid windowtext 1.0pt;border-left:
  none;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;
  padding:0cm 5.4pt 0cm 5.4pt;height:5.45pt'>
  <p class=MsoNormal align=center style='mso-margin-top-alt:auto;mso-margin-bottom-alt:
  auto;text-align:center'><b style='mso-bidi-font-weight:normal'><span
  style='color:black;mso-themecolor:text1'>Nội dung yêu cầu</span></b></p>
  <p class=MsoNormal align=center style='mso-margin-top-alt:auto;mso-margin-bottom-alt:
  auto;text-align:center;mso-line-height-alt:5.45pt'><span lang=RU
  style='color:black;mso-themecolor:text1;mso-ansi-language:RU'>Требование</span></p>
  </td>
  <td width=99 valign=top style='width:49.5pt;border:solid windowtext 1.0pt;
  border-left:none;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:
  solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:5.45pt'>
  <p class=MsoNormal align=center style='mso-margin-top-alt:auto;mso-margin-bottom-alt:
  auto;text-align:center;mso-line-height-alt:5.45pt'><b style='mso-bidi-font-weight:
  normal'><span style='color:black;mso-themecolor:text1'>Thiết bị đo SC</span></b></p>
  <p class=MsoNormal align=center style='mso-margin-top-alt:auto;mso-margin-bottom-alt:
  auto;text-align:center;mso-line-height-alt:5.45pt'><span lang=RU
  style='color:black;mso-themecolor:text1;mso-ansi-language:RU'>Оборудование</span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:1;mso-yfti-lastrow:yes;height:20.2pt'>
  <td width=62 style='width:30.9pt;border:solid windowtext 1.0pt;border-top:none;mso-border-top-alt:
  solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;
  height:20.2pt'>
  <p class=MsoNormal align=center style='mso-margin-top-alt:auto;mso-margin-bottom-alt:
  auto;text-align:center'>1</p>
  </td>
  <td width=243 style='width:121.35pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;
  border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;
  mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;
  padding:0cm 5.4pt 0cm 5.4pt;height:20.2pt'>
  <p class=MsoNormal align=center style='mso-margin-top-alt:auto;mso-margin-bottom-alt:auto;text-align:center'>
  <?php echo dText($item['mavt']); ?><?php if(!empty($item['model'])) echo ' - '.dText($item['model']); ?></p>
  </td>
  <td width=142 style='width:70.85pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;
  border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;
  mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;
  padding:0cm 5.4pt 0cm 5.4pt;height:20.2pt'>
  <p class=MsoNormal align=center style='mso-margin-top-alt:auto;mso-margin-bottom-alt:auto;text-align:center'>
  <?php echo dText($item['somay']); ?></p>
  </td>
  <td width=238 style='width:118.8pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;
  border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;
  mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;
  padding:0cm 5.4pt 0cm 5.4pt;height:20.2pt'>
  <p class=MsoNormal align=center style='mso-margin-top-alt:auto;mso-margin-bottom-alt:auto;text-align:center'>
  <?php echo nl2br(dText($item['ttktbefore'])); ?></p>
  </td>
  <td width=145 style='width:72.65pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;
  border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;
  mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;
  padding:0cm 5.4pt 0cm 5.4pt;height:20.2pt'>
  <p class=MsoNormal align=center style='mso-margin-top-alt:auto;mso-margin-bottom-alt:auto;text-align:center'>
  <?php echo dText($item['cv']); ?></p>
  </td>
  <td width=99 valign=top style='width:49.5pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;
  border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;
  mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;
  padding:0cm 5.4pt 0cm 5.4pt;height:20.2pt'>
  <p class=MsoNormal align=center style='mso-margin-top-alt:auto;mso-margin-bottom-alt:auto;text-align:center'>
  <?php echo dText($item['tbdosc']); ?></p>
  </td>
 </tr>
</table>

<p class=MsoNormal style='margin-bottom:12.0pt'><span lang=VI style='mso-fareast-font-family:
"Times New Roman";mso-ansi-language:VI'><o:p>&nbsp;</o:p></span></p>

<p><i><span style='font-weight:italic'><span lang=VI style='mso-ansi-language:
VI'>Ghi chú: Cột "Nội dung yêu cầu" được ghi như sau: </span></br><span
style='font-weight:italic'>BD: Yêu cầu bảo dưỡng thiết bị / SC: Yêu cầu sửa chữa
thiết bị bị hỏng / KT: Yêu cầu kiểm tra sự hoạt</span></span></i></br><span
lang=VI> </span><i><span style='font-weight:italic'><span lang=VI
style='mso-ansi-language:VI'>động của thiết bị mà không cần bảo dưỡng (VD như:
KT để nghiệm thu TB mới, KT tình trạng của thiết bị</span></span></i></br><span
lang=VI> </span><i><span style='font-weight:italic'><span lang=VI
style='mso-ansi-language:VI'>đã được BD trước đây nhưng chưa thả đo trong giếng
khoan, v.v.).</span></span></i></p>

<p><span lang=VI style='mso-ansi-language:VI'>4. Các yêu cầu khác (nếu có):<?php echo nl2br(dText($item['ycthemkh'])); ?><b><o:p></o:p></b></span></p>

<p><span lang=VI style='mso-ansi-language:VI'>5. Phục vụ sản xuất cho Lô/ Dịch
vụ ngoài: <b><?php echo dText($item['lo']); ?></b><o:p></o:p></span></p>

<p><span lang=VI style='mso-ansi-language:VI'>Tên mỏ: <b><?php echo dText($item['mo']); ?></b> Tên giếng: <b><?php echo dText($item['gieng']); ?></b><o:p></o:p></span></p>

<p><span lang=VI style='mso-ansi-language:VI'>6. Xem xét của lãnh đạo Xưởng (nếu
có): <?php echo nl2br(dText($item['xemxetxuong'])); ?><b> <o:p></o:p></b></span></p>

<p class=MsoNormal style='margin-bottom:12.0pt'><span lang=VI style='mso-fareast-font-family:
"Times New Roman";mso-ansi-language:VI'><o:p>&nbsp;</o:p></span></p>

<p><span lang=VI style='mso-ansi-language:VI'>Lãnh đạo Xưởng / Trưởng nhóm <i>(ký
ghi rõ họ tên) </i><o:p></o:p></span></p>

<table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0
 style='mso-cellspacing:0cm;mso-yfti-tbllook:1184;
 mso-padding-alt:0cm 0cm 0cm 0cm' id=hrdftrtbl>
 <tr style='mso-yfti-irow:0;mso-yfti-firstrow:yes;mso-yfti-lastrow:yes'>
  <td style='padding:0cm 0cm 0cm 0cm;border:none'>
  <div>
  <table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0 width="100%"
   style='width:100.0%;mso-cellspacing:0cm;mso-yfti-tbllook:1184;mso-padding-alt:
   0cm 0cm 0cm 0cm'>
   <tr style='mso-yfti-irow:0;mso-yfti-firstrow:yes;mso-yfti-lastrow:yes'>
    <td style='padding:0cm 0cm 0cm 0cm;border:none'>
    <p class=MsoNormal><span lang=VI style='mso-fareast-font-family:"Times New Roman";
    mso-ansi-language:VI'>BM.25.02<br>
    01/01/2024 <o:p></o:p></span></p>
    </td>
   </tr>
  </table>
  </div>
  </td>
 </tr>
</table>

<p class=MsoNormal><span style='mso-fareast-font-family:"Times New Roman"'><o:p>&nbsp;</o:p></span></p>

</div>

</body>

</html>
