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

        // Lấy danh sách thiết bị
        $offset = ($page - 1) * $perPage;
        $devices = $this->model->getList($search, $chusohuu, $offset, $perPage);
        $total = $this->model->countList($search, $chusohuu);
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
        $data = [
            'tenthietbi' => $_POST['tenthietbi'] ?? '',
            'tenvt' => $_POST['tenvt'] ?? '',
            'chusohuu' => $_POST['chusohuu'] ?? '',
            'serialnumber' => $_POST['serialnumber'] ?? '',
            'ngaykd' => $_POST['ngaykd'] ?? null,
            'hankd' => (int)($_POST['hankd'] ?? 0),
            'ngaykdtt' => $_POST['ngaykdtt'] ?? null,
            'tlkt' => $_POST['tlkt'] ?? '',
            'hosomay' => $_POST['hosomay'] ?? '',
            'cdung' => (int)($_POST['cdung'] ?? 0),
            'thly' => (int)($_POST['thly'] ?? 0)
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
        $data = [
            'tenthietbi' => $_POST['tenthietbi'] ?? '',
            'tenvt' => $_POST['tenvt'] ?? '',
            'chusohuu' => $_POST['chusohuu'] ?? '',
            'serialnumber' => $_POST['serialnumber'] ?? '',
            'ngaykd' => $_POST['ngaykd'] ?? null,
            'hankd' => (int)($_POST['hankd'] ?? 0),
            'ngaykdtt' => $_POST['ngaykdtt'] ?? null,
            'tlkt' => $_POST['tlkt'] ?? '',
            'hosomay' => $_POST['hosomay'] ?? '',
            'cdung' => (int)($_POST['cdung'] ?? 0),
            'thly' => (int)($_POST['thly'] ?? 0)
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
}
