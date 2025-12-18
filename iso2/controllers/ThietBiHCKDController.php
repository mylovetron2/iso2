<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/ThietBiHCKD.php';
require_once __DIR__ . '/../models/DonVi.php';

class ThietBiHCKDController
{
    private ThietBiHCKD $model;
    private DonVi $donViModel;

    public function __construct()
    {
        $this->model = new ThietBiHCKD();
        $this->donViModel = new DonVi();
    }

    public function index(): void
    {
        try {
            $search = $_GET['search'] ?? '';
            $bophansh = $_GET['bophansh'] ?? '';
            $loaitb = $_GET['loaitb'] ?? '';
            $filter = $_GET['filter'] ?? ''; // all, saphethan, dahethan
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = 20;
            $offset = ($page - 1) * $limit;

            $conditions = [];
            $params = [];

            if ($search) {
                $conditions[] = "(mavattu LIKE :search OR tenviettat LIKE :search OR tenthietbi LIKE :search OR somay LIKE :search OR hangsx LIKE :search)";
                $params[':search'] = "%$search%";
            }

            if ($bophansh !== '') {
                $conditions[] = "bophansh = :bophansh";
                $params[':bophansh'] = $bophansh;
            }

            if ($loaitb !== '') {
                $conditions[] = "loaitb = :loaitb";
                $params[':loaitb'] = $loaitb;
            }

            // Filter by expiry status
            if ($filter === 'saphethan') {
                $conditions[] = "ngayktnghiemthu IS NOT NULL AND DATEDIFF(DATE_ADD(ngayktnghiemthu, INTERVAL CAST(thoihankd AS SIGNED) MONTH), CURDATE()) <= 30 AND DATEDIFF(DATE_ADD(ngayktnghiemthu, INTERVAL CAST(thoihankd AS SIGNED) MONTH), CURDATE()) >= 0";
            } elseif ($filter === 'dahethan') {
                $conditions[] = "ngayktnghiemthu IS NOT NULL AND DATEDIFF(DATE_ADD(ngayktnghiemthu, INTERVAL CAST(thoihankd AS SIGNED) MONTH), CURDATE()) < 0";
            }

            $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
            $orderBy = 'ORDER BY stt DESC';
            
            $items = $this->model->getAll($where . ' ' . $orderBy, $params, $limit, $offset);
            $total = $this->model->count($where, $params);
            $totalPages = ceil($total / $limit);

            $boPhanList = $this->model->getAllBoPhanSH();
            $loaiTBList = $this->model->getAllLoaiTB();

            require_once __DIR__ . '/../views/thietbihckd/index.php';
        } catch (Exception $e) {
            error_log("Error in ThietBiHCKDController::index: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Set default values to prevent view errors
            $items = [];
            $total = 0;
            $totalPages = 0;
            $boPhanList = [];
            $loaiTBList = [];
            $search = '';
            $bophansh = '';
            $loaitb = '';
            $filter = '';
            $page = 1;
            $error = 'Có lỗi xảy ra: ' . $e->getMessage();
            
            require_once __DIR__ . '/../views/thietbihckd/index.php';
        }
    }

    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'mavattu' => trim($_POST['mavattu'] ?? ''),
                'tenviettat' => trim($_POST['tenviettat'] ?? ''),
                'tenthietbi' => trim($_POST['tenthietbi'] ?? ''),
                'somay' => trim($_POST['somay'] ?? ''),
                'hangsx' => trim($_POST['hangsx'] ?? ''),
                'bophansh' => trim($_POST['bophansh'] ?? ''),
                'chusohuu' => trim($_POST['chusohuu'] ?? ''),
                'thoihankd' => trim($_POST['thoihankd'] ?? ''),
                'ngayktnghiemthu' => trim($_POST['ngayktnghiemthu'] ?? ''),
                'loaitb' => trim($_POST['loaitb'] ?? ''),
                'tlkt' => trim($_POST['tlkt'] ?? ''),
                'danchuan' => (int)($_POST['danchuan'] ?? 0)
            ];

            $errors = [];
            if (empty($data['mavattu'])) $errors[] = 'Mã vật tư không được để trống';
            if (empty($data['tenviettat'])) $errors[] = 'Tên viết tắt không được để trống';
            if (empty($data['tenthietbi'])) $errors[] = 'Tên thiết bị không được để trống';
            if (empty($data['somay'])) $errors[] = 'Số máy không được để trống';
            if (empty($data['bophansh'])) $errors[] = 'Bộ phận sử dụng không được để trống';

            if (empty($errors)) {
                $id = $this->model->create($data);
                if ($id) {
                    header('Location: /iso2/thietbihckd.php?success=created');
                    exit;
                }
                $errors[] = 'Có lỗi xảy ra khi tạo thiết bị';
            }

            $error = implode(', ', $errors);
        }

        $boPhanList = $this->model->getAllBoPhanSH();
        $loaiTBList = $this->model->getAllLoaiTB();
        require_once __DIR__ . '/../views/thietbihckd/create.php';
    }

    public function edit(): void
    {
        $stt = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$stt) {
            header('Location: /iso2/thietbihckd.php?error=invalid');
            exit;
        }

        $item = $this->model->findById($stt);
        if (!$item) {
            header('Location: /iso2/thietbihckd.php?error=notfound');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'mavattu' => trim($_POST['mavattu'] ?? ''),
                'tenviettat' => trim($_POST['tenviettat'] ?? ''),
                'tenthietbi' => trim($_POST['tenthietbi'] ?? ''),
                'somay' => trim($_POST['somay'] ?? ''),
                'hangsx' => trim($_POST['hangsx'] ?? ''),
                'bophansh' => trim($_POST['bophansh'] ?? ''),
                'chusohuu' => trim($_POST['chusohuu'] ?? ''),
                'thoihankd' => trim($_POST['thoihankd'] ?? ''),
                'ngayktnghiemthu' => trim($_POST['ngayktnghiemthu'] ?? ''),
                'loaitb' => trim($_POST['loaitb'] ?? ''),
                'tlkt' => trim($_POST['tlkt'] ?? ''),
                'danchuan' => (int)($_POST['danchuan'] ?? 0)
            ];

            $errors = [];
            if (empty($data['mavattu'])) $errors[] = 'Mã vật tư không được để trống';
            if (empty($data['tenviettat'])) $errors[] = 'Tên viết tắt không được để trống';
            if (empty($data['tenthietbi'])) $errors[] = 'Tên thiết bị không được để trống';
            if (empty($data['somay'])) $errors[] = 'Số máy không được để trống';
            if (empty($data['bophansh'])) $errors[] = 'Bộ phận sử dụng không được để trống';

            if (empty($errors)) {
                $success = $this->model->update($stt, $data);
                if ($success) {
                    header('Location: /iso2/thietbihckd.php?success=updated');
                    exit;
                }
                $errors[] = 'Có lỗi xảy ra khi cập nhật thiết bị';
            }

            $error = implode(', ', $errors);
        }

        $boPhanList = $this->model->getAllBoPhanSH();
        $loaiTBList = $this->model->getAllLoaiTB();
        require_once __DIR__ . '/../views/thietbihckd/edit.php';
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /iso2/thietbihckd.php');
            exit;
        }

        $stt = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if (!$stt) {
            header('Location: /iso2/thietbihckd.php?error=invalid');
            exit;
        }

        $success = $this->model->delete($stt);
        if ($success) {
            header('Location: /iso2/thietbihckd.php?success=deleted');
        } else {
            header('Location: /iso2/thietbihckd.php?error=delete_failed');
        }
        exit;
    }
}
