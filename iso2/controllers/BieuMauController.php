<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/BieuMau.php';
require_once __DIR__ . '/../models/BieuMauThuMuc.php';

class BieuMauController
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;
    private const ALLOWED_EXTENSIONS = ['doc', 'docx'];
    private const ALLOWED_MIME_TYPES = [
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/octet-stream',
    ];

    private BieuMau $model;
    private BieuMauThuMuc $folderModel;
    private string $storageDir;

    public function __construct()
    {
        $this->model = new BieuMau();
        $this->folderModel = new BieuMauThuMuc();
        $this->storageDir = dirname(__DIR__) . '/storage/bieu-mau';
    }

    public function index(): void
    {
        $this->requireViewPermission();
        $folders = $this->folderModel->allOrdered();
        $allBieuMaus = $this->model->allOrdered();
        $selectedFolderId = isset($_GET['folder_id']) ? (int)$_GET['folder_id'] : null;
        if ($selectedFolderId !== null && $selectedFolderId <= 0) {
            $selectedFolderId = null;
        }
        if ($selectedFolderId !== null && $selectedFolderId > 0 && !$this->folderModel->find($selectedFolderId)) {
            $selectedFolderId = null;
        }
        $search = trim((string)($_GET['q'] ?? ''));
        if (mb_strlen($search) > 100) {
            $search = mb_substr($search, 0, 100);
        }
        $bieuMaus = $selectedFolderId !== null && $selectedFolderId > 0
            ? $this->model->allOrdered($selectedFolderId, false, $search)
            : (isset($_GET['uncategorized']) ? $this->model->allOrdered(null, true, $search) : ($search !== '' ? $this->model->allOrdered(null, false, $search) : $allBieuMaus));
        $selectedFolderName = 'Tất cả biểu mẫu';
        foreach ($folders as $folder) {
            if ((int)$folder['id'] === $selectedFolderId) {
                $selectedFolderName = $folder['ten_thu_muc'];
                break;
            }
        }
        $isAdmin = $this->canManage();
        require __DIR__ . '/../views/bieumau/index.php';
    }

    public function upload(): void
    {
        $this->requireManagePermission();
        $this->verifyCsrf();

        $folderId = (int)($_POST['thu_muc_id'] ?? 0);
        if ($folderId > 0 && !$this->folderModel->find($folderId)) {
            $this->redirect('Thư mục không tồn tại.');
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->redirect('Vui lòng chọn file Word hợp lệ.');
        }

        $file = $_FILES['file'];
        $originalName = basename((string)$file['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $this->redirect('Chỉ chấp nhận file .doc hoặc .docx.');
        }
        if ((int)$file['size'] > self::MAX_FILE_SIZE) {
            $this->redirect('File không được lớn hơn 10 MB.');
        }

        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: 'application/octet-stream';
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            $this->redirect('Nội dung file không phải tài liệu Word hợp lệ.');
        }

        $displayName = trim((string)($_POST['ten_hien_thi'] ?? ''));
        if ($displayName === '') {
            $displayName = pathinfo($originalName, PATHINFO_FILENAME);
        }
        if (mb_strlen($displayName) > 255) {
            $this->redirect('Tên biểu mẫu không được dài quá 255 ký tự.');
        }

        if (!is_dir($this->storageDir) && !mkdir($this->storageDir, 0750, true) && !is_dir($this->storageDir)) {
            $this->redirect('Không thể tạo thư mục lưu biểu mẫu.');
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = $this->storageDir . DIRECTORY_SEPARATOR . $storedName;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $this->redirect('Không thể lưu file biểu mẫu.');
        }

        try {
            $this->model->create([
                'ten_hien_thi' => $displayName,
                'thu_muc_id' => $folderId > 0 ? $folderId : null,
                'ten_luu_tru' => $storedName,
                'mime_type' => $mimeType,
                'kich_thuoc' => (int)$file['size'],
                'nguoi_tai_len' => (int)($_SESSION['user_id'] ?? 0) ?: null,
            ]);
        } catch (Throwable $exception) {
            @unlink($destination);
            error_log('BieuMau upload database error: ' . $exception->getMessage());
            $this->redirect('Không thể ghi thông tin biểu mẫu vào cơ sở dữ liệu.');
        }

        $this->redirect('', true);
    }

    public function createFolder(): void
    {
        $this->requireManagePermission();
        $this->verifyCsrf();
        $name = trim((string)($_POST['ten_thu_muc'] ?? ''));
        if ($name === '' || mb_strlen($name) > 150) {
            $this->redirect('Tên thư mục không hợp lệ.');
        }

        try {
            $this->folderModel->create(['ten_thu_muc' => $name]);
        } catch (Throwable $exception) {
            $this->redirect('Tên thư mục đã tồn tại hoặc không thể tạo thư mục.');
        }
        $this->redirect('', true);
    }

    public function renameFolder(): void
    {
        $this->requireManagePermission();
        $this->verifyCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['ten_thu_muc'] ?? ''));
        if ($id <= 0 || $name === '' || mb_strlen($name) > 150) {
            $this->redirect('Tên thư mục không hợp lệ.');
        }
        try {
            $this->folderModel->update($id, ['ten_thu_muc' => $name]);
        } catch (Throwable $exception) {
            $this->redirect('Tên thư mục đã tồn tại hoặc không thể đổi tên.');
        }
        $this->redirect('', true);
    }

    public function deleteFolder(): void
    {
        $this->requireManagePermission();
        $this->verifyCsrf();
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0 || !$this->folderModel->find($id)) {
            $this->redirect('Không tìm thấy thư mục.');
        }
        if ($this->model->count('WHERE thu_muc_id = ?', [$id]) > 0) {
            $this->redirect('Không thể xóa thư mục đang có biểu mẫu.');
        }
        $this->folderModel->delete($id);
        $this->redirect('', true);
    }

    public function rename(): void
    {
        $this->requireManagePermission();
        $this->verifyCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['ten_hien_thi'] ?? ''));
        if ($id <= 0 || $name === '' || mb_strlen($name) > 255) {
            $this->redirect('Tên biểu mẫu không hợp lệ.');
        }

        if (!$this->model->update($id, ['ten_hien_thi' => $name])) {
            $this->redirect('Không tìm thấy biểu mẫu để đổi tên.');
        }
        $this->redirect('', true);
    }

    public function move(): void
    {
        $this->requireManagePermission();
        $this->verifyCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $folderId = (int)($_POST['thu_muc_id'] ?? 0);
        if ($id <= 0 || !$this->model->find($id)) {
            $this->redirect('Không tìm thấy biểu mẫu.');
        }
        if ($folderId > 0 && !$this->folderModel->find($folderId)) {
            $this->redirect('Thư mục đích không tồn tại.');
        }

        $this->model->update($id, ['thu_muc_id' => $folderId > 0 ? $folderId : null]);
        $this->redirect('', true);
    }

    public function delete(): void
    {
        $this->requireManagePermission();
        $this->verifyCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $item = $id > 0 ? $this->model->find($id) : false;
        if (!$item) {
            $this->redirect('Không tìm thấy biểu mẫu.');
        }

        if ($this->model->delete($id) > 0) {
            $path = $this->storageDir . DIRECTORY_SEPARATOR . basename((string)$item['ten_luu_tru']);
            if (is_file($path)) {
                @unlink($path);
            }
        }
        $this->redirect('', true);
    }

    private function canManage(): bool
    {
        return hasRole(ROLE_ADMIN) || hasPermission('bieumau.manage');
    }

    private function requireViewPermission(): void
    {
        if (!hasRole(ROLE_ADMIN) && !hasPermission('bieumau.view')) {
            http_response_code(403);
            exit('Bạn không có quyền xem biểu mẫu.');
        }
    }

    private function requireManagePermission(): void
    {
        if (!$this->canManage()) {
            http_response_code(403);
            exit('Bạn không có quyền thực hiện thao tác này.');
        }
    }

    private function verifyCsrf(): void
    {
        if (!hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)($_POST['csrf_token'] ?? ''))) {
            http_response_code(419);
            exit('Phiên thao tác không hợp lệ. Vui lòng tải lại trang.');
        }
    }

    private function redirect(string $error = '', bool $success = false): void
    {
        $query = $success ? '?success=' . rawurlencode('Đã cập nhật biểu mẫu.') : '?error=' . rawurlencode($error);
        header('Location: bieu_mau.php' . $query);
        exit;
    }
}
