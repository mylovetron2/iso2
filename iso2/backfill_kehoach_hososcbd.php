<?php
declare(strict_types=1);

/**
 * CLI entry point and shared runner for the authenticated web tool.
 * Match device by mavt + somay and ngayth in completed plan quarters, regardless of cv.
 */
function backfillHoSoQuarterRanges(array $plan): array
{
    $year = (int)$plan['nam'];
    if ($year < 1000 || $year > 9998) {
        throw new RuntimeException('Invalid plan year for plan ' . $plan['id']);
    }
    $ranges = [];
    for ($quarter = 1; $quarter <= 4; $quarter++) {
        if ((int)($plan["qui_{$quarter}_hoantat"] ?? 0) !== 1) {
            continue;
        }
        $start = sprintf('%04d-%02d-01', $year, ($quarter - 1) * 3 + 1);
        $end = $quarter === 4
            ? sprintf('%04d-01-01', $year + 1)
            : sprintf('%04d-%02d-01', $year, $quarter * 3 + 1);
        $ranges[] = [$start, $end];
    }
    return $ranges;
}

function backfillHoSoCandidateQuery(array $ranges, bool $apply): string
{
    if ($ranges === []) {
        throw new InvalidArgumentException('At least one completed quarter is required');
    }
    $dates = array_fill(0, count($ranges), '(h.ngayth >= ? AND h.ngayth < ?)');
    return "SELECT h.stt, h.hoso, h.phieu, h.ngayth
        FROM hososcbd_iso h
        WHERE h.mavt = ? AND h.somay = ?
          AND (" . implode(' OR ', $dates) . ")
        ORDER BY h.ngayth DESC, h.stt DESC
        LIMIT 1" . ($apply ? ' FOR UPDATE' : '');
}

function backfillHoSoMain(array $argv): int
{
    if (PHP_SAPI !== 'cli') {
        http_response_code(403);
        echo "CLI only.\n";
        return 1;
    }
    $apply = false;
    $year = null;
    foreach (array_slice($argv, 1) as $argument) {
        if ($argument === '--help') {
            echo "Usage: php backfill_kehoach_hososcbd.php [--year=2026] [--dry-run | --apply]\n";
            echo "Default: dry-run, all years. Output: one JSON record per plan and a summary.\n";
            echo "Only CNM/RDNGA, unlinked plans, matching mavt+somay and ngayth; no cv filter.\n";
            echo "Latest ngayth wins; same date: greatest stt. No fallback if already linked.\n";
            return 0;
        }
        if ($argument === '--apply' || $argument === '--dry-run') {
            if (in_array('--apply', $argv, true) && in_array('--dry-run', $argv, true)) {
                throw new InvalidArgumentException('Choose --apply OR --dry-run');
            }
            $apply = $argument === '--apply';
        } elseif (preg_match('/^--year=([1-9][0-9]{3})$/D', $argument, $matches) && (int)$matches[1] <= 9998) {
            $year = (int)$matches[1];
        } else {
            throw new InvalidArgumentException('Unknown or invalid argument: ' . $argument);
        }
    }

    require_once __DIR__ . '/config/database.php';
    $summary = backfillHoSoRun(getDBConnection(), $apply, $year, static function (array $record): void {
        echo json_encode($record, JSON_THROW_ON_ERROR) . PHP_EOL;
    });
    echo json_encode($summary, JSON_THROW_ON_ERROR) . PHP_EOL;
    return 0;
}

function backfillHoSoRun(PDO $db, bool $apply, ?int $year, callable $report): array
{
    if ($year !== null && ($year < 1000 || $year > 9998)) {
        throw new InvalidArgumentException('Invalid plan year');
    }
    foreach ([
        'ke_hoach_bao_duong_dinh_ky_iso' => [
            'id', 'thietbi_id', 'nam', 'donvi_lam_chinh',             'hososcbd_id', 'hososcbd_doi_id', 'nguon_hoso',
            'qui_1_hoantat', 'qui_2_hoantat', 'qui_3_hoantat', 'qui_4_hoantat',
        ],
        'hososcbd_iso' => ['stt', 'hoso', 'phieu', 'mavt', 'somay', 'ngayth'],
        'thietbi_iso' => ['stt', 'mavt', 'somay'],
    ] as $table => $required) {
        $columns = $db->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
        $missing = array_diff($required, $columns);
        if ($missing !== []) {
            throw new RuntimeException("Missing columns in $table: " . implode(', ', $missing));
        }
        $engine = $db->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
        $engine->execute([$table]);
        if (strtoupper((string)$engine->fetchColumn()) !== 'INNODB') {
            throw new RuntimeException("$table must use InnoDB");
        }
    }

    $summary = ['mode' => $apply ? 'apply' : 'dry-run', 'eligible' => 0, 'matched' => 0, 'updated' => 0, 'skipped' => 0];
    $reserved = [];
    $db->beginTransaction();
    try {
        $sql = "SELECT k.* FROM ke_hoach_bao_duong_dinh_ky_iso k
            WHERE UPPER(TRIM(k.donvi_lam_chinh)) IN ('CNM', 'RDNGA')
              AND k.hososcbd_id IS NULL AND k.hososcbd_doi_id IS NULL
              AND (k.nguon_hoso IS NULL OR k.nguon_hoso = 'hososcbd_iso')
              AND (k.qui_1_hoantat = 1 OR k.qui_2_hoantat = 1 OR k.qui_3_hoantat = 1 OR k.qui_4_hoantat = 1)";
        $params = [];
        if ($year !== null) {
            $sql .= ' AND k.nam = ?';
            $params[] = $year;
        }
        $sql .= ' ORDER BY k.id' . ($apply ? ' FOR UPDATE' : '');
        $statement = $db->prepare($sql);
        $statement->execute($params);
        $plans = $statement->fetchAll(PDO::FETCH_ASSOC);
        $deviceQuery = $db->prepare('SELECT mavt, somay FROM thietbi_iso WHERE stt = ?');
        $identityCount = $db->prepare('SELECT COUNT(*) FROM thietbi_iso WHERE mavt = ? AND somay = ?');
        $ownerQuery = $db->prepare('SELECT id FROM ke_hoach_bao_duong_dinh_ky_iso WHERE hososcbd_id = ?');
        $update = $db->prepare("UPDATE ke_hoach_bao_duong_dinh_ky_iso SET hososcbd_id = ?, nguon_hoso = 'hososcbd_iso'
            WHERE id = ? AND hososcbd_id IS NULL AND hososcbd_doi_id IS NULL
              AND (nguon_hoso IS NULL OR nguon_hoso = 'hososcbd_iso')
              AND UPPER(TRIM(donvi_lam_chinh)) IN ('CNM', 'RDNGA')");

        foreach ($plans as $plan) {
            $summary['eligible']++;
            $record = ['plan_id' => $plan['id'], 'year' => $plan['nam']];
            $deviceQuery->execute([$plan['thietbi_id']]);
            $device = $deviceQuery->fetch(PDO::FETCH_ASSOC);
            $record['somay'] = $device === false ? null : $device['somay'];
            $candidate = false;
            if (!$device || trim((string)$device['mavt']) === '' || trim((string)$device['somay']) === '') {
                $record['status'] = 'missing_device_identity';
            } else {
                $identity = [$device['mavt'], $device['somay']];
                $identityCount->execute($identity);
                if ((int)$identityCount->fetchColumn() !== 1) {
                    $record['status'] = 'ambiguous_device_identity';
                } else {
                    $ranges = backfillHoSoQuarterRanges($plan);
                    $candidates = $db->prepare(backfillHoSoCandidateQuery($ranges, $apply));
                    $candidateParams = $identity;
                    foreach ($ranges as $range) {
                        array_push($candidateParams, ...$range);
                    }
                    $candidates->execute($candidateParams);
                    $candidate = $candidates->fetch(PDO::FETCH_ASSOC);
                    $record['status'] = 'no_matching_dossier';
                }
            }
            if ($candidate) {
                $id = (string)$candidate['stt'];
                $record['dossier_id'] = $id;
                $record['phieu'] = $candidate['phieu'];
                $record['start_date'] = $candidate['ngayth'];
                $ownerQuery->execute([$id]);
                $owner = $ownerQuery->fetchColumn();
                if ($owner !== false || isset($reserved[$id])) {
                    $record['status'] = 'dossier_already_linked';
                    $record['owner_plan_id'] = $owner !== false ? $owner : $reserved[$id];
                } else {
                    $reserved[$id] = $plan['id'];
                    $summary['matched']++;
                    $record['status'] = $apply ? 'pending_commit' : 'would_update';
                    if ($apply) {
                        $update->execute([$id, $plan['id']]);
                        if ($update->rowCount() !== 1) {
                            throw new RuntimeException('Plan changed during update: ' . $plan['id']);
                        }
                        $summary['updated']++;
                    }
                }
            }
            if (!in_array($record['status'], ['would_update', 'pending_commit'], true)) {
                $summary['skipped']++;
            }
            $report($record);
        }
        if ($apply) {
            $db->commit();
        } else {
            $db->rollBack();
        }
    } catch (Throwable $error) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $error;
    }
    $summary['result'] = $apply ? 'committed' : 'no_changes';
    return $summary;
}

if (realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    if (PHP_SAPI !== 'cli') {
        http_response_code(403);
        exit('CLI only. Open backfill_kehoach_hososcbd_web.php after signing in as administrator.');
    }
    try {
        exit(backfillHoSoMain($argv));
    } catch (Throwable $error) {
        fwrite(STDERR, 'ERROR: ' . $error->getMessage() . '. No successful commit confirmed.' . PHP_EOL);
        exit(1);
    }
}
