<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/kehoach_image.php';

use PHPUnit\Framework\TestCase;

final class KeHoachImageTest extends TestCase
{
    public function testImageHelperIsOnlyRequiredForImageUploads(): void
    {
        $page = file_get_contents(__DIR__ . '/../kehoach_thietbi_2026.php');
        $uploadStart = strpos($page, "if (\$ext !== 'pdf') {");
        $this->assertNotFalse($uploadStart);
        $this->assertStringNotContainsString('kehoach_image.php', substr($page, 0, $uploadStart));

        $uploadEnd = strpos($page, '$baseName =', $uploadStart);
        $upload = substr($page, $uploadStart, $uploadEnd - $uploadStart);
        $this->assertStringContainsString('if (!is_readable($imageHelper))', $upload);
        $this->assertStringContainsString('throw new RuntimeException(', $upload);
        $this->assertStringContainsString('require_once $imageHelper;', $upload);
        $this->assertStringContainsString('catch (RuntimeException $e)', $upload);
        $this->assertStringContainsString("error_log('Plan image upload: '", $upload);
    }

    /**
     * @dataProvider imageCases
     */
    public function testResizesWithoutUpscalingAndKeepsFormat(
        string $extension,
        int $width,
        int $height,
        int $expectedWidth,
        int $expectedHeight,
        string $mime
    ): void {
        $path = tempnam(sys_get_temp_dir(), 'kh_image_');
        $image = imagecreatetruecolor($width, $height);
        try {
            $encoder = 'image' . ($extension === 'jpg' ? 'jpeg' : $extension);
            $this->assertTrue($encoder($image, $path));
            resizeKehoachImage($path, $extension);
            $info = getimagesize($path);
            $this->assertSame($expectedWidth, $info[0]);
            $this->assertSame($expectedHeight, $info[1]);
            $this->assertSame($mime, $info['mime']);
        } finally {
            imagedestroy($image);
            unlink($path);
        }
    }

    public static function imageCases(): array
    {
        return [
            'landscape JPEG' => ['jpg', 2560, 1440, 1920, 1080, 'image/jpeg'],
            'portrait JPEG' => ['jpeg', 1440, 2560, 1080, 1920, 'image/jpeg'],
            'small JPEG' => ['jpg', 640, 480, 640, 480, 'image/jpeg'],
            'boundary JPEG' => ['jpg', 1920, 1080, 1920, 1080, 'image/jpeg'],
            'PNG' => ['png', 2400, 1200, 1920, 960, 'image/png'],
            'GIF' => ['gif', 1200, 2400, 960, 1920, 'image/gif'],
            'WebP' => ['webp', 2400, 1200, 1920, 960, 'image/webp'],
            'small PNG' => ['png', 100, 50, 100, 50, 'image/png'],
        ];
    }

    public function testPngRetainsTransparency(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'kh_image_');
        $image = imagecreatetruecolor(20, 10);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
        imagepng($image, $path);
        imagedestroy($image);
        try {
            resizeKehoachImage($path, 'png');
            $result = imagecreatefrompng($path);
            try {
                $this->assertSame(127, imagecolorsforindex($result, imagecolorat($result, 0, 0))['alpha']);
            } finally {
                imagedestroy($result);
            }
        } finally {
            unlink($path);
        }
    }

    /**
     * @dataProvider lossyFormats
     */
    public function testUsesQuality85(string $extension): void
    {
        $path = tempnam(sys_get_temp_dir(), 'kh_image_');
        $expected = tempnam(sys_get_temp_dir(), 'kh_image_');
        $image = imagecreatetruecolor(100, 50);
        $encoder = 'image' . $extension;
        $decoder = 'imagecreatefrom' . $extension;
        for ($x = 0; $x < 100; $x++) {
            imageline($image, $x, 0, $x, 49, imagecolorallocate($image, $x * 2, 255 - $x, $x));
        }
        $encoder($image, $path, 100);
        imagedestroy($image);
        try {
            $decoded = $decoder($path);
            try {
                $encoder($decoded, $expected, 85);
            } finally {
                imagedestroy($decoded);
            }
            resizeKehoachImage($path, $extension);
            $this->assertSame(file_get_contents($expected), file_get_contents($path));
        } finally {
            unlink($path);
            unlink($expected);
        }
    }

    public static function lossyFormats(): array
    {
        return [['jpeg'], ['webp']];
    }

    public function testAppliesCameraOrientation(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'kh_image_');
        $image = imagecreatetruecolor(30, 20);
        imagejpeg($image, $path);
        imagedestroy($image);
        // Minimal EXIF APP1 segment: orientation 6 means rotate 90 degrees clockwise.
        $exif = hex2bin('45786966000049492a0008000000010012010300010000000600000000000000');
        $jpeg = file_get_contents($path);
        file_put_contents($path, substr($jpeg, 0, 2) . "\xFF\xE1" . pack('n', strlen($exif) + 2) . $exif . substr($jpeg, 2));
        try {
            resizeKehoachImage($path, 'jpg');
            $info = getimagesize($path);
            $this->assertSame(20, $info[0]);
            $this->assertSame(30, $info[1]);
        } finally {
            unlink($path);
        }
    }
}
