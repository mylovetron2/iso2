<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../controllers/ThietBiController.php';

class ThietBiYearTest extends TestCase
{
    private function parseManufacturingYear(mixed $value, array &$errors): ?int
    {
        $controller = (new ReflectionClass(ThietBiController::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(ThietBiController::class, 'parseManufacturingYear');
        $method->setAccessible(true);

        return $method->invokeArgs($controller, [$value, &$errors]);
    }

    public function testManufacturingYearCanBeEmptyOrAValidYear(): void
    {
        $errors = [];
        self::assertNull($this->parseManufacturingYear('', $errors));
        self::assertSame([], $errors);

        self::assertSame(2020, $this->parseManufacturingYear('2020', $errors));
        self::assertSame([], $errors);
    }

    public function testManufacturingYearRejectsValuesOutsideDatabaseYearRange(): void
    {
        $errors = [];

        self::assertNull($this->parseManufacturingYear('1899', $errors));
        self::assertNotEmpty($errors);

        $errors = [];
        self::assertNull($this->parseManufacturingYear('2156', $errors));
        self::assertNotEmpty($errors);
    }

    public function testCreatedDeviceIsHighlightedAndScrolledIntoView(): void
    {
        $controller = file_get_contents(__DIR__ . '/../controllers/ThietBiController.php');
        $listView = file_get_contents(__DIR__ . '/../views/thietbi/index.php');

        self::assertStringContainsString('?success=created&highlight=', $controller);
        self::assertStringContainsString('id="device-<?php echo (int)$item[\'stt\']; ?>"', $listView);
        self::assertStringContainsString("row.scrollIntoView({ behavior: 'smooth', block: 'center' })", $listView);
    }

    public function testManufacturingYearIsAvailableForCreateAndEdit(): void
    {
        $controller = file_get_contents(__DIR__ . '/../controllers/ThietBiController.php');
        $createView = file_get_contents(__DIR__ . '/../views/thietbi/create.php');
        $editView = file_get_contents(__DIR__ . '/../views/thietbi/edit.php');

        self::assertSame(2, substr_count($controller, "parseManufacturingYear(\$_POST['nam_sx'] ?? '', \$errors)"));
        self::assertStringContainsString('name="nam_sx"', $createView);
        self::assertStringContainsString('name="nam_sx"', $editView);
    }
}
