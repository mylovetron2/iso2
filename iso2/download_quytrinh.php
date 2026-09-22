<?php
declare(strict_types=1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/models/QuyTrinh.php';

requireAuth();

$mode = isset($_GET['view']) ? 'inline' : 'attachment';
$id = (int)($_GET['id'] ?? 0);
$item = $id > 0 ? (new QuyTrinh())->find($id) : false;
$storageDir = __DIR__ . '/storage/quy-trinh';
$filePath = $item && !empty($item['ten_luu_tru'])
    ? $storageDir . DIRECTORY_SEPARATOR . basename((string)$item['ten_luu_tru'])
    : '';

if (!$item || $filePath === '' || !is_file($filePath)) {
    http_response_code(404);
    exit('Không tìm thấy tài liệu quy trình.');
}

$mime = (string)($item['mime_type'] ?? 'application/octet-stream');
$extension = strtolower(pathinfo((string)$item['ten_luu_tru'], PATHINFO_EXTENSION)) ?: 'pdf';
$baseName = trim((string)($item['ten_hien_thi'] ?? ('quy-trinh-' . (string)$item['so_qt'])));
$baseName = preg_replace('/\.(pdf|docx?|PDF|DOCX?)$/i', '', $baseName) ?: ('quy-trinh-' . (string)$item['so_qt']);
$baseName = preg_replace('/[\r\n"\\\/]/', '', $baseName) ?: ('quy-trinh-' . (string)$item['so_qt']);
$asciiName = preg_replace('/[^A-Za-z0-9._ -]/', '', $baseName) ?: ('quy-trinh-' . (string)$item['so_qt']);
$fileName = $baseName . '.' . $extension;
$asciiFileName = $asciiName . '.' . $extension;

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($filePath));
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: ' . $mode . '; filename="' . $asciiFileName . '"; filename*=UTF-8\'\'' . rawurlencode($fileName));
readfile($filePath);
exit;
