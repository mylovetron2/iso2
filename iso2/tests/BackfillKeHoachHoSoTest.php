<?php
declare(strict_types=1);

require_once __DIR__ . '/../backfill_kehoach_hososcbd.php';

use PHPUnit\Framework\TestCase;

final class BackfillKeHoachHoSoTest extends TestCase
{
    public function testOnlyCompletedQuartersAreIncluded(): void
    {
        $this->assertSame([
            ['2026-01-01', '2026-04-01'],
            ['2026-07-01', '2026-10-01'],
        ], backfillHoSoQuarterRanges([
            'id' => 1, 'nam' => 2026,
            'qui_1_hoantat' => '1', 'qui_2_hoantat' => 0,
            'qui_3_hoantat' => 1, 'qui_4_hoantat' => null,
        ]));
    }

    public function testFourthQuarterEndsAtNextYear(): void
    {
        $this->assertSame([['2026-10-01', '2027-01-01']], backfillHoSoQuarterRanges([
            'id' => 1, 'nam' => 2026, 'qui_4_hoantat' => 1,
        ]));
        $this->assertSame([], backfillHoSoQuarterRanges(['id' => 1, 'nam' => 2026]));
    }

    public function testInvalidYearFailsExplicitly(): void
    {
        $this->expectException(RuntimeException::class);
        backfillHoSoQuarterRanges(['id' => 1, 'nam' => 0]);
    }

    public function testQueryUsesStartDateWithoutCvFilterAndDeterministicLatestOrdering(): void
    {
        $ranges = [['2026-01-01', '2026-04-01'], ['2026-07-01', '2026-10-01']];
        $sql = backfillHoSoCandidateQuery($ranges, false);
        $this->assertSame(6, substr_count($sql, '?'));
        $this->assertStringContainsString('h.phieu', $sql);
        $this->assertStringNotContainsString('h.cv', $sql);
        $this->assertStringNotContainsString('BDDK', $sql);
        $this->assertStringContainsString('h.mavt = ? AND h.somay = ?', $sql);
        $this->assertStringContainsString('(h.ngayth >= ? AND h.ngayth < ?) OR (h.ngayth >= ? AND h.ngayth < ?)', $sql);
        $this->assertStringContainsString('ORDER BY h.ngayth DESC, h.stt DESC', $sql);
        $this->assertStringNotContainsString('FOR UPDATE', $sql);
        $this->assertStringEndsWith('LIMIT 1 FOR UPDATE', backfillHoSoCandidateQuery($ranges, true));
    }

    public function testEmptyQuartersCannotProduceUnrestrictedSearch(): void
    {
        $this->expectException(InvalidArgumentException::class);
        backfillHoSoCandidateQuery([], false);
    }
}
