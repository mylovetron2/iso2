<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/doi_execution.php';

use PHPUnit\Framework\TestCase;

final class DoiExecutionQuarterTest extends TestCase
{
    public function testEveryMonthMarksOnlyItsQuarterAndMatchingPlanYear(): void
    {
        for ($month = 1; $month <= 12; $month++) {
            $quarter = intdiv($month - 1, 3) + 1;
            $statement = $this->createMock(PDOStatement::class);
            $statement->expects($this->once())->method('execute')->with([
                ':dossier_id' => 82,
                ':year' => 2026,
            ]);
            $db = $this->createMock(PDO::class);
            $db->expects($this->once())->method('prepare')->with($this->callback(
                function (string $sql) use ($quarter): bool {
                    $this->assertStringContainsString("SET qui_{$quarter}_hoantat = 1", $sql);
                    for ($q = 1; $q <= 4; $q++) {
                        $value = $q === $quarter ? 1 : 0;
                        $this->assertStringContainsString("qui_{$q}_hoantat = {$value}", $sql);
                    }
                    $this->assertStringContainsString('hososcbd_doi_id = :dossier_id', $sql);
                    $this->assertStringContainsString('nam = :year', $sql);
                    $this->assertStringNotContainsString("'TO'", $sql);
                    $this->assertStringNotContainsString('ngaykt', $sql);
                    return true;
                }
            ))->willReturn($statement);
            markDoiExecutionQuarterComplete($db, 82, sprintf('2026-%02d-01', $month));
        }
    }

    public function testInvalidDateCannotUpdateCompletion(): void
    {
        $db = $this->createMock(PDO::class);
        $db->expects($this->never())->method('prepare');
        $this->expectException(InvalidArgumentException::class);
        markDoiExecutionQuarterComplete($db, 82, '2026-02-30');
    }
}
