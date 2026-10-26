<?php
declare(strict_types=1);

require_once __DIR__ . '/bao_duong_activity.php';

function markDoiExecutionQuarterComplete(PDO $db, int $dossierId, string $performedOn): void
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $performedOn);
    if ($date === false || $date->format('Y-m-d') !== $performedOn || $dossierId <= 0) {
        throw new InvalidArgumentException('Ngày thực hiện hoặc ID hồ sơ đội không hợp lệ.');
    }

    $quarter = intdiv((int)$date->format('n') - 1, 3) + 1;
    $assignments = ["qui_{$quarter}_hoantat = 1"];
    for ($otherQuarter = 1; $otherQuarter <= 4; $otherQuarter++) {
        if ($otherQuarter !== $quarter) {
            $assignments[] = "qui_{$otherQuarter}_hoantat = 0";
        }
    }
    $stmt = $db->prepare("UPDATE ke_hoach_bao_duong_dinh_ky_iso
        SET " . implode(', ', $assignments) . "
        WHERE hososcbd_doi_id = :dossier_id
          AND (nguon_hoso IS NULL OR nguon_hoso = 'hososcbd_doi_iso')
          AND nam = :year");
    $stmt->execute([':dossier_id' => $dossierId, ':year' => (int)$date->format('Y')]);
    if ($stmt->rowCount() > 0) {
        $completion = [];
        for ($q = 1; $q <= 4; $q++) {
            $completion["qui_{$q}_hoantat"] = $q === $quarter ? 1 : 0;
        }
        logBaoDuongActivity($db, 'UPDATE', null, null, array_merge([
            'operation' => 'execution_quarter', 'hososcbd_doi_id' => $dossierId,
            'nam' => (int)$date->format('Y'), 'ngayth' => $performedOn,
        ], $completion));
    }
}
