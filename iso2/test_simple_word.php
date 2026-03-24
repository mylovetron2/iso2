<?php
// Simple Word export test
header("Content-Type: application/msword; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"test-simple.doc\""); 
header("Pragma: no-cache");
header("Expires: 0");

echo "\xEF\xBB\xBF";
?>
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<html xmlns:v="urn:schemas-microsoft-com:vml"
xmlns:o="urn:schemas-microsoft-com:office:office"
xmlns:w="urn:schemas-microsoft-com:office:word"
xmlns:m="http://schemas.microsoft.com/office/2004/12/omml"
xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv=Content-Type content="text/html; charset=UTF-8">
<meta name=ProgId content=Word.Document>
<style>
body { font-family: 'Times New Roman'; }
table { border-collapse: collapse; width: 100%; }
td { border: 1px solid black; padding: 5pt; }
</style>
</head>
<body>
<p align="center"><b>TEST DOCUMENT</b></p>
<p>This is a simple test document.</p>
<table>
<tr>
<td><b>STT</b></td>
<td><b>Name</b></td>
<td><b>Description</b></td>
</tr>
<tr>
<td>1</td>
<td>Test Item 1</td>
<td>Test Description 1</td>
</tr>
<tr>
<td>2</td>
<td>Test Item 2</td>
<td>Test Description 2</td>
</tr>
</table>
</body>
</html>
