<?php
// Export Word document for Giao Nhan Thiet Bi
// Set headers first
header("Content-Type: application/vnd.ms-word");
header("Content-Disposition: attachment; filename=\"GNTHIETBI-{$record['id']}.doc\""); 
header("Cache-Control: max-age=0");

// Output UTF-8 BOM for proper encoding detection by Word
echo "\xEF\xBB\xBF";

/**
 * Escape and encode text for Word XML
 * Handles latin1-stored UTF-8 data and escapes XML special characters
 */
function escapeWordText($text) {
    if ($text === null || $text === '') {
        return '';
    }
    
    // First strip any HTML tags
    $text = strip_tags($text);
    
    // Decode HTML entities (e.g., &ocirc; -> ô)
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // If data is stored as UTF-8 in latin1 column, it needs to be re-encoded
    // First check if it's valid UTF-8
    if (!mb_check_encoding($text, 'UTF-8')) {
        // Try to convert from latin1 to UTF-8
        $text = mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
    }
    
    // Escape XML special characters AFTER ensuring proper encoding
    $text = str_replace('&', '&amp;', $text);
    $text = str_replace('<', '&lt;', $text);
    $text = str_replace('>', '&gt;', $text);
    $text = str_replace('"', '&quot;', $text);
    $text = str_replace("'", '&#39;', $text);
    
    return $text;
}

// Format dates
$ngay_giao = !empty($record['ngay_giao']) ? date('d/m/Y', strtotime($record['ngay_giao'])) : '';
$ngay_gui_kiemdinh = !empty($record['ngay_gui_kiemdinh']) ? date('d/m/Y', strtotime($record['ngay_gui_kiemdinh'])) : '';
$ngay_giao_lai = !empty($record['ngay_giao_lai']) ? date('d/m/Y', strtotime($record['ngay_giao_lai'])) : '';

// Prepare data
$so_phieu = $record['id'] ?? '';
$ten_donvi_giao = $record['ten_donvi_giao'] ?? '';
$nguoi_giao = $record['nguoi_giao'] ?? '';
$ghichu = $record['ghichu'] ?? '';
?>
<html xmlns:v="urn:schemas-microsoft-com:vml"
xmlns:o="urn:schemas-microsoft-com:office:office"
xmlns:w="urn:schemas-microsoft-com:office:word"
xmlns:m="http://schemas.microsoft.com/office/2004/12/omml"
xmlns="http://www.w3.org/TR/REC-html40">

<head>
<meta http-equiv=Content-Type content="text/html; charset=unicode">
<meta name=ProgId content=Word.Document>
<meta name=Generator content="Microsoft Word 12">
<meta name=Originator content="Microsoft Word 12">
<!--[if gte mso 9]><xml>
 <o:DocumentProperties>
  <o:Author>VSP</o:Author>
  <o:Template>Normal</o:Template>
  <o:LastAuthor>VSP</o:LastAuthor>
  <o:Revision>1</o:Revision>
  <o:TotalTime>1</o:TotalTime>
  <o:Created><?php echo date('Y-m-d\TH:i:s\Z'); ?></o:Created>
  <o:LastSaved><?php echo date('Y-m-d\TH:i:s\Z'); ?></o:LastSaved>
  <o:Pages>1</o:Pages>
  <o:Words>200</o:Words>
  <o:Characters>1000</o:Characters>
  <o:Lines>10</o:Lines>
  <o:Paragraphs>5</o:Paragraphs>
  <o:CharactersWithSpaces>1200</o:CharactersWithSpaces>
  <o:Version>12.00</o:Version>
 </o:DocumentProperties>
</xml><![endif]-->
<!--[if gte mso 9]><xml>
 <w:WordDocument>
  <w:Zoom>110</w:Zoom>
  <w:TrackMoves>false</w:TrackMoves>
  <w:TrackFormatting/>
  <w:ValidateAgainstSchemas/>
  <w:SaveIfXMLInvalid>false</w:SaveIfXMLInvalid>
  <w:IgnoreMixedContent>false</w:IgnoreMixedContent>
  <w:AlwaysShowPlaceholderText>false</w:AlwaysShowPlaceholderText>
  <w:DoNotPromoteQF/>
  <w:LidThemeOther>EN-US</w:LidThemeOther>
  <w:LidThemeAsian>X-NONE</w:LidThemeAsian>
  <w:LidThemeComplexScript>X-NONE</w:LidThemeComplexScript>
  <w:Compatibility>
   <w:BreakWrappedTables/>
   <w:SnapToGridInCell/>
   <w:WrapTextWithPunct/>
   <w:UseAsianBreakRules/>
   <w:DontGrowAutofit/>
   <w:SplitPgBreakAndParaMark/>
   <w:DontVertAlignCellWithSp/>
   <w:DontBreakConstrainedForcedTables/>
   <w:DontVertAlignInTxbx/>
   <w:Word11KerningPairs/>
   <w:CachedColBalance/>
  </w:Compatibility>
  <w:BrowserLevel>MicrosoftInternetExplorer4</w:BrowserLevel>
  <m:mathPr>
   <m:mathFont m:val="Cambria Math"/>
   <m:brkBin m:val="before"/>
   <m:brkBinSub m:val="--"/>
   <m:smallFrac m:val="off"/>
   <m:dispDef/>
   <m:lMargin m:val="0"/>
   <m:rMargin m:val="0"/>
   <m:defJc m:val="centerGroup"/>
   <m:wrapIndent m:val="1440"/>
   <m:intLim m:val="subSup"/>
   <m:naryLim m:val="undOvr"/>
  </m:mathPr></w:WordDocument>
</xml><![endif]-->
<style>
<!--
 /* Font Definitions */
 @font-face
	{font-family:"Cambria Math";
	panose-1:2 4 5 3 5 4 6 3 2 4;
	mso-font-charset:0;
	mso-generic-font-family:roman;
	mso-font-pitch:variable;
	mso-font-signature:-1610611985 1107304683 0 0 159 0;}
@font-face
	{font-family:Calibri;
	panose-1:2 15 5 2 2 2 4 3 2 4;
	mso-font-charset:0;
	mso-generic-font-family:swiss;
	mso-font-pitch:variable;
	mso-font-signature:-1610611985 1073750139 0 0 159 0;}
 /* Style Definitions */
 p.MsoNormal, li.MsoNormal, div.MsoNormal
	{mso-style-unhide:no;
	mso-style-qformat:yes;
	mso-style-parent:"";
	margin:0in;
	margin-bottom:.0001pt;
	mso-pagination:widow-orphan;
	font-size:12.0pt;
	font-family:"Times New Roman","serif";
	mso-fareast-font-family:"Times New Roman";}
p
	{mso-style-noshow:yes;
	mso-style-priority:99;
	mso-margin-top-alt:auto;
	margin-right:0in;
	mso-margin-bottom-alt:auto;
	margin-left:0in;
	mso-pagination:widow-orphan;
	font-size:12.0pt;
	font-family:"Times New Roman","serif";
	mso-fareast-font-family:"Times New Roman";}
.MsoChpDefault
	{mso-style-type:export-only;
	mso-default-props:yes;
	font-size:10.0pt;
	mso-ansi-font-size:10.0pt;
	mso-bidi-font-size:10.0pt;}
@page Section1
	{size:595.35pt 841.95pt;
	margin:1.0in .75in 1.0in .75in;
	mso-header-margin:.5in;
	mso-footer-margin:.5in;
	mso-footer: f1;
	mso-paper-source:0;}
div.Section1
	{page:Section1;}
table.MsoTableGrid
	{mso-style-name:"Table Grid";
	mso-tstyle-rowband-size:0;
	mso-tstyle-colband-size:0;
	mso-style-priority:59;
	mso-style-unhide:no;
	border:solid windowtext 1.0pt;
	mso-border-alt:solid windowtext .5pt;
	mso-padding-alt:0in 5.4pt 0in 5.4pt;
	mso-border-insideh:.5pt solid windowtext;
	mso-border-insidev:.5pt solid windowtext;
	mso-para-margin:0in;
	mso-para-margin-bottom:.0001pt;
	mso-pagination:widow-orphan;
	font-size:10.0pt;
	font-family:"Times New Roman","serif";}
p.MsoFooter, li.MsoFooter, div.MsoFooter
{
    margin:0in;
    margin-bottom:.0001pt;
    mso-pagination:widow-orphan;
    tab-stops:center 3.0in right 6.0in;
    font-size:12.0pt;
}
table#hrdftrtbl{
    margin:0in 0in 0in 9in;
}
-->
</style>
</head>
<body lang=EN-US style='tab-interval:.5in'>

<div class=Section1>

<!-- Header -->
<table class=MsoNormalTable border=0 cellpadding=0 width="100%" style='width:100.0%;mso-cellspacing:1.5pt'>
 <tr>
  <td style='padding:.75pt .75pt .75pt .75pt'>
  <p class=MsoNormal><b><span style='mso-fareast-font-family:"Times New Roman"'>
  LIÊN DOANH VIỆT – NGA VIETSOVPETRO<br/>
  XÍ NGHIỆP ĐỊA VẬT LÝ GIẾNG KHOAN
  </span></b></p>
  </td>
  <td style='padding:.75pt .75pt .75pt .75pt; text-align:center'>
  <p class=MsoNormal><b><span style='mso-fareast-font-family:"Times New Roman"'>
  CỘNG HOÀ XÃ HỘI CHỦ NGHĨA VIỆT NAM<br/>
  Độc lập - Tự do - Hạnh phúc
  </span></b></p>
  </td>
 </tr>
</table>

<p class=MsoNormal style='margin-top:12pt;margin-bottom:12pt'><span style='mso-fareast-font-family:"Times New Roman"'>&nbsp;</span></p>

<p class=MsoNormal><span style='mso-fareast-font-family:"Times New Roman"'>
Số: ________________
</span></p>

<p class=MsoNormal style='margin-top:12pt;margin-bottom:12pt'><span style='mso-fareast-font-family:"Times New Roman"'>&nbsp;</span></p>

<p class=MsoNormal><span style='mso-fareast-font-family:"Times New Roman"'>
Kính gửi: Ông Dương Hoàng Hải<br/>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Giám đốc Xí nghiệp Cơ điện
</span></p>

<p class=MsoNormal style='margin-top:24pt;margin-bottom:12pt;text-align:center' align=center>
<b><span style='mso-fareast-font-family:"Times New Roman";font-size:14.0pt'>
PHIẾU YÊU CẦU CUNG CẤP DỊCH VỤ<br/>
ЗАЯВКА НА ПРЕДОСТАВЛЕНИЕ УСЛУГ
</span></b></p>

<p class=MsoNormal><span style='mso-fareast-font-family:"Times New Roman"'>
Loại phiếu: Bình thường ☐, Gấp ☐, Sự cố ☐
</span></p>

<p class=MsoNormal style='margin-top:6pt'><span style='mso-fareast-font-family:"Times New Roman"'>
Trong KH ☐ mục số: ________________ / Ngoài KH ☐<br/>
<i>(ghi chú *: mục số là STT trong file bảng tổng hợp nhu cầu hằng năm)</i>
</span></p>

<p class=MsoNormal style='margin-top:12pt'><span style='mso-fareast-font-family:"Times New Roman"'>
<b>1. Tên công việc / Наименование работ:</b> HC/KĐ thiết bị đo lường
</span></p>

<p class=MsoNormal style='margin-top:12pt'><span style='mso-fareast-font-family:"Times New Roman"'>
<b>2. Nội dung công việc / Содержание работ:</b>
</span></p>

<p class=MsoNormal style='margin-top:6pt;text-align:justify'><span style='mso-fareast-font-family:"Times New Roman"'>
Xí nghiệp Địa vật lý giếng khoan hiện đang sử dụng một số thiết bị đo lường đã đến kỳ hạn cần phải được hiệu chuẩn / kiểm định để phục vụ sản xuất. Kính đề nghị Ông xem xét và chỉ thị cho bộ phận chức năng Hiệu chuẩn/Kiểm định <?php echo count($thietbiList); ?> (<?php 
$soLuongChu = ['một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín', 'mười'];
echo $soLuongChu[count($thietbiList) - 1] ?? count($thietbiList);
?>) thiết bị được mô tả dưới đây:
</span></p>

<!-- Bảng thiết bị -->
<table class=MsoTableGrid border=1 cellspacing=0 cellpadding=0 style='border-collapse:collapse;border:solid windowtext 1.0pt;margin-top:12pt'>
 <tr style='height:30pt'>
  <td width=50 style='width:50pt;border:solid windowtext 1.0pt;padding:3pt;text-align:center'>
   <p class=MsoNormal align=center><b><span style='font-size:11.0pt'>Stt</span></b></p>
  </td>
  <td width=200 style='width:200pt;border:solid windowtext 1.0pt;padding:3pt;text-align:center'>
   <p class=MsoNormal align=center><b><span style='font-size:11.0pt'>Tên thiết bị</span></b></p>
  </td>
  <td width=120 style='width:120pt;border:solid windowtext 1.0pt;padding:3pt;text-align:center'>
   <p class=MsoNormal align=center><b><span style='font-size:11.0pt'>Mã hiệu</span></b></p>
  </td>
  <td width=100 style='width:100pt;border:solid windowtext 1.0pt;padding:3pt;text-align:center'>
   <p class=MsoNormal align=center><b><span style='font-size:11.0pt'>Số máy</span></b></p>
  </td>
  <td width=80 style='width:80pt;border:solid windowtext 1.0pt;padding:3pt;text-align:center'>
   <p class=MsoNormal align=center><b><span style='font-size:11.0pt'>Yêu cầu<br/>KĐ/HC</span></b></p>
  </td>
  <td width=80 style='width:80pt;border:solid windowtext 1.0pt;padding:3pt;text-align:center'>
   <p class=MsoNormal align=center><b><span style='font-size:11.0pt'>Sở hữu<br/>XDT</span></b></p>
  </td>
  <td width=120 style='width:120pt;border:solid windowtext 1.0pt;padding:3pt;text-align:center'>
   <p class=MsoNormal align=center><b><span style='font-size:11.0pt'>Ghi chú</span></b></p>
  </td>
 </tr>
<?php 
$stt = 1;
foreach ($thietbiList as $tb): 
?>
 <tr>
  <td style='border:solid windowtext 1.0pt;padding:3pt;text-align:center'>
   <p class=MsoNormal align=center><span style='font-size:11.0pt'><?php echo $stt; ?></span></p>
  </td>
  <td style='border:solid windowtext 1.0pt;padding:3pt'>
   <p class=MsoNormal><span style='font-size:11.0pt'><?php echo escapeWordText($tb['ten_thietbi'] ?? ''); ?></span></p>
  </td>
  <td style='border:solid windowtext 1.0pt;padding:3pt;text-align:center'>
   <p class=MsoNormal align=center><span style='font-size:11.0pt'><?php echo escapeWordText($tb['ky_ma_hieu'] ?? ''); ?></span></p>
  </td>
  <td style='border:solid windowtext 1.0pt;padding:3pt;text-align:center'>
   <p class=MsoNormal align=center><span style='font-size:11.0pt'><?php echo escapeWordText($tb['so_may'] ?? ''); ?></span></p>
  </td>
  <td style='border:solid windowtext 1.0pt;padding:3pt;text-align:center'>
   <p class=MsoNormal align=center><span style='font-size:11.0pt'>KĐ/HC</span></p>
  </td>
  <td style='border:solid windowtext 1.0pt;padding:3pt;text-align:center'>
   <p class=MsoNormal align=center><span style='font-size:11.0pt'>XDT</span></p>
  </td>
  <td style='border:solid windowtext 1.0pt;padding:3pt'>
   <p class=MsoNormal><span style='font-size:11.0pt'><?php echo escapeWordText($tb['ghichu'] ?? ''); ?></span></p>
  </td>
 </tr>
<?php 
$stt++;
endforeach; 
?>
</table>

<p class=MsoNormal style='margin-top:12pt'><span style='mso-fareast-font-family:"Times New Roman"'>
<b>3. Thời gian thực hiện / Срок исполнения:</b>
</span></p>

<p class=MsoNormal style='margin-left:.5in'><span style='mso-fareast-font-family:"Times New Roman"'>
• Bắt đầu / Начало: <?php echo escapeWordText($ngay_giao); ?><br/>
• Kết thúc / Завешение: <?php echo escapeWordText($ngay_giao_lai ?: '________________'); ?>
</span></p>

<p class=MsoNormal style='margin-top:12pt'><span style='mso-fareast-font-family:"Times New Roman"'>
<b>4. Mục tài chính / Пукнт финансирования:</b> ________________
</span></p>

<p class=MsoNormal style='margin-left:.5in'><span style='mso-fareast-font-family:"Times New Roman"'>
☐ Tên XN YCCDV: ________ (nêu MTC nếu cần)<br/>
☐ XNCĐ: ________ (nêu MTC nếu cần)
</span></p>

<p class=MsoNormal style='margin-top:12pt'><span style='mso-fareast-font-family:"Times New Roman"'>
<b>5. Các yêu cầu khác / Другие требования:</b>
</span></p>

<p class=MsoNormal style='margin-top:6pt'><span style='mso-fareast-font-family:"Times New Roman"'>
<?php echo escapeWordText($ghichu); ?>
</span></p>

<p class=MsoNormal style='margin-top:36pt;text-align:right' align=right>
<b><span style='mso-fareast-font-family:"Times New Roman"'>
LÃNH ĐẠO XN ĐỊA VẬT LÝ GK
</span></b></p>

<p class=MsoNormal style='margin-top:12pt'><span style='mso-fareast-font-family:"Times New Roman"'>
<b>Nơi nhận:</b><br/>
• Như trên<br/>
Lưu: Xưởng SCTBĐVL
</span></p>

<p class=MsoNormal style='margin-top:60pt;text-align:right' align=right>
<span style='mso-fareast-font-family:"Times New Roman"'>
Phạm Hồng Khanh
</span></p>

<p class=MsoNormal style='margin-top:24pt'><span style='mso-fareast-font-family:"Times New Roman"'>
<b>Ký tắt:</b>
</span></p>

<p class=MsoNormal><span style='mso-fareast-font-family:"Times New Roman"'>
Xưởng SCTBĐVL: Trương Ngọc Sang
</span></p>

<p class=MsoNormal style='margin-top:12pt'><span style='mso-fareast-font-family:"Times New Roman"'>
Thực hiện: Nguyễn Duy Khanh - 0915626365
</span></p>

<!-- Footer -->
<table id='hrdftrtbl' border='1' cellspacing='0' cellpadding='0'>
 <tr>
  <td>
   <div style='mso-element:footer' id="f1">
    <p class="MsoFooter">
     <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
       <td class="footer">
        <span lang=VI style='mso-ansi-language:VI'>
         MB-DD001-01<br/>
         01/01/2024
        </span>
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
