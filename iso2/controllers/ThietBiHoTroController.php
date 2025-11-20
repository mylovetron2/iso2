<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/ThietBiHoTro.php';
require_once __DIR__ . '/../includes/permissions.php';

class ThietBiHoTroController {
    private ThietBiHoTro $model;

    public function __construct() {
        $this->model = new ThietBiHoTro();
    }

    public function index(): void {
        requirePermission(PERMISSION_PROJECT_VIEW);
        
        // Lấy tham số filter, search, phân trang
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 15;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $chusohuu = isset($_GET['chusohuu']) ? trim($_GET['chusohuu']) : '';
        $trangthai = isset($_GET['trangthai']) ? trim($_GET['trangthai']) : '';

        // Lấy danh sách thiết bị
        $offset = ($page - 1) * $perPage;
        $devices = $this->model->getList($search, $chusohuu, $trangthai, $offset, $perPage);
        $total = $this->model->countList($search, $chusohuu, $trangthai);
        $totalPages = ceil($total / $perPage);

        // Thống kê
        $stats = $this->model->getStats();
        
        // Danh sách chủ sở hữu
        $chusohuuList = $this->model->getChuSoHuuList();

        $title = 'Quản lý Thiết bị Hỗ trợ';
        include __DIR__ . '/../views/thietbihotro/index.php';
    }

    public function create(): void {
        requirePermission(PERMISSION_PROJECT_CREATE);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->store();
            return;
        }
        
        $title = 'Thêm Thiết bị Hỗ trợ';
        include __DIR__ . '/../views/thietbihotro/create.php';
    }

    private function store(): void {
        // Xử lý upload file
        $hosomayFile = $this->handleFileUpload('hosomay', 'hosomay');
        $tlktFile = $this->handleFileUpload('tlkt', 'tlkt');
        
        $data = [
            'tenthietbi' => $_POST['tenthietbi'] ?? '',
            'tenvt' => $_POST['tenvt'] ?? '',
            'chusohuu' => $_POST['chusohuu'] ?? '',
            'serialnumber' => $_POST['serialnumber'] ?? '',
            'ngaykd' => $_POST['ngaykd'] ?? null,
            'hankd' => (int)($_POST['hankd'] ?? 0),
            'ngaykdtt' => $_POST['ngaykdtt'] ?? null,
            'tlkt' => $tlktFile,
            'hosomay' => $hosomayFile,
            'cdung' => isset($_POST['cdung']) && $_POST['cdung'] == 1 ? 1 : 0,
            'thly' => isset($_POST['thly']) && $_POST['thly'] == 1 ? 1 : 0
        ];

        $errors = $this->validate($data);
        
        if (empty($errors)) {
            $id = $this->model->create($data);
            if ($id) {
                header('Location: thietbihotro.php?success=Thêm thiết bị thành công');
                exit;
            } else {
                $errors[] = 'Không thể thêm thiết bị';
            }
        }
        
        $title = 'Thêm Thiết bị Hỗ trợ';
        include __DIR__ . '/../views/thietbihotro/create.php';
    }

    public function edit(int $id): void {
        requirePermission(PERMISSION_PROJECT_EDIT);
        
        $device = $this->model->find($id);
        if (!$device) {
            die('Thiết bị không tồn tại');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update($id);
            return;
        }
        
        $title = 'Sửa Thiết bị Hỗ trợ';
        include __DIR__ . '/../views/thietbihotro/edit.php';
    }

    private function update(int $id): void {
        $device = $this->model->find($id);
        
        // Xử lý upload file (giữ file cũ nếu không upload mới)
        $hosomayFile = $this->handleFileUpload('hosomay', 'hosomay') ?: $device['hosomay'];
        $tlktFile = $this->handleFileUpload('tlkt', 'tlkt') ?: $device['tlkt'];
        
        $data = [
            'tenthietbi' => $_POST['tenthietbi'] ?? '',
            'tenvt' => $_POST['tenvt'] ?? '',
            'chusohuu' => $_POST['chusohuu'] ?? '',
            'serialnumber' => $_POST['serialnumber'] ?? '',
            'ngaykd' => $_POST['ngaykd'] ?? null,
            'hankd' => (int)($_POST['hankd'] ?? 0),
            'ngaykdtt' => $_POST['ngaykdtt'] ?? null,
            'tlkt' => $tlktFile,
            'hosomay' => $hosomayFile,
            'cdung' => isset($_POST['cdung']) && $_POST['cdung'] == 1 ? 1 : 0,
            'thly' => isset($_POST['thly']) && $_POST['thly'] == 1 ? 1 : 0
        ];

        $errors = $this->validate($data);
        
        if (empty($errors)) {
            $updated = $this->model->update($id, $data);
            if ($updated) {
                header('Location: thietbihotro.php?success=Cập nhật thiết bị thành công');
                exit;
            } else {
                $errors[] = 'Không thể cập nhật thiết bị';
            }
        }
        
        $device = $this->model->find($id);
        $title = 'Sửa Thiết bị Hỗ trợ';
        include __DIR__ . '/../views/thietbihotro/edit.php';
    }

    public function delete(int $id): void {
        requirePermission(PERMISSION_PROJECT_DELETE);
        
        $deleted = $this->model->delete($id);
        if ($deleted) {
            header('Location: thietbihotro.php?success=Xóa thiết bị thành công');
        } else {
            header('Location: thietbihotro.php?error=Không thể xóa thiết bị');
        }
        exit;
    }

    public function view(int $id): void {
        requirePermission(PERMISSION_PROJECT_VIEW);
        
        $device = $this->model->find($id);
        if (!$device) {
            die('Thiết bị không tồn tại');
        }
        
        $title = 'Chi tiết Thiết bị';
        include __DIR__ . '/../views/thietbihotro/view.php';
    }

    private function validate(array $data): array {
        $errors = [];
        
        if (empty($data['tenthietbi'])) {
            $errors[] = 'Tên thiết bị không được để trống';
        }
        
        if (empty($data['chusohuu'])) {
            $errors[] = 'Chủ sở hữu không được để trống';
        }
        
        return $errors;
    }
    
    private function handleFileUpload(string $fieldName, string $folder): string {
        if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
            return '';
        }
        
        $file = $_FILES[$fieldName];
        
        // Kiểm tra lỗi upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return '';
        }
        
        // Kiểm tra kích thước file (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            return '';
        }
        
        // Kiểm tra loại file
        $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 
                        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'image/jpeg', 'image/jpg', 'image/png'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            return '';
        }
        
        // Tạo tên file unique
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        
        // Tạo thư mục nếu chưa có
        $uploadDir = __DIR__ . '/../uploads/' . $folder . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Di chuyển file
        $destination = $uploadDir . $filename;
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return $filename;
        }
        
        return '';
    }
}
