<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class MachineHistoryPageTest extends TestCase
{
    public function testHistoryEntryPointRequiresViewPermission(): void
    {
        $entryPoint = file_get_contents(__DIR__ . '/../hososcbd_machine_history.php');

        $this->assertStringContainsString('requireAuth();', $entryPoint);
        $this->assertStringContainsString("requirePermission('hososcbd.view');", $entryPoint);
        $this->assertStringContainsString('$model->getAll(', $entryPoint);
        $this->assertStringContainsString('$model->count(', $entryPoint);
        $this->assertStringContainsString("foreach (\$contents as \$recordIdValue => \$content)", $entryPoint);
        $this->assertStringNotContainsString('getMachineHistory(', $entryPoint);
        $this->assertStringContainsString("AND ngayth >= ?", $entryPoint);
        $this->assertStringContainsString("AND ngayth <= ?", $entryPoint);
    }

    public function testHistoryViewShowsRequestedFieldsAndSafeMachineText(): void
    {
        $view = file_get_contents(__DIR__ . '/../views/hososcbd/machine_history.php');

        foreach (['hoso', 'ngayth', 'ngaykt', 'honghoc', 'khacphuc', 'noidung'] as $field) {
            $this->assertStringContainsString("historyItem['{$field}']", $view);
        }
        $this->assertStringContainsString("displayText(\$historyItem['honghoc']", $view);
        $this->assertStringContainsString('history_page', $view);
        $this->assertStringContainsString("historyItem['mavt']", $view);
        $this->assertStringContainsString("historyItem['somay']", $view);
        $this->assertStringContainsString('name="ngayth_from"', $view);
        $this->assertStringContainsString('name="ngayth_to"', $view);
        $this->assertStringNotContainsString('>Số phiếu<', $view);
        $this->assertStringContainsString('name="noidung[', $view);
        $this->assertStringContainsString('Lưu tất cả', $view);
        $this->assertStringContainsString('machine_history_token', $view);
        $this->assertStringContainsString('name="scroll_top"', $view);
        $this->assertStringContainsString('window.scrollTo(0, scrollTop)', $view);
    }

    public function testRepairDetailsLinksToMachineHistory(): void
    {
        $view = file_get_contents(__DIR__ . '/../views/hososcbd/repair_details.php');

        $this->assertStringContainsString('href="hososcbd_machine_history.php', $view);
    }

    public function testAdminMenuLinksToMachineHistory(): void
    {
        $header = file_get_contents(__DIR__ . '/../views/layouts/header.php');

        $this->assertStringContainsString("appUrl('hososcbd_machine_history.php')", $header);
        $this->assertStringContainsString('Danh sách hồ sơ SC/BD', $header);
    }
}
