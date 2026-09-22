<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/QuyTrinh.php';
require_once __DIR__ . '/../models/QuyTrinhGopY.php';

class QuyTrinhController
{
    private const MAX_FILE_SIZE = 20 * 1024 * 1024; // 20 MB
    private const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx'];
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/octet-stream',
    ];
    private const MAX_NOTE_LENGTH = 4000;

    private QuyTrinh $model;
    private QuyTrinhGopY $gopYModel;
    private string $storageDir;

    public function __construct()
    {
        $this->model = new QuyTrinh();
        $this->gopYModel = new QuyTrinhGopY();
        $this->storageDir = dirname(__DIR__) . '/storage/quy-trinh';
    }

    public function index(): void
    {
        $databaseError = false;
        try {
            $quyTrinhs = $this->model->allOrdered();
        } catch (Throwable $e) {
            $quyTrinhs = [];
            $databaseError = true;
            error_log('QuyTrinh index database error: ' . $e->getMessage());
        }
        $isManager = $this->canManage();
        try {
            $pendingCount = $isManager ? $this->gopYModel->pendingCount() : 0;
        } catch (Throwable $e) {
            $pendingCount = 0;
            $databaseError = true;
            error_log('QuyTrinh pending count database error: ' . $e->getMessage());
        }
        require __DIR__ . '/../views/quytrinh/index.php';
    }

    public function view(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $quyTrinh = $id > 0 ? $this->model->find($id) : false;
        if (!$quyTrinh) {
            http_response_code(404);
            exit('Không tìm thấy quy trình.');
        }
        $gopYs = $this->gopYModel->byQuyTrinh($id);
        $isManager = $this->canManage();
        $hasFile = !empty($quyTrinh['ten_luu_tru'])
            && is_file($this->storageDir . DIRECTORY_SEPARATOR . basename((string)$quyTrinh['ten_luu_tru']));
        require __DIR__ . '/../views/quytrinh/view.php';
    }

    public function pending(): void
    {
        $this->requireManagePermission();
        $items = $this->gopYModel->allPending(500);
        $isManager = true;
        require __DIR__ . '/../views/quytrinh/pending.php';
    }

    public function addGopY(): void
    {
        $this->verifyCsrf();
        $quyTrinhId = (int)($_POST['quy_trinh_id'] ?? 0);
        $noiDung = trim((string)($_POST['noi_dung'] ?? ''));

        if ($quyTrinhId <= 0 || !$this->model->find($quyTrinhId)) {
            $this->redirectTo('quytrinh.php', 'Không tìm thấy quy trình.');
        }
        if ($noiDung === '' || mb_strlen($noiDung) < 3) {
            $this->redirectTo('quytrinh.php?action=view&id=' . $quyTrinhId, 'Nội dung góp ý quá ngắn.');
        }
        if (mb_strlen($noiDung) > self::MAX_NOTE_LENGTH) {
            $noiDung = mb_substr($noiDung, 0, self::MAX_NOTE_LENGTH);
        }

        try {
            $this->gopYModel->create([
                'quy_trinh_id'   => $quyTrinhId,
                'noi_dung'       => $noiDung,
                'nguoi_gui_id'   => (int)($_SESSION['user_id'] ?? 0) ?: null,
                'nguoi_gui_ten'  => (string)($_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Ẩn danh'),
                'trang_thai'     => 'moi',
            ]);
        } catch (Throwable $e) {
            error_log('QuyTrinh addGopY error: ' . $e->getMessage());
            $this->redirectTo('quytrinh.php?action=view&id=' . $quyTrinhId, 'Không thể lưu góp ý.');
        }

        $this->redirectTo('quytrinh.php?action=view&id=' . $quyTrinhId, '', true, 'Đã gửi góp ý. Cảm ơn bạn!');
    }

    public function updateGopY(): void
    {
        $this->requireManagePermission();
        $this->verifyCsrf();

        $id = (int)($_POST['id'] ?? 0);
        $item = $id > 0 ? $this->gopYModel->find($id) : false;
        if (!$item) {
            $this->redirectTo('quytrinh.php', 'Không tìm thấy góp ý.');
        }

        $trangThai = (string)($_POST['trang_thai'] ?? 'moi');
        if (!isset(QuyTrinhGopY::TRANG_THAI[$trangThai])) {
            $trangThai = 'moi';
        }
        $phanHoi = trim((string)($_POST['phan_hoi'] ?? ''));
        if (mb_strlen($phanHoi) > self::MAX_NOTE_LENGTH) {
            $phanHoi = mb_substr($phanHoi, 0, self::MAX_NOTE_LENGTH);
        }

        $this->gopYModel->update($id, [
            'trang_thai'      => $trangThai,
            'phan_hoi'        => $phanHoi !== '' ? $phanHoi : null,
            'nguoi_xu_ly_id'  => (int)($_SESSION['user_id'] ?? 0) ?: null,
            'nguoi_xu_ly_ten' => (string)($_SESSION['user_name'] ?? $_SESSION['username'] ?? ''),
            'ngay_xu_ly'      => date('Y-m-d H:i:s'),
        ]);

        $back = (string)($_POST['redirect'] ?? ('quytrinh.php?action=view&id=' . (int)$item['quy_trinh_id']));
        $this->redirectTo($back, '', true, 'Đã cập nhật góp ý.');
    }

    public function deleteGopY(): void
    {
        $this->requireManagePermission();
        $this->verifyCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $item = $id > 0 ? $this->gopYModel->find($id) : false;
        if (!$item) {
            $this->redirectTo('quytrinh.php', 'Không tìm thấy góp ý.');
        }
        $this->gopYModel->delete($id);
        $back = (string)($_POST['redirect'] ?? ('quytrinh.php?action=view&id=' . (int)$item['quy_trinh_id']));
        $this->redirectTo($back, '', true, 'Đã xóa góp ý.');
    }

    public function upload(): void
    {
        $this->requireManagePermission();
        $this->verifyCsrf();

        $quyTrinhId = (int)($_POST['quy_trinh_id'] ?? 0);
        $item = $quyTrinhId > 0 ? $this->model->find($quyTrinhId) : false;
        if (!$item) {
            $this->redirectTo('quytrinh.php', 'Không tìm thấy quy trình.');
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->redirectTo('quytrinh.php?action=view&id=' . $quyTrinhId, 'Vui lòng chọn file hợp lệ.');
        }

        $file = $_FILES['file'];
        $originalName = basename((string)$file['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $this->redirectTo('quytrinh.php?action=view&id=' . $quyTrinhId, 'Chỉ chấp nhận .pdf, .doc, .docx.');
        }
        if ((int)$file['size'] > self::MAX_FILE_SIZE) {
            $this->redirectTo('quytrinh.php?action=view&id=' . $quyTrinhId, 'File không được lớn hơn 20 MB.');
        }

        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: 'application/octet-stream';
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            $this->redirectTo('quytrinh.php?action=view&id=' . $quyTrinhId, 'Nội dung file không hợp lệ.');
        }

        if (!is_dir($this->storageDir) && !mkdir($this->storageDir, 0750, true) && !is_dir($this->storageDir)) {
            $this->redirectTo('quytrinh.php?action=view&id=' . $quyTrinhId, 'Không thể tạo thư mục lưu trữ.');
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = $this->storageDir . DIRECTORY_SEPARATOR . $storedName;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $this->redirectTo('quytrinh.php?action=view&id=' . $quyTrinhId, 'Không thể lưu file.');
        }

        $displayName = trim((string)($_POST['ten_hien_thi'] ?? ''));
        if ($displayName === '') {
            $displayName = pathinfo($originalName, PATHINFO_FILENAME);
        }
        if (mb_strlen($displayName) > 255) {
            $displayName = mb_substr($displayName, 0, 255);
        }

        $oldStored = (string)($item['ten_luu_tru'] ?? '');

        try {
            $this->model->update($quyTrinhId, [
                'ten_hien_thi'   => $displayName,
                'ten_luu_tru'    => $storedName,
                'mime_type'      => $mimeType,
                'kich_thuoc'     => (int)$file['size'],
                'nguoi_tai_len'  => (int)($_SESSION['user_id'] ?? 0) ?: null,
            ]);
        } catch (Throwable $e) {
            @unlink($destination);
            error_log('QuyTrinh upload DB error: ' . $e->getMessage());
            $this->redirectTo('quytrinh.php?action=view&id=' . $quyTrinhId, 'Không thể lưu vào cơ sở dữ liệu.');
        }

        if ($oldStored !== '' && $oldStored !== $storedName) {
            $oldPath = $this->storageDir . DIRECTORY_SEPARATOR . basename($oldStored);
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $this->redirectTo('quytrinh.php?action=view&id=' . $quyTrinhId, '', true, 'Đã cập nhật tài liệu.');
    }

    public function updateMeta(): void
    {
        $this->requireManagePermission();
        $this->verifyCsrf();
        $quyTrinhId = (int)($_POST['quy_trinh_id'] ?? 0);
        $item = $quyTrinhId > 0 ? $this->model->find($quyTrinhId) : false;
        if (!$item) {
            $this->redirectTo('quytrinh.php', 'Không tìm thấy quy trình.');
        }
        $tenQt = trim((string)($_POST['ten_qt'] ?? ''));
        $moTa = trim((string)($_POST['mo_ta'] ?? ''));
        if ($tenQt === '' || mb_strlen($tenQt) > 500) {
            $this->redirectTo('quytrinh.php?action=view&id=' . $quyTrinhId, 'Tên quy trình không hợp lệ.');
        }
        $this->model->update($quyTrinhId, [
            'ten_qt' => $tenQt,
            'mo_ta'  => $moTa !== '' ? $moTa : null,
        ]);
        $this->redirectTo('quytrinh.php?action=view&id=' . $quyTrinhId, '', true, 'Đã cập nhật thông tin quy trình.');
    }

    private function canManage(): bool
    {
        return hasRole(ROLE_ADMIN) || hasPermission('quytrinh.manage');
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

    private function redirectTo(string $target, string $error = '', bool $success = false, string $successMsg = 'Đã cập nhật.'): void
    {
        $separator = str_contains($target, '?') ? '&' : '?';
        if ($success) {
            $target .= $separator . 'success=' . rawurlencode($successMsg);
        } elseif ($error !== '') {
            $target .= $separator . 'error=' . rawurlencode($error);
        }
        header('Location: ' . $target);
        exit;
    }
}
