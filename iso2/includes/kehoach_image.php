<?php
declare(strict_types=1);

function resizeKehoachImage(string $path, string $extension): void
{
    if (!extension_loaded('gd')) {
        throw new RuntimeException('Máy chủ cần bật PHP GD để giảm kích thước ảnh.');
    }

    $decoders = [
        'jpg' => 'imagecreatefromjpeg',
        'jpeg' => 'imagecreatefromjpeg',
        'png' => 'imagecreatefrompng',
        'gif' => 'imagecreatefromgif',
        'webp' => 'imagecreatefromwebp',
    ];
    $decoder = $decoders[$extension] ?? null;
    if ($decoder === null || !function_exists($decoder)) {
        throw new RuntimeException('Máy chủ không hỗ trợ xử lý định dạng ảnh này.');
    }

    $source = $decoder($path);
    if ($source === false) {
        throw new RuntimeException('Không đọc được ảnh.');
    }

    $resized = null;
    try {
        // Apply camera orientation before dropping EXIF during re-encoding.
        if (in_array($extension, ['jpg', 'jpeg'], true) && function_exists('exif_read_data')) {
            $exif = exif_read_data($path);
            $orientation = (int)($exif['Orientation'] ?? 1);
            if (in_array($orientation, [2, 4, 5, 7], true)) {
                if (!imageflip($source, IMG_FLIP_HORIZONTAL)) {
                    throw new RuntimeException('Không chỉnh được hướng ảnh.');
                }
            }
            $angle = [3 => 180, 4 => 180, 5 => 90, 6 => -90, 7 => -90, 8 => 90][$orientation] ?? 0;
            if ($angle !== 0) {
                $rotated = imagerotate($source, $angle, 0);
                if ($rotated === false) {
                    throw new RuntimeException('Không xoay được ảnh.');
                }
                imagedestroy($source);
                $source = $rotated;
            }
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, 1920 / max($width, $height));
        $newWidth = max(1, (int)round($width * $scale));
        $newHeight = max(1, (int)round($height * $scale));
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        if ($resized === false) {
            throw new RuntimeException('Không tạo được ảnh thu nhỏ.');
        }

        if (in_array($extension, ['png', 'webp', 'gif'], true)) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefill($resized, 0, 0, $transparent);
            if ($extension === 'gif') {
                imagecolortransparent($resized, $transparent);
            }
        }

        if (!imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height)) {
            throw new RuntimeException('Không giảm được kích thước ảnh.');
        }

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $saved = imagejpeg($resized, $path, 85);
                break;
            case 'webp':
                $saved = imagewebp($resized, $path, 85);
                break;
            case 'png':
                $saved = imagepng($resized, $path, 9);
                break;
            case 'gif':
                $saved = imagegif($resized, $path);
                break;
        }
        if (!$saved) {
            throw new RuntimeException('Không lưu được ảnh đã nén.');
        }
    } finally {
        imagedestroy($source);
        if ($resized !== null && $resized !== false) {
            imagedestroy($resized);
        }
    }
}
