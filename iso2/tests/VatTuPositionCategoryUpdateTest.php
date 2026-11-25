<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/vattu_position_category_update.php';

use PHPUnit\Framework\TestCase;

final class VatTuPositionCategoryUpdateTest extends TestCase
{
    private function database(): PDO
    {
        $db = new PDO('sqlite::memory:');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec('CREATE TABLE phanloai_vattu_thanh_ly_iso (id INTEGER PRIMARY KEY, ten_phanloai TEXT)');
        $db->exec("INSERT INTO phanloai_vattu_thanh_ly_iso VALUES (4, 'Công cụ dụng cụ')");
        $db->exec('CREATE TABLE vattu_thanh_ly_iso (stt INTEGER PRIMARY KEY, mavattu TEXT, vi_tri_sap_xep INTEGER, phanloai_id INTEGER)');
        $db->exec("
            INSERT INTO vattu_thanh_ly_iso VALUES
                (1, 'VT-137', 137, 1),
                (2, 'VT-138', 138, 1),
                (3, 'VT-139', 139, NULL),
                (4, 'VT-DONE', 140, 4),
                (5, 'VT-NULL-POS', NULL, 1)
        ");
        return $db;
    }

    public function testUpdatesOnlyPositionsAtLeast138AndKeepsAlreadyCorrectRows(): void
    {
        $db = $this->database();
        $preview = vattuPositionCategoryPreview($db);
        $this->assertSame(['VT-138', 'VT-139', 'VT-DONE'], array_column($preview['items'], 'mavattu'));
        $this->assertSame(['update', 'update', 'unchanged'], array_column($preview['items'], 'status'));
        $this->assertSame(2, vattuPositionCategoryApply($db, $preview));
        $values = $db->query('SELECT mavattu, phanloai_id FROM vattu_thanh_ly_iso ORDER BY stt')->fetchAll(PDO::FETCH_KEY_PAIR);
        $this->assertSame([1, 4, 4, 4, 1], array_map('intval', array_values($values)));
    }

    public function testCategoryMustBeUnique(): void
    {
        $db = $this->database();
        $db->exec("INSERT INTO phanloai_vattu_thanh_ly_iso VALUES (5, 'Công cụ dụng cụ')");
        $this->expectException(RuntimeException::class);
        vattuPositionCategoryPreview($db);
    }

    public function testConcurrentPositionOrCategoryChangeIsRejected(): void
    {
        $db = $this->database();
        $preview = vattuPositionCategoryPreview($db);
        $db->exec('UPDATE vattu_thanh_ly_iso SET vi_tri_sap_xep = 137 WHERE stt = 2');
        $this->expectException(RuntimeException::class);
        vattuPositionCategoryApply($db, $preview);
    }
}
