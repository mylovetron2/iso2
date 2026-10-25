<?php
declare(strict_types=1);

require_once __DIR__ . '/../controllers/KeHoachBaoDuongDinhKyController.php';

use PHPUnit\Framework\TestCase;

final class KeHoachDoiExecutionTest extends TestCase
{
    public function testExecutionUpdateWritesOnlyToLinkedTeamDossier(): void
    {
        $plan = $this->createMock(PDOStatement::class);
        $plan->expects($this->once())->method('execute')->with([
            ':id' => 17,
            ':user_madv0' => 'KTKT',
        ]);
        $plan->method('fetch')->willReturn([
            'hososcbd_id' => null,
            'hososcbd_doi_id' => 82,
            'nguon_hoso' => 'hososcbd_doi_iso',
            'donvi_lam_chinh' => 'KTKT',
            'nam' => 2026,
            'mavt' => 'VT-1',
            'somay' => 'SN-1',
            'model' => 'Model 1',
            'madv' => 'KTKT',
            'dossier_ngayth' => null,
            'dossier_ngaykt' => null,
        ]);

        $update = $this->createMock(PDOStatement::class);
        $update->expects($this->once())->method('execute')->with([
            ':ngayth' => '2026-10-01',
            ':ngaykt' => '2026-10-02',
            ':nguoi_thuc_hien' => 'Nguyễn Văn A',
            ':stt' => 82,
        ]);

        $db = $this->createMock(PDO::class);
        $db->expects($this->once())->method('beginTransaction');
        $db->expects($this->once())->method('commit');
        $db->expects($this->never())->method('rollBack');
        $queries = [];
        $db->expects($this->exactly(2))->method('prepare')->willReturnCallback(
            function (string $sql) use (&$queries, $plan, $update): PDOStatement {
                $queries[] = $sql;
                return count($queries) === 1 ? $plan : $update;
            }
        );

        $class = new ReflectionClass(KeHoachBaoDuongDinhKyController::class);
        $controller = $class->newInstanceWithoutConstructor();
        foreach (['db' => $db, 'userIsAdmin' => false, 'userMadv' => 'KTKT'] as $name => $value) {
            $property = $class->getProperty($name);
            $property->setAccessible(true);
            $property->setValue($controller, $value);
        }

        $method = $class->getMethod('saveDoiExecution');
        $method->setAccessible(true);
        $method->invoke($controller, 17, '2026-10-01', '2026-10-02', 'Nguyễn Văn A');

        $this->assertStringContainsString("k.nguon_hoso IS NULL OR k.nguon_hoso = 'hososcbd_doi_iso'", $queries[0]);
        $this->assertStringContainsString('INNER JOIN thietbi_iso t ON t.stt = k.thietbi_id', $queries[0]);
        $this->assertStringContainsString('t.stt = k.thietbi_id', $queries[0]);
        $this->assertStringNotContainsString('ke_hoach_bao_duong_dinh_ky_iso.thietbi_id', $queries[0]);
        $this->assertStringContainsString('UPDATE hososcbd_doi_iso', $queries[1]);
        $this->assertStringContainsString('SET ngayth = :ngayth, ngaykt = :ngaykt, nguoi_thuc_hien_iso = :nguoi_thuc_hien', $queries[1]);
        $this->assertStringNotContainsString('hososcbd_iso', $queries[1]);
    }

    public function testExecutionUpdateWithExistingDateQueuesReviewForNonAdmin(): void
    {
        $plan = $this->createMock(PDOStatement::class);
        $plan->expects($this->once())->method('execute')->with([
            ':id' => 17,
            ':user_madv0' => 'KTKT',
        ]);
        $plan->method('fetch')->willReturn([
            'hososcbd_id' => null,
            'hososcbd_doi_id' => 82,
            'nguon_hoso' => 'hososcbd_doi_iso',
            'donvi_lam_chinh' => 'KTKT',
            'nam' => 2026,
            'mavt' => 'VT-1',
            'somay' => 'SN-1',
            'model' => 'Model 1',
            'madv' => 'KTKT',
            'dossier_ngayth' => '2026-09-10',
            'dossier_ngaykt' => null,
        ]);

        $deletePending = $this->createMock(PDOStatement::class);
        $deletePending->expects($this->once())->method('execute')->with([
            ':stt' => 82,
            ':user_id' => 5,
        ]);
        $insertPending = $this->createMock(PDOStatement::class);
        $insertPending->expects($this->once())->method('execute')->with($this->callback(
            static function (array $params): bool {
                $data = json_decode($params[':data'], true);
                return $params[':stt'] === 82
                    && $params[':user_id'] === 5
                    && $params[':username'] === 'operator'
                    && $data === [
                        'ngayth' => '2026-10-01',
                        'ngaykt' => '2026-10-02',
                        'nguoi_thuc_hien_iso' => 'Nguyễn Văn A',
                    ];
            }
        ));

        $db = $this->createMock(PDO::class);
        $db->expects($this->once())->method('beginTransaction');
        $db->expects($this->once())->method('commit');
        $db->expects($this->never())->method('rollBack');
        $queries = [];
        $db->expects($this->exactly(3))->method('prepare')->willReturnCallback(
            function (string $sql) use (&$queries, $plan, $deletePending, $insertPending): PDOStatement {
                $queries[] = $sql;
                return match (count($queries)) {
                    1 => $plan,
                    2 => $deletePending,
                    3 => $insertPending,
                };
            }
        );

        $class = new ReflectionClass(KeHoachBaoDuongDinhKyController::class);
        $controller = $class->newInstanceWithoutConstructor();
        foreach (['db' => $db, 'userIsAdmin' => false, 'userMadv' => 'KTKT'] as $name => $value) {
            $property = $class->getProperty($name);
            $property->setAccessible(true);
            $property->setValue($controller, $value);
        }

        $previousSession = $_SESSION ?? null;
        $_SESSION = ['user_id' => 5, 'username' => 'operator'];
        try {
            $method = $class->getMethod('saveDoiExecution');
            $method->setAccessible(true);
            $this->assertTrue($method->invoke($controller, 17, '2026-10-01', '2026-10-02', 'Nguyễn Văn A'));
        } finally {
            if ($previousSession === null) {
                unset($_SESSION);
            } else {
                $_SESSION = $previousSession;
            }
        }

        $this->assertStringContainsString("LEFT JOIN hososcbd_doi_iso h ON h.stt = k.hososcbd_doi_id", $queries[0]);
        $this->assertStringContainsString("record_type = 'hososcbd_doi_iso'", $queries[1]);
        $this->assertStringContainsString("'hososcbd_doi_iso'", $queries[2]);
    }

    public function testUnlinkedPlanCreatesTeamDossierAndLinksItsSource(): void
    {
        $plan = $this->createMock(PDOStatement::class);
        $plan->expects($this->once())->method('execute')->with([':id' => 17]);
        $plan->method('fetch')->willReturn([
            'hososcbd_id' => null,
            'hososcbd_doi_id' => null,
            'nguon_hoso' => null,
            'donvi_lam_chinh' => 'KTKT',
            'nam' => 2026,
            'mavt' => 'VT-1',
            'somay' => 'SN-1',
            'model' => 'Model 1',
            'madv' => 'KTKT',
        ]);

        $insert = $this->createMock(PDOStatement::class);
        $insert->expects($this->once())->method('execute')->with([
            ':maql' => '2026-10-01-KTKT-DOI-2026-00000017',
            ':phieu' => 'DOI-2026-00000017',
            ':hoso' => 'DOI-2026-00000017-1',
            ':ngayyc' => '2026-10-01',
            ':madv' => 'KTKT',
            ':cv' => 'BDDK',
            ':mavt' => 'VT-1',
            ':somay' => 'SN-1',
            ':model' => 'Model 1',
            ':ngayth' => '2026-10-01',
            ':ngaykt' => null,
            ':nguoi_thuc_hien' => 'Nguyễn Văn A',
        ]);

        $link = $this->createMock(PDOStatement::class);
        $link->expects($this->once())->method('execute')->with([':dossier_id' => 82, ':plan_id' => 17]);
        $link->expects($this->once())->method('rowCount')->willReturn(1);

        $db = $this->createMock(PDO::class);
        $db->expects($this->once())->method('beginTransaction');
        $db->expects($this->once())->method('commit');
        $db->expects($this->never())->method('rollBack');
        $db->expects($this->once())->method('lastInsertId')->willReturn('82');
        $queries = [];
        $db->expects($this->exactly(3))->method('prepare')->willReturnCallback(
            function (string $sql) use (&$queries, $plan, $insert, $link): PDOStatement {
                $queries[] = $sql;
                return match (count($queries)) {
                    1 => $plan,
                    2 => $insert,
                    3 => $link,
                };
            }
        );

        $class = new ReflectionClass(KeHoachBaoDuongDinhKyController::class);
        $controller = $class->newInstanceWithoutConstructor();
        foreach (['db' => $db, 'userIsAdmin' => true] as $name => $value) {
            $property = $class->getProperty($name);
            $property->setAccessible(true);
            $property->setValue($controller, $value);
        }

        $method = $class->getMethod('saveDoiExecution');
        $method->setAccessible(true);
        $method->invoke($controller, 17, '2026-10-01', null, 'Nguyễn Văn A');

        $this->assertStringContainsString('INSERT INTO hososcbd_doi_iso', $queries[1]);
        $this->assertStringContainsString("SET hososcbd_doi_id = :dossier_id, nguon_hoso = 'hososcbd_doi_iso'", $queries[2]);
    }
}
