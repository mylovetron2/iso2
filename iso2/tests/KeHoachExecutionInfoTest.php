<?php
declare(strict_types=1);

require_once __DIR__ . '/../controllers/KeHoachBaoDuongDinhKyController.php';

use PHPUnit\Framework\TestCase;

final class KeHoachExecutionInfoTest extends TestCase
{
    private function invokeLoader(PDO $db, array $items): array
    {
        $class = new ReflectionClass(KeHoachBaoDuongDinhKyController::class);
        $controller = $class->newInstanceWithoutConstructor();
        $property = $class->getProperty('db');
        $property->setAccessible(true);
        $property->setValue($controller, $db);
        $method = $class->getMethod('loadHoSoExecutionInfo');
        $method->setAccessible(true);
        return $method->invoke($controller, $items);
    }

    public function testEmptyPageDoesNotQueryDatabase(): void
    {
        $db = $this->createMock(PDO::class);
        $db->expects($this->never())->method('prepare');
        $this->assertSame([], $this->invokeLoader($db, []));
    }

    public function testBatchPreservesRowsAndCollectsPeopleFromCorrectSources(): void
    {
        $main = $this->createMock(PDOStatement::class);
        $main->expects($this->once())->method('execute')->with([11, 12, 13]);
        $main->method('fetch')->willReturnOnConsecutiveCalls(
            ['id' => 11, 'ngayth' => '2026-01-15', 'ngaykt' => '2026-01-20', 'nguoi_thuc_hien' => ' A '],
            ['id' => 11, 'ngayth' => '2026-01-15', 'ngaykt' => '2026-01-20', 'nguoi_thuc_hien' => 'B'],
            ['id' => 11, 'ngayth' => '2026-01-15', 'ngaykt' => '2026-01-20', 'nguoi_thuc_hien' => 'A'],
            false
        );
        $team = $this->createMock(PDOStatement::class);
        $team->expects($this->once())->method('execute')->with([11, 12, 13]);
        $team->method('fetch')->willReturnOnConsecutiveCalls(
            [
                'id' => 12,
                'ngayth' => '2026-03-01',
                'ngaykt' => null,
                'nguoi_thuc_hien' => 'C',
                'pending_review_data' => json_encode([
                    'ngayth' => '2026-03-02',
                    'ngaykt' => '2026-03-03',
                    'nguoi_thuc_hien_iso' => 'Nguyễn Văn B',
                ]),
            ],
            ['id' => 12, 'ngayth' => '2026-03-01', 'ngaykt' => null, 'nguoi_thuc_hien' => ''],
            false
        );
        $db = $this->createMock(PDO::class);
        $queries = [];
        $db->expects($this->exactly(2))->method('prepare')->willReturnCallback(
            function (string $sql) use (&$queries, $main, $team): PDOStatement {
                $queries[] = $sql;
                return count($queries) === 1 ? $main : $team;
            }
        );
        $items = $this->invokeLoader($db, [['id' => 11], ['id' => 12], ['id' => 13]]);
        $this->assertSame([11, 12, 13], array_column($items, 'id'));
        $this->assertSame(['A', 'B'], $items[0]['cac_nguoi_thuc_hien']);
        $this->assertSame('2026-01-15', $items[0]['ngay_thuc_hien']);
        $this->assertSame('2026-01-20', $items[0]['ngay_ket_thuc']);
        $this->assertSame(['C'], $items[1]['cac_nguoi_thuc_hien']);
        $this->assertNull($items[1]['ngay_ket_thuc']);
        $this->assertTrue($items[1]['pending_review']);
        $this->assertSame('2026-03-02', $items[1]['pending_review_ngayth']);
        $this->assertSame('2026-03-03', $items[1]['pending_review_ngaykt']);
        $this->assertSame('Nguyễn Văn B', $items[1]['pending_review_person']);
        $this->assertNull($items[2]['ngay_thuc_hien']);
        $this->assertSame([], $items[2]['cac_nguoi_thuc_hien']);
        $this->assertStringContainsString('n.mahoso = h.hoso', $queries[0]);
        $this->assertStringContainsString("k.nguon_hoso = 'hososcbd_iso'", $queries[0]);
        $this->assertStringNotContainsString('ngthuchien_iso', $queries[1]);
        $this->assertStringContainsString("k.nguon_hoso = 'hososcbd_doi_iso'", $queries[1]);
        $this->assertStringContainsString('h.stt = k.hososcbd_doi_id', $queries[1]);
        $this->assertStringContainsString("pe_pending.record_type = 'hososcbd_doi_iso'", $queries[1]);
    }
}
