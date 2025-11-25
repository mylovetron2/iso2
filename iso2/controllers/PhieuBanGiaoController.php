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

        // Extract devices for view
        $devices = $item['thietbi'] ?? [];

        require_once __DIR__ . '/../views/phieubangiao/view.php';
    }

    public function edit(): void
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

        // Chỉ cho phép sửa phiếu nháp
        if ($item['trangthai'] == 1) {
            $_SESSION['error'] = 'Không thể sửa phiếu đã duyệt';
            header("Location: /iso2/phieubangiao.php?action=view&id=" . $id);
            exit;
        }

        // GET: Hiển thị form
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $devices = $item['thietbi'] ?? [];
            $donViList = $this->donViModel->getAllSimple();
            require_once __DIR__ . '/../views/phieubangiao/edit.php';
            return;
        }

        // POST: Xử lý update
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'ngaybg' => $_POST['ngaybg'] ?? '',
                'nguoigiao' => $_POST['nguoigiao'] ?? '',
                'nguoinhan' => $_POST['nguoinhan'] ?? '',
                'donvinhan' => $_POST['donvinhan'] ?? '',
                'ghichu' => $_POST['ghichu'] ?? '',
                'nguoisua' => $_SESSION['username'] ?? 'system'
            ];

            // Validate
            if (empty($data['ngaybg']) || empty($data['nguoigiao']) || empty($data['nguoinhan']) || empty($data['donvinhan'])) {
                $_SESSION['error'] = 'Vui lòng điền đầy đủ thông tin bắt buộc';
                header("Location: /iso2/phieubangiao.php?action=edit&id=" . $id);
                exit;
            }

            // Cập nhật trạng thái nếu có checkbox duyệt
            if (isset($_POST['duyet']) && $_POST['duyet'] == '1') {
                $data['trangthai'] = 1;
            }

            // Update phiếu
            if ($this->model->update($id, $data)) {
                // Cập nhật tình trạng thiết bị nếu có
                foreach ($item['thietbi'] as $device) {
                    $stt = $device['stt'];
                    if (isset($_POST['tinhtrang_' . $stt])) {
                        $this->thietBiModel->update($stt, [
                            'tinhtrang' => $_POST['tinhtrang_' . $stt],
                            'ghichu' => $_POST['ghichu_tb_' . $stt] ?? ''
                        ]);
                    }
                }

                $_SESSION['success'] = 'Đã cập nhật phiếu bàn giao';
                header("Location: /iso2/phieubangiao.php?action=view&id=" . $id);
            } else {
                $_SESSION['error'] = 'Có lỗi khi cập nhật phiếu bàn giao';
                header("Location: /iso2/phieubangiao.php?action=edit&id=" . $id);
            }
            exit;
        }
    }

    public function delete(): void
    {
        // Enable error reporting for debugging
        ini_set('display_errors', '1');
        error_reporting(E_ALL);
        
        // Log request info
        error_log("=== DELETE REQUEST START ===");
        error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
        error_log("POST data: " . print_r($_POST, true));
        error_log("GET data: " . print_r($_GET, true));
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("Error: Not POST method");
            $_SESSION['error'] = 'Invalid request method';
            header("Location: /iso2/phieubangiao.php");
            exit;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        error_log("ID from POST: $id");
        
        if (!$id) {
            error_log("Error: ID is 0 or not set");
            $_SESSION['error'] = 'ID không hợp lệ - POST id: ' . ($_POST['id'] ?? 'not set');
            header("Location: /iso2/phieubangiao.php");
            exit;
        }

        try {
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
            
            // Xóa chi tiết thiết bị trước
            $deletedDetails = $this->thietBiModel->deleteBySoPhieu($phieu['sophieu']);
            error_log("Deleted $deletedDetails device details for sophieu: {$phieu['sophieu']}");
            
            // Xóa phiếu chính
            $deletedPhieu = $this->model->delete($id);
            error_log("Deleted phieu result: $deletedPhieu");
            
            if ($deletedPhieu > 0) {
                // Cập nhật lại trạng thái bg=0 cho các thiết bị
                foreach ($thietBiList as $tb) {
                    $this->hosoModel->update($tb['hososcbd_stt'], ['bg' => 0]);
                    error_log("Updated hososcbd_stt {$tb['hososcbd_stt']} bg=0");
                }
                
                $_SESSION['success'] = 'Đã xóa phiếu bàn giao';
            } else {
                $_SESSION['error'] = 'Không thể xóa phiếu bàn giao (rowCount = 0)';
                error_log("Delete phieu failed: rowCount = 0 for ID $id");
            }
            
        } catch (Exception $e) {
            error_log("Exception deleting phieu ban giao ID $id: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $_SESSION['error'] = 'Có lỗi khi xóa: ' . $e->getMessage();
        }

        header("Location: /iso2/phieubangiao.php");
        exit;
    }
}
