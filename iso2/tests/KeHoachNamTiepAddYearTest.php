<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../controllers/KeHoachBaoDuongNamTiepController.php';

final class KeHoachNamTiepAddYearTest extends TestCase
{
    private function parseManufacturingYear(mixed $value): ?string
    {
        $controller = (new ReflectionClass(KeHoachBaoDuongNamTiepController::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(KeHoachBaoDuongNamTiepController::class, 'parseManufacturingYear');
        $method->setAccessible(true);

        return $method->invoke($controller, $value);
    }

    public function testManufacturingYearIsOptionalAndConvertedToDatabaseDate(): void
    {
        self::assertNull($this->parseManufacturingYear(null));
        self::assertNull($this->parseManufacturingYear(''));
        self::assertSame('2020-01-01', $this->parseManufacturingYear('2020'));
    }

    public function testManufacturingYearMustContainFourDigits(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->parseManufacturingYear('20x0');
    }

    public function testNewRowSendsYearAndPersistsItInNgayKtsd(): void
    {
        $view = file_get_contents(__DIR__ . '/../views/kehoachbaoduongdinhky/ke_hoach_nam_tiep.php');
        $controller = file_get_contents(__DIR__ . '/../controllers/KeHoachBaoDuongNamTiepController.php');

        self::assertStringContainsString('id="new-year"', $view);
        self::assertStringContainsString('nam_sx: yearInput.value.trim()', $view);
        self::assertStringContainsString('UPDATE thietbi_iso SET ngayktsd = :ngayktsd WHERE stt = :stt', $controller);
        self::assertStringContainsString("':ngayktsd' => \$ngayKtsd", $controller);
    }

    public function testNewlyAddedPlanIsAddressableAndScrolledIntoView(): void
    {
        $view = file_get_contents(__DIR__ . '/../views/kehoachbaoduongdinhky/ke_hoach_nam_tiep.php');

        self::assertStringContainsString('id="plan-row-<?php echo (int)$plan[\'id\']; ?>"', $view);
        self::assertStringContainsString("url.hash = 'plan-row-' + data.plan_id", $view);
        self::assertStringContainsString("url.searchParams.delete('search')", $view);
        self::assertStringContainsString("url.searchParams.delete('trangthai')", $view);
        self::assertStringContainsString("newPlanRow.scrollIntoView({ behavior: 'smooth', block: 'center' })", $view);
    }
}
