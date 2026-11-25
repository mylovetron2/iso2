<?php
declare(strict_types=1);

const VATTU_NQL_GHICHU_SEPARATOR = '; ';

function vattuNguoiQuanLyGhiChuMaxLength(PDO $db): ?int
{
    $statement = $db->prepare(
        'SELECT CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS '
        . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
    );
    $statement->execute([':table' => 'vattu_thanh_ly_iso', ':column' => 'ghichu']);
    $length = $statement->fetchColumn();
    if ($length === false) {
        throw new RuntimeException('Không tìm thấy cột ghichu.');
    }

    // TEXT/LONGTEXT có giới hạn tính bằng byte nên không kiểm tra theo ký tự.
    return $length === null || (int)$length > 65535 ? null : (int)$length;
}

function vattuNguoiQuanLyGhiChuPreview(PDO $db, bool $lockRows = false): array
{
    $lock = $lockRows && $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
    $rows = $db->query(
        "SELECT stt, mavattu, nguoiquanly, ghichu FROM vattu_thanh_ly_iso "
        . "WHERE nguoiquanly IS NOT NULL AND TRIM(nguoiquanly) <> '' ORDER BY stt ASC" . $lock
    )->fetchAll(PDO::FETCH_ASSOC);
    $maxLength = vattuNguoiQuanLyGhiChuMaxLength($db);

    $items = [];
    $skipped = 0;
    $tooLong = [];
    foreach ($rows as $row) {
        $owner = trim((string)$row['nguoiquanly']);
        $oldNote = $row['ghichu'] === null ? null : (string)$row['ghichu'];
        $trimmedNote = $oldNote === null ? '' : trim($oldNote);

        if ($trimmedNote === '') {
            $newNote = $owner;
        } elseif ($trimmedNote === $owner
            || substr($trimmedNote, -strlen(VATTU_NQL_GHICHU_SEPARATOR . $owner)) === VATTU_NQL_GHICHU_SEPARATOR . $owner) {
            $skipped++;
            continue;
        } else {
            $newNote = $trimmedNote . VATTU_NQL_GHICHU_SEPARATOR . $owner;
        }

        if ($maxLength !== null && mb_strlen($newNote, 'UTF-8') > $maxLength) {
            $tooLong[] = ['stt' => $row['stt'], 'mavattu' => $row['mavattu'], 'length' => mb_strlen($newNote, 'UTF-8')];
            continue;
        }
        $items[] = [
            'stt' => $row['stt'],
            'mavattu' => $row['mavattu'],
            'nguoiquanly' => (string)$row['nguoiquanly'],
            'old' => $oldNote,
            'new' => $newNote,
        ];
    }

    return ['items' => $items, 'skipped' => $skipped, 'too_long' => $tooLong, 'max_length' => $maxLength];
}

function vattuNguoiQuanLyGhiChuFingerprint(array $preview): string
{
    return hash('sha256', json_encode(
        $preview,
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    ));
}

function vattuNguoiQuanLyGhiChuApply(PDO $db, array $preview): int
{
    $updated = 0;
    foreach ($preview['items'] as $item) {
        $noteGuard = $item['old'] === null ? 'ghichu IS NULL' : 'ghichu = :old_note';
        $statement = $db->prepare(
            'UPDATE vattu_thanh_ly_iso SET ghichu = :new_note '
            . 'WHERE stt = :stt AND nguoiquanly = :owner AND ' . $noteGuard
        );
        $params = [
            ':new_note' => $item['new'],
            ':stt' => $item['stt'],
            ':owner' => $item['nguoiquanly'],
        ];
        if ($item['old'] !== null) {
            $params[':old_note'] = $item['old'];
        }
        $statement->execute($params);
        if ($statement->rowCount() !== 1) {
            throw new InvalidArgumentException(
                "Không cập nhật được dòng STT {$item['stt']} (dữ liệu đã thay đổi hoặc không khớp); cần xem trước lại."
            );
        }
        $updated++;
    }

    return $updated;
}
