<?php
declare(strict_types=1);

function canEditRepairAttachments(array $item, bool $isAdmin, bool $canEdit): bool
{
    $completed = !empty($item['ngaykt']) && $item['ngaykt'] !== '0000-00-00';
    return $isAdmin || ($canEdit && !$completed);
}

final class RepairAttachments
{
    public const MAX_SIZE = 20 * 1024 * 1024;
    private string $directory;
    private const MIME_TYPES = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'gif' => 'image/gif', 'webp' => 'image/webp', 'pdf' => 'application/pdf',
    ];

    public function __construct(string $root, int $id)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID hồ sơ không hợp lệ.');
        }
        $this->directory = $root . DIRECTORY_SEPARATOR . $id;
    }

    public function listFiles(): array
    {
        if (!is_dir($this->directory)) {
            return [];
        }
        $names = scandir($this->directory);
        if ($names === false) {
            throw new RuntimeException('Không đọc được thư mục hồ sơ đính kèm.');
        }
        $files = [];
        foreach ($names as $name) {
            if (!$this->validName($name)) {
                continue;
            }
            $path = $this->directory . DIRECTORY_SEPARATOR . $name;
            if (!is_file($path) || is_link($path)) {
                continue;
            }
            $size = filesize($path);
            if ($size === false) {
                throw new RuntimeException('Không đọc được kích thước file đính kèm.');
            }
            $files[] = [
                'name' => $name,
                'original' => explode('_', $name, 3)[2],
                'is_pdf' => pathinfo($name, PATHINFO_EXTENSION) === 'pdf',
                'size' => $size,
            ];
        }
        return $files;
    }

    public function filePath(string $name): string
    {
        if (!$this->validName($name)) {
            throw new InvalidArgumentException('Tên file đính kèm không hợp lệ.');
        }
        $path = $this->directory . DIRECTORY_SEPARATOR . $name;
        if (!is_file($path) || is_link($path)) {
            throw new RuntimeException('Không tìm thấy file đính kèm.');
        }
        return $path;
    }

    public function validateUpload(array $file): string
    {
        foreach (['name', 'tmp_name', 'size', 'error'] as $key) {
            if (!isset($file[$key]) || is_array($file[$key])) {
                throw new InvalidArgumentException('Dữ liệu tải lên không hợp lệ.');
            }
        }
        if ((int)$file['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Tải lên thất bại (mã ' . (int)$file['error'] . '). Kiểm tra giới hạn upload trên máy chủ.');
        }
        if ((int)$file['size'] <= 0 || (int)$file['size'] > self::MAX_SIZE) {
            throw new InvalidArgumentException('File phải có nội dung và không vượt quá 20MB.');
        }
        $extension = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if (!isset(self::MIME_TYPES[$extension])) {
            throw new InvalidArgumentException('Chỉ nhận ảnh JPG, PNG, GIF, WebP hoặc PDF.');
        }
        if (!is_file((string)$file['tmp_name'])) {
            throw new InvalidArgumentException('Không tìm thấy file tải lên.');
        }
        if (!class_exists('finfo')) {
            throw new RuntimeException('Máy chủ cần bật PHP Fileinfo để kiểm tra file.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string)$file['tmp_name']);
        if ($mime !== self::MIME_TYPES[$extension]) {
            throw new InvalidArgumentException('Nội dung file không phù hợp định dạng.');
        }
        return $extension;
    }

    public function upload(array $file): string
    {
        $extension = $this->validateUpload($file);
        if (!is_uploaded_file((string)$file['tmp_name'])) {
            throw new InvalidArgumentException('File không đến từ yêu cầu tải lên.');
        }
        if ($extension !== 'pdf') {
            $helper = __DIR__ . '/kehoach_image.php';
            if (!is_readable($helper)) {
                throw new RuntimeException('Thiếu bộ xử lý ảnh includes/kehoach_image.php trên máy chủ.');
            }
            require_once $helper;
            resizeKehoachImage((string)$file['tmp_name'], $extension);
        }
        if (!is_dir($this->directory) && !mkdir($this->directory, 0755, true) && !is_dir($this->directory)) {
            throw new RuntimeException('Không tạo được thư mục hồ sơ đính kèm.');
        }
        $base = preg_replace('/[^A-Za-z0-9_.-]+/', '_', pathinfo((string)$file['name'], PATHINFO_FILENAME));
        $base = trim(substr($base, 0, 80), '._') ?: 'file';
        $name = time() . '_' . bin2hex(random_bytes(8)) . '_' . $base . '.' . $extension;
        if (!move_uploaded_file((string)$file['tmp_name'], $this->directory . DIRECTORY_SEPARATOR . $name)) {
            throw new RuntimeException('Không lưu được file đính kèm.');
        }
        return $name;
    }

    public function delete(string $name): void
    {
        if (!unlink($this->filePath($name))) {
            throw new RuntimeException('Không xóa được file đính kèm.');
        }
    }

    public function mimeType(string $name): string
    {
        $this->filePath($name);
        return self::MIME_TYPES[pathinfo($name, PATHINFO_EXTENSION)];
    }

    private function validName(string $name): bool
    {
        return preg_match('/^\d+_[a-f0-9]{16}_[A-Za-z0-9_.-]+\.(jpg|jpeg|png|gif|webp|pdf)$/D', $name) === 1;
    }
}
