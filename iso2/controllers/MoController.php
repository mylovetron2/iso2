<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Mo.php';

class MoController
{
    private Mo $model;

    public function __construct()
    {
        $this->model = new Mo();
    }

    public function index(): void
    {
        $search = $_GET['search'] ?? '';
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $items = $this->model->getList($search, $offset, $limit);
        $total = $this->model->countList($search);
        $totalPages = ceil($total / $limit);

        require_once __DIR__ . '/../views/mo/index.php';
    }

    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            require_once __DIR__ . '/../views/mo/create.php';
            return;
        }

        // POST: Xử lý tạo mới
        $data = [
            'mamo' => trim($_POST['mamo'] ?? ''),
            'tenmo' => trim($_POST['tenmo'] ?? '')
        ];

        // Validate
        $errors = [];
        if (empty($data['mamo'])) {
            $errors[] = 'Mã mỏ không được để trống';
        }
        if (empty($data['tenmo'])) {
            $errors[] = 'Tên mỏ không được để trống';
        }
        
        // Kiểm tra trùng mã
        if ($this->model->isCodeExists($data['mamo'])) {
            $errors[] = 'Mã mỏ đã tồn tại';
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode(', ', $errors);
            header("Location: /iso2/mo.php?action=create");
            exit;
        }

        if ($this->model->create($data)) {
            $_SESSION['success'] = 'Đã tạo mỏ mới';
            header("Location: /iso2/mo.php");
        } else {
            $_SESSION['error'] = 'Có lỗi khi tạo mỏ';
            header("Location: /iso2/mo.php?action=create");
        }
        exit;
    }

    public function edit(): void
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if (!$id) {
            header("Location: /iso2/mo.php");
            exit;
        }

        $item = $this->model->findById($id);
        
        if (!$item) {
            $_SESSION['error'] = 'Không tìm thấy mỏ';
            header("Location: /iso2/mo.php");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            require_once __DIR__ . '/../views/mo/edit.php';
            return;
        }

        // POST: Xử lý cập nhật
        $data = [
            'mamo' => trim($_POST['mamo'] ?? ''),
            'tenmo' => trim($_POST['tenmo'] ?? '')
        ];

        // Validate
        $errors = [];
        if (empty($data['mamo'])) {
            $errors[] = 'Mã mỏ không được để trống';
        }
        if (empty($data['tenmo'])) {
            $errors[] = 'Tên mỏ không được để trống';
        }
        
        // Kiểm tra trùng mã (ngoại trừ chính nó)
        if ($this->model->isCodeExists($data['mamo'], $id)) {
            $errors[] = 'Mã mỏ đã tồn tại';
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode(', ', $errors);
            header("Location: /iso2/mo.php?action=edit&id=$id");
            exit;
        }

        if ($this->model->update($id, $data)) {
            $_SESSION['success'] = 'Đã cập nhật mỏ';
            header("Location: /iso2/mo.php");
        } else {
            $_SESSION['error'] = 'Có lỗi khi cập nhật mỏ';
            header("Location: /iso2/mo.php?action=edit&id=$id");
        }
        exit;
    }

    public function delete(): void
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if (!$id) {
            header("Location: /iso2/mo.php");
            exit;
        }

        if ($this->model->delete($id)) {
            $_SESSION['success'] = 'Đã xóa mỏ';
        } else {
            $_SESSION['error'] = 'Có lỗi khi xóa mỏ';
        }
        
        header("Location: /iso2/mo.php");
        exit;
    }
}
