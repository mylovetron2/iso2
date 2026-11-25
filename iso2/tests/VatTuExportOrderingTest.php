<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/VatTuThanhLy.php';

use PHPUnit\Framework\TestCase;

final class VatTuExportOrderingModel extends VatTuThanhLy
{
    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->table = 'vattu_thanh_ly_iso';
    }

    protected function query(string $sql, array $params = []): PDOStatement
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement;
    }
}

final class VatTuExportOrderingTest extends TestCase
{
    public function testExportOrderingIsNumericAndDoesNotChangeDefaultOrder(): void
    {
        $db = new PDO('sqlite::memory:');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec('CREATE TABLE vattu_thanh_ly_iso (stt INTEGER PRIMARY KEY, phanloai_id INTEGER, vi_tri_sap_xep INTEGER, soluong_conlai REAL, dongia REAL)');
        $db->exec('CREATE TABLE phanloai_vattu_thanh_ly_iso (id INTEGER, ten_phanloai TEXT, mau_sac TEXT)');
        $db->exec('CREATE TABLE vattu_thanh_ly_sudung_iso (id INTEGER, vattu_stt INTEGER, trangthai TEXT, soluong REAL)');
        $db->exec('INSERT INTO vattu_thanh_ly_iso (stt, phanloai_id, vi_tri_sap_xep) VALUES (1, 1, 10), (2, 1, 2), (3, 1, NULL), (4, 1, 2), (5, 2, 1)');
        $model = new VatTuExportOrderingModel($db);

        $export = $model->getAllWithStats('WHERE v.phanloai_id = :id', [':id' => 1], 0, 0, true);
        $this->assertSame([4, 2, 1, 3], array_map('intval', array_column($export, 'stt')));
        $list = $model->getAllWithStats('WHERE v.phanloai_id = :id', [':id' => 1], 2);
        $this->assertSame([4, 3], array_map('intval', array_column($list, 'stt')));
    }
}
