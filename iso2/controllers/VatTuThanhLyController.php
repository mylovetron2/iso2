<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/VatTuThanhLy.php';

class VatTuThanhLyController
{
    private VatTuThanhLy $model;

    public function __construct()
    {
        $this->model = new VatTuThanhLy();
    }

    public function index(): void
    {
        try {
            $search = $_GET['search'] ?? '';
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = 20;
            $offset = ($page - 1) * $limit;

            $conditions = [];
            $params = [];

            if ($search) {
                $conditions[] = "(v.mavattu LIKE :search1 OR v.ten_tienganh LIKE :search2 OR v.ten_tiengnga LIKE :search3 OR v.ten_tiengviet LIKE :search4 OR v.nguoiquanly LIKE :search5)";
                $params[':search1'] = "%$search%";
                $params[':search2'] = "%$search%";
                $params[':search3'] = "%$search%";
                $params[':search4'] = "%$search%";
                $params[':search5'] = "%$search%";
            }

            $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            $items = $this->model->getAllWithStats($where, $params, $limit, $offset);
            $total = $this->model->count($where, $params);
            $totalPages = ceil($total / $limit);

            require_once __DIR__ . '/../views/vattuthanhly/index.php';
        } catch (Exception $e) {
            error_log("Error in VatTuThanhLyController::index: " . $e->getMessage());
            
            $items = [];
            $total = 0;
            $totalPages = 0;
            $error = 'Có lỗi xảy ra: ' . $e->getMessage();
            
            require_once __DIR__ . '/../views/vattuthanhly/index.php';
        }
    }
    
    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Xử lý dữ liệu, convert empty string thành NULL cho các trường số
            $data = [
                'mavattu' => $_POST['mavattu'] ?? null,
                'ten_tienganh' => $_POST['ten_tienganh'] ?? null,
                'ten_tiengnga' => $_POST['ten_tiengnga'] ?? null,
                'ten_tiengviet' => $_POST['ten_tiengviet'] ?? null,
                'dactinhkt_tiengnga' => $_POST['dactinhkt_tiengnga'] ?? null,
                'dactinhkt_tiengviet' => $_POST['dactinhkt_tiengviet'] ?? null,
                'dvt_tiengnga' => $_POST['dvt_tiengnga'] ?? null,
                'dvt_tiengviet' => $_POST['dvt_tiengviet'] ?? null,
                'soluong_conlai' => !empty($_POST['soluong_conlai']) ? $_POST['soluong_conlai'] : null,
                'dongia' => !empty($_POST['dongia']) ? $_POST['dongia'] : null,
                'ngaynhan' => !empty($_POST['ngaynhan']) ? $_POST['ngaynhan'] : null,
                'sohd' => $_POST['sohd'] ?? null,
                'ngaykyhd' => !empty($_POST['ngaykyhd']) ? $_POST['ngaykyhd'] : null,
                'nguoiquanly' => $_POST['nguoiquanly'] ?? null,
                'vitribaoquan' => $_POST['vitribaoquan'] ?? null,
                'ghichu' => $_POST['ghichu'] ?? null,
            ];
            
            if ($this->model->create($data)) {
                header('Location: /iso2/vattuthanhly.php?success=created');
                exit;
            } else {
                header('Location: /iso2/vattuthanhly.php?error=create_failed');
                exit;
            }
        }
        
        require_once __DIR__ . '/../views/vattuthanhly/create.php';
    }
    
    public function edit(): void
    {
        try {
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                header('Location: /iso2/vattuthanhly.php?error=missing_id');
                exit;
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Xử lý dữ liệu, convert empty string thành NULL cho các trường số
                $data = [
                    'mavattu' => $_POST['mavattu'] ?? null,
                    'ten_tienganh' => $_POST['ten_tienganh'] ?? null,
                    'ten_tiengnga' => $_POST['ten_tiengnga'] ?? null,
                    'ten_tiengviet' => $_POST['ten_tiengviet'] ?? null,
                    'dactinhkt_tiengnga' => $_POST['dactinhkt_tiengnga'] ?? null,
                    'dactinhkt_tiengviet' => $_POST['dactinhkt_tiengviet'] ?? null,
                    'dvt_tiengnga' => $_POST['dvt_tiengnga'] ?? null,
                    'dvt_tiengviet' => $_POST['dvt_tiengviet'] ?? null,
                    'soluong_conlai' => !empty($_POST['soluong_conlai']) ? $_POST['soluong_conlai'] : null,
                    'dongia' => !empty($_POST['dongia']) ? $_POST['dongia'] : null,
                    'ngaynhan' => !empty($_POST['ngaynhan']) ? $_POST['ngaynhan'] : null,
                    'sohd' => $_POST['sohd'] ?? null,
                    'ngaykyhd' => !empty($_POST['ngaykyhd']) ? $_POST['ngaykyhd'] : null,
                    'nguoiquanly' => $_POST['nguoiquanly'] ?? null,
                    'vitribaoquan' => $_POST['vitribaoquan'] ?? null,
                    'ghichu' => $_POST['ghichu'] ?? null,
                ];
                
                if ($this->model->update((int)$id, $data)) {
                    header('Location: /iso2/vattuthanhly.php?success=updated');
                    exit;
                } else {
                    header('Location: /iso2/vattuthanhly.php?error=update_failed');
                    exit;
                }
            }
            
            $item = $this->model->findById((int)$id);
            if (!$item) {
                header('Location: /iso2/vattuthanhly.php?error=not_found');
                exit;
            }
            
            require_once __DIR__ . '/../views/vattuthanhly/edit.php';
        } catch (Exception $e) {
            error_log("Error in VatTuThanhLyController::edit: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            $error = 'Có lỗi xảy ra: ' . $e->getMessage();
            $item = [];
            
            require_once __DIR__ . '/../views/vattuthanhly/edit.php';
        }
    }
    
    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /iso2/vattuthanhly.php');
            exit;
        }
        
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            header('Location: /iso2/vattuthanhly.php?error=missing_id');
            exit;
        }
        
        if ($this->model->delete((int)$id)) {
            header('Location: /iso2/vattuthanhly.php?success=deleted');
        } else {
            header('Location: /iso2/vattuthanhly.php?error=delete_failed');
        }
        exit;
    }
    
    /**
     * API: Lấy chi tiết sử dụng của vật tư
     */
    public function getChiTiet(): void
    {
        header('Content-Type: application/json');
        
        $vattuStt = $_GET['vattu_stt'] ?? null;
        
        if (!$vattuStt) {
            echo json_encode(['error' => 'Missing vattu_stt']);
            exit;
        }
        
        $chiTiet = $this->model->getChiTietSuDung((int)$vattuStt);
        echo json_encode(['success' => true, 'data' => $chiTiet]);
        exit;
    }
    
    /**
     * API: Thêm chi tiết sử dụng
     */
    public function addChiTiet(): void
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Invalid request method']);
            exit;
        }
        
        $data = [
            'vattu_stt' => $_POST['vattu_stt'] ?? null,
            'nguoisudung' => $_POST['nguoisudung'] ?? null,
            'ngaysd_nhan' => $_POST['ngaysd_nhan'] ?? null,
            'soluong' => $_POST['soluong'] ?? null,
            'bophan' => $_POST['bophan'] ?? null,
            'mucdich_sudung' => $_POST['mucdich_sudung'] ?? null,
            'trangthai' => $_POST['trangthai'] ?? 'dangdung',
            'ghichu' => $_POST['ghichu'] ?? null,
        ];
        
        if ($this->model->addChiTietSuDung($data)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Failed to add chi tiet']);
        }
        exit;
    }
    
    /**
     * API: Xóa chi tiết sử dụng
     */
    public function deleteChiTiet(): void
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Invalid request method']);
            exit;
        }
        
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            echo json_encode(['error' => 'Missing id']);
            exit;
        }
        
        if ($this->model->deleteChiTietSuDung((int)$id)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Failed to delete chi tiet']);
        }
        exit;
    }
}
