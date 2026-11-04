<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Vitri.php';

class VitriController
{
    private Vitri $model;

    public function __construct()
    {
        $this->model = new Vitri();
    }

    public function index(): void
    {
        $search = trim($_GET['search'] ?? '');
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $items = $this->model->getList($search, $offset, $limit);
        $total = $this->model->countList($search);
        $totalPages = (int)ceil($total / $limit);

        require_once __DIR__ . '/../views/vitri/index.php';
    }

    public function create(): void
    {
        $this->requirePermission('vitri.create');

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            require_once __DIR__ . '/../views/vitri/create.php';
            return;
        }

        $mavitri = trim($_POST['mavitri'] ?? '');
        $tenvitri = trim($_POST['tenvitri'] ?? '');

        $errors = [];
        if ($mavitri === '') {
            $errors[] = 'Mã vị trí không được để trống';
        } elseif ($this->model->isCodeExists($mavitri)) {
            $errors[] = 'Mã vị trí đã tồn tại';
        }
        if ($tenvitri === '') {
            $errors[] = 'Tên vị trí không được để trống';
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode(', ', $errors);
            header('Location: /iso2/vitri.php?action=create');
            exit;
        }

        $this->model->create(['mavitri' => $mavitri, 'tenvitri' => $tenvitri]);
        $_SESSION['success'] = 'Đã thêm vị trí mới';
        header('Location: /iso2/vitri.php');
        exit;
    }

    public function edit(): void
    {
        $this->requirePermission('vitri.edit');

        $id = (int)($_GET['id'] ?? 0);
        $item = $id ? $this->model->findById($id) : false;

        if (!$item) {
            $_SESSION['error'] = 'Không tìm thấy vị trí';
            header('Location: /iso2/vitri.php');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            require_once __DIR__ . '/../views/vitri/edit.php';
            return;
        }

        $mavitri = trim($_POST['mavitri'] ?? '');
        $tenvitri = trim($_POST['tenvitri'] ?? '');

        $errors = [];
        if ($mavitri === '') {
            $errors[] = 'Mã vị trí không được để trống';
        } elseif ($this->model->isCodeExists($mavitri, $id)) {
            $errors[] = 'Mã vị trí đã tồn tại';
        }
        if ($tenvitri === '') {
            $errors[] = 'Tên vị trí không được để trống';
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode(', ', $errors);
            header("Location: /iso2/vitri.php?action=edit&id=$id");
            exit;
        }

        // update() trả về 0 khi dữ liệu không đổi, vẫn là thành công
        $this->model->update($id, ['mavitri' => $mavitri, 'tenvitri' => $tenvitri]);
        $_SESSION['success'] = 'Đã cập nhật vị trí';
        header('Location: /iso2/vitri.php');
        exit;
    }

    public function delete(): void
    {
        $this->requirePermission('vitri.delete');

        $id = (int)($_POST['id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$id) {
            header('Location: /iso2/vitri.php');
            exit;
        }

        if ($this->model->delete($id)) {
            $_SESSION['success'] = 'Đã xóa vị trí';
        } else {
            $_SESSION['error'] = 'Có lỗi khi xóa vị trí';
        }

        header('Location: /iso2/vitri.php');
        exit;
    }

    private function requirePermission(string $permission): void
    {
        if (!hasPermission($permission)) {
            $_SESSION['error'] = 'Bạn không có quyền thực hiện thao tác này';
            header('Location: /iso2/vitri.php');
            exit;
        }
    }
}
