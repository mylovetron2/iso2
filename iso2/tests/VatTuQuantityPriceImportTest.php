<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/vattu_import_quantity_price.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\TestCase;

final class VatTuQuantityPriceImportTest extends TestCase
{
    private function database(): PDO
    {
        $db = new PDO('sqlite::memory:');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec('CREATE TABLE vattu_thanh_ly_iso (stt INTEGER PRIMARY KEY, mavattu TEXT, soluong_conlai REAL, dongia_usd REAL)');
        $db->exec("INSERT INTO vattu_thanh_ly_iso VALUES (1, '003.001.0104', 1, 0)");
        return $db;
    }

    private function workbook(): Spreadsheet
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setCellValue('A1', 'Mã vật Tư');
        $sheet->setCellValue('B1', 'Số lượng');
        $sheet->setCellValue('C1', 'Đơn giá USD');
        return $book;
    }

    public function testExcelValuesOverwriteAndBlankCellsKeepExistingValues(): void
    {
        $db = $this->database();
        $book = $this->workbook();
        $sheet = $book->getActiveSheet();
        $sheet->setCellValue('A2', '003.001.0104');
        $sheet->setCellValue('B2', 5.5);
        $sheet->setCellValue('C2', 0);
        $sheet->setCellValue('A3', 'not-found');
        $sheet->setCellValue('B3', 8);

        $path = tempnam(sys_get_temp_dir(), 'vattu_quantity_price_test_');
        try {
            (new Xlsx($book))->save($path);
            $loaded = IOFactory::load($path);
            $rows = vattuQuantityPriceReadRows($loaded->getActiveSheet());
            $preview = vattuQuantityPricePreview($db, $rows);
            $this->assertSame('update', $preview[0]['status']);
            $this->assertSame(['soluong_conlai'], array_keys($preview[0]['changes']));
            $this->assertSame('not_found', $preview[1]['status']);
            $this->assertSame(1, vattuQuantityPriceApply($db, $preview));
            $result = $db->query('SELECT soluong_conlai, dongia_usd FROM vattu_thanh_ly_iso')->fetch(PDO::FETCH_ASSOC);
            $this->assertSame(5.5, (float)$result['soluong_conlai']);
            $this->assertSame(0.0, (float)$result['dongia_usd']);
            $loaded->disconnectWorksheets();
        } finally {
            $book->disconnectWorksheets();
            if ($path !== false && is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testDuplicateCodesAndFormulaValuesAreSkipped(): void
    {
        $db = $this->database();
        $book = $this->workbook();
        $sheet = $book->getActiveSheet();
        $sheet->setCellValue('A2', '003.001.0104');
        $sheet->setCellValue('B2', 2);
        $sheet->setCellValue('A3', '003.001.0104');
        $sheet->setCellValue('B3', 3);
        $sheet->setCellValue('A4', 'formula');
        $sheet->setCellValue('B4', '=1+2');
        $preview = vattuQuantityPricePreview($db, vattuQuantityPriceReadRows($sheet));
        $this->assertSame(['duplicate_file', 'duplicate_file', 'invalid'], array_column($preview, 'status'));
        $this->assertSame(0, vattuQuantityPriceApply($db, $preview));
        $book->disconnectWorksheets();
    }

    public function testConcurrentDatabaseChangeIsNotOverwritten(): void
    {
        $db = $this->database();
        $book = $this->workbook();
        $book->getActiveSheet()->setCellValue('A2', '003.001.0104');
        $book->getActiveSheet()->setCellValue('B2', 9);
        $preview = vattuQuantityPricePreview($db, vattuQuantityPriceReadRows($book->getActiveSheet()));
        $db->exec('UPDATE vattu_thanh_ly_iso SET soluong_conlai = 4 WHERE stt = 1');
        $this->expectException(RuntimeException::class);
        vattuQuantityPriceApply($db, $preview);
    }
}
