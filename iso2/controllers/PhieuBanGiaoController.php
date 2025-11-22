<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/PhieuBanGiao.php';
require_once __DIR__ . '/../models/PhieuBanGiaoThietBi.php';
require_once __DIR__ . '/../models/HoSoSCBD.php';
require_once __DIR__ . '/../models/DonVi.php';

class PhieuBanGiaoController
{
    private PhieuBanGiao $model;
    private PhieuBanGiaoThietBi $thietBiModel;
    private HoSoSCBD $hosoModel;
    private DonVi $donViModel;

    public function __construct()
    {
        $this->model = new PhieuBanGiao();
        $this->thietBiModel = new PhieuBanGiaoThietBi();
        $this->hosoModel = new HoSoSCBD();
        $this->donViModel = new DonVi();
    }

    public function index(): void
    {
        $search = $_GET['search'] ?? '';
        $phieuyc = $_GET['phieuyc'] ?? '';
        $trangthai = $_GET['trangthai'] ?? '';
        $donvi = $_GET['donvi'] ?? '';
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $items = $this->model->getList($search, $phieuyc, $trangthai, $donvi, $offset, $limit);
        $total = $this->model->countList($search, $phieuyc, $trangthai, $donvi);
        $totalPages = ceil($total / $limit);

        $stats = $this->model->getStats();
        $donViList = $this->donViModel->getAllSimple();

        require_once __DIR__ . '/../views/phieubangiao/index.php';
    }

    public function selectDevices(): void
    {
        // Step 1: Hiển thị trang chọn thiết bị
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $donViList = $this->donViModel->getAllSimple();
            require_once __DIR__ . '/../views/phieubangiao/select_devices.php';
            return;
        }

        // Step 2: Xử lý khi submit chọn thiết bị
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_devices'])) {
            $selectedIds = $_POST['selected_devices']; // Array of hososcbd_stt
            
            if (empty($selectedIds)) {
                $_SESSION['error'] = 'Vui lòng chọn ít nhất 1 thiết bị';
                header("Location: /iso2/phieubangiao.php?action=select");
                exit;
            }

            // Load thiết bị đã chọn
            $devices = [];
            foreach ($selectedIds as $id) {
                $device = $this->hosoModel->findById((int)$id);
                if ($device && $device['bg'] == 0) { // Chỉ lấy thiết bị chưa BG
                    $devices[] = $device;
                }
            }

            // Nhóm thiết bị theo phiếu YC
            $groupedByPhieu = [];
            foreach ($devices as $device) {
                $groupedByPhieu[$device['phieu']][] = $device;
            }

            // Lưu vào session để dùng ở bước confirm
            $_SESSION['pbg_temp_devices'] = $groupedByPhieu;

            header("Location: /iso2/phieubangiao.php?action=confirm");
            exit;
        }
    }

    public function confirmCreate(): void
    {
        // Load thiết bị đã chọn từ session
        if (!isset($_SESSION['pbg_temp_devices']) || empty($_SESSION['pbg_temp_devices'])) {
            header("Location: /iso2/phieubangiao.php?action=select");
            exit;
        }

        $groupedByPhieu = $_SESSION['pbg_temp_devices'];
        $donViList = $this->donViModel->getAllSimple();

        // GET: Hiển thị form xác nhận
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            require_once __DIR__ . '/../views/phieubangiao/confirm_create.php';
            return;
        }

        // POST: Xử lý tạo phiếu bàn giao
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = [];
            $createdCount = 0;

            try {
                foreach ($groupedByPhieu as $phieuyc => $devices) {
                    // Tạo phiếu bàn giao
                    $sophieu = $this->model->getNextSoPhieu();
                    
                    $phieuData = [
                        'sophieu' => $sophieu,
                        'phieuyc' => $phieuyc,
                        'ngaybg' => $_POST['ngaybg_' . $phieuyc] ?? date('Y-m-d'),
                        'nguoigiao' => $_POST['nguoigiao_' . $phieuyc] ?? '',
                        'nguoinhan' => $_POST['nguoinhan_' . $phieuyc] ?? '',
                        'donvigiao' => $devices[0]['madv'], // Lấy từ TB đầu tiên
                        'donvinhan' => $_POST['donvinhan_' . $phieuyc] ?? '',
                        'ghichu' => $_POST['ghichu_' . $phieuyc] ?? '',
                        'trangthai' => isset($_POST['duyet_' . $phieuyc]) ? 1 : 0,
                        'nguoitao' => $_SESSION['username'] ?? ''
                    ];

                    $phieuId = $this->model->create($phieuData);

                    if ($phieuId) {
                        // Thêm thiết bị vào phiếu BG
                        $thietBiList = [];
                        foreach ($devices as $device) {
                            $thietBiList[] = [
                                'hososcbd_stt' => $device['stt'],
                                'tinhtrang' => $_POST['tinhtrang_' . $device['stt']] ?? 'Hoạt động tốt',
                                'ghichu' => $_POST['ghichu_tb_' . $device['stt']] ?? ''
                            ];

                            // Cập nhật trạng thái bg=1 trong hososcbd
                            $this->hosoModel->update($device['stt'], ['bg' => 1]);
                        }

                        $this->thietBiModel->createMultiple($sophieu, $thietBiList);
                        $createdCount++;
                    }
                }

                // Clear session
                unset($_SESSION['pbg_temp_devices']);

                if ($createdCount > 0) {
                    $_SESSION['success'] = "Đã tạo $createdCount phiếu bàn giao thành công!";
                    header("Location: /iso2/phieubangiao.php");
                    exit;
                }

                $errors[] = 'Không thể tạo phiếu bàn giao';
            } catch (Exception $e) {
                error_log("Error creating phieu ban giao: " . $e->getMessage());
                $errors[] = 'Có lỗi xảy ra: ' . $e->getMessage();
            }

            if (!empty($errors)) {
                $_SESSION['error'] = implode(', ', $errors);
                header("Location: /iso2/phieubangiao.php?action=confirm");
                exit;
            }
        }
    }

    public function view(): void
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if (!$id) {
            header("Location: /iso2/phieubangiao.php");
            exit;
        }

        $item = $this->model->getDetailWithDevices($id);
        
        if (!$item) {
            $_SESSION['error'] = 'Không tìm thấy phiếu bàn giao';
            header("Location: /iso2/phieubangiao.php");
            exit;
        }

        require_once __DIR__ . '/../views/phieubangiao/view.php';
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /iso2/phieubangiao.php");
            exit;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        if (!$id) {
            $_SESSION['error'] = 'ID không hợp lệ';
            header("Location: /iso2/phieubangiao.php");
            exit;
        }

        $phieu = $this->model->findById($id);
        
        if (!$phieu) {
            $_SESSION['error'] = 'Không tìm thấy phiếu bàn giao';
            header("Location: /iso2/phieubangiao.php");
            exit;
        }

        // Chỉ cho phép xóa phiếu nháp
        if ($phieu['trangthai'] == 1) {
            $_SESSION['error'] = 'Không thể xóa phiếu đã duyệt';
            header("Location: /iso2/phieubangiao.php");
            exit;
        }

        // Lấy danh sách thiết bị để cập nhật lại trạng thái bg=0
        $thietBiList = $this->thietBiModel->getBySoPhieu($phieu['sophieu']);
        
        // Xóa phiếu (cascade sẽ xóa chi tiết)
        if ($this->model->delete($id)) {
            // Cập nhật lại trạng thái bg=0 cho các thiết bị
            foreach ($thietBiList as $tb) {
                $this->hosoModel->update($tb['hososcbd_stt'], ['bg' => 0]);
            }
            
            $_SESSION['success'] = 'Đã xóa phiếu bàn giao';
        } else {
            $_SESSION['error'] = 'Có lỗi khi xóa phiếu bàn giao';
        }

        header("Location: /iso2/phieubangiao.php");
        exit;
    }
}
