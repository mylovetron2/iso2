<?php
declare(strict_types=1);

function vattuNguoiQuanLyMap(): array
{
    return [
        'Vũ Anh Đức' => ['A. Đức', 'A.Đức', 'Đức', 'V.A.Đức'],
        'Vũ Xuân Diệu' => ['Diệu', 'V.X.Diệu'],
        'Đặng Văn Tuệ' => ['A. Tuệ'],
        'Lê Mạnh Cường' => ['Cường'],
        'Mỵ Minh Ngọc' => ['Ngọc'],
        'Trần Quốc Hưng' => ['Hưng'],
        'Cao Đắc Hân' => ['Hân'],
        'Trương Ngọc Sang' => ['Sang'],
        'Đỗ Thành Luân' => ['Luân'],
        'Nguyễn Minh Đạt' => ['Đạt'],
        'Dương Trung Kiên' => ['Kiên'],
        'Trần Quốc Huy' => ['Huy'],
        'Nguyễn Duy Khanh' => ['Khanh'],
        'Trần Danh Thành' => ['Thành'],
        'Trần Hữu Dũng' => ['H.Dũng'],
    ];
}

function vattuNguoiQuanLyNormalize(string $value): string
{
    if (class_exists('Normalizer')) {
        $value = Normalizer::normalize($value, Normalizer::FORM_C) ?: $value;
    }

    return $value;
}

// Regex cho một tên: khoảng trắng linh hoạt, cho phép khoảng trắng quanh dấu chấm.
function vattuNguoiQuanLyNamePattern(string $name): string
{
    $name = preg_replace('/\s*\.\s*/u', '.', trim(preg_replace('/\s+/u', ' ', $name)));
    $pattern = '';
    foreach (preg_split('//u', $name, -1, PREG_SPLIT_NO_EMPTY) as $char) {
        if ($char === '.') {
            $pattern .= '\s*\.\s*';
        } elseif ($char === ' ') {
            $pattern .= '\s+';
        } else {
            $pattern .= preg_quote($char, '/');
        }
    }

    return $pattern;
}

// Mỗi mục: [regex, tên chuẩn]. Tên đầy đủ đứng trước, rồi alias dài trước alias ngắn.
function vattuNguoiQuanLyLookup(): array
{
    $full = [];
    $aliases = [];
    foreach (vattuNguoiQuanLyMap() as $target => $names) {
        $full[] = [mb_strlen($target), vattuNguoiQuanLyNamePattern($target), $target];
        foreach ($names as $alias) {
            $aliases[] = [mb_strlen($alias), vattuNguoiQuanLyNamePattern($alias), $target];
        }
    }
    $byLength = static fn(array $a, array $b): int => $b[0] <=> $a[0];
    usort($full, $byLength);
    usort($aliases, $byLength);
    $entries = array_merge($full, $aliases);

    $targets = [];
    $alternatives = [];
    foreach ($entries as [, $pattern, $target]) {
        $targets[] = $target;
        $alternatives[] = '(' . $pattern . ')';
    }
    $known = implode('|', array_map(static fn(array $entry): string => $entry[1], $entries));

    // Tên không được dính liền chữ cái phía trước; phía sau không được là một từ lạ toàn chữ
    // (ví dụ "Đức Anh"), trừ khi từ đó cũng là một tên cần đổi. Mã có chứa số (MY59280001) thì được phép.
    $regex = '/(?<!\p{L})(?:' . implode('|', $alternatives) . ')'
        . '(?:(?!\s*\p{L}++(?![\p{L}\p{N}]))|(?=\s+(?:' . $known . '|CK)(?!\p{L})))/iu';

    return ['regex' => $regex, 'targets' => $targets];
}

// Thay mọi tên có trong chuỗi bằng tên chuẩn và bỏ toàn bộ thông tin khác; trả về null nếu không có gì đổi được.
// Ví dụ "Sang SGH521SPHW Đỗ Thành Luân SGH543SRW6" -> "Trương Ngọc Sang, Đỗ Thành Luân".
function vattuNguoiQuanLyReplace(string $value, array $lookup): ?string
{
    $found = [];
    preg_replace_callback(
        $lookup['regex'],
        static function (array $match) use ($lookup, &$found): string {
            for ($i = 1; $i < count($match); $i++) {
                if ($match[$i] !== '') {
                    $found[] = $lookup['targets'][$i - 1];
                    break;
                }
            }

            return $match[0];
        },
        vattuNguoiQuanLyNormalize($value)
    );

    if ($found === []) {
        // Không có tên trong bảng quy đổi: chỉ bỏ số lượng cuối chuỗi, ví dụ "CK (4)" -> "CK".
        $trimmed = trim(vattuNguoiQuanLyNormalize($value));
        $stripped = preg_replace('/\s*\(\d+\)$/u', '', $trimmed);

        return $stripped !== null && $stripped !== '' && $stripped !== $trimmed ? $stripped : null;
    }

    return implode(', ', array_unique($found));
}

function vattuNguoiQuanLyPreview(PDO $db, bool $lockRows = false): array
{
    $lock = $lockRows && $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
    $rows = $db->query(
        "SELECT stt, mavattu, nguoiquanly FROM vattu_thanh_ly_iso "
        . "WHERE nguoiquanly IS NOT NULL AND nguoiquanly <> '' ORDER BY stt ASC" . $lock
    )->fetchAll(PDO::FETCH_ASSOC);

    $lookup = vattuNguoiQuanLyLookup();
    $items = [];
    $unmapped = [];
    foreach ($rows as $row) {
        $old = (string)$row['nguoiquanly'];
        $new = vattuNguoiQuanLyReplace($old, $lookup);
        if ($new === null) {
            $unmapped[$old] = ($unmapped[$old] ?? 0) + 1;
            continue;
        }
        if ($new === $old) {
            continue;
        }
        $items[] = [
            'stt' => $row['stt'],
            'mavattu' => $row['mavattu'],
            'old' => $old,
            'new' => $new,
        ];
    }
    ksort($unmapped);

    return ['items' => $items, 'unmapped' => $unmapped];
}

function vattuNguoiQuanLyFingerprint(array $preview): string
{
    return hash('sha256', json_encode(
        $preview,
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    ));
}

function vattuNguoiQuanLyApply(PDO $db, array $preview): int
{
    $statement = $db->prepare(
        'UPDATE vattu_thanh_ly_iso SET nguoiquanly = :new_value '
        . 'WHERE stt = :stt AND nguoiquanly = :old_value'
    );
    $updated = 0;
    foreach ($preview['items'] as $item) {
        $statement->execute([
            ':new_value' => $item['new'],
            ':stt' => $item['stt'],
            ':old_value' => $item['old'],
        ]);
        if ($statement->rowCount() !== 1) {
            throw new InvalidArgumentException(
                "Không cập nhật được dòng STT {$item['stt']} (dữ liệu đã thay đổi hoặc không khớp); cần xem trước lại."
            );
        }
        $updated++;
    }

    return $updated;
}
