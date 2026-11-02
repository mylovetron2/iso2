<?php
declare(strict_types=1);

require_once __DIR__ . '/../controllers/KeHoachBaoDuongDinhKyController.php';

use PHPUnit\Framework\TestCase;

final class KeHoachPrintStatisticsTest extends TestCase
{
    private function controller(PDO $db): KeHoachBaoDuongDinhKyController
    {
        $class = new ReflectionClass(KeHoachBaoDuongDinhKyController::class);
        $controller = $class->newInstanceWithoutConstructor();
        foreach (['db' => $db, 'userIsAdmin' => false, 'userMadv' => 'KTKT'] as $name => $value) {
            $property = $class->getProperty($name);
            $property->setAccessible(true);
            $property->setValue($controller, $value);
        }
        return $controller;
    }

    public function testPrintUsesScopedFilteredPlansAndApprovedExecutionData(): void
    {
        $plans = $this->createMock(PDOStatement::class);
        $plans->expects($this->once())->method('execute')->with([
            ':nam' => 2026,
            ':user_madv0' => 'KTKT',
            ':donvi_chinh0' => 'ĐVLTH',
            ':donvi_chinh1' => 'DVLTH',
            ':search1' => '%AK%',
            ':search2' => '%AK%',
        ]);
        $plans->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn([
            [
                'id' => 10, 'ten_thietbi' => 'AK <script>alert(1)</script>', 'so_serial' => '00&3',
                'donvi_lam_chinh' => 'ĐVLTH', 'ghi_chu' => "A & B\nC",
                'qui_3' => 'TO', 'qui_2_hoantat' => 1,
            ],
            [
                'id' => 11, 'ten_thietbi' => 'Thiết bị B', 'so_serial' => 'B-1',
                'donvi_lam_chinh' => 'ĐVLTH', 'qui_2' => 'TO', 'qui_3_hoantat' => 1,
            ],
            ['id' => 12, 'ten_thietbi' => 'Ngoài kỳ', 'qui_4' => 'TO'],
        ]);
        $main = $this->createMock(PDOStatement::class);
        $main->expects($this->once())->method('execute')->with([10, 11]);
        $main->method('fetch')->willReturnOnConsecutiveCalls([
            'id' => 10, 'ngayth' => '2026-06-26', 'ngaykt' => '2026-07-08',
            'nguoi_thuc_hien' => 'Nguyễn <A>', 'nguoi_thuc_hien_nhap' => null,
        ], false);
        $team = $this->createMock(PDOStatement::class);
        $team->expects($this->once())->method('execute')->with([10, 11]);
        $team->method('fetch')->willReturnOnConsecutiveCalls([
            'id' => 11, 'ngayth' => '2026-07-02', 'ngaykt' => '2026-07-15',
            'nguoi_thuc_hien' => "Trần Văn A\nLê Văn B", 'nguoi_thuc_hien_nhap' => "Trần Văn A\nLê Văn B",
            'pending_review_data' => json_encode([
                'ngayth' => '2026-12-01', 'nguoi_thuc_hien_iso' => 'Người chưa duyệt',
            ]),
        ], false);

        $queries = [];
        $db = $this->createMock(PDO::class);
        $db->expects($this->exactly(3))->method('prepare')->willReturnCallback(
            function (string $sql) use (&$queries, $plans, $main, $team): PDOStatement {
                $queries[] = $sql;
                return [$plans, $main, $team][count($queries) - 1];
            }
        );
        $previousGet = $_GET;
        $_GET = [
            'nam' => '2026', 'qui' => '3', 'search' => 'AK',
            'nhomsc' => 'CNC+RDNGA', 'donvi_chinh' => 'ĐVLTH',
        ];
        try {
            ob_start();
            $this->controller($db)->inThongKe();
            $html = ob_get_clean();
        } finally {
            $_GET = $previousGet;
        }

        $this->assertStringContainsString('TRIM(t.madv) IN (:user_madv0)', $queries[0]);
        $this->assertStringContainsString('TRIM(donvi_lam_chinh) IN (:donvi_chinh0,:donvi_chinh1)', $queries[0]);
        $this->assertStringContainsString("nhomsc IN ('CNC', 'RDNGA')", $queries[0]);
        $this->assertStringContainsString("k.nguon_hoso = 'hososcbd_iso'", $queries[1]);
        $this->assertStringContainsString("k.nguon_hoso = 'hososcbd_doi_iso'", $queries[2]);
        $this->assertStringContainsString('Từ đầu năm đến hết Quý 3 năm 2026', $html);
        $this->assertStringContainsString('Đơn vị: KTKT - Bộ phận thực hiện chính: ĐVLTH', $html);
        $this->assertStringNotContainsString('Bộ lọc', $html);
        $this->assertStringContainsString('Lưu ý: Mốc thời gian để tính là ngày bắt đầu thực hiện', $html);
        $this->assertStringContainsString('Xưởng trưởng Xưởng SCTBĐVL', $html);
        $this->assertStringContainsString('Đặng Văn Tuệ', $html);
        $this->assertStringContainsString('AK &lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringContainsString('00&amp;3', $html);
        $this->assertStringContainsString('Nguyễn &lt;A&gt;', $html);
        $this->assertStringContainsString("Trần Văn A<br />\nLê Văn B", $html);
        $this->assertStringContainsString('26/06/2026', $html);
        $this->assertStringContainsString('08/07/2026', $html);
        $this->assertStringContainsString('50,00%', $html);
        $this->assertStringContainsString('100,00%', $html);
        $this->assertStringNotContainsString('Ngoài kỳ', $html);
        $this->assertStringNotContainsString('Người chưa duyệt', $html);
        $this->assertStringNotContainsString('01/12/2026', $html);

        $dom = new DOMDocument();
        $previousErrors = libxml_use_internal_errors(true);
        try {
            $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
            $xpath = new DOMXPath($dom);
            $rows = $xpath->query('//table[contains(@class, "details")]/tbody/tr');
            $this->assertCount(2, $rows);
            $this->assertSame('Trước kế hoạch', trim($xpath->query('./td[8]', $rows->item(0))->item(0)->textContent));
            $this->assertSame('Sau kế hoạch', trim($xpath->query('./td[8]', $rows->item(1))->item(0)->textContent));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
        }
    }

    public function testPrintLoadsAllDevicesInBoundedBatches(): void
    {
        $records = [];
        for ($id = 1; $id <= 501; $id++) {
            $records[] = ['id' => $id, 'ten_thietbi' => 'TB-' . $id, 'qui_1' => 'TO'];
        }
        $plans = $this->createMock(PDOStatement::class);
        $plans->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($records);
        $executionQueries = [];
        foreach ([range(1, 500), range(1, 500), [501], [501]] as $ids) {
            $statement = $this->createMock(PDOStatement::class);
            $statement->expects($this->once())->method('execute')->with($ids);
            $statement->method('fetch')->willReturn(false);
            $executionQueries[] = $statement;
        }

        $db = $this->createMock(PDO::class);
        $db->expects($this->exactly(5))->method('prepare')->willReturnOnConsecutiveCalls(
            $plans, ...$executionQueries
        );
        $previousGet = $_GET;
        $_GET = ['nam' => '2026', 'qui' => '1'];
        try {
            ob_start();
            $this->controller($db)->inThongKe();
            $html = ob_get_clean();
        } finally {
            $_GET = $previousGet;
        }

        $this->assertStringContainsString('TB-501', $html);
        $this->assertStringContainsString('<td>501</td>', $html);
        $this->assertSame(501, substr_count($html, 'Chưa thực hiện</td>'));
    }

    public function testPrintRouteChecksPermissionWithoutBootstrapWarnings(): void
    {
        $script = <<<'PHP'
class FakeDatabase extends PDO { public function __construct() {} }
function getDBConnection(bool $debug = false): PDO { return new FakeDatabase(); }
function requireAuth(): void {}
function hasPermission(string $permission): bool { return false; }
$_GET = ['action' => 'inThongKe'];
register_shutdown_function(static function (): void { echo 'HTTP_STATUS=' . http_response_code(); });
require $argv[1];
PHP;
        $process = proc_open(
            [PHP_BINARY, '-d', 'display_errors=1', '-d', 'log_errors=0', '-r', $script, __DIR__ . '/../kehoachbaoduongdinhky.php'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        $this->assertIsResource($process);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        $this->assertSame(0, $exitCode);
        $this->assertSame('', $stderr);
        $this->assertSame('HTTP_STATUS=302', $stdout);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testPrintReportsAndLogsDatabaseErrors(): void
    {
        $db = $this->createMock(PDO::class);
        $db->expects($this->once())->method('prepare')->willThrowException(new PDOException('database unavailable'));
        $logPath = tempnam(sys_get_temp_dir(), 'iso2-print-');
        $previousLog = ini_get('error_log');
        $previousGet = $_GET;
        ini_set('error_log', $logPath);
        $_GET = ['nam' => '2026', 'qui' => '3'];
        try {
            http_response_code(200);
            ob_start();
            $this->controller($db)->inThongKe();
            $html = ob_get_clean();

            $this->assertSame(500, http_response_code());
            $this->assertStringContainsString('Không tải được báo cáo bảo dưỡng', $html);
            $this->assertStringContainsString('database unavailable', file_get_contents($logPath));
        } finally {
            $_GET = $previousGet;
            ini_set('error_log', $previousLog);
            unlink($logPath);
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testYearOnlyStatisticsLinkUsesTheDefaultQuarter(): void
    {
        $years = $this->createMock(PDOStatement::class);
        $years->method('fetchAll')->with(PDO::FETCH_COLUMN)->willReturn([2026]);
        $units = $this->createMock(PDOStatement::class);
        $units->method('fetchAll')->with(PDO::FETCH_COLUMN)->willReturn([]);
        $plans = $this->createMock(PDOStatement::class);
        $plans->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn([]);
        $db = $this->createMock(PDO::class);
        $db->method('query')->willReturn($years);
        $db->expects($this->exactly(2))->method('prepare')->willReturnOnConsecutiveCalls($units, $plans);
        $previousGet = $_GET;
        $previousSession = $_SESSION ?? null;
        $_GET = ['nam' => '2026'];
        $_SESSION = [];
        try {
            ob_start();
            $this->controller($db)->thongke();
            $html = ob_get_clean();
            $selectedQuarter = $_GET['qui'];
        } finally {
            $_GET = $previousGet;
            if ($previousSession === null) {
                unset($_SESSION);
            } else {
                $_SESSION = $previousSession;
            }
        }

        $this->assertSame((string)(int)ceil((int)date('n') / 3), $selectedQuarter);
        $dom = new DOMDocument();
        $previousErrors = libxml_use_internal_errors(true);
        try {
            $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
            $xpath = new DOMXPath($dom);
            $links = $xpath->query('//a[contains(@href, "action=inThongKe")]');
            $this->assertCount(1, $links);
            parse_str((string)parse_url($links->item(0)->getAttribute('href'), PHP_URL_QUERY), $params);
            $this->assertSame('2026', $params['nam']);
            $this->assertSame($selectedQuarter, $params['qui']);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
        }
    }

    public function testYearOnlyPrintUsesTheSameDefaultQuarter(): void
    {
        $plans = $this->createMock(PDOStatement::class);
        $plans->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn([]);
        $db = $this->createMock(PDO::class);
        $db->expects($this->once())->method('prepare')->willReturn($plans);
        $previousGet = $_GET;
        $_GET = ['nam' => '2026'];
        try {
            ob_start();
            $this->controller($db)->inThongKe();
            $html = ob_get_clean();
        } finally {
            $_GET = $previousGet;
        }

        $quarter = (int)ceil((int)date('n') / 3);
        $this->assertStringContainsString('Từ đầu năm đến hết Quý ' . $quarter . ' năm 2026', $html);
        $this->assertStringContainsString('Không có thiết bị phù hợp với bộ lọc.', $html);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @dataProvider departmentFilterValues
     */
    public function testStatisticsViewCarriesFiltersToPrintAndPdfLinks(string $department): void
    {
        $years = $this->createMock(PDOStatement::class);
        $years->method('fetchAll')->with(PDO::FETCH_COLUMN)->willReturn([2026]);
        $units = $this->createMock(PDOStatement::class);
        $units->expects($this->once())->method('execute')->with([':nam' => 2026, ':user_madv0' => 'KTKT']);
        $units->method('fetchAll')->with(PDO::FETCH_COLUMN)->willReturn(['KTKT', 'DVLTH', 'ĐVLTH', '0']);
        $plans = $this->createMock(PDOStatement::class);
        $plans->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn([]);

        $db = $this->createMock(PDO::class);
        $db->expects($this->once())->method('query')->willReturn($years);
        $db->expects($this->exactly(2))->method('prepare')->willReturnOnConsecutiveCalls($units, $plans);
        $previousGet = $_GET;
        $previousSession = $_SESSION ?? null;
        $_GET = [
            'nam' => '2026', 'qui' => '3', 'search' => 'AK&1',
            'nhomsc' => 'CNC+RDNGA', 'donvi_chinh' => $department,
        ];
        $_SESSION = [];
        try {
            ob_start();
            $this->controller($db)->thongke();
            $html = ob_get_clean();
        } finally {
            $_GET = $previousGet;
            if ($previousSession === null) {
                unset($_SESSION);
            } else {
                $_SESSION = $previousSession;
            }
        }

        $dom = new DOMDocument();
        $previousErrors = libxml_use_internal_errors(true);
        try {
            $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
            $xpath = new DOMXPath($dom);
            $options = $xpath->query('//select[@name="donvi_chinh"]/option[@value="' . $department . '"]');
            $this->assertCount(1, $options);
            $this->assertTrue($options->item(0)->hasAttribute('selected'));
            $badges = $xpath->query('//span[contains(., "Bộ phận chính:")]');
            $this->assertCount(1, $badges);
            $this->assertStringContainsString($department === '__none__' ? 'Chưa chọn' : $department, $badges->item(0)->textContent);

            $linksByAction = [];
            foreach ($xpath->query('//a[contains(@href, "action=")]') as $link) {
                parse_str((string)parse_url($link->getAttribute('href'), PHP_URL_QUERY), $params);
                $linksByAction[$params['action']][] = $params;
            }
            foreach (['inThongKe', 'exportPdf', 'exportPdfDanhSachQuy'] as $action) {
                $this->assertCount(1, $linksByAction[$action], $action);
                $params = $linksByAction[$action][0];
                $this->assertSame('2026', $params['nam']);
                $this->assertSame('3', $params['qui']);
                $this->assertSame('AK&1', $params['search']);
                $this->assertSame('CNC+RDNGA', $params['nhomsc']);
                $this->assertSame($department, $params['donvi_chinh']);
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
        }
    }

    public function departmentFilterValues(): array
    {
        return [['ĐVLTH'], ['0'], ['__none__']];
    }
}
