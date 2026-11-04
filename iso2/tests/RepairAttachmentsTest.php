<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/repair_attachments.php';

use PHPUnit\Framework\TestCase;

final class RepairAttachmentsTest extends TestCase
{
    private string $root;
    private string $name = '1793760000_0123456789abcdef_report.pdf';
    private string $pdf = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n";

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'repair_test_' . bin2hex(random_bytes(8));
        mkdir($this->root);
        mkdir($this->root . DIRECTORY_SEPARATOR . '41');
        file_put_contents($this->root . DIRECTORY_SEPARATOR . '41' . DIRECTORY_SEPARATOR . $this->name, $this->pdf);
    }

    protected function tearDown(): void
    {
        foreach (scandir($this->root . DIRECTORY_SEPARATOR . '41') as $name) {
            if ($name !== '.' && $name !== '..') {
                unlink($this->root . DIRECTORY_SEPARATOR . '41' . DIRECTORY_SEPARATOR . $name);
            }
        }
        rmdir($this->root . DIRECTORY_SEPARATOR . '41');
        rmdir($this->root);
    }

    public function testListsOnlyFilesOfCurrentDossierAndDeletesExactFile(): void
    {
        $store = new RepairAttachments($this->root, 41);
        $this->assertSame([[
            'name' => $this->name, 'original' => 'report.pdf',
            'is_pdf' => true, 'size' => strlen($this->pdf),
        ]], $store->listFiles());
        $this->assertSame([], (new RepairAttachments($this->root, 42))->listFiles());
        $this->assertSame('application/pdf', $store->mimeType($this->name));
        $this->assertSame($this->pdf, file_get_contents($store->filePath($this->name)));
        $store->delete($this->name);
        $this->assertSame([], $store->listFiles());
    }

    public function testCannotReadFileFromDifferentDossier(): void
    {
        $this->expectException(RuntimeException::class);
        (new RepairAttachments($this->root, 42))->filePath($this->name);
    }

    public function testRejectsTraversal(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new RepairAttachments($this->root, 41))->delete('..' . DIRECTORY_SEPARATOR . $this->name);
    }

    public function testValidatesPdfWithoutModifyingIt(): void
    {
        $store = new RepairAttachments($this->root, 41);
        $path = $store->filePath($this->name);
        $this->assertSame('pdf', $store->validateUpload([
            'name' => 'document.PDF', 'tmp_name' => $path,
            'size' => strlen($this->pdf), 'error' => UPLOAD_ERR_OK,
        ]));
        $this->assertSame($this->pdf, file_get_contents($path));
    }

    /**
     * @dataProvider invalidUploads
     */
    public function testRejectsInvalidUploads(array $overrides): void
    {
        $store = new RepairAttachments($this->root, 41);
        $file = [
            'name' => 'report.pdf', 'tmp_name' => $store->filePath($this->name),
            'size' => strlen($this->pdf), 'error' => UPLOAD_ERR_OK,
        ];
        $this->expectException(InvalidArgumentException::class);
        $store->validateUpload(array_replace($file, $overrides));
    }

    public static function invalidUploads(): array
    {
        return [
            'oversized' => [['size' => RepairAttachments::MAX_SIZE + 1]],
            'empty' => [['size' => 0]],
            'upload error' => [['error' => UPLOAD_ERR_INI_SIZE]],
            'unsupported extension' => [['name' => 'report.php']],
            'wrong MIME' => [['name' => 'report.jpg']],
            'malformed upload' => [['name' => ['report.pdf']]],
        ];
    }

    /**
     * @dataProvider editPermissions
     */
    public function testEditPermissions(array $item, bool $admin, bool $edit, bool $expected): void
    {
        $this->assertSame($expected, canEditRepairAttachments($item, $admin, $edit));
    }

    public static function editPermissions(): array
    {
        return [
            'ongoing editor' => [['ngaykt' => '0000-00-00'], false, true, true],
            'empty end date editor' => [['ngaykt' => null], false, true, true],
            'viewer' => [['ngaykt' => null], false, false, false],
            'completed editor' => [['ngaykt' => '2026-11-04'], false, true, false],
            'completed admin' => [['ngaykt' => '2026-11-04'], true, false, true],
        ];
    }

    public function testUploadApiIsHandledBeforeRepairUpdateEvenWhenPostBodyIsEmpty(): void
    {
        $view = file_get_contents(__DIR__ . '/../views/hososcbd/repair_details.php');
        $attachmentRoute = strpos($view, 'if ($isAttachmentPost)');
        $repairUpdate = strpos($view, '$model->update($stt, $data)');
        $this->assertNotFalse($attachmentRoute);
        $this->assertLessThan($repairUpdate, $attachmentRoute);
        $this->assertStringContainsString("isset(\$_GET['repair_attachment_api'])", $view);
        $this->assertStringContainsString("url.searchParams.set('repair_attachment_api', '1')", $view);
        $this->assertStringContainsString("hash_equals(\$_SESSION['repair_attachment_token']", $view);
        $entry = file_get_contents(__DIR__ . '/../hososcbd_repair_details.php');
        $this->assertStringContainsString('requireAuth();', $entry);
        $this->assertStringContainsString("requirePermission('hososcbd.view');", $entry);
    }
}
