<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/constants.php';

use PHPUnit\Framework\TestCase;

final class DeviceHistoryAttachmentsTest extends TestCase
{
    private function render(int $id, array $attachments): string
    {
        $historyDossierId = $id;
        $historyAttachments = $attachments;
        ob_start();
        try {
            require __DIR__ . '/../views/thietbi/history_attachments.php';
            return ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }

    public function testShowsImagesAndPdfsForOnlyTheMatchingDossier(): void
    {
        $html = $this->render(41, [
            41 => [
                ['name' => '1793760000_0123456789abcdef_photo.jpg', 'original' => 'photo.jpg', 'is_pdf' => false],
                ['name' => '1793760000_0123456789abcdef_report.pdf', 'original' => '<report>.pdf', 'is_pdf' => true],
            ],
            42 => [
                ['name' => 'other.jpg', 'original' => 'other.jpg', 'is_pdf' => false],
            ],
        ]);
        $this->assertStringContainsString('Ảnh &amp; PDF (2)', $html);
        $this->assertStringContainsString('id=41&amp;repair_attachment=', $html);
        $this->assertStringContainsString(appUrl('hososcbd_repair_details.php'), $html);
        $this->assertSame(1, substr_count($html, '<img '));
        $this->assertStringContainsString('PDF: &lt;report&gt;.pdf', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertStringNotContainsString('other.jpg', $html);
        $this->assertStringNotContainsString('<report>', $html);
        $this->assertStringNotContainsString('<button', $html);
    }

    public function testNoFilesDoesNotAddAnEmptySection(): void
    {
        $this->assertSame('', trim($this->render(41, [])));
    }

    public function testBothHistoryTabsUseTheSameAttachmentView(): void
    {
        $view = file_get_contents(__DIR__ . '/../views/thietbi/view.php');
        $this->assertSame(2, substr_count($view, "require __DIR__ . '/history_attachments.php';"));
        $this->assertStringContainsString("\$historyDossierId = (int)(\$ls['stt'] ?? 0)", $view);
        $this->assertStringContainsString("\$historyDossierId = (int)(\$ld['stt'] ?? 0)", $view);
        $controller = file_get_contents(__DIR__ . '/../controllers/ThietBiController.php');
        $this->assertStringContainsString("\$canViewHistoryAttachments = hasPermission('hososcbd.view');", $controller);
        $this->assertStringContainsString('array_merge($lichSuSuaChua, $lichSuKiemDinh)', $controller);
    }
}
