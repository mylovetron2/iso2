<?php
declare(strict_types=1);

function buildBaoDuongReport(array $plans, int $lastQuarter): array
{
    if ($lastQuarter < 1 || $lastQuarter > 4) {
        throw new InvalidArgumentException('Quý báo cáo phải nằm trong khoảng 1 đến 4.');
    }

    $summary = [
        'total_plans' => count($plans),
        'truoc_han' => 0,
        'dung_han' => 0,
        'sau_han' => 0,
        'chua_thuc_hien' => 0,
    ];
    $items = [];

    foreach ($plans as $plan) {
        $plannedQuarters = [];
        for ($quarter = 1; $quarter <= $lastQuarter; $quarter++) {
            $field = 'qui_' . $quarter;
            if (!empty($plan[$field]) && trim((string)$plan[$field]) !== '') {
                $plannedQuarters[] = $quarter;
            }
        }
        if ($plannedQuarters === []) {
            throw new InvalidArgumentException('Thiết bị trong báo cáo không có kế hoạch trong kỳ.');
        }

        $referenceQuarter = null;
        $completedQuarter = null;
        $shortestDistance = PHP_INT_MAX;
        foreach ($plannedQuarters as $plannedQuarter) {
            for ($quarter = 1; $quarter <= 4; $quarter++) {
                if (empty($plan['qui_' . $quarter . '_hoantat'])) {
                    continue;
                }
                $distance = abs($quarter - $plannedQuarter);
                if ($distance < $shortestDistance) {
                    $referenceQuarter = $plannedQuarter;
                    $completedQuarter = $quarter;
                    $shortestDistance = $distance;
                }
            }
        }

        if ($completedQuarter === null) {
            $status = 'chua_thuc_hien';
        } elseif ($completedQuarter < $referenceQuarter) {
            $status = 'truoc_han';
        } elseif ($completedQuarter > $referenceQuarter) {
            $status = 'sau_han';
        } else {
            $status = 'dung_han';
        }

        $summary[$status]++;
        $plan['report_status'] = $status;
        $plan['report_planned_quarters'] = $plannedQuarters;
        $plan['report_reference_quarter'] = $referenceQuarter;
        $plan['report_completed_quarter'] = $completedQuarter;
        $items[] = $plan;
    }

    $completed = $summary['truoc_han'] + $summary['dung_han'] + $summary['sau_han'];
    $summary['tyle_hoan_thanh'] = $summary['total_plans'] > 0
        ? round($completed * 100 / $summary['total_plans'], 2)
        : 0;

    return ['summary' => $summary, 'items' => $items];
}
