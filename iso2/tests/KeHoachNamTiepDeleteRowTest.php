<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class KeHoachNamTiepDeleteRowTest extends TestCase
{
    public function testDeleteActionRequiresPostAndAdminUsername(): void
    {
        $entry = file_get_contents(__DIR__ . '/../kehoachbaoduong2027.php');

        self::assertStringContainsString("case 'deleteRow':", $entry);
        self::assertStringContainsString("\$_SERVER['REQUEST_METHOD'] !== 'POST'", $entry);
        self::assertStringContainsString("(\$_SESSION['username'] ?? '') !== 'admin'", $entry);
        self::assertStringContainsString('$controller->deleteRow();', $entry);
    }

    public function testDeleteButtonIsOnlyRenderedForAdminUsername(): void
    {
        $view = file_get_contents(__DIR__ . '/../views/kehoachbaoduongdinhky/ke_hoach_nam_tiep.php');

        self::assertStringContainsString("\$canDelete = (\$_SESSION['username'] ?? '') === 'admin';", $view);
        self::assertStringContainsString('if ($canDelete):', $view);
        self::assertStringContainsString('class="delete-plan-row', $view);
        self::assertStringContainsString("post('deleteRow'", $view);
    }

    public function testDeletionIsScopedAndRefusesPlansWithExecutionDetails(): void
    {
        $controller = file_get_contents(__DIR__ . '/../controllers/KeHoachBaoDuongNamTiepController.php');

        self::assertStringContainsString("' FOR UPDATE'", $controller);
        self::assertStringContainsString('$this->applyDonViFilter($sql, $params);', $controller);
        self::assertStringContainsString('baoduong_dinhky_chitiet_iso WHERE kehoach_id = :id', $controller);
        self::assertStringContainsString('DELETE FROM ke_hoach_bao_duong_dinh_ky_iso WHERE id = :id AND nam = :nam', $controller);
        self::assertStringContainsString("logBaoDuongActivity(\$this->db, 'DELETE'", $controller);
    }
}
