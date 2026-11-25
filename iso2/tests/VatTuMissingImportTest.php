<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/vattu_import_missing.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\TestCase;

final class VatTuMissingImportTest extends TestCase
{
    private function database(): PDO
    {
        $db = new PDO('sqlite::memory:');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec('CREATE TABLE phanloai_vattu_thanh_ly_iso (id INTEGER, ma_phanloai TEXT, ten_phanloai TEXT)');
        $db->exec("INSERT INTO phanloai_vattu_thanh_ly_iso VALUES (2, 'CCDC', 'Công cụ dụng cụ')");
        $db->exec('CREATE TABLE vattu_thanh_ly_iso (stt INTEGER PRIMARY KEY, mavattu TEXT, vi_tri_sap_xep INTEGER, ten_tienganh TEXT, dactinhkt_tiengnga TEXT, dactinhkt_tiengviet TEXT, dvt_tiengnga TEXT, dvt_tiengviet TEXT, phanloai_id INTEGER)');
        $db->exec("INSERT INTO vattu_thanh_ly_iso VALUES (1, '050.002.00506', 138, NULL, NULL, 'Có sẵn', '', '0', NULL)");
        return $db;
    }

    private function workbook(): Spreadsheet
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setCellValue('A1', 'Mã vật tư');
        foreach (VATTU_MISSING_COLUMNS as [$column, $label]) {
            $sheet->setCellValue($column . '1', $label);
        }
        return $book;
    }

    public function testRealWorkbookOnlyFillsMissingValues(): void
    {
        $db = $this->database();
        $book = $this->workbook();
        $sheet = $book->getActiveSheet();
        $sheet->setCellValue('A2', '050.002.00506');
        $sheet->setCellValue('C2', 'Assorted Bit Set');
        $sheet->setCellValue('D2', "Набор\n• Описание");
        $sheet->setCellValue('E2', 'Không ghi đè');
        $sheet->setCellValue('F2', 'Шт.');
        $sheet->setCellValue('G2', 'Cái');
        $sheet->setCellValue('H2', 'Công cụ dụng cụ');
        $sheet->setCellValue('A3', 'khong-ton-tai');
        $sheet->setCellValue('D3', 'Bỏ qua');
        $path = tempnam(sys_get_temp_dir(), 'vattu_test_');
        try {
            (new Xlsx($book))->save($path);
            $loaded = IOFactory::load($path);
            $preview = vattuMissingPreview($db, vattuMissingReadRows($loaded->getActiveSheet()));
            $this->assertSame('update', $preview[0]['status']);
            $this->assertSame('not_found', $preview[1]['status']);
            $this->assertSame(['ten_tienganh', 'dactinhkt_tiengnga', 'dvt_tiengnga', 'phanloai_id'], array_keys($preview[0]['changes']));
            $this->assertSame(1, vattuMissingApply($db, $preview));
            $result = $db->query('SELECT * FROM vattu_thanh_ly_iso')->fetch(PDO::FETCH_ASSOC);
            $this->assertSame('Assorted Bit Set', $result['ten_tienganh']);
            $this->assertSame("Набор\n• Описание", $result['dactinhkt_tiengnga']);
            $this->assertSame('Có sẵn', $result['dactinhkt_tiengviet']);
            $this->assertSame('Шт.', $result['dvt_tiengnga']);
            $this->assertSame('0', $result['dvt_tiengviet']);
            $this->assertSame(2, (int)$result['phanloai_id']);
            $this->assertSame('no_change', vattuMissingPreview($db, vattuMissingReadRows($loaded->getActiveSheet()))[0]['status']);
            $loaded->disconnectWorksheets();
        } finally {
            $book->disconnectWorksheets();
            if ($path !== false && is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testDuplicatesAndInvalidCategoryAreReportedWithoutOverwriting(): void
    {
        $db = $this->database();
        $book = $this->workbook();
        $sheet = $book->getActiveSheet();
        $sheet->setCellValue('A2', '050.002.00506');
        $sheet->setCellValue('D2', 'Русский');
        $sheet->setCellValue('H2', 'Không có');
        $rows = vattuMissingReadRows($sheet);
        $preview = vattuMissingPreview($db, $rows);
        $this->assertSame('update', $preview[0]['status']);
        $this->assertArrayNotHasKey('phanloai_id', $preview[0]['changes']);
        $this->assertStringContainsString('Phân loại không tồn tại', $preview[0]['message']);
        $sheet->setCellValue('A3', '050.002.00506');
        $this->assertSame(['duplicate_file', 'duplicate_file'], array_column(vattuMissingPreview($db, vattuMissingReadRows($sheet)), 'status'));
        $db->exec("INSERT INTO vattu_thanh_ly_iso (stt, mavattu) VALUES (2, '050.002.00506')");
        $this->assertSame('duplicate_db', vattuMissingPreview($db, $rows)[0]['status']);
        $book->disconnectWorksheets();
    }

    public function testChangedValueCannotBeOverwrittenAfterPreview(): void
    {
        $db = $this->database();
        $book = $this->workbook();
        $book->getActiveSheet()->setCellValue('A2', '050.002.00506');
        $book->getActiveSheet()->setCellValue('D2', 'Русский');
        $preview = vattuMissingPreview($db, vattuMissingReadRows($book->getActiveSheet()));
        $db->exec("UPDATE vattu_thanh_ly_iso SET dactinhkt_tiengnga = 'Đã thay đổi' WHERE stt = 1");
        $this->expectException(RuntimeException::class);
        vattuMissingApply($db, $preview);
    }

    public function testExistingCategoryIsUpdatedAndFormulaIsRejected(): void
    {
        $db = $this->database();
        $db->exec('UPDATE vattu_thanh_ly_iso SET phanloai_id = 1 WHERE stt = 1');
        $book = $this->workbook();
        $sheet = $book->getActiveSheet();
        $sheet->setCellValue('A2', '050.002.00506');
        $sheet->setCellValue('D2', 'Русский');
        $sheet->setCellValue('H2', 'CCDC');
        $sheet->setCellValue('A3', 'khac');
        $sheet->setCellValue('D3', '=1+1');
        $preview = vattuMissingPreview($db, vattuMissingReadRows($sheet));
        $this->assertSame(2, $preview[0]['changes']['phanloai_id']['value']);
        $this->assertSame('invalid', $preview[1]['status']);
        $this->assertStringContainsString('công thức', $preview[1]['message']);
        $this->assertSame(1, vattuMissingApply($db, $preview));
        $this->assertSame(2, (int)$db->query('SELECT phanloai_id FROM vattu_thanh_ly_iso')->fetchColumn());
        $this->assertArrayNotHasKey('phanloai_id', vattuMissingPreview($db, vattuMissingReadRows($sheet))[0]['changes']);
        $book->disconnectWorksheets();
    }

    public function testBlankOrInvalidCategoryKeepsExistingValue(): void
    {
        $db = $this->database();
        $db->exec('UPDATE vattu_thanh_ly_iso SET phanloai_id = 1 WHERE stt = 1');
        $book = $this->workbook();
        $sheet = $book->getActiveSheet();
        $sheet->setCellValue('A2', '050.002.00506');
        $sheet->setCellValue('D2', 'Русский');
        $sheet->setCellValue('H2', 'Không có');
        $preview = vattuMissingPreview($db, vattuMissingReadRows($sheet));
        $this->assertArrayNotHasKey('phanloai_id', $preview[0]['changes']);
        $this->assertStringContainsString('Phân loại không tồn tại', $preview[0]['message']);
        $sheet->setCellValue('H2', '');
        $preview = vattuMissingPreview($db, vattuMissingReadRows($sheet));
        $this->assertArrayNotHasKey('phanloai_id', $preview[0]['changes']);
        $this->assertSame(1, vattuMissingApply($db, $preview));
        $this->assertSame(1, (int)$db->query('SELECT phanloai_id FROM vattu_thanh_ly_iso')->fetchColumn());
        $book->disconnectWorksheets();
    }

    public function testEnglishNameAlreadyPresentIsNotOverwritten(): void
    {
        $db = $this->database();
        $db->exec("UPDATE vattu_thanh_ly_iso SET ten_tienganh = 'Existing name' WHERE stt = 1");
        $book = $this->workbook();
        $sheet = $book->getActiveSheet();
        $sheet->setCellValue('A2', '050.002.00506');
        $sheet->setCellValue('C2', 'New name');
        $preview = vattuMissingPreview($db, vattuMissingReadRows($sheet));
        $this->assertSame('no_change', $preview[0]['status']);
        $this->assertArrayNotHasKey('ten_tienganh', $preview[0]['changes']);
        $this->assertSame(0, vattuMissingApply($db, $preview));
        $this->assertSame('Existing name', $db->query('SELECT ten_tienganh FROM vattu_thanh_ly_iso')->fetchColumn());
        $book->disconnectWorksheets();
    }

    public function testCategoryRequiresPositionAtLeast138WhileOtherFieldsStillUpdate(): void
    {
        $db = $this->database();
        $db->exec('UPDATE vattu_thanh_ly_iso SET vi_tri_sap_xep = 137, phanloai_id = 1 WHERE stt = 1');
        $book = $this->workbook();
        $sheet = $book->getActiveSheet();
        $sheet->setCellValue('A2', '050.002.00506');
        $sheet->setCellValue('C2', 'New name');
        $sheet->setCellValue('H2', 'CCDC');
        $rows = vattuMissingReadRows($sheet);
        $preview = vattuMissingPreview($db, $rows);
        $this->assertSame('update', $preview[0]['status']);
        $this->assertArrayNotHasKey('phanloai_id', $preview[0]['changes']);
        $this->assertStringContainsString('138', $preview[0]['message']);
        $this->assertSame(1, vattuMissingApply($db, $preview));
        $this->assertSame(1, (int)$db->query('SELECT phanloai_id FROM vattu_thanh_ly_iso')->fetchColumn());
        $this->assertSame('New name', $db->query('SELECT ten_tienganh FROM vattu_thanh_ly_iso')->fetchColumn());

        $db->exec('UPDATE vattu_thanh_ly_iso SET vi_tri_sap_xep = 138 WHERE stt = 1');
        $preview = vattuMissingPreview($db, $rows);
        $this->assertSame(2, $preview[0]['changes']['phanloai_id']['value']);
        $db->exec('UPDATE vattu_thanh_ly_iso SET vi_tri_sap_xep = 137 WHERE stt = 1');
        $this->expectException(RuntimeException::class);
        vattuMissingApply($db, $preview);
    }

    public function testMissingPositionDoesNotPermitCategoryUpdate(): void
    {
        $db = $this->database();
        $db->exec('UPDATE vattu_thanh_ly_iso SET vi_tri_sap_xep = NULL WHERE stt = 1');
        $book = $this->workbook();
        $sheet = $book->getActiveSheet();
        $sheet->setCellValue('A2', '050.002.00506');
        $sheet->setCellValue('H2', 'CCDC');
        $preview = vattuMissingPreview($db, vattuMissingReadRows($sheet));
        $this->assertSame('no_change', $preview[0]['status']);
        $this->assertStringContainsString('138', $preview[0]['message']);
        $book->disconnectWorksheets();
    }
}
