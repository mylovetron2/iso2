<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/KeHoachISO.php';
require_once __DIR__ . '/../models/HoSoHCKD.php';
require_once __DIR__ . '/../models/ThietBiHCKD.php';
require_once __DIR__ . '/../models/Resume.php';

class BangCanhBaoController
{
    private KeHoachISO $keHoachModel;
    private HoSoHCKD $hoSoModel;
    private ThietBiHCKD $thietBiModel;
    private Resume $resumeModel;

    public function __construct()
    {
        $this->keHoachModel = new KeHoachISO();
        $this->hoSoModel = new HoSoHCKD();
        $this->thietBiModel = new ThietBiHCKD();
        $this->resumeModel = new Resume();
    }

    /**
     * Hiển thị bảng cảnh báo - Trang chủ
     */
    public function index(): void
    {
        try {
            $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
            $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = 10;
            $offset = ($page - 1) * $limit;

            // Validate month
            if ($month < 1 || $month > 12) {
                $month = (int)date('m');
            }

            // Lấy tham số tìm kiếm
            $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
            $searchType = isset($_GET['search_type']) ? $_GET['search_type'] : 'all';
            $statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

            // Lấy dữ liệu
            try {
                $total = $this->keHoachModel->countByMonthYear($month, $year, $searchTerm, $searchType, $statusFilter);
            } catch (Exception $e) {
                error_log("Error in countByMonthYear: " . $e->getMessage());
                throw new Exception("Lỗi đếm dữ liệu: " . $e->getMessage());
            }
            
            try {
                $data = $this->keHoachModel->getWithHCStatus($month, $year, $limit, $offset, $searchTerm, $searchType, $statusFilter);
            } catch (Exception $e) {
                error_log("Error in getWithHCStatus: " . $e->getMessage());
                throw new Exception("Lỗi lấy dữ liệu: " . $e->getMessage());
            }
            
            $totalPages = ceil($total / $limit);
            $years = $this->keHoachModel->getAvailableYears();
            
            // Đảm bảo $years không rỗng
            if (empty($years)) {
                $years = [(int)date('Y')];
            }

            // Load view
            require_once __DIR__ . '/../views/bangcanhbao/index.php';
        } catch (Exception $e) {
            error_log("Error in BangCanhBaoController::index: " . $e->getMessage());
            die("Có lỗi xảy ra: " . htmlspecialchars($e->getMessage()));
        }
    }

    /**
     * Form nhập/sửa hồ sơ hiệu chuẩn
     */
    public function formHoSo(): void
    {
        try {
            $mavattu = $_GET['mavattu'] ?? '';
            $ngayhc = $_GET['ngayhc'] ?? '';
            $mode = 'add'; // add hoặc edit

            $thietBi = null;
            $hoSo = null;
            $danhChuanList = [];

            // Lấy danh sách thiết bị dẫn chuẩn
            $danhChuanList = $this->thietBiModel->getDanhChuan();

            // Lấy danh sách nhân viên
            $nhanVienList = $this->resumeModel->getActiveEmployees();

            if ($mavattu) {
                // Lấy thông tin thiết bị
                $thietBi = $this->thietBiModel->getByMaVatTu($mavattu);
                
                if ($ngayhc) {
                    // Edit mode - lấy hồ sơ hiện có
                    $hoSo = $this->hoSoModel->getByDeviceAndDate($mavattu, $ngayhc);
                    $mode = 'edit';
                } else {
                    // Add mode - lấy thông tin hồ sơ cũ để tham khảo
                    $hoSo = $this->hoSoModel->getLatestByDevice($mavattu);
                }
            }

            // Lấy danh sách thiết bị để chọn
            $thietBiList = $this->thietBiModel->getAllGrouped();

            require_once __DIR__ . '/../views/bangcanhbao/form_hoso.php';
        } catch (Exception $e) {
            error_log("Error in BangCanhBaoController::formHoSo: " . $e->getMessage());
            die("Có lỗi xảy ra: " . htmlspecialchars($e->getMessage()));
        }
    }

    /**
     * Lưu hồ sơ hiệu chuẩn
     */
    public function saveHoSo(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Invalid request method");
            }

            // Lấy dữ liệu từ form
            $mavattu = $_POST['tenmay'] ?? '';
            $sohs = $_POST['sohs'] ?? '';
            $ngayhc = $_POST['ngayhc'] ?? '';
            $ngayhctt = $_POST['ngayhctt'] ?? '';
            $nhanvien = $_POST['nhanvien'] ?? '';
            $noithuchien = $_POST['noithuchien'] ?? '';
            $ttkt = $_POST['ttkt'] ?? '';

            // Phương pháp chuẩn
            $danchuan = isset($_POST['danchuan']) ? 1 : 0;
            $mauchuan = isset($_POST['mauchuan']) ? 1 : 0;
            $dinhky = isset($_POST['dinhky']) ? 1 : 0;
            $dotxuat = isset($_POST['dotxuat']) ? 1 : 0;

            // Thiết bị dẫn chuẩn (5 thiết bị)
            $thietbidc1 = $_POST['thietbidc1'] ?? '';
            $thietbidc2 = $_POST['thietbidc2'] ?? '';
            $thietbidc3 = $_POST['thietbidc3'] ?? '';
            $thietbidc4 = $_POST['thietbidc4'] ?? '';
            $thietbidc5 = $_POST['thietbidc5'] ?? '';

            // Xác định công việc (HC hoặc CM)
            $thietBi = $this->thietBiModel->getByMaVatTu($mavattu);
            $congviec = 'HC';
            if ($thietBi) {
                $tenviettat = $thietBi['tenviettat'] ?? '';
                $loaitb = $thietBi['loaitb'] ?? 0;
                $kitList = ['KIT', 'DL/60', 'DL/76', 'KITA', 'KITB', 'ION'];
                
                if (in_array($tenviettat, $kitList) || in_array($loaitb, [5, 6])) {
                    $congviec = 'CM';
                }
            }

            // Xác định năm
            $namkh = (int)date('Y', strtotime($ngayhc));

            // Tự động tạo số hồ sơ nếu chưa có
            if (empty($sohs)) {
                $month = (int)date('m', strtotime($ngayhc));
                $year = (int)date('Y', strtotime($ngayhc));
                $sohs = $this->hoSoModel->generateSoHS($month, $year);
            }

            // Chuẩn bị dữ liệu
            $data = [
                'sohs' => $sohs,
                'tenmay' => $mavattu,
                'congviec' => $congviec,
                'ngayhc' => $ngayhc,
                'ngayhctt' => $ngayhctt,
                'nhanvien' => $nhanvien,
                'noithuchien' => $noithuchien,
                'ttkt' => $ttkt,
                'danchuan' => $danchuan,
                'mauchuan' => $mauchuan,
                'dinhky' => $dinhky,
                'dotxuat' => $dotxuat,
                'thietbidc1' => $thietbidc1,
                'thietbidc2' => $thietbidc2,
                'thietbidc3' => $thietbidc3,
                'thietbidc4' => $thietbidc4,
                'thietbidc5' => $thietbidc5,
                'namkh' => $namkh
            ];

            // Lưu vào database
            $result = $this->hoSoModel->saveHoSo($data);

            if ($result) {
                // Chuyển về trang bảng cảnh báo
                $month = (int)date('m', strtotime($ngayhc));
                $year = (int)date('Y', strtotime($ngayhc));
                header("Location: bangcanhbao.php?month=$month&year=$year&success=1");
                exit;
            } else {
                throw new Exception("Không thể lưu hồ sơ");
            }
        } catch (Exception $e) {
            error_log("Error in BangCanhBaoController::saveHoSo: " . $e->getMessage());
            $error = htmlspecialchars($e->getMessage());
            header("Location: bangcanhbao.php?action=formhoso&error=" . urlencode($error));
            exit;
        }
    }

    /**
     * Danh sách phiếu yêu cầu
     */
    public function phieuYeuCau(): void
    {
        try {
            $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
            $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = 20;
            $offset = ($page - 1) * $limit;

            // Lấy danh sách thiết bị cần HC
            $data = $this->keHoachModel->getByMonthYear($month, $year, $limit, $offset);
            $total = $this->keHoachModel->countByMonthYear($month, $year);
            $totalPages = ceil($total / $limit);
            $years = $this->keHoachModel->getAvailableYears();
            
            // Đảm bảo $years không rỗng
            if (empty($years)) {
                $years = [(int)date('Y')];
            }

            require_once __DIR__ . '/../views/bangcanhbao/phieu_yeucau.php';
        } catch (Exception $e) {
            error_log("Error in BangCanhBaoController::phieuYeuCau: " . $e->getMessage());
            die("Có lỗi xảy ra: " . htmlspecialchars($e->getMessage()));
        }
    }

    /**
     * Form phiếu kiểm tra
     */
    public function phieuKiemTra(): void
    {
        try {
            $mavattu = $_GET['mavattu'] ?? '';
            $stt = isset($_GET['stt']) ? (int)$_GET['stt'] : 0;

            $hoSo = null;
            $thietBi = null;
            $danhChuanList = [];

            if ($stt > 0) {
                // Lấy hồ sơ theo STT
                $hoSo = $this->hoSoModel->findById($stt);
                if ($hoSo) {
                    $thietBi = $this->thietBiModel->getByMaVatTu($hoSo['tenmay']);
                }
            } elseif ($mavattu) {
                // Lấy hồ sơ mới nhất của thiết bị
                $hoSo = $this->hoSoModel->getLatestByDevice($mavattu);
                $thietBi = $this->thietBiModel->getByMaVatTu($mavattu);
            }

            // Lấy danh sách thiết bị dẫn chuẩn
            $danhChuanList = $this->thietBiModel->getDanhChuan();

            require_once __DIR__ . '/../views/bangcanhbao/phieu_kiemtra.php';
        } catch (Exception $e) {
            error_log("Error in BangCanhBaoController::phieuKiemTra: " . $e->getMessage());
            die("Có lỗi xảy ra: " . htmlspecialchars($e->getMessage()));
        }
    }

    /**
     * Lưu kết quả kiểm tra
     */
    public function saveKiemTra(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Invalid request method");
            }

            $stt = isset($_POST['stt']) ? (int)$_POST['stt'] : 0;
            $ttkt = $_POST['ttkt'] ?? 'Tốt';
            $danchuan = isset($_POST['danchuan']) ? 1 : 0;
            $mauchuan = isset($_POST['mauchuan']) ? 1 : 0;

            // Thiết bị dẫn chuẩn
            $thietbidc1 = $_POST['thietbidc1'] ?? '';
            $thietbidc2 = $_POST['thietbidc2'] ?? '';
            $thietbidc3 = $_POST['thietbidc3'] ?? '';
            $thietbidc4 = $_POST['thietbidc4'] ?? '';
            $thietbidc5 = $_POST['thietbidc5'] ?? '';

            if ($stt <= 0) {
                throw new Exception("STT không hợp lệ");
            }

            // Cập nhật thông tin kiểm tra
            $data = [
                'ttkt' => $ttkt,
                'danchuan' => $danchuan,
                'mauchuan' => $mauchuan,
                'thietbidc1' => $thietbidc1,
                'thietbidc2' => $thietbidc2,
                'thietbidc3' => $thietbidc3,
                'thietbidc4' => $thietbidc4,
                'thietbidc5' => $thietbidc5
            ];

            $result = $this->hoSoModel->update($stt, $data);

            if ($result >= 0) {
                header("Location: bangcanhbao.php?success=2");
                exit;
            } else {
                throw new Exception("Không thể cập nhật kết quả kiểm tra");
            }
        } catch (Exception $e) {
            error_log("Error in BangCanhBaoController::saveKiemTra: " . $e->getMessage());
            $error = htmlspecialchars($e->getMessage());
            header("Location: bangcanhbao.php?action=phieukt&error=" . urlencode($error));
            exit;
        }
    }

    /**
     * API - Tạo số hồ sơ tự động
     */
    public function apiGenerateSoHS(): void
    {
        header('Content-Type: application/json');
        try {
            $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
            $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

            $sohs = $this->hoSoModel->generateSoHS($month, $year);

            echo json_encode([
                'success' => true,
                'sohs' => $sohs
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
