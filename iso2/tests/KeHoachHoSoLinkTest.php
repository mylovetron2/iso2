<?php
declare(strict_types=1);

require_once __DIR__ . '/../controllers/KeHoachBaoDuongDinhKyController.php';

use PHPUnit\Framework\TestCase;

final class KeHoachHoSoLinkTest extends TestCase
{
    /**
     * @dataProvider linkCases
     */
    public function testLinkValidationAndTransaction(
        ?array $plan,
        bool $deviceMatches,
        bool $alreadyUsed,
        ?string $expectedError
    ): void {
        $db = $this->createMock(PDO::class);
        $db->expects($this->once())->method('beginTransaction');
        $db->expects($expectedError === null ? $this->once() : $this->never())->method('commit');
        $db->expects($expectedError === null ? $this->never() : $this->once())->method('rollBack');
        $db->method('inTransaction')->willReturn(true);
        $queries = [];
        $db->method('prepare')->willReturnCallback(function (string $sql) use (
            &$queries, $plan, $deviceMatches, $alreadyUsed
        ): PDOStatement {
            $queries[] = $sql;
            $stmt = $this->createMock(PDOStatement::class);
            if (str_contains($sql, 'SELECT *')) {
                $this->assertStringContainsString('TRIM(t.madv) IN (:user_madv0)', $sql);
                $this->assertStringEndsWith('FOR UPDATE', $sql);
                $stmt->expects($this->once())->method('execute')->with([':id' => 11, ':user_madv0' => 'KTKT']);
                $stmt->method('fetch')->willReturn($plan ?? false);
            } elseif (str_contains($sql, 'SELECT h.stt')) {
                $this->assertStringContainsString('t.mavt = h.mavt AND t.somay = h.somay', $sql);
                $this->assertStringNotContainsString('ngayth', $sql);
                $this->assertStringNotContainsString('h.cv', $sql);
                $stmt->expects($this->once())->method('execute')->with([':hoso_id' => 55, ':thietbi_id' => 7]);
                $stmt->method('fetchColumn')->willReturn($deviceMatches ? 55 : false);
            } elseif (str_contains($sql, 'SELECT id')) {
                $stmt->expects($this->once())->method('execute')->with([55]);
                $stmt->method('fetchColumn')->willReturn($alreadyUsed ? 99 : false);
            } else {
                $this->assertStringContainsString('hososcbd_id IS NULL AND hososcbd_doi_id IS NULL', $sql);
                $stmt->expects($this->once())->method('execute')->with([55, 11]);
                $stmt->method('rowCount')->willReturn(1);
            }
            return $stmt;
        });
        $class = new ReflectionClass(KeHoachBaoDuongDinhKyController::class);
        $controller = $class->newInstanceWithoutConstructor();
        foreach (['db' => $db, 'userIsAdmin' => false, 'userMadv' => 'KTKT'] as $name => $value) {
            $property = $class->getProperty($name);
            $property->setAccessible(true);
            $property->setValue($controller, $value);
        }
        $method = $class->getMethod('linkHoSoId');
        $method->setAccessible(true);
        if ($expectedError !== null) {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage($expectedError);
        }
        $method->invoke($controller, 11, 55);
        $this->assertCount(4, $queries);
    }

    public function linkCases(): array
    {
        $plan = ['hososcbd_id' => null, 'hososcbd_doi_id' => null, 'nguon_hoso' => null, 'donvi_lam_chinh' => ' cnm ', 'thietbi_id' => 7];
        return [
            'valid' => [$plan, true, false, null],
            'valid with source already chosen' => [array_replace($plan, ['nguon_hoso' => 'hososcbd_iso']), true, false, null],
            'team source chosen' => [array_replace($plan, ['nguon_hoso' => 'hososcbd_doi_iso']), true, false, 'nguồn hồ sơ khác'],
            'outside scope' => [null, true, false, 'ngoài phạm vi'],
            'existing main link' => [array_replace($plan, ['hososcbd_id' => 5]), true, false, 'đã có liên kết'],
            'existing team link' => [array_replace($plan, ['hososcbd_doi_id' => 6]), true, false, 'đã có liên kết'],
            'other main unit' => [array_replace($plan, ['donvi_lam_chinh' => 'KTKT']), true, false, 'CNM/RDNGA'],
            'wrong device or missing dossier' => [$plan, false, false, 'không tồn tại hoặc không đúng'],
            'dossier already used' => [$plan, true, true, 'kế hoạch khác'],
        ];
    }
}
