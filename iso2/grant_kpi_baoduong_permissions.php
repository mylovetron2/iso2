<?php
declare(strict_types=1);

/**
 * Script cap quyen KPI bao duong thiet bi cho cac role dang ton tai.
 *
 * Run once:
 * php grant_kpi_baoduong_permissions.php
 */

require_once __DIR__ . '/config/database.php';

echo "=== Bat dau cap quyen KPI bao duong thiet bi ===\n\n";

try {
    $db = getDBConnection();

    $stmt = $db->query("SELECT id, name, permissions FROM roles");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $newPerms = ['kpi_baoduong.view', 'kpi_baoduong.import', 'kpi_baoduong.edit'];

    foreach ($roles as $role) {
        $roleName = (string)($role['name'] ?? '');
        $currentPermsRaw = (string)($role['permissions'] ?? '');
        $currentPerms = array_values(array_filter(array_map('trim', explode(',', $currentPermsRaw))));

        $hasLegacyView = in_array('kehoachbaoduong.view', $currentPerms, true);
        $hasLegacyCreate = in_array('kehoachbaoduong.create', $currentPerms, true);
        $hasLegacyEdit = in_array('kehoachbaoduong.edit', $currentPerms, true);

        $granted = [];

        if ($hasLegacyView && !in_array('kpi_baoduong.view', $currentPerms, true)) {
            $currentPerms[] = 'kpi_baoduong.view';
            $granted[] = 'kpi_baoduong.view';
        }

        if ($hasLegacyCreate && !in_array('kpi_baoduong.import', $currentPerms, true)) {
            $currentPerms[] = 'kpi_baoduong.import';
            $granted[] = 'kpi_baoduong.import';
        }

        if ($hasLegacyEdit && !in_array('kpi_baoduong.edit', $currentPerms, true)) {
            $currentPerms[] = 'kpi_baoduong.edit';
            $granted[] = 'kpi_baoduong.edit';
        }

        if (empty($granted)) {
            echo "- Role {$roleName}: khong thay doi\n";
            continue;
        }

        $updatedPerms = implode(',', array_values(array_unique($currentPerms)));
        $updateStmt = $db->prepare("UPDATE roles SET permissions = :permissions WHERE id = :id");
        $updateStmt->execute([
            ':permissions' => $updatedPerms,
            ':id' => (int)$role['id'],
        ]);

        echo "+ Role {$roleName}: them " . implode(', ', $granted) . "\n";
    }

    echo "\nHoan tat cap quyen KPI bao duong thiet bi.\n";
} catch (Throwable $e) {
    echo "Loi: " . $e->getMessage() . "\n";
    exit(1);
}
