<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/PhieuBanGiao.php';
require_once __DIR__ . '/../models/PhieuBanGiaoThietBi.php';
require_once __DIR__ . '/../models/HoSoSCBD.php';
require_once __DIR__ . '/../models/DonVi.php';

class PhieuBanGiaoPhieuYCController
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

    /**
     * Hiển thị danh sách phiếu YC có thiết bị chưa bàn giao
     */
    public function index(): void
    {
        // Lấy danh sách phiếu YC có thiết bị chưa bàn giao (bg=0)
        $phieuYCList = $this->model->getPhieuYCWithUndeliveredDevices();
        
        require_once __DIR__ . '/../views/phieubangiao_phieuyc/index.php';
    }

    /**
     * Bước 1: Chọn phiếu YC
     */
    public function selectPhieuYC(): void
    {
        // GET: Hiển thị danh sách phiếu YC để chọn
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $phieuYCList = $this->model->getPhieuYCWithUndeliveredDevices();
            require_once __DIR__ . '/../views/phieubangiao_phieuyc/select_phieuyc.php';
            return;
        }

        // POST: Xử lý khi submit chọn phiếu YC
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_phieuyc'])) {
            $selectedPhieuYC = $_POST['selected_phieuyc']; // Array of phieu numbers
            
            if (empty($selectedPhieuYC)) {
                $_SESSION['error'] = 'Vui lòng chọn ít nhất 1 phiếu yêu cầu';
                header("Location: /iso2/phieubangiao_phieuyc.php?action=select");
                exit;
            }

            // Lưu danh sách phiếu YC đã chọn vào session
            $_SESSION['pbg_phieuyc_selected'] = $selectedPhieuYC;

            // Chuyển sang bước 2: Chọn thiết bị
            header("Location: /iso2/phieubangiao_phieuyc.php?action=select_devices");
            exit;
        }
    }

    /**
     * Bước 2: Chọn thiết bị cần bàn giao
     */
    public function selectDevices(): void
    {
        // Kiểm tra đã có phiếu YC chưa
        if (!isset($_SESSION['pbg_phieuyc_selected']) || empty($_SESSION['pbg_phieuyc_selected'])) {
            header("Location: /iso2/phieubangiao_phieuyc.php?action=select");
            exit;
        }

        $selectedPhieuYC = $_SESSION['pbg_phieuyc_selected'];

        // GET: Hiển thị danh sách thiết bị để chọn
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Load tất cả thiết bị chưa bàn giao của các phiếu YC đã chọn
            $groupedByPhieu = [];
            foreach ($selectedPhieuYC as $phieu) {
                $devices = $this->hosoModel->getUndeliveredByPhieu($phieu);
                if (!empty($devices)) {
                    $groupedByPhieu[$phieu] = $devices;
                }
            }

            if (empty($groupedByPhieu)) {
                $_SESSION['error'] = 'Không tìm thấy thiết bị chưa bàn giao trong các phiếu đã chọn';
                header("Location: /iso2/phieubangiao_phieuyc.php?action=select");
                exit;
            }

            require_once __DIR__ . '/../views/phieubangiao_phieuyc/select_devices.php';
            return;
        }

        // POST: Xử lý khi submit chọn thiết bị
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_devices'])) {
            $selectedDeviceIds = $_POST['selected_devices']; // Array of device stt
            
            if (empty($selectedDeviceIds)) {
                $_SESSION['error'] = 'Vui lòng chọn ít nhất 1 thiết bị cần bàn giao';
                header("Location: /iso2/phieubangiao_phieuyc.php?action=select_devices");
                exit;
            }

            // Load thiết bị đã chọn và nhóm theo phiếu YC
            $groupedByPhieu = [];
            foreach ($selectedDeviceIds as $stt) {
                $device = $this->hosoModel->getDeviceWithDetails((int)$stt);
                if ($device && $device['bg'] == 0) {
                    $phieu = $device['phieu'];
                    if (!isset($groupedByPhieu[$phieu])) {
                        $groupedByPhieu[$phieu] = [];
                    }
                    $groupedByPhieu[$phieu][] = $device;
                }
            }

            if (empty($groupedByPhieu)) {
                $_SESSION['error'] = 'Không tìm thấy thiết bị hợp lệ';
                header("Location: /iso2/phieubangiao_phieuyc.php?action=select_devices");
                exit;
            }

            // Lưu vào session để dùng ở bước confirm
            $_SESSION['pbg_phieuyc_temp_devices'] = $groupedByPhieu;

            header("Location: /iso2/phieubangiao_phieuyc.php?action=confirm");
            exit;
        }
    }

    /**
     * Bước 2: Xác nhận và tạo phiếu bàn giao
     */
    public function confirmCreate(): void
    {
        // Load thiết bị đã chọn từ session
        if (!isset($_SESSION['pbg_phieuyc_temp_devices']) || empty($_SESSION['pbg_phieuyc_temp_devices'])) {
            header("Location: /iso2/phieubangiao_phieuyc.php?action=select");
            exit;
        }

        $groupedByPhieu = $_SESSION['pbg_phieuyc_temp_devices'];
        $donViList = $this->donViModel->getAllSimple();

        // GET: Hiển thị form xác nhận
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            require_once __DIR__ . '/../views/phieubangiao_phieuyc/confirm_create.php';
            return;
        }

        // POST: Xử lý tạo phiếu bàn giao
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $createdCount = 0;
                $errors = [];

                // Tạo từng phiếu bàn giao cho mỗi phiếu YC
                foreach ($groupedByPhieu as $phieuyc => $devices) {
                    // Lấy thông tin từ form
                    $data = [
                        'phieuyc' => $phieuyc,
                        'ngaybg' => $_POST['ngaybg_' . $phieuyc] ?? date('Y-m-d'),
                        'nguoigiao' => $_POST['nguoigiao_' . $phieuyc] ?? '',
                        'nguoinhan' => $_POST['nguoinhan_' . $phieuyc] ?? '',
                        'donvigiao' => $devices[0]['madv'] ?? '', // Lấy từ thiết bị đầu tiên
                        'donvinhan' => $_POST['donvinhan_' . $phieuyc] ?? '',
                        'ghichu' => $_POST['ghichu_' . $phieuyc] ?? '',
                        'trangthai' => isset($_POST['duyet_' . $phieuyc]) ? 1 : 0,
                    ];

                    // Validate
                    if (empty($data['nguoigiao']) || empty($data['nguoinhan']) || empty($data['donvinhan'])) {
                        $errors[] = "Phiếu YC {$phieuyc}: Vui lòng điền đầy đủ thông tin bắt buộc";
                        continue;
                    }

                    // Tạo phiếu bàn giao
                    $phieuBGId = $this->model->create($data);

                    if ($phieuBGId) {
                        // Thêm thiết bị vào phiếu bàn giao
                        foreach ($devices as $device) {
                            $thietbiData = [
                                'phieubangiao_stt' => $phieuBGId,
                                'hososcbd_stt' => $device['stt'],
                                'tinhtrang' => $_POST['tinhtrang_' . $device['stt']] ?? 'Hoạt động tốt',
                                'ghichu' => $_POST['ghichu_device_' . $device['stt']] ?? '',
                            ];
                            $this->thietBiModel->create($thietbiData);

                            // Cập nhật trạng thái bg=1 trong hososcbd_iso nếu duyệt
                            if ($data['trangthai'] == 1) {
                                $this->hosoModel->updateBGStatus((int)$device['stt'], 1);
                            }
                        }
                        $createdCount++;
                    } else {
                        $errors[] = "Không thể tạo phiếu bàn giao cho phiếu YC {$phieuyc}";
                    }
                }

                // Xóa session
                unset($_SESSION['pbg_phieuyc_temp_devices']);

                // Thông báo kết quả
                if ($createdCount > 0) {
                    $_SESSION['success'] = "Đã tạo thành công {$createdCount} phiếu bàn giao";
                }
                if (!empty($errors)) {
                    $_SESSION['error'] = implode('<br>', $errors);
                }

                header("Location: /iso2/phieubangiao.php");
                exit;

            } catch (Exception $e) {
                $_SESSION['error'] = 'Lỗi: ' . $e->getMessage();
                header("Location: /iso2/phieubangiao_phieuyc.php?action=confirm");
                exit;
            }
        }
    }
}
