<?php
// Export Word document
// Set headers first
header("Content-Type: application/vnd.ms-word");
header("Content-Disposition: attachment; filename=\"{$sohoso}-YCDV.doc\""); 
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
  <o:Author>Ducop</o:Author>
  <o:Template>Normal</o:Template>
  <o:LastAuthor>Ducop</o:LastAuthor>
  <o:Revision>2</o:Revision>
  <o:TotalTime>2</o:TotalTime>
  <o:Created>2017-10-01T03:06:00Z</o:Created>
  <o:LastSaved>2017-10-01T03:06:00Z</o:LastSaved>
  <o:Pages>1</o:Pages>
  <o:Words>111</o:Words>
  <o:Characters>633</o:Characters>
  <o:Lines>5</o:Lines>
  <o:Paragraphs>1</o:Paragraphs>
  <o:CharactersWithSpaces>743</o:CharactersWithSpaces>
  <o:Version>12.00</o:Version>
 </o:DocumentProperties>
</xml><![endif]-->
<!--[if gte mso 9]><xml>
 <w:WordDocument>
  <w:Zoom>110</w:Zoom>
  <w:TrackMoves>false</w:TrackMoves>
  <w:TrackFormatting/>
  <w:DrawingGridHorizontalSpacing>6 pt</w:DrawingGridHorizontalSpacing>
  <w:DisplayHorizontalDrawingGridEvery>2</w:DisplayHorizontalDrawingGridEvery>
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
</xml><![endif]--><!--[if gte mso 9]><xml>
 <w:LatentStyles DefLockedState="false" DefUnhideWhenUsed="true"
  DefSemiHidden="true" DefQFormat="false" DefPriority="99"
  LatentStyleCount="267">
  <w:LsdException Locked="false" Priority="0" SemiHidden="false"
   UnhideWhenUsed="false" QFormat="true" Name="Normal"/>
  <w:LsdException Locked="false" Priority="9" SemiHidden="false"
   UnhideWhenUsed="false" QFormat="true" Name="heading 1"/>
  <w:LsdException Locked="false" Priority="9" QFormat="true" Name="heading 2"/>
  <w:LsdException Locked="false" Priority="9" QFormat="true" Name="heading 3"/>
  <w:LsdException Locked="false" Priority="9" QFormat="true" Name="heading 4"/>
  <w:LsdException Locked="false" Priority="9" QFormat="true" Name="heading 5"/>
  <w:LsdException Locked="false" Priority="9" QFormat="true" Name="heading 6"/>
  <w:LsdException Locked="false" Priority="9" QFormat="true" Name="heading 7"/>
  <w:LsdException Locked="false" Priority="9" QFormat="true" Name="heading 8"/>
  <w:LsdException Locked="false" Priority="9" QFormat="true" Name="heading 9"/>
  <w:LsdException Locked="false" Priority="39" Name="toc 1"/>
  <w:LsdException Locked="false" Priority="39" Name="toc 2"/>
  <w:LsdException Locked="false" Priority="39" Name="toc 3"/>
  <w:LsdException Locked="false" Priority="39" Name="toc 4"/>
  <w:LsdException Locked="false" Priority="39" Name="toc 5"/>
  <w:LsdException Locked="false" Priority="39" Name="toc 6"/>
  <w:LsdException Locked="false" Priority="39" Name="toc 7"/>
  <w:LsdException Locked="false" Priority="39" Name="toc 8"/>
  <w:LsdException Locked="false" Priority="39" Name="toc 9"/>
  <w:LsdException Locked="false" Priority="35" QFormat="true" Name="caption"/>
  <w:LsdException Locked="false" Priority="10" SemiHidden="false"
   UnhideWhenUsed="false" QFormat="true" Name="Title"/>
  <w:LsdException Locked="false" Priority="1" Name="Default Paragraph Font"/>
  <w:LsdException Locked="false" Priority="11" SemiHidden="false"
   UnhideWhenUsed="false" QFormat="true" Name="Subtitle"/>
  <w:LsdException Locked="false" Priority="22" SemiHidden="false"
   UnhideWhenUsed="false" QFormat="true" Name="Strong"/>
  <w:LsdException Locked="false" Priority="20" SemiHidden="false"
   UnhideWhenUsed="false" QFormat="true" Name="Emphasis"/>
  <w:LsdException Locked="false" Priority="59" SemiHidden="false"
   UnhideWhenUsed="false" Name="Table Grid"/>
  <w:LsdException Locked="false" UnhideWhenUsed="false" Name="Placeholder Text"/>
  <w:LsdException Locked="false" Priority="1" SemiHidden="false"
   UnhideWhenUsed="false" QFormat="true" Name="No Spacing"/>
  <w:LsdException Locked="false" Priority="60" SemiHidden="false"
   UnhideWhenUsed="false" Name="Light Shading"/>
  <w:LsdException Locked="false" Priority="61" SemiHidden="false"
   UnhideWhenUsed="false" Name="Light List"/>
  <w:LsdException Locked="false" Priority="62" SemiHidden="false"
   UnhideWhenUsed="false" Name="Light Grid"/>
  <w:LsdException Locked="false" Priority="63" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Shading 1"/>
  <w:LsdException Locked="false" Priority="64" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Shading 2"/>
  <w:LsdException Locked="false" Priority="65" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium List 1"/>
  <w:LsdException Locked="false" Priority="66" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium List 2"/>
  <w:LsdException Locked="false" Priority="67" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Grid 1"/>
  <w:LsdException Locked="false" Priority="68" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Grid 2"/>
  <w:LsdException Locked="false" Priority="69" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Grid 3"/>
  <w:LsdException Locked="false" Priority="70" SemiHidden="false"
   UnhideWhenUsed="false" Name="Dark List"/>
  <w:LsdException Locked="false" Priority="71" SemiHidden="false"
   UnhideWhenUsed="false" Name="Colorful Shading"/>
  <w:LsdException Locked="false" Priority="72" SemiHidden="false"
   UnhideWhenUsed="false" Name="Colorful List"/>
  <w:LsdException Locked="false" Priority="73" SemiHidden="false"
   UnhideWhenUsed="false" Name="Colorful Grid"/>
  <w:LsdException Locked="false" Priority="60" SemiHidden="false"
   UnhideWhenUsed="false" Name="Light Shading Accent 1"/>
  <w:LsdException Locked="false" Priority="61" SemiHidden="false"
   UnhideWhenUsed="false" Name="Light List Accent 1"/>
  <w:LsdException Locked="false" Priority="62" SemiHidden="false"
   UnhideWhenUsed="false" Name="Light Grid Accent 1"/>
  <w:LsdException Locked="false" Priority="63" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Shading 1 Accent 1"/>
  <w:LsdException Locked="false" Priority="64" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Shading 2 Accent 1"/>
  <w:LsdException Locked="false" Priority="65" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium List 1 Accent 1"/>
  <w:LsdException Locked="false" UnhideWhenUsed="false" Name="Revision"/>
  <w:LsdException Locked="false" Priority="34" SemiHidden="false"
   UnhideWhenUsed="false" QFormat="true" Name="List Paragraph"/>
  <w:LsdException Locked="false" Priority="29" SemiHidden="false"
   UnhideWhenUsed="false" QFormat="true" Name="Quote"/>
  <w:LsdException Locked="false" Priority="30" SemiHidden="false"
   UnhideWhenUsed="false" QFormat="true" Name="Intense Quote"/>
  <w:LsdException Locked="false" Priority="66" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium List 2 Accent 1"/>
  <w:LsdException Locked="false" Priority="67" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Grid 1 Accent 1"/>
  <w:LsdException Locked="false" Priority="68" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Grid 2 Accent 1"/>
  <w:LsdException Locked="false" Priority="69" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Grid 3 Accent 1"/>
  <w:LsdException Locked="false" Priority="70" SemiHidden="false"
   UnhideWhenUsed="false" Name="Dark List Accent 1"/>
  <w:LsdException Locked="false" Priority="71" SemiHidden="false"
   UnhideWhenUsed="false" Name="Colorful Shading Accent 1"/>
  <w:LsdException Locked="false" Priority="72" SemiHidden="false"
   UnhideWhenUsed="false" Name="Colorful List Accent 1"/>
  <w:LsdException Locked="false" Priority="73" SemiHidden="false"
   UnhideWhenUsed="false" Name="Colorful Grid Accent 1"/>
  <w:LsdException Locked="false" Priority="60" SemiHidden="false"
   UnhideWhenUsed="false" Name="Light Shading Accent 2"/>
  <w:LsdException Locked="false" Priority="61" SemiHidden="false"
   UnhideWhenUsed="false" Name="Light List Accent 2"/>
  <w:LsdException Locked="false" Priority="62" SemiHidden="false"
   UnhideWhenUsed="false" Name="Light Grid Accent 2"/>
  <w:LsdException Locked="false" Priority="63" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Shading 1 Accent 2"/>
  <w:LsdException Locked="false" Priority="64" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Shading 2 Accent 2"/>
  <w:LsdException Locked="false" Priority="65" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium List 1 Accent 2"/>
  <w:LsdException Locked="false" Priority="66" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium List 2 Accent 2"/>
  <w:LsdException Locked="false" Priority="67" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Grid 1 Accent 2"/>
  <w:LsdException Locked="false" Priority="68" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Grid 2 Accent 2"/>
  <w:LsdException Locked="false" Priority="69" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Grid 3 Accent 2"/>
  <w:LsdException Locked="false" Priority="70" SemiHidden="false"
   UnhideWhenUsed="false" Name="Dark List Accent 2"/>
  <w:LsdException Locked="false" Priority="71" SemiHidden="false"
   UnhideWhenUsed="false" Name="Colorful Shading Accent 2"/>
  <w:LsdException Locked="false" Priority="72" SemiHidden="false"
   UnhideWhenUsed="false" Name="Colorful List Accent 2"/>
  <w:LsdException Locked="false" Priority="73" SemiHidden="false"
   UnhideWhenUsed="false" Name="Colorful Grid Accent 2"/>
  <w:LsdException Locked="false" Priority="60" SemiHidden="false"
   UnhideWhenUsed="false" Name="Light Shading Accent 3"/>
  <w:LsdException Locked="false" Priority="61" SemiHidden="false"
   UnhideWhenUsed="false" Name="Light List Accent 3"/>
  <w:LsdException Locked="false" Priority="62" SemiHidden="false"
   UnhideWhenUsed="false" Name="Light Grid Accent 3"/>
  <w:LsdException Locked="false" Priority="63" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Shading 1 Accent 3"/>
  <w:LsdException Locked="false" Priority="64" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Shading 2 Accent 3"/>
  <w:LsdException Locked="false" Priority="65" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium List 1 Accent 3"/>
  <w:LsdException Locked="false" Priority="66" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium List 2 Accent 3"/>
  <w:LsdException Locked="false" Priority="67" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Grid 1 Accent 3"/>
  <w:LsdException Locked="false" Priority="68" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Grid 2 Accent 3"/>
  <w:LsdException Locked="false" Priority="69" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Grid 3 Accent 3"/>
  <w:LsdException Locked="false" Priority="70" SemiHidden="false"
   UnhideWhenUsed="false" Name="Dark List Accent 3"/>
  <w:LsdException Locked="false" Priority="71" SemiHidden="false"
   UnhideWhenUsed="false" Name="Colorful Shading Accent 3"/>
  <w:LsdException Locked="false" Priority="72" SemiHidden="false"
   UnhideWhenUsed="false" Name="Colorful List Accent 3"/>
  <w:LsdException Locked="false" Priority="73" SemiHidden="false"
   UnhideWhenUsed="false" Name="Colorful Grid Accent 3"/>
  <w:LsdException Locked="false" Priority="60" SemiHidden="false"
   UnhideWhenUsed="false" Name="Light Shading Accent 4"/>
  <w:LsdException Locked="false" Priority="61" SemiHidden="false"
   UnhideWhenUsed="false" Name="Light List Accent 4"/>
  <w:LsdException Locked="false" Priority="62" SemiHidden="false"
   UnhideWhenUsed="false" Name="Light Grid Accent 4"/>
  <w:LsdException Locked="false" Priority="63" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Shading 1 Accent 4"/>
  <w:LsdException Locked="false" Priority="64" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Shading 2 Accent 4"/>
  <w:LsdException Locked="false" Priority="65" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium List 1 Accent 4"/>
  <w:LsdException Locked="false" Priority="66" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium List 2 Accent 4"/>
  <w:LsdException Locked="false" Priority="67" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Grid 1 Accent 4"/>
  <w:LsdException Locked="false" Priority="68" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Grid 2 Accent 4"/>
  <w:LsdException Locked="false" Priority="69" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Grid 3 Accent 4"/>
  <w:LsdException Locked="false" Priority="70" SemiHidden="false"
   UnhideWhenUsed="false" Name="Dark List Accent 4"/>
  <w:LsdException Locked="false" Priority="71" SemiHidden="false"
   UnhideWhenUsed="false" Name="Colorful Shading Accent 4"/>
  <w:LsdException Locked="false" Priority="72" SemiHidden="false"
   UnhideWhenUsed="false" Name="Colorful List Accent 4"/>
  <w:LsdException Locked="false" Priority="73" SemiHidden="false"
   UnhideWhenUsed="false" Name="Colorful Grid Accent 4"/>
  <w:LsdException Locked="false" Priority="60" SemiHidden="false"
   UnhideWhenUsed="false" Name="Light Shading Accent 5"/>
  <w:LsdException Locked="false" Priority="61" SemiHidden="false"
   UnhideWhenUsed="false" Name="Light List Accent 5"/>
  <w:LsdException Locked="false" Priority="62" SemiHidden="false"
   UnhideWhenUsed="false" Name="Light Grid Accent 5"/>
  <w:LsdException Locked="false" Priority="63" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Shading 1 Accent 5"/>
  <w:LsdException Locked="false" Priority="64" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Shading 2 Accent 5"/>
  <w:LsdException Locked="false" Priority="65" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium List 1 Accent 5"/>
  <w:LsdException Locked="false" Priority="66" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium List 2 Accent 5"/>
  <w:LsdException Locked="false" Priority="67" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Grid 1 Accent 5"/>
  <w:LsdException Locked="false" Priority="68" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Grid 2 Accent 5"/>
  <w:LsdException Locked="false" Priority="69" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Grid 3 Accent 5"/>
  <w:LsdException Locked="false" Priority="70" SemiHidden="false"
   UnhideWhenUsed="false" Name="Dark List Accent 5"/>
  <w:LsdException Locked="false" Priority="71" SemiHidden="false"
   UnhideWhenUsed="false" Name="Colorful Shading Accent 5"/>
  <w:LsdException Locked="false" Priority="72" SemiHidden="false"
   UnhideWhenUsed="false" Name="Colorful List Accent 5"/>
  <w:LsdException Locked="false" Priority="73" SemiHidden="false"
   UnhideWhenUsed="false" Name="Colorful Grid Accent 5"/>
  <w:LsdException Locked="false" Priority="60" SemiHidden="false"
   UnhideWhenUsed="false" Name="Light Shading Accent 6"/>
  <w:LsdException Locked="false" Priority="61" SemiHidden="false"
   UnhideWhenUsed="false" Name="Light List Accent 6"/>
  <w:LsdException Locked="false" Priority="62" SemiHidden="false"
   UnhideWhenUsed="false" Name="Light Grid Accent 6"/>
  <w:LsdException Locked="false" Priority="63" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Shading 1 Accent 6"/>
  <w:LsdException Locked="false" Priority="64" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Shading 2 Accent 6"/>
  <w:LsdException Locked="false" Priority="65" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium List 1 Accent 6"/>
  <w:LsdException Locked="false" Priority="66" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium List 2 Accent 6"/>
  <w:LsdException Locked="false" Priority="67" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Grid 1 Accent 6"/>
  <w:LsdException Locked="false" Priority="68" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Grid 2 Accent 6"/>
  <w:LsdException Locked="false" Priority="69" SemiHidden="false"
   UnhideWhenUsed="false" Name="Medium Grid 3 Accent 6"/>
  <w:LsdException Locked="false" Priority="70" SemiHidden="false"
   UnhideWhenUsed="false" Name="Dark List Accent 6"/>
  <w:LsdException Locked="false" Priority="71" SemiHidden="false"
   UnhideWhenUsed="false" Name="Colorful Shading Accent 6"/>
  <w:LsdException Locked="false" Priority="72" SemiHidden="false"
   UnhideWhenUsed="false" Name="Colorful List Accent 6"/>
  <w:LsdException Locked="false" Priority="73" SemiHidden="false"
   UnhideWhenUsed="false" Name="Colorful Grid Accent 6"/>
  <w:LsdException Locked="false" Priority="19" SemiHidden="false"
   UnhideWhenUsed="false" QFormat="true" Name="Subtle Emphasis"/>
  <w:LsdException Locked="false" Priority="21" SemiHidden="false"
   UnhideWhenUsed="false" QFormat="true" Name="Intense Emphasis"/>
  <w:LsdException Locked="false" Priority="31" SemiHidden="false"
   UnhideWhenUsed="false" QFormat="true" Name="Subtle Reference"/>
  <w:LsdException Locked="false" Priority="32" SemiHidden="false"
   UnhideWhenUsed="false" QFormat="true" Name="Intense Reference"/>
  <w:LsdException Locked="false" Priority="33" SemiHidden="false"
   UnhideWhenUsed="false" QFormat="true" Name="Book Title"/>
  <w:LsdException Locked="false" Priority="37" Name="Bibliography"/>
  <w:LsdException Locked="false" Priority="39" QFormat="true" Name="TOC Heading"/>
 </w:LatentStyles>
</xml><![endif]-->
<style>
<!--
 /* Font Definitions */
 @font-face
	{font-family:Batang;
	panose-1:2 3 6 0 0 1 1 1 1 1;
	mso-font-alt:??;
	mso-font-charset:129;
	mso-generic-font-family:roman;
	mso-font-pitch:variable;
	mso-font-signature:-1342176593 1775729915 48 0 524447 0;}
@font-face
	{font-family:"Cambria Math";
	panose-1:2 4 5 3 5 4 6 3 2 4;
	mso-font-charset:1;
	mso-generic-font-family:roman;
	mso-font-format:other;
	mso-font-pitch:variable;
	mso-font-signature:0 0 0 0 0 0;}
@font-face
	{font-family:Meiryo;
	panose-1:2 11 6 4 3 5 4 4 2 4;
	mso-font-charset:0;
	mso-generic-font-family:roman;
	mso-font-format:other;
	mso-font-pitch:auto;
	mso-font-signature:0 0 0 0 0 0;}
@font-face
	{font-family:"\@Batang";
	panose-1:2 3 6 0 0 1 1 1 1 1;
	mso-font-charset:129;
	mso-generic-font-family:roman;
	mso-font-pitch:variable;
	mso-font-signature:-1342176593 1775729915 48 0 524447 0;}
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
	mso-fareast-font-family:"Times New Roman";
	mso-fareast-theme-font:minor-fareast;}
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
	mso-fareast-font-family:"Times New Roman";
	mso-fareast-theme-font:minor-fareast;}
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
-->
</style>
<style>
v\:* {behavior:url(#default#VML);}
o\:* {behavior:url(#default#VML);}
w\:* {behavior:url(#default#VML);}
.shape {behavior:url(#default#VML);}
</style>

<!--[if gte mso 10]>
<style>
 /* Style Definitions */
 table.MsoNormalTable
	{mso-style-name:"Table Normal";
	mso-tstyle-rowband-size:0;
	mso-tstyle-colband-size:0;
	mso-style-noshow:yes;
	mso-style-priority:99;
	mso-style-qformat:yes;
	mso-style-parent:"";
	mso-padding-alt:0in 5.4pt 0in 5.4pt;
	mso-para-margin:0in;
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
	
</style>
<![endif]--><!--[if gte mso 9]><xml>
 <o:shapedefaults v:ext="edit" spidmax="2050"/>
</xml><![endif]--><!--[if gte mso 9]><xml>
 <o:shapelayout v:ext="edit">
  <o:idmap v:ext="edit" data="1"/>
 </o:shapelayout></xml><![endif]-->
</head>
<body lang=EN-US style='tab-interval:.5in'>

<div class=Section1>

<table class=MsoNormalTable border=0 cellpadding=0 width="100%"
 style='width:100.0%;mso-cellspacing:1.5pt;mso-yfti-tbllook:1184'>
 <tr style='mso-yfti-irow:0;mso-yfti-firstrow:yes;mso-yfti-lastrow:yes'>
  <td style='padding:.75pt .75pt .75pt .75pt'>
  <p class=MsoNormal><span style='mso-fareast-font-family:"Times New Roman"'>XN
  Địa vật lý GK <br/>
  Xưởng SCTBĐVL <o:p></o:p></span></p>
  </td>
  <td style='padding:.75pt .75pt .75pt .75pt'>
  <p class=MsoNormal><b><span style='mso-fareast-font-family:"Times New Roman"'>&nbsp;&nbsp;&nbsp;&nbsp;
  PHIẾU YÊU CẦU DỊCH VỤ</span></b><span style='mso-fareast-font-family:"Times New Roman"'><br/><br/>
  <strong>Số hồ sơ:</strong> <strong><?php echo escapeWordText($sohoso); ?></strong>
  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>Ngày, Датa: <?php echo escapeWordText($ngay); ?></strong>
  <o:p></o:p></span></p>
  </td>
 </tr>
</table>

<p class=MsoNormal><span lang=VI style='mso-fareast-font-family:"Times New Roman";
mso-ansi-language:VI'><o:p>&nbsp;</o:p></span></p>

<table class=MsoNormalTable border=0 cellpadding=0 width="100%"
 style='width:100.0%;mso-cellspacing:1.5pt;mso-yfti-tbllook:1184'>
 <tr style='mso-yfti-irow:0;mso-yfti-firstrow:yes'>
  <td width="65%" style='width:65.0%;padding:.75pt .75pt .75pt .75pt'>
  <p class=MsoNormal><span style='mso-fareast-font-family:"Times New Roman"'>1.
  Người yêu cầu/bàn giao TB,Сдал:&nbsp; <b><?php echo escapeWordText($khachhang); ?></b><o:p></o:p></span></p>
  </td>
  <td width="40%" style='width:40.0%;padding:.75pt .75pt .75pt .75pt'>
  <p class=MsoNormal><span style='mso-fareast-font-family:"Times New Roman"'>Ký tên(Сдал /Подпись): .........<o:p></o:p></span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:1'>
  <td width="60%" style='width:60.0%;padding:.75pt .75pt .75pt .75pt'>
  <p class=MsoNormal><span style='mso-fareast-font-family:"Times New Roman"'>&nbsp;&nbsp;&nbsp;&nbsp;Đơn vị,Подр: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <b><?php echo escapeWordText($donvi); ?></b> <o:p></o:p></span></p>
  </td>
  <td width="40%" style='width:40.0%;padding:.75pt .75pt .75pt .75pt'>
  <p class=MsoNormal><span style='mso-fareast-font-family:"Times New Roman"'>Điện thoại liên lạc (Tel):&nbsp; <b><?php echo escapeWordText($dienthoai); ?></b><o:p></o:p></span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:0;mso-yfti-firstrow:yes'>
  <td width="60%" style='width:60.0%;padding:.75pt .75pt .75pt .75pt'>
  <p class=MsoNormal><span style='mso-fareast-font-family:"Times New Roman"'>2.
  Người nhận thiết bị, Принял:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <b><?php echo escapeWordText($nhanvien); ?></b><o:p></o:p></span></p>
  </td>
  <td width="40%" style='width:40.0%;padding:.75pt .75pt .75pt .75pt'>
  <p class=MsoNormal><span style='mso-fareast-font-family:"Times New Roman"'>Ký tên(Принял /Подпись): .........<o:p></o:p></span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:2;mso-yfti-lastrow:yes'>
  <td style='padding:.75pt .75pt .75pt .75pt'>
  <p class=MsoNormal><span style='mso-fareast-font-family:"Times New Roman"'>3.
  Nội dung: <o:p></o:p></span></p>
  </td>
  <td style='padding:.75pt .75pt .75pt .75pt'></td>
 </tr>
</table>

<p class=MsoNormal><span lang=VI style='mso-fareast-font-family:"Times New Roman";
mso-ansi-language:VI'><o:p>&nbsp;</o:p></span></p>
<table class=MsoNormalTable border=1 cellspacing=0 cellpadding=0 width=716
 style='width:536.7pt;margin-left:-12.6pt;border-collapse:collapse;border:none;
 mso-border-alt:solid windowtext .5pt;mso-yfti-tbllook:1184;mso-padding-alt:
 0in 5.4pt 0in 5.4pt;mso-border-insideh:.5pt solid windowtext;mso-border-insidev:
 .5pt solid windowtext'>
 <tr style='mso-yfti-irow:0;mso-yfti-firstrow:yes;height:5.45pt'>
  <td width=41 style='width:30.9pt;border:solid windowtext 1.0pt;mso-border-alt:
  solid windowtext .5pt;padding:0in 5.4pt 0in 5.4pt;height:5.45pt'>
  <p class=MsoNormal align=center style='margin-top:0in;margin-right:-5.4pt;
  margin-bottom:0in;margin-left:5.4pt;margin-bottom:.0001pt;text-align:center;
  text-indent:-5.4pt'><b style='mso-bidi-font-weight:normal'><span
  style='color:black;mso-themecolor:text1'>STT</span></b></p>
  <p class=MsoNormal align=center style='margin-top:0in;margin-right:-5.4pt;
  margin-bottom:0in;margin-left:5.4pt;margin-bottom:.0001pt;text-align:center;
  text-indent:-5.4pt;mso-line-height-alt:5.45pt'><span style='color:black;
  mso-themecolor:text1'>П/П</span></p>
  </td>
  <td width=162 style='width:121.35pt;border:solid windowtext 1.0pt;border-left:
  none;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;
  padding:0in 5.4pt 0in 5.4pt;height:5.45pt'>
  <p class=MsoNormal align=center style='mso-margin-top-alt:auto;mso-margin-bottom-alt:
  auto;text-align:center'><b style='mso-bidi-font-weight:normal'><span
  style='color:black;mso-themecolor:text1'>Tên thiết</span></b><b
  style='mso-bidi-font-weight:normal'><span style='color:black;mso-themecolor:
  text1;mso-ansi-language:RU'> </span><span style='color:black;mso-themecolor:
  text1'> bị</span></b><b style='mso-bidi-font-weight:normal'><span lang=RU
  style='color:black;mso-themecolor:text1;mso-ansi-language:RU'> - </span><span
  style='color:black;mso-themecolor:text1'>Model</span></b><span
  lang=RU style='mso-ansi-language:RU'><o:p></o:p></span></p>
  <p class=MsoNormal align=center style='mso-margin-top-alt:auto;mso-margin-bottom-alt:
  auto;text-align:center;mso-line-height-alt:5.45pt'><span lang=RU
  style='color:black;mso-themecolor:text1;mso-ansi-language:RU'>Наим-е  оборудования</span><span
  lang=RU style='mso-ansi-language:RU'><o:p></o:p></span></p>
  </td>
  <td width=94 style='width:70.85pt;border:solid windowtext 1.0pt;border-left:
  none;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;
  padding:0in 5.4pt 0in 5.4pt;height:5.45pt'>
  <p class=MsoNormal align=center style='mso-margin-top-alt:auto;mso-margin-bottom-alt:
  auto;text-align:center'><b style='mso-bidi-font-weight:normal'><span
  style='color:black;mso-themecolor:text1'>Số của thiết bị - Serial</span></b></p>
  <p class=MsoNormal align=center style='mso-margin-top-alt:auto;mso-margin-bottom-alt:
  auto;text-align:center;mso-line-height-alt:5.45pt'><span style='color:black;
  mso-themecolor:text1'>Номер</span></p>
  </td>

  <td width=158 style='width:1.65in;border:solid windowtext 1.0pt;border-left:
  none;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;
  padding:0in 5.4pt 0in 5.4pt;height:5.45pt'>
  <p class=MsoNormal align=center style='mso-margin-top-alt:auto;mso-margin-bottom-alt:
  auto;text-align:center'><b style='mso-bidi-font-weight:normal'><span
  style='color:black;mso-themecolor:text1'>Mô tả chi tiết tình trạng</span></b><b
  style='mso-bidi-font-weight:normal'><span style='font-family:"Batang","serif";
  mso-bidi-font-family:Batang;color:black;mso-themecolor:text1'></span><span
  style='color:black;mso-themecolor:text1'> kỹ thuật của thiết bị trước khi đưa về Xưởng</span></b></p>
  <p class=MsoNormal align=center style='mso-margin-top-alt:auto;mso-margin-bottom-alt:
  auto;text-align:center;mso-line-height-alt:5.45pt'><span lang=RU
  style='color:black;mso-themecolor:text1;mso-ansi-language:RU'>Тех</span><span
  style='color:black;mso-themecolor:text1'>. </span><span lang=RU
  style='color:black;mso-themecolor:text1;mso-ansi-language:RU'></span><span
  style='color:black;mso-themecolor:text1'>состояние</span></p>
  </td>
  <td width=97 style='width:72.65pt;border:solid windowtext 1.0pt;border-left:
  none;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;
  padding:0in 5.4pt 0in 5.4pt;height:5.45pt'>
  <p class=MsoNormal align=center style='mso-margin-top-alt:auto;mso-margin-bottom-alt:
  auto;text-align:center'><b style='mso-bidi-font-weight:normal'><span
  style='color:black;mso-themecolor:text1'>Nội dung</span></b><b
  style='mso-bidi-font-weight:normal'><span style='font-family:"Batang","serif";
  mso-bidi-font-family:Batang;color:black;mso-themecolor:text1;mso-ansi-language:
  RU'></span><span style='color:black;mso-themecolor:text1'> yêu cầu</span></b><span
  style='color:black;mso-themecolor:text1'><o:p></o:p></span></p>
  <p class=MsoNormal align=center style='mso-margin-top-alt:auto;mso-margin-bottom-alt:
  auto;text-align:center;mso-line-height-alt:5.45pt'><span lang=RU
  style='color:black;mso-themecolor:text1;mso-ansi-language:RU'>Требование<o:p></o:p></span></p>
  </td>
  <td width=66 valign=top style='width:49.5pt;border:solid windowtext 1.0pt;
  border-left:none;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:
  solid windowtext .5pt;padding:0in 5.4pt 0in 5.4pt;height:5.45pt'>
  <p class=MsoNormal align=center style='mso-margin-top-alt:auto;mso-margin-bottom-alt:
  auto;text-align:center;mso-line-height-alt:5.45pt'><b style='mso-bidi-font-weight:
  normal'><span style='color:black;mso-themecolor:text1'>Thiết</span></b><b
  style='mso-bidi-font-weight:normal'><span style='color:black;mso-themecolor:
  text1;mso-ansi-language:RU'> </span><span style='color:black;mso-themecolor:
  text1'>bị</span></b><b style='mso-bidi-font-weight:normal'><span
  style='color:black;mso-themecolor:text1;mso-ansi-language:RU'> </span><span
  style='color:black;mso-themecolor:text1'>từ</span></b><b style='mso-bidi-font-weight:
  normal'><span lang=RU style='color:black;mso-themecolor:text1;mso-ansi-language:
  RU'></span></b><b style='mso-bidi-font-weight:normal'><span
  style='mso-ascii-font-family:Meiryo;mso-hansi-font-family:Meiryo;mso-bidi-font-family:
  Meiryo;color:black;mso-themecolor:text1;mso-ansi-language:RU'></span></b><b
  style='mso-bidi-font-weight:normal'><span lang=RU style='color:black;
  mso-themecolor:text1;mso-ansi-language:RU'> đâu đưa về Xưởng</span></b><span
  lang=RU style='color:black;mso-themecolor:text1;mso-ansi-language:RU'><o:p></o:p></span></p>
  </td>
 </tr>
<?php
$i = 1;
while ($i <= 5 + $solan) {
    if (isset($thietbi[$i]) && $thietbi[$i] != "") {
        // Display format: mavt-model
        $mamay = $mavt[$i] ?? '';
        if (!empty($model[$i])) {
            $mamay .= (!empty($mamay) ? '-' : '') . $model[$i];
        }
        // If no mavt-model, fallback to thietbi name
        if (empty($mamay)) {
            $mamay = $thietbi[$i];
        }
?>
 <tr style='mso-yfti-irow:1;mso-yfti-lastrow:yes;height:20.2pt'>
  <td width=41 style='width:30.9pt;border:solid windowtext 1.0pt;border-top:
  none;mso-border-top-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;
  padding:0in 5.4pt 0in 5.4pt;height:20.2pt'>
  <p class=MsoNormal align=center style='mso-margin-top-alt:auto;mso-margin-bottom-alt:
  auto;text-align:center;mso-line-height-alt:5.95pt'><span style='color:black;
  mso-themecolor:text1'><?php echo $i; ?><o:p></o:p></span></p>
  </td>
  <td width=162 style='width:121.35pt;border-top:none;border-left:none;
  border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;
  mso-border-alt:solid windowtext .5pt;padding:0in 5.4pt 0in 5.4pt;height:20.2pt'>
  <p class=MsoNormal align=center style='mso-margin-top-alt:auto;mso-margin-bottom-alt:
  auto;text-align:center;mso-line-height-alt:5.95pt'><span style='color:black;
  mso-themecolor:text1'><?php echo escapeWordText($mamay); ?></span></p>
  </td>
  <td width=94 style='width:70.85pt;border-top:none;border-left:none;
  border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;
  mso-border-alt:solid windowtext .5pt;padding:0in 5.4pt 0in 5.4pt;height:20.2pt'>
  <p class=MsoNormal align=center style='mso-margin-top-alt:auto;mso-margin-bottom-alt:
  auto;text-align:center;mso-line-height-alt:5.95pt'><span style='color:black;
  mso-themecolor:text1'><?php echo escapeWordText($somay[$i]); ?></span></p>
  </td>
  <td width=158 style='width:1.65in;border-top:none;border-left:none;
  border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;
  mso-border-alt:solid windowtext .5pt;padding:0in 5.4pt 0in 5.4pt;height:20.2pt'>
  <p class=MsoNormal align=center style='mso-margin-top-alt:auto;mso-margin-bottom-alt:
  auto;text-align:center;mso-line-height-alt:5.95pt'><span style='color:black;
  mso-themecolor:text1'><?php echo escapeWordText($tinhtrang[$i]); ?></span></p>
  </td>
  <td width=97 style='width:72.65pt;border-top:none;border-left:none;
  border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;
  mso-border-alt:solid windowtext .5pt;padding:0in 5.4pt 0in 5.4pt;height:20.2pt'>
  <p class=MsoNormal align=center style='mso-margin-top-alt:auto;mso-margin-bottom-alt:
  auto;text-align:center;mso-line-height-alt:5.95pt'><span style='color:black;
  mso-themecolor:text1'><?php echo escapeWordText($yeucau[$i]); ?></span></p>
  </td>
  <td width=66 style='width:49.5pt;border-top:none;border-left:none;border-bottom:
  solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;mso-border-top-alt:
  solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:
  solid windowtext .5pt;padding:0in 5.4pt 0in 5.4pt;height:20.2pt'>
  <p class=MsoNormal align=center style='mso-margin-top-alt:auto;mso-margin-bottom-alt:
  auto;text-align:center;mso-line-height-alt:5.95pt'><span style='color:black;
  mso-themecolor:text1'><?php 
    $vitriText = $vitri[$i] ?? '';
    // Format "Tam Đảo X" to "TĐ 0X"
    if (preg_match('/Tam Đảo\s+(\d+)/i', $vitriText, $matches)) {
        $number = (int)$matches[1];
        $formattedNumber = str_pad($number, 2, '0', STR_PAD_LEFT);
        $vitriText = preg_replace('/Tam Đảo\s+\d+/i', 'TĐ ' . $formattedNumber, $vitriText);
    }
    echo escapeWordText($vitriText);
  ?></span></p>
  </td>
 </tr>
<?php
    }
    $i++;
}
?>
</table>
<p class=MsoNormal style='margin-bottom:12.0pt'><span lang=VI style='mso-fareast-font-family:
"Times New Roman";mso-ansi-language:VI'><br/>

<p><i><span lang=VI style='mso-ansi-language:VI;font-weight:italic'>Ghi chú: Cột "Nội dung yêu cầu" được ghi như sau: </span></i><br/>
<i><span lang=VI style='mso-ansi-language:VI;font-weight:italic'>BD: Yêu cầu bảo dưỡng thiết bị / SC: Yêu cầu sửa chữa thiết bị bị hỏng / KT: Yêu cầu kiểm tra sự hoạt</span></i><br/>
<i><span lang=VI style='mso-ansi-language:VI;font-weight:italic'>động của thiết bị mà không cần bảo dưỡng (VD như: KT để nghiệm thu TB mới, KT tình trạng của thiết bị</span></i><br/>
<i><span lang=VI style='mso-ansi-language:VI;font-weight:italic'>đã được BD trước đây nhưng chưa thả đo trong giếng khoan, v.v.).</span></i></p>

<p><span lang=VI style='mso-ansi-language:VI'>4. Các yêu cầu khác (nếu có):...</span><span lang=VI style='mso-ansi-language:VI;font-weight:bold'><?php echo escapeWordText($ycthemkh); ?><o:p></o:p></span></p>
<p><span lang=VI style='mso-ansi-language:VI'>5. Phục vụ sản xuất cho Lô/ Dịch vụ ngoài:...... </span><span lang=VI style='mso-ansi-language:VI;font-weight:bold'><?php echo escapeWordText($lo); ?>......<o:p></o:p></span></p>
<p><span lang=VI style='mso-ansi-language:VI'>Tên mỏ :.....</span><span lang=VI style='mso-ansi-language:VI;font-weight:bold'><?php echo escapeWordText($mo); ?>.....<o:p></o:p></span><span lang=VI style='mso-ansi-language:VI'>Tên giếng:......</span><span lang=VI style='mso-ansi-language:VI;font-weight:bold'><?php echo escapeWordText($gieng); ?>.....<o:p></o:p></span></p>

<p><span lang=VI style='mso-ansi-language:VI'>6. Xem xét của lãnh đạo Xưởng (nếu có):.....</span><span lang=VI style='mso-ansi-language:VI;font-weight:bold'><?php echo escapeWordText($xemxetxuong); ?> <o:p></o:p></span></p>

<p class=MsoNormal style='margin-bottom:12.0pt'><span lang=VI style='mso-fareast-font-family:
"Times New Roman";mso-ansi-language:VI'><br/>

<p><span lang=VI style='mso-ansi-language:VI'>Lãnh đạo Xưởng / Trưởng nhóm <i>(ký ghi rõ họ tên) </i><o:p></o:p></span></p>
<table id='hrdftrtbl' border='1' cellspacing='0' cellpadding='0'>
        <tr>
          <td>
            <div style='mso-element:footer' id="f1">
                <p class="MsoFooter">
                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td  class="footer">
                            <span lang=VI style='mso-ansi-language:VI'>BM.25.02<br/>
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
