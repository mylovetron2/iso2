<?php
declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

const VATTU_MISSING_COLUMNS = [
    'ten_tienganh' => ['C', 'Tên tiếng Anh'],
    'dactinhkt_tiengnga' => ['D', 'Đặc tính kỹ thuật tiếng Nga'],
    'dactinhkt_tiengviet' => ['E', 'Đặc tính kỹ thuật tiếng Việt'],
    'dvt_tiengnga' => ['F', 'ĐVT tiếng Nga'],
    'dvt_tiengviet' => ['G', 'ĐVT tiếng Việt'],
    'phanloai_id' => ['H', 'Phân loại'],
];

function vattuMissingTrim(string $value): string
{
    $result = preg_replace('/\A[\p{Z}\s]+|[\p{Z}\s]+\z/u', '', $value);
    if ($result === null) {
        throw new InvalidArgumentException('Dữ liệu Excel không phải UTF-8 hợp lệ.');
    }
    return $result;
}

function vattuMissingCell(Worksheet $sheet, string $address): ?string
{
    $cell = $sheet->getCell($address);
    if ($cell->getDataType() === DataType::TYPE_FORMULA || $cell->getDataType() === DataType::TYPE_ERROR) {
        throw new InvalidArgumentException("Ô {$address} chứa công thức hoặc lỗi Excel.");
    }
    $value = $cell->getFormattedValue();
    if (!is_scalar($value) && $value !== null) {
        throw new InvalidArgumentException("Ô {$address} không chứa dữ liệu văn bản hợp lệ.");
    }
    $text = vattuMissingTrim((string)$value);
    return $text === '' ? null : $text;
}

function vattuMissingReadRows(Worksheet $sheet): array
{
    $headers = ['A' => 'Mã vật tư'];
    foreach (VATTU_MISSING_COLUMNS as [$column, $label]) {
        $headers[$column] = $label;
    }
    foreach ($headers as $column => $label) {
        $header = vattuMissingCell($sheet, $column . '1');
        if ($header === null || mb_strtolower($header, 'UTF-8') !== mb_strtolower($label, 'UTF-8')) {
            throw new InvalidArgumentException("Tiêu đề {$column}1 phải là “{$label}”.");
        }
    }

    $rows = [];
    for ($number = 2; $number <= $sheet->getHighestDataRow(); $number++) {
        try {
            $code = vattuMissingCell($sheet, 'A' . $number);
            $values = [];
            foreach (VATTU_MISSING_COLUMNS as $field => [$column]) {
                $values[$field] = vattuMissingCell($sheet, $column . $number);
            }
            if ($code === null && count(array_filter($values, static fn($v) => $v !== null)) === 0) {
                continue;
            }
            $rows[] = ['row' => $number, 'code' => $code, 'values' => $values, 'error' => null];
        } catch (InvalidArgumentException $error) {
            $rows[] = ['row' => $number, 'code' => null, 'values' => [], 'error' => $error->getMessage()];
        }
    }
    return $rows;
}

function vattuMissingPreview(PDO $db, array $rows, bool $lock = false): array
{
    $suffix = $lock ? ' FOR UPDATE' : '';
    $categories = $db->query(
        'SELECT id, ma_phanloai, ten_phanloai FROM phanloai_vattu_thanh_ly_iso' . $suffix
    )->fetchAll(PDO::FETCH_ASSOC);
    $categoryMap = [];
    foreach ($categories as $category) {
        foreach (['ma_phanloai', 'ten_phanloai'] as $field) {
            $key = mb_strtolower(vattuMissingTrim((string)$category[$field]), 'UTF-8');
            if ($key !== '') {
                $categoryMap[$key][(string)$category['id']] = true;
            }
        }
    }

    $counts = [];
    foreach ($rows as $row) {
        if ($row['code'] !== null) {
            $key = mb_strtolower($row['code'], 'UTF-8');
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }
    }
    $find = $db->prepare(
        'SELECT stt, mavattu, vi_tri_sap_xep, ten_tienganh, dactinhkt_tiengnga, dactinhkt_tiengviet, dvt_tiengnga, '
        . 'dvt_tiengviet, phanloai_id FROM vattu_thanh_ly_iso WHERE mavattu = ?' . $suffix
    );
    $result = [];
    foreach ($rows as $row) {
        $entry = [
            'row' => $row['row'], 'code' => $row['code'], 'status' => '',
            'message' => $row['error'], 'old' => [], 'changes' => [],
        ];
        if ($row['error'] !== null) {
            $entry['status'] = 'invalid';
        } elseif ($row['code'] === null) {
            $entry['status'] = 'missing_code';
        } elseif ($counts[mb_strtolower($row['code'], 'UTF-8')] > 1) {
            $entry['status'] = 'duplicate_file';
        } else {
            $find->execute([$row['code']]);
            $matches = $find->fetchAll(PDO::FETCH_ASSOC);
            if (count($matches) !== 1) {
                $entry['status'] = count($matches) === 0 ? 'not_found' : 'duplicate_db';
            } else {
                $existing = $matches[0];
                $entry['stt'] = $existing['stt'];
                $entry['old']['vi_tri_sap_xep'] = $existing['vi_tri_sap_xep'];
                foreach (VATTU_MISSING_COLUMNS as $field => $definition) {
                    $old = $existing[$field];
                    $entry['old'][$field] = $old;
                    $new = $row['values'][$field];
                    if ($new === null) {
                        continue;
                    }
                    if ($field === 'phanloai_id') {
                        $key = mb_strtolower($new, 'UTF-8');
                        $ids = array_keys($categoryMap[$key] ?? []);
                        if (count($ids) !== 1) {
                            $entry['message'] = 'Phân loại không tồn tại hoặc không duy nhất: ' . $new;
                            continue;
                        }
                        if ($old !== null && (string)$old !== '' && (string)$old === (string)$ids[0]) {
                            continue;
                        }
                        if ($existing['vi_tri_sap_xep'] === null
                            || !is_numeric($existing['vi_tri_sap_xep'])
                            || (float)$existing['vi_tri_sap_xep'] < 138) {
                            $entry['message'] = 'Không cập nhật phân loại: vị trí sắp xếp phải từ 138 trở lên.';
                            continue;
                        }
                        $entry['changes'][$field] = ['value' => (int)$ids[0], 'display' => $new];
                    } elseif ($old === null || vattuMissingTrim((string)$old) === '') {
                        $entry['changes'][$field] = ['value' => $new, 'display' => $new];
                    }
                }
                $entry['status'] = $entry['changes'] ? 'update' : 'no_change';
            }
        }
        $result[] = $entry;
    }
    return $result;
}

function vattuMissingFingerprint(array $preview): string
{
    return hash('sha256', json_encode($preview, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
}

function vattuMissingApply(PDO $db, array $preview): int
{
    $updated = 0;
    foreach ($preview as $entry) {
        if ($entry['status'] !== 'update') {
            continue;
        }
        $sets = [];
        $guards = [];
        $params = [':stt' => $entry['stt'], ':code' => $entry['code']];
        foreach ($entry['changes'] as $field => $change) {
            if (!isset(VATTU_MISSING_COLUMNS[$field])) {
                throw new LogicException('Trường cập nhật không hợp lệ.');
            }
            $sets[] = "`{$field}` = :{$field}";
            if ($field === 'phanloai_id') {
                $guards[] = 'vi_tri_sap_xep >= 138';
            }
            if ($entry['old'][$field] === null) {
                $guards[] = "`{$field}` IS NULL";
            } else {
                $guards[] = "`{$field}` = :old_{$field}";
                $params[":old_{$field}"] = $entry['old'][$field];
            }
            $params[":{$field}"] = $change['value'];
        }
        $statement = $db->prepare(
            'UPDATE vattu_thanh_ly_iso SET ' . implode(', ', $sets)
            . ' WHERE stt = :stt AND mavattu = :code AND ' . implode(' AND ', $guards)
        );
        $statement->execute($params);
        if ($statement->rowCount() !== 1) {
            throw new RuntimeException('Dữ liệu đã thay đổi khi cập nhật; cần xem trước lại.');
        }
        $updated++;
    }
    return $updated;
}
