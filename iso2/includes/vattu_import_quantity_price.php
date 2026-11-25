<?php
declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

const VATTU_QUANTITY_PRICE_FIELDS = [
    'soluong_conlai' => 'Số lượng',
    'dongia_usd' => 'Đơn giá USD',
];

function vattuQuantityPriceTrim(string $value): string
{
    $trimmed = preg_replace('/\A[\p{Z}\s]+|[\p{Z}\s]+\z/u', '', $value);
    if ($trimmed === null) {
        throw new InvalidArgumentException('Dữ liệu Excel không phải UTF-8 hợp lệ.');
    }
    return $trimmed;
}

function vattuQuantityPriceTextCell(Worksheet $sheet, string $address): array
{
    $cell = $sheet->getCell($address);
    if (in_array($cell->getDataType(), [DataType::TYPE_FORMULA, DataType::TYPE_ERROR], true)) {
        return ['valid' => false, 'value' => null];
    }
    $value = $cell->getFormattedValue();
    if ($value !== null && !is_scalar($value)) {
        return ['valid' => false, 'value' => null];
    }
    $text = vattuQuantityPriceTrim((string)$value);
    return ['valid' => true, 'value' => $text === '' ? null : $text];
}

function vattuQuantityPriceNumberCell(Worksheet $sheet, string $address): array
{
    $cell = $sheet->getCell($address);
    if (in_array($cell->getDataType(), [DataType::TYPE_FORMULA, DataType::TYPE_ERROR], true)) {
        return ['valid' => false, 'value' => null];
    }
    $value = $cell->getValue();
    if ($value === null || $value === '') {
        return ['valid' => true, 'value' => null];
    }
    if (is_int($value) || is_float($value)) {
        return is_finite((float)$value)
            ? ['valid' => true, 'value' => (float)$value]
            : ['valid' => false, 'value' => null];
    }
    if (!is_string($value)) {
        return ['valid' => false, 'value' => null];
    }

    $number = vattuQuantityPriceTrim($value);
    $number = preg_replace('/[\s\p{Z}]+/u', '', $number) ?? $number;
    if ($number === '') {
        return ['valid' => true, 'value' => null];
    }
    $comma = strrpos($number, ',');
    $dot = strrpos($number, '.');
    if ($comma !== false && $dot !== false) {
        if ($comma > $dot) {
            $number = str_replace('.', '', $number);
            $number = str_replace(',', '.', $number);
        } else {
            $number = str_replace(',', '', $number);
        }
    } elseif ($comma !== false) {
        $number = str_replace(',', '.', $number);
    }
    if (!is_numeric($number) || !is_finite((float)$number)) {
        return ['valid' => false, 'value' => null];
    }
    return ['valid' => true, 'value' => (float)$number];
}

function vattuQuantityPriceReadRows(Worksheet $sheet): array
{
    $headers = ['A1' => 'Mã vật tư', 'B1' => 'Số lượng', 'C1' => 'Đơn giá'];
    foreach ($headers as $address => $expected) {
        $actual = vattuQuantityPriceTextCell($sheet, $address);
        if (!$actual['valid'] || $actual['value'] === null
            || mb_stripos($actual['value'], $expected, 0, 'UTF-8') === false) {
            throw new InvalidArgumentException("Không đúng mẫu Excel: kiểm tra tiêu đề ô {$address}.");
        }
    }

    $rows = [];
    for ($number = 2; $number <= $sheet->getHighestDataRow(); $number++) {
        $code = vattuQuantityPriceTextCell($sheet, "A{$number}");
        $quantity = vattuQuantityPriceNumberCell($sheet, "B{$number}");
        $price = vattuQuantityPriceNumberCell($sheet, "C{$number}");
        if ($code['valid'] && $quantity['valid'] && $price['valid']
            && $code['value'] === null && $quantity['value'] === null && $price['value'] === null) {
            continue;
        }
        $rows[] = [
            'row' => $number,
            'code' => $code['value'],
            'valid' => $code['valid'] && $quantity['valid'] && $price['valid'],
            'values' => [
                'soluong_conlai' => $quantity['value'],
                'dongia_usd' => $price['value'],
            ],
        ];
    }
    return $rows;
}

function vattuQuantityPricePreview(PDO $db, array $rows, bool $lockRows = false): array
{
    $counts = [];
    foreach ($rows as $row) {
        if ($row['code'] !== null) {
            $key = mb_strtolower($row['code'], 'UTF-8');
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }
    }

    $lock = $lockRows && $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
    $find = $db->prepare(
        'SELECT stt, mavattu, soluong_conlai, dongia_usd FROM vattu_thanh_ly_iso WHERE mavattu = ?' . $lock
    );
    $preview = [];
    foreach ($rows as $row) {
        $entry = [
            'row' => $row['row'], 'code' => $row['code'] ?? '', 'status' => '',
            'values' => $row['values'], 'old' => [], 'changes' => [],
        ];
        if (!$row['valid']) {
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
                foreach (VATTU_QUANTITY_PRICE_FIELDS as $field => $label) {
                    $oldValue = $existing[$field];
                    $newValue = $row['values'][$field];
                    $entry['old'][$field] = $oldValue;
                    if ($newValue === null) {
                        continue;
                    }
                    if ($oldValue === null || $oldValue === ''
                        || abs((float)$oldValue - $newValue) > 0.000001) {
                        $entry['changes'][$field] = ['old' => $oldValue, 'new' => $newValue];
                    }
                }
                $entry['status'] = $entry['changes'] ? 'update' : 'no_change';
            }
        }
        $preview[] = $entry;
    }
    return $preview;
}

function vattuQuantityPriceFingerprint(array $preview): string
{
    return hash('sha256', json_encode(
        $preview,
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    ));
}

function vattuQuantityPriceApply(PDO $db, array $preview): int
{
    $updated = 0;
    foreach ($preview as $row) {
        if ($row['status'] !== 'update') {
            continue;
        }
        $set = [];
        $guards = [];
        $params = [':stt' => $row['stt'], ':code' => $row['code']];
        foreach ($row['changes'] as $field => $change) {
            if (!isset(VATTU_QUANTITY_PRICE_FIELDS[$field])) {
                throw new LogicException('Trường cập nhật không hợp lệ.');
            }
            $set[] = "`{$field}` = :{$field}";
            if ($row['old'][$field] === null) {
                $guards[] = "`{$field}` IS NULL";
            } else {
                $guards[] = "`{$field}` = :old_{$field}";
                $params[":old_{$field}"] = $row['old'][$field];
            }
            $params[":{$field}"] = $change['new'];
        }
        $statement = $db->prepare(
            'UPDATE vattu_thanh_ly_iso SET ' . implode(', ', $set)
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
