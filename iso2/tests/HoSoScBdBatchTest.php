<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/HoSoSCBD.php';

use PHPUnit\Framework\TestCase;

final class HoSoScBdBatchTest extends TestCase
{
    /**
     * @dataProvider batchProvider
     */
    public function testBatchHandlesMissingDeviceLinks(
        array $items,
        array $lookupRows,
        array $hckdRows,
        array $bddkRows,
        array $expected,
        ?string $expectedDeviceIds,
        ?string $expectedHckdIds
    ): void {
        $model = $this->getMockBuilder(HoSoSCBD::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['query'])
            ->getMock();
        $queries = [];
        $model->method('query')->willReturnCallback(function (string $sql) use (
            &$queries, $lookupRows, $hckdRows, $bddkRows
        ): PDOStatement {
            $this->assertDoesNotMatchRegularExpression('/IN\s*\(\s*\)/i', $sql);
            $queries[] = $sql;
            if (str_contains($sql, 'FROM thietbi_iso t')) {
                $rows = $lookupRows;
            } elseif (str_contains($sql, 'FROM thietbihckd_iso')) {
                $rows = $hckdRows;
            } elseif (str_contains($sql, 'FROM ke_hoach_bao_duong_dinh_ky_iso')) {
                $rows = $bddkRows;
            } elseif (str_contains($sql, 'FROM kehoach_kiemdinh_2026_iso')) {
                $rows = [['stt' => 9, 'planned_months' => '3', 'planned_months_dot2' => '9']];
            } elseif (str_contains($sql, 'FROM hosohckd_iso')) {
                $rows = [['thietbi_stt' => 9, 'inspected_months' => '3']];
            } else {
                $this->fail('Unexpected query: ' . $sql);
            }
            $statement = $this->createMock(PDOStatement::class);
            $statement->method('fetchAll')->willReturn($rows);
            return $statement;
        });

        $this->assertSame($expected, $model->getBddkHckdBatch($items));

        $bddkQueries = array_values(array_filter($queries, static function (string $sql): bool {
            return str_contains($sql, 'FROM ke_hoach_bao_duong_dinh_ky_iso');
        }));
        $lookupQueries = array_values(array_filter($queries, static function (string $sql): bool {
            return str_contains($sql, 'FROM thietbi_iso t');
        }));
        if ($expectedDeviceIds === null) {
            $this->assertSame([], $bddkQueries);
            $this->assertSame([], $lookupQueries);
        } else {
            $this->assertCount(1, $bddkQueries);
            $this->assertCount(1, $lookupQueries);
            $this->assertStringContainsString("IN ($expectedDeviceIds)", $bddkQueries[0]);
            $this->assertStringContainsString("IN ($expectedDeviceIds)", $lookupQueries[0]);
        }
        $hcQueries = array_values(array_filter($queries, static function (string $sql): bool {
            return str_contains($sql, 'FROM kehoach_kiemdinh_2026_iso')
                || str_contains($sql, 'FROM hosohckd_iso');
        }));
        if ($expectedHckdIds === null) {
            $this->assertSame([], $hcQueries);
        } else {
            $this->assertCount(2, $hcQueries);
            foreach ($hcQueries as $sql) {
                $this->assertStringContainsString("IN ($expectedHckdIds)", $sql);
            }
        }
        if ($items === []) {
            $this->assertSame([], $queries);
        }
    }

    public function batchProvider(): array
    {
        $empty = [
            'bddk_quarters' => [],
            'planned_months' => '',
            'planned_months_dot2' => '',
            'inspected_months' => '',
            'thckd_stt' => 0,
            'thckd_mavattu' => '',
        ];
        $hckd = [
            'bddk_quarters' => [],
            'planned_months' => '3',
            'planned_months_dot2' => '9',
            'inspected_months' => '3',
            'thckd_stt' => 9,
            'thckd_mavattu' => 'VT1',
        ];
        $linked = $hckd;
        $linked['bddk_quarters'] = [
            ['quarter' => 'Q1', 'completed' => true],
            ['quarter' => 'Q2', 'completed' => false],
        ];
        $unlinkedItem = ['stt' => 1, 'thietbi_stt' => null, 'mavt' => 'VT1', 'somay' => 'SN1', 'ngayyc' => '2026-10-01'];
        $linkedItem = ['stt' => 2, 'thietbi_stt' => 7, 'mavt' => 'VT1', 'somay' => 'SN1', 'ngayyc' => '2026-10-01'];
        $hckdRows = [['stt' => 9, 'mavattu' => 'VT1', 'somay' => 'SN1']];
        $lookupRows = [['thietbi_stt' => 7, 'thckd_stt' => 9, 'thckd_mavattu' => 'VT1']];
        $bddkRows = [['thietbi_id' => 7, 'nam' => 2026, 'bddk_quarters_raw' => 'Q1:1,Q2:0,']];

        return [
            'empty search results' => [[], [], [], [], [], null, null],
            'only unlinked devices' => [[$unlinkedItem], [], [], [], [1 => $empty], null, null],
            'missing device key' => [[['stt' => 1]], [], [], [], [1 => $empty], null, null],
            'unlinked device with HC fallback' => [[$unlinkedItem], [], $hckdRows, [], [1 => $hckd], null, '9'],
            'linked device' => [[$linkedItem], $lookupRows, $hckdRows, $bddkRows, [2 => $linked], '7', '9'],
            'mixed linked and unlinked devices' => [
                [$unlinkedItem, $linkedItem], $lookupRows, $hckdRows, $bddkRows,
                [1 => $hckd, 2 => $linked], '7', '9',
            ],
            'duplicate device links' => [
                [$linkedItem, array_merge($linkedItem, ['stt' => 3])], $lookupRows, $hckdRows, $bddkRows,
                [2 => $linked, 3 => $linked], '7', '9',
            ],
        ];
    }
}
