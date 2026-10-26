<?php
declare(strict_types=1);

require_once __DIR__ . '/ActivityLogger.php';

function logBaoDuongActivity(
    PDO $db,
    string $action,
    ?int $planId,
    ?array $oldData,
    ?array $newData
): void {
    $logger = new ActivityLogger($db);
    $logger->log('ke_hoach_bao_duong_dinh_ky_iso', $action, $planId, $oldData, $newData);
}
