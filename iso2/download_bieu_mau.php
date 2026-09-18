<?php
declare(strict_types=1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/models/BieuMau.php';

requireAuth();
if (!hasRole(ROLE_ADMIN) && !hasPermission('bieumau.view')) {
    http_response_code(403);
    exit('Bạn không có quyền tải biểu mẫu.');
}
$id = (int)($_GET['id'] ?? 0);
$item = $id > 0 ? (new BieuMau())->find($id) : false;
$storageDir = __DIR__ . '/storage/bieu-mau';
$filePath = $item ? $storageDir . DIRECTORY_SEPARATOR . basename((string)$item['ten_luu_tru']) : '';

if (!$item || !is_file($filePath)) {
    http_response_code(404);
    exit('Không tìm thấy biểu mẫu.');
}

header('Content-Type: ' . $item['mime_type']);
header('Content-Length: ' . filesize($filePath));
$downloadName = trim((string)$item['ten_hien_thi']);
$downloadName = preg_replace('/\.(docx?|DOCX?)$/i', '', $downloadName) ?: 'bieu-mau';
$downloadName = preg_replace('/[\r\n"\\\/]/', '', $downloadName) ?: 'bieu-mau';
$asciiName = preg_replace('/[^A-Za-z0-9._ -]/', '', $downloadName) ?: 'bieu-mau';
$encodedName = rawurlencode($downloadName . '.docx');
header('Content-Disposition: attachment; filename="' . $asciiName . '.docx"; filename*=UTF-8\'\'' . $encodedName);
header('X-Content-Type-Options: nosniff');
readfile($filePath);
exit;
