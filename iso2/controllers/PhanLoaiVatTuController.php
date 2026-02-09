<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/PhanLoaiVatTu.php';

class PhanLoaiVatTuController
{
    private PhanLoaiVatTu $model;

    public function __construct()
    {
        $this->model = new PhanLoaiVatTu();
    }

    public function index(): void
    {
        try {
            $items = $this->model->getAllOrdered();
            
            // Lấy số lượng vật tư sử dụng mỗi phân loại
            foreach ($items as $key => $item) {
                $items[$key]['so_luong_vattu'] = $this->model->countUsedInVatTu((int)$item['id']);
            }
            
            require_once __DIR__ . '/../views/phanloaivattu/index.php';
        } catch (Exception $e) {
            error_log("Error in PhanLoaiVatTuController::index: " . $e->getMessage());
            $items = [];
            $error = 'Có lỗi xảy ra: ' . $e->getMessage();
            require_once __DIR__ . '/../views/phanloaivattu/index.php';
        }
    }
    
    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'ma_phanloai' => strtoupper(trim($_POST['ma_phanloai'] ?? '')),
                'ten_phanloai' => trim($_POST['ten_phanloai'] ?? ''),
                'mau_sac' => trim($_POST['mau_sac'] ?? ''),
                'thu_tu' => !empty($_POST['thu_tu']) ? (int)$_POST['thu_tu'] : 0,
                'mo_ta' => trim($_POST['mo_ta'] ?? '') ?: null,
            ];
            
            // Validate
            if (empty($data['ma_phanloai']) || empty($data['ten_phanloai'])) {
                header('Location: /iso2/phanloaivattu.php?action=create&error=Vui lòng điền đầy đủ thông tin bắt buộc');
                exit;
            }
            
            // Kiểm tra mã đã tồn tại
            if ($this->model->isCodeExists($data['ma_phanloai'])) {
                header('Location: /iso2/phanloaivattu.php?action=create&error=Mã phân loại đã tồn tại');
                exit;
            }
            
            if ($this->model->create($data)) {
                header('Location: /iso2/phanloaivattu.php?success=created');
                exit;
            } else {
                header('Location: /iso2/phanloaivattu.php?error=create_failed');
                exit;
            }
        }
        
        require_once __DIR__ . '/../views/phanloaivattu/create.php';
    }
    
    public function edit(): void
    {
        try {
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                header('Location: /iso2/phanloaivattu.php?error=missing_id');
                exit;
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = [
                    'ma_phanloai' => strtoupper(trim($_POST['ma_phanloai'] ?? '')),
                    'ten_phanloai' => trim($_POST['ten_phanloai'] ?? ''),
                    'mau_sac' => trim($_POST['mau_sac'] ?? ''),
                    'thu_tu' => !empty($_POST['thu_tu']) ? (int)$_POST['thu_tu'] : 0,
                    'mo_ta' => trim($_POST['mo_ta'] ?? '') ?: null,
                ];
                
                // Validate
                if (empty($data['ma_phanloai']) || empty($data['ten_phanloai'])) {
                    header('Location: /iso2/phanloaivattu.php?action=edit&id=' . $id . '&error=Vui lòng điền đầy đủ thông tin bắt buộc');
                    exit;
                }
                
                // Kiểm tra mã đã tồn tại (trừ chính nó)
                if ($this->model->isCodeExists($data['ma_phanloai'], (int)$id)) {
                    header('Location: /iso2/phanloaivattu.php?action=edit&id=' . $id . '&error=Mã phân loại đã tồn tại');
                    exit;
                }
                
                if ($this->model->update((int)$id, $data)) {
                    header('Location: /iso2/phanloaivattu.php?success=updated');
                    exit;
                } else {
                    header('Location: /iso2/phanloaivattu.php?error=update_failed');
                    exit;
                }
            }
            
            $item = $this->model->findById((int)$id);
            if (!$item) {
                header('Location: /iso2/phanloaivattu.php?error=not_found');
                exit;
            }
            
            // Đếm số vật tư đang sử dụng
            $item['so_luong_vattu'] = $this->model->countUsedInVatTu((int)$id);
            
            require_once __DIR__ . '/../views/phanloaivattu/edit.php';
        } catch (Exception $e) {
            error_log("Error in PhanLoaiVatTuController::edit: " . $e->getMessage());
            $error = 'Có lỗi xảy ra: ' . $e->getMessage();
            $item = [];
            require_once __DIR__ . '/../views/phanloaivattu/edit.php';
        }
    }
    
    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /iso2/phanloaivattu.php');
            exit;
        }
        
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            header('Location: /iso2/phanloaivattu.php?error=missing_id');
            exit;
        }
        
        // Kiểm tra có đang được sử dụng không
        if ($this->model->isUsedInVatTu((int)$id)) {
            $count = $this->model->countUsedInVatTu((int)$id);
            header('Location: /iso2/phanloaivattu.php?error=Không thể xóa! Phân loại này đang được sử dụng bởi ' . $count . ' vật tư');
            exit;
        }
        
        if ($this->model->delete((int)$id)) {
            header('Location: /iso2/phanloaivattu.php?success=deleted');
        } else {
            header('Location: /iso2/phanloaivattu.php?error=delete_failed');
        }
        exit;
    }
}
