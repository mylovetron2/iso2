<?php
// Test Word export with minimal content

header("Content-Type: application/msword; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"test.doc\"");
header("Pragma: no-cache");
header("Expires: 0");

// Output UTF-8 BOM
echo "\xEF\xBB\xBF";
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
xmlns:w="urn:schemas-microsoft-com:office:word"
xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Test Word Document</title>
</head>
<body>
<h1>Test Word Export</h1>
<p>Đây là văn bản tiếng Việt có dấu.</p>
<table border="1">
    <tr>
        <th>STT</th>
        <th>Tên</th>
        <th>Mô tả</th>
    </tr>
    <tr>
        <td>1</td>
        <td>Thiết bị A</td>
        <td>Mô tả thiết bị</td>
    </tr>
    <tr>
        <td>2</td>
        <td>Thiết bị B</td>
        <td>Bảo dưỡng định kỳ</td>
    </tr>
</table>
</body>
</html>
