<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/ThietBi.php';
require_once __DIR__ . '/../models/DonVi.php';

class ThietBiController
{
    private ThietBi $model;
    private DonVi $donViModel;

    public function __construct()
    {
        $this->model = new ThietBi();
        $this->donViModel = new DonVi();
    }

    public function index(): void
    {
        try {
            $search = $_GET['search'] ?? '';
            $madv = $_GET['madv'] ?? '';
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = 20;
            $offset = ($page - 1) * $limit;

            $conditions = [];
            $params = [];

            if ($search) {
                $conditions[] = "(mavt LIKE :search OR tenvt LIKE :search OR somay LIKE :search OR model LIKE :search)";
                $params[':search'] = "%$search%";
            }

            if ($madv !== '') {
                $conditions[] = "madv = :madv";
                $params[':madv'] = $madv;
            }

            $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
            $orderBy = 'ORDER BY stt DESC';
            
            $items = $this->model->getAll($where . ' ' . $orderBy, $params, $limit, $offset);
            $total = $this->model->count($where, $params);
            $totalPages = ceil($total / $limit);

            $donViList = $this->donViModel->getAllSimple();

            require_once __DIR__ . '/../views/thietbi/index.php';
        } catch (Exception $e) {
            error_log("Error in ThietBiController::index: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Set default values to prevent view errors
            $items = [];
            $total = 0;
            $totalPages = 0;
            $donViList = [];
            $search = '';
            $madv = '';
            $page = 1;
            $error = 'Có lỗi xảy ra: ' . $e->getMessage();
            
            require_once __DIR__ . '/../views/thietbi/index.php';
        }
    }

    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'mavt' => trim($_POST['mavt'] ?? ''),
                'tenvt' => trim($_POST['tenvt'] ?? ''),
                'somay' => trim($_POST['somay'] ?? ''),
                'model' => trim($_POST['model'] ?? ''),
                'homay' => trim($_POST['homay'] ?? ''),
                'dienap' => trim($_POST['dienap'] ?? ''),
                'thongtincb' => trim($_POST['thongtincb'] ?? ''),
                'loaidau' => trim($_POST['loaidau'] ?? ''),
                'mucdau' => trim($_POST['mucdau'] ?? ''),
                'madv' => trim($_POST['madv'] ?? ''),
                'bdtime' => (int)($_POST['bdtime'] ?? 0),
                'ngayktsd' => trim($_POST['ngayktsd'] ?? date('Y-m-d')),
                'tlkt' => trim($_POST['tlkt'] ?? ''),
                'hosomay' => trim($_POST['hosomay'] ?? ''),
                'mamay' => trim($_POST['mamay'] ?? '')
            ];

            $errors = [];
            if (empty($data['mavt'])) $errors[] = 'Mã vật tư không được để trống';
            if (empty($data['tenvt'])) $errors[] = 'Tên vật tư không được để trống';
            if (empty($data['somay'])) $errors[] = 'Số máy không được để trống';
            if (empty($data['model'])) $errors[] = 'Model không được để trống';
            if (empty($data['homay'])) $errors[] = 'Hộp máy không được để trống';
            if (empty($data['dienap'])) $errors[] = 'Điện áp không được để trống';
            if (empty($data['mucdau'])) $errors[] = 'Mức dầu không được để trống';
            if (empty($data['madv'])) $errors[] = 'Đơn vị không được để trống';
            if (empty($data['mamay'])) $errors[] = 'Mã máy không được để trống';

            if (empty($errors)) {
                $id = $this->model->create($data);
                if ($id) {
                    header('Location: /iso2/thietbi.php?success=created');
                    exit;
                }
                $errors[] = 'Có lỗi xảy ra khi tạo thiết bị';
            }

            $error = implode(', ', $errors);
        }

        $donViList = $this->donViModel->getAllSimple();
        require_once __DIR__ . '/../views/thietbi/create.php';
    }

    public function edit(): void
    {
        $stt = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$stt) {
            header('Location: /iso2/thietbi.php?error=invalid');
            exit;
        }

        $item = $this->model->findById($stt);
        if (!$item) {
            header('Location: /iso2/thietbi.php?error=notfound');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'mavt' => trim($_POST['mavt'] ?? ''),
                'tenvt' => trim($_POST['tenvt'] ?? ''),
                'somay' => trim($_POST['somay'] ?? ''),
                'model' => trim($_POST['model'] ?? ''),
                'homay' => trim($_POST['homay'] ?? ''),
                'dienap' => trim($_POST['dienap'] ?? ''),
                'thongtincb' => trim($_POST['thongtincb'] ?? ''),
                'loaidau' => trim($_POST['loaidau'] ?? ''),
                'mucdau' => trim($_POST['mucdau'] ?? ''),
                'madv' => trim($_POST['madv'] ?? ''),
                'bdtime' => (int)($_POST['bdtime'] ?? 0),
                'ngayktsd' => trim($_POST['ngayktsd'] ?? date('Y-m-d')),
                'tlkt' => trim($_POST['tlkt'] ?? ''),
                'hosomay' => trim($_POST['hosomay'] ?? ''),
                'mamay' => trim($_POST['mamay'] ?? '')
            ];

            $errors = [];
            if (empty($data['mavt'])) $errors[] = 'Mã vật tư không được để trống';
            if (empty($data['tenvt'])) $errors[] = 'Tên vật tư không được để trống';
            if (empty($data['somay'])) $errors[] = 'Số máy không được để trống';
            if (empty($data['model'])) $errors[] = 'Model không được để trống';
            if (empty($data['homay'])) $errors[] = 'Hộp máy không được để trống';
            if (empty($data['dienap'])) $errors[] = 'Điện áp không được để trống';
            if (empty($data['mucdau'])) $errors[] = 'Mức dầu không được để trống';
            if (empty($data['madv'])) $errors[] = 'Đơn vị không được để trống';
            if (empty($data['mamay'])) $errors[] = 'Mã máy không được để trống';

            if (empty($errors)) {
                $success = $this->model->update($stt, $data);
                if ($success) {
                    header('Location: /iso2/thietbi.php?success=updated');
                    exit;
                }
                $errors[] = 'Có lỗi xảy ra khi cập nhật thiết bị';
            }

            $error = implode(', ', $errors);
        }

        $donViList = $this->donViModel->getAllSimple();
        require_once __DIR__ . '/../views/thietbi/edit.php';
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /iso2/thietbi.php');
            exit;
        }

        $stt = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if (!$stt) {
            header('Location: /iso2/thietbi.php?error=invalid');
            exit;
        }

        $success = $this->model->delete($stt);
        if ($success) {
            header('Location: /iso2/thietbi.php?success=deleted');
        } else {
            header('Location: /iso2/thietbi.php?error=delete_failed');
        }
        exit;
    }
}
