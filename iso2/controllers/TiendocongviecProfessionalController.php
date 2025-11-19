<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/TiendocongviecProfessional.php';
require_once __DIR__ . '/../models/TiendocongviecPause.php';

class TiendocongviecProfessionalController {
    private TiendocongviecProfessional $model;
    private TiendocongviecPause $pauseModel;
    
    public function __construct() {
        $this->model = new TiendocongviecProfessional();
        $this->pauseModel = new TiendocongviecPause();
    }

    public function index(): array {
        // Lấy tham số filter, search, phân trang
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 15;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $status = isset($_GET['status']) ? $_GET['status'] : '';

        // Lấy danh sách tiến độ công việc (có filter, search, phân trang)
        $offset = ($page - 1) * $perPage;
        $workProgressList = $this->model->getList($search, $status, $offset, $perPage);
        $total = $this->model->countList($search, $status);
        $totalPages = ceil($total / $perPage);

        // Thống kê nhanh
        $stats = $this->model->getStats();

        // Lấy danh sách bảo dưỡng định kỳ (giả lập, có thể mở rộng)
        $maintenanceList = $this->model->getMaintenanceList($offset, $perPage);
        $maintenanceTotal = $this->model->countMaintenanceList();
        $maintenancePages = ceil($maintenanceTotal / $perPage);

    $title = 'Tiến độ công việc';
    // Trả về mảng dữ liệu cho view
    return compact('title', 'workProgressList', 'total', 'totalPages', 'stats', 'maintenanceList', 'maintenanceTotal', 'maintenancePages');
    }

    public function create(): void {
        $title = 'Thêm tiến độ công việc';
        require __DIR__ . '/../views/tiendocongviec_professional/create.php';
    }

    public function store(array $data): void {
        $this->model->create($data);
        header('Location: tiendocongviec2.php');
        exit;
    }

    public function edit(int $id): void {
        $item = $this->model->find($id);
        $title = 'Sửa tiến độ công việc';
        require __DIR__ . '/../views/tiendocongviec_professional/edit.php';
    }

    public function update(int $id, array $data): void {
        $this->model->update($id, $data);
        header('Location: tiendocongviec2.php');
        exit;
    }

    public function delete(int $id): void {
        $this->model->delete($id);
        header('Location: tiendocongviec2.php');
        exit;
    }

    // AJAX: Danh sách tạm dừng cho 1 công việc
    public function ajaxListPause(int $work_id): void {
        header('Content-Type: application/json');
        $list = $this->pauseModel->getByWorkId($work_id);
        echo json_encode(['success'=>true, 'data'=>$list]);
        exit;
    }

    // AJAX: Thêm tạm dừng
    public function ajaxAddPause(array $data): void {
        header('Content-Type: application/json');
        $id = $this->pauseModel->createPause($data);
        echo json_encode(['success'=>true, 'id'=>$id]);
        exit;
    }

    // AJAX: Sửa tạm dừng
    public function ajaxUpdatePause(int $id, array $data): void {
        header('Content-Type: application/json');
        $ok = $this->pauseModel->updatePause($id, $data);
        echo json_encode(['success'=>$ok>0]);
        exit;
    }

    // AJAX: Xóa tạm dừng
    public function ajaxDeletePause(int $id): void {
        header('Content-Type: application/json');
        $ok = $this->pauseModel->deletePause($id);
        echo json_encode(['success'=>$ok>0]);
        exit;
    }
}
