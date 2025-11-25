<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Lo.php';

class LoController
{
    private Lo $model;

    public function __construct()
    {
        $this->model = new Lo();
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

        require_once __DIR__ . '/../views/lo/index.php';
    }

    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            require_once __DIR__ . '/../views/lo/create.php';
            return;
        }

        // POST: Xử lý tạo mới
        $data = [
            'malo' => trim($_POST['malo'] ?? ''),
            'tenlo' => trim($_POST['tenlo'] ?? ''),
            'ghichu' => trim($_POST['ghichu'] ?? ''),
            'nguoitao' => $_SESSION['username'] ?? 'system'
        ];

        // Validate
        $errors = [];
        if (empty($data['malo'])) {
            $errors[] = 'Mã lô không được để trống';
        }
        if (empty($data['tenlo'])) {
            $errors[] = 'Tên lô không được để trống';
        }
        
        // Kiểm tra trùng mã
        if ($this->model->isCodeExists($data['malo'])) {
            $errors[] = 'Mã lô đã tồn tại';
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode(', ', $errors);
            header("Location: /iso2/lo.php?action=create");
            exit;
        }

        if ($this->model->create($data)) {
            $_SESSION['success'] = 'Đã tạo lô mới';
            header("Location: /iso2/lo.php");
        } else {
            $_SESSION['error'] = 'Có lỗi khi tạo lô';
            header("Location: /iso2/lo.php?action=create");
        }
        exit;
    }

    public function edit(): void
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if (!$id) {
            header("Location: /iso2/lo.php");
            exit;
        }

        $item = $this->model->findById($id);
        
        if (!$item) {
            $_SESSION['error'] = 'Không tìm thấy lô';
            header("Location: /iso2/lo.php");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            require_once __DIR__ . '/../views/lo/edit.php';
            return;
        }

        // POST: Xử lý cập nhật
        $data = [
            'malo' => trim($_POST['malo'] ?? ''),
            'tenlo' => trim($_POST['tenlo'] ?? ''),
            'ghichu' => trim($_POST['ghichu'] ?? ''),
            'nguoisua' => $_SESSION['username'] ?? 'system'
        ];

        // Validate
        $errors = [];
        if (empty($data['malo'])) {
            $errors[] = 'Mã lô không được để trống';
        }
        if (empty($data['tenlo'])) {
            $errors[] = 'Tên lô không được để trống';
        }
        
        // Kiểm tra trùng mã (trừ chính nó)
        if ($this->model->isCodeExists($data['malo'], $id)) {
            $errors[] = 'Mã lô đã tồn tại';
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode(', ', $errors);
            header("Location: /iso2/lo.php?action=edit&id=" . $id);
            exit;
        }

        if ($this->model->update($id, $data)) {
            $_SESSION['success'] = 'Đã cập nhật lô';
            header("Location: /iso2/lo.php");
        } else {
            $_SESSION['error'] = 'Có lỗi khi cập nhật lô';
            header("Location: /iso2/lo.php?action=edit&id=" . $id);
        }
        exit;
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /iso2/lo.php");
            exit;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        if (!$id) {
            $_SESSION['error'] = 'ID không hợp lệ';
            header("Location: /iso2/lo.php");
            exit;
        }

        if ($this->model->delete($id)) {
            $_SESSION['success'] = 'Đã xóa lô';
        } else {
            $_SESSION['error'] = 'Có lỗi khi xóa lô';
        }

        header("Location: /iso2/lo.php");
        exit;
    }
}
