<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bao_duong_report.php';

use PHPUnit\Framework\TestCase;

final class BaoDuongReportTest extends TestCase
{
    public function testReportClassifiesEachDeviceAgainstNearestPlannedQuarter(): void
    {
        $report = buildBaoDuongReport([
            ['id' => 1, 'qui_3' => 'TO', 'qui_2_hoantat' => 1],
            ['id' => 2, 'qui_3' => 'TO', 'qui_3_hoantat' => 1],
            ['id' => 3, 'qui_2' => 'TO', 'qui_3_hoantat' => 1],
            ['id' => 4, 'qui_1' => 'TO', 'ngay_thuc_hien' => '2026-01-15'],
            ['id' => 5, 'qui_1' => 'TO', 'qui_3' => 'TO', 'qui_2_hoantat' => 1],
            ['id' => 6, 'qui_1' => 'TO', 'qui_3' => 'TO', 'qui_4' => 'TO', 'qui_3_hoantat' => 1],
        ], 3);

        $this->assertSame([
            'total_plans' => 6,
            'truoc_han' => 1,
            'dung_han' => 2,
            'sau_han' => 2,
            'chua_thuc_hien' => 1,
            'tyle_hoan_thanh' => 83.33,
        ], $report['summary']);
        $this->assertSame(
            ['truoc_han', 'dung_han', 'sau_han', 'chua_thuc_hien', 'sau_han', 'dung_han'],
            array_column($report['items'], 'report_status')
        );
        $this->assertSame([1, 3], $report['items'][5]['report_planned_quarters']);
        $this->assertSame(1, $report['items'][4]['report_reference_quarter']);
        $this->assertSame(3, $report['items'][5]['report_reference_quarter']);
        $this->assertNull($report['items'][3]['report_completed_quarter']);
    }

    public function testCompletionOutsideReportingPeriodAndMultipleFlags(): void
    {
        $report = buildBaoDuongReport([
            ['id' => 1, 'qui_3' => 'TO', 'qui_4_hoantat' => 1],
            ['id' => 2, 'qui_3' => 'TO', 'qui_1_hoantat' => 1, 'qui_3_hoantat' => 1],
        ], 3);

        $this->assertSame(['sau_han', 'dung_han'], array_column($report['items'], 'report_status'));
        $this->assertSame([4, 3], array_column($report['items'], 'report_completed_quarter'));
        $this->assertSame(100.0, $report['summary']['tyle_hoan_thanh']);
    }

    public function testEmptyReportHasZeroCountsAndRate(): void
    {
        $report = buildBaoDuongReport([], 4);

        $this->assertSame([], $report['items']);
        $this->assertSame(0, $report['summary']['total_plans']);
        $this->assertSame(0, $report['summary']['tyle_hoan_thanh']);
        $this->assertSame(0, array_sum([
            $report['summary']['truoc_han'],
            $report['summary']['dung_han'],
            $report['summary']['sau_han'],
            $report['summary']['chua_thuc_hien'],
        ]));
    }

    public function testEmptyReportRendersZeroPercentagesAndNoChartSegments(): void
    {
        $report = buildBaoDuongReport([], 4);
        $nam = 2026;
        $qui = '4';
        $reportUnit = 'Tất cả bộ phận';
        $search = '';
        $nhomsc = '';
        ob_start();
        require __DIR__ . '/../views/kehoachbaoduongdinhky/print_thongke.php';
        $html = ob_get_clean();

        $this->assertStringContainsString('Không có thiết bị phù hợp với bộ lọc.', $html);
        $this->assertSame(9, substr_count($html, '0,00%'));
        $this->assertStringNotContainsString('pathLength="100"', $html);
        $this->assertStringNotContainsString('NaN', $html);
    }

    public function testInvalidQuarterIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        buildBaoDuongReport([], 5);
    }

    public function testDeviceWithoutAPlanInThePeriodIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        buildBaoDuongReport([['qui_4' => 'TO']], 3);
    }
}
