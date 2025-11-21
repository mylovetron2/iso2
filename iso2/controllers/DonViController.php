<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/DonVi.php';

class DonViController
{
    private DonVi $model;

    public function __construct()
    {
        $this->model = new DonVi();
    }

    public function index(): void
    {
        $search = $_GET['search'] ?? '';
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $conditions = [];
        $params = [];

        if ($search) {
            $conditions[] = "(madv LIKE :search OR tendv LIKE :search)";
            $params[':search'] = "%$search%";
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $orderBy = 'ORDER BY stt DESC';
        
        $items = $this->model->getAll($where . ' ' . $orderBy, $params, $limit, $offset);
        $total = $this->model->count($where, $params);
        $totalPages = ceil($total / $limit);

        require_once __DIR__ . '/../views/donvi/index.php';
    }

    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'madv' => trim($_POST['madv'] ?? ''),
                'tendv' => trim($_POST['tendv'] ?? '')
            ];

            $errors = [];
            if (empty($data['madv'])) {
                $errors[] = 'Mã đơn vị không được để trống';
            }
            if (empty($data['tendv'])) {
                $errors[] = 'Tên đơn vị không được để trống';
            }

            if (empty($errors) && $this->model->existsMaDV($data['madv'])) {
                $errors[] = 'Mã đơn vị đã tồn tại';
            }

            if (empty($errors)) {
                $id = $this->model->create($data);
                if ($id) {
                    header('Location: /iso2/donvi.php?success=created');
                    exit;
                }
                $errors[] = 'Có lỗi xảy ra khi tạo đơn vị';
            }

            $error = implode(', ', $errors);
        }

        require_once __DIR__ . '/../views/donvi/create.php';
    }

    public function edit(): void
    {
        $stt = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$stt) {
            header('Location: /iso2/donvi.php?error=invalid');
            exit;
        }

        $item = $this->model->findById($stt);
        if (!$item) {
            header('Location: /iso2/donvi.php?error=notfound');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'madv' => trim($_POST['madv'] ?? ''),
                'tendv' => trim($_POST['tendv'] ?? '')
            ];

            $errors = [];
            if (empty($data['madv'])) {
                $errors[] = 'Mã đơn vị không được để trống';
            }
            if (empty($data['tendv'])) {
                $errors[] = 'Tên đơn vị không được để trống';
            }

            if (empty($errors) && $data['madv'] !== $item['madv']) {
                if ($this->model->existsMaDV($data['madv'])) {
                    $errors[] = 'Mã đơn vị đã tồn tại';
                }
            }

            if (empty($errors)) {
                $success = $this->model->update($stt, $data);
                if ($success) {
                    header('Location: /iso2/donvi.php?success=updated');
                    exit;
                }
                $errors[] = 'Có lỗi xảy ra khi cập nhật đơn vị';
            }

            $error = implode(', ', $errors);
        }

        require_once __DIR__ . '/../views/donvi/edit.php';
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /iso2/donvi.php');
            exit;
        }

        $stt = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if (!$stt) {
            header('Location: /iso2/donvi.php?error=invalid');
            exit;
        }

        $success = $this->model->delete($stt);
        if ($success) {
            header('Location: /iso2/donvi.php?success=deleted');
        } else {
            header('Location: /iso2/donvi.php?error=delete_failed');
        }
        exit;
    }
}
