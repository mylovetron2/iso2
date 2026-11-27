<?php
declare(strict_types=1);

/**
 * Hai nhóm của Đội ĐVL Tổng hợp (lưu trong bophan với mã CNM / RDNGA).
 */
function getDvlTongHopGroups(): array
{
    return [
        'CNM' => 'Đội ĐVL Tổng hợp-CNM',
        'RDNGA' => 'Đội ĐVL Tổng hợp-Nga',
    ];
}

/**
 * Bổ sung 2 nhóm của Đội ĐVL Tổng hợp vào danh sách đơn vị (nếu chưa có).
 */
function appendDvlTongHopGroups(array $donViList): array
{
    $existing = array_map('strtoupper', array_column($donViList, 'madv'));
    foreach (getDvlTongHopGroups() as $madv => $tendv) {
        if (!in_array($madv, $existing, true)) {
            $donViList[] = ['madv' => $madv, 'tendv' => $tendv];
        }
    }
    return $donViList;
}

/**
 * Ẩn các đơn vị không dùng làm bộ phận trong biểu mẫu thanh lý vật tư.
 */
function filterHiddenVatTuThanhLyDepartments(array $donViList): array
{
    $hiddenNames = [
        'baker hughes',
        'giàn rp1 - xnkt',
        'xí nghiệp địa vật lý giếng khoan',
    ];

    return array_values(array_filter($donViList, static function (array $donVi) use ($hiddenNames): bool {
        $name = mb_strtolower(trim((string)($donVi['tendv'] ?? '')), 'UTF-8');
        return !in_array($name, $hiddenNames, true);
    }));
}
