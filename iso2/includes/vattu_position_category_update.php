<?php
declare(strict_types=1);

function vattuPositionCategoryName(string $value): string
{
    $trimmed = preg_replace('/\A[\p{Z}\s]+|[\p{Z}\s]+\z/u', '', $value);
    if ($trimmed === null) {
        throw new InvalidArgumentException('Tên phân loại không hợp lệ.');
    }

    return mb_strtolower($trimmed, 'UTF-8');
}

function vattuPositionCategoryPreview(PDO $db, bool $lockRows = false): array
{
    $lock = $lockRows && $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
    $categoryQuery = $db->query(
        'SELECT id, ten_phanloai FROM phanloai_vattu_thanh_ly_iso' . $lock
    );
    $categoryIds = [];
    foreach ($categoryQuery->fetchAll(PDO::FETCH_ASSOC) as $category) {
        if (vattuPositionCategoryName((string)$category['ten_phanloai'])
            === vattuPositionCategoryName('Công cụ dụng cụ')) {
            $categoryIds[(string)$category['id']] = (int)$category['id'];
        }
    }
    if (count($categoryIds) !== 1) {
        throw new RuntimeException('Không tìm thấy duy nhất một phân loại “Công cụ dụng cụ”.');
    }
    $targetCategoryId = reset($categoryIds);

    $items = $db->query(
        'SELECT stt, mavattu, vi_tri_sap_xep, phanloai_id FROM vattu_thanh_ly_iso '
        . 'WHERE vi_tri_sap_xep >= 138 ORDER BY vi_tri_sap_xep ASC, stt ASC' . $lock
    )->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as &$item) {
        $item['status'] = (string)$item['phanloai_id'] === (string)$targetCategoryId
            ? 'unchanged'
            : 'update';
    }
    unset($item);

    return ['category_id' => $targetCategoryId, 'items' => $items];
}

function vattuPositionCategoryFingerprint(array $preview): string
{
    return hash('sha256', json_encode(
        $preview,
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    ));
}

function vattuPositionCategoryApply(PDO $db, array $preview): int
{
    $updated = 0;
    foreach ($preview['items'] as $item) {
        if ($item['status'] !== 'update') {
            continue;
        }
        $oldCategory = $item['phanloai_id'];
        $oldCategoryGuard = $oldCategory === null
            ? 'phanloai_id IS NULL'
            : 'phanloai_id = :old_category';
        $statement = $db->prepare(
            'UPDATE vattu_thanh_ly_iso SET phanloai_id = :category_id '
            . 'WHERE stt = :stt AND vi_tri_sap_xep = :position AND vi_tri_sap_xep >= 138 '
            . 'AND ' . $oldCategoryGuard
        );
        $params = [
            ':category_id' => $preview['category_id'],
            ':stt' => $item['stt'],
            ':position' => $item['vi_tri_sap_xep'],
        ];
        if ($oldCategory !== null) {
            $params[':old_category'] = $oldCategory;
        }
        $statement->execute($params);
        if ($statement->rowCount() !== 1) {
            throw new RuntimeException('Dữ liệu đã thay đổi khi cập nhật; cần xem trước lại.');
        }
        $updated++;
    }

    return $updated;
}
