<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn() || !hasPermission('kpi_baoduong.view')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Khong co quyen truy cap']);
    exit;
}

try {
    $db = getDBConnection();

    $q = trim((string)($_GET['q'] ?? ''));
    if ($q === '') {
        echo json_encode(['success' => true, 'items' => []]);
        exit;
    }

    $sql = "SELECT stt, mavt, somay, tenvt, model, madv
            FROM thietbi_iso
            WHERE mavt LIKE :q OR somay LIKE :q OR tenvt LIKE :q
            ORDER BY tenvt ASC
            LIMIT 30";
    $stmt = $db->prepare($sql);
    $stmt->execute([':q' => '%' . $q . '%']);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $items = array_map(static function (array $row): array {
        return [
            'stt' => (int)$row['stt'],
            'mavt' => (string)$row['mavt'],
            'somay' => (string)$row['somay'],
            'tenvt' => (string)$row['tenvt'],
            'model' => (string)($row['model'] ?? ''),
            'madv' => (string)($row['madv'] ?? ''),
            'label' => trim(($row['tenvt'] ?? '') . ' — ' . ($row['mavt'] ?? '') . ' / SN:' . ($row['somay'] ?? '') . (($row['model'] ?? '') !== '' ? ' / ' . $row['model'] : '')),
        ];
    }, $rows);

    echo json_encode(['success' => true, 'items' => $items]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Loi truy van: ' . $e->getMessage()]);
}
