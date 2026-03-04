<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/PhieuYeuCau.php';
require_once __DIR__ . '/../models/HoSoSCBD.php';
require_once __DIR__ . '/../models/DonVi.php';

/**
 * Controller: PhieuYeuCauController
 * Quản lý số phiếu yêu cầu dịch vụ
 */
class PhieuYeuCauController
{
    private PhieuYeuCau $model;
    private HoSoSCBD $hosoModel;
    private DonVi $donViModel;

    public function __construct()
    {
        $this->model = new PhieuYeuCau();
        $this->hosoModel = new HoSoSCBD();
        $this->donViModel = new DonVi();
    }

    /**
     * Trang chủ: Danh sách phiếu yêu cầu
     */
    public function index(): void
    {
        $search = $_GET['search'] ?? '';
        $madv = $_GET['madv'] ?? '';
        $nhomsc = $_GET['nhomsc'] ?? '';
        $trangthai = $_GET['trangthai'] ?? '';
        $fromDate = $_GET['from_date'] ?? '';
        $toDate = $_GET['to_date'] ?? '';
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $phieuList = $this->model->getPhieuList($search, $madv, $nhomsc, $trangthai, $fromDate, $toDate, $offset, $limit);
        $total = $this->model->countPhieuList($search, $madv, $nhomsc, $trangthai, $fromDate, $toDate);
        $totalPages = ceil($total / $limit);

        $stats = $this->model->getStats($nhomsc);
        $donViList = $this->donViModel->getAllSimple();

        require_once __DIR__ . '/../views/phieuyeucau/index.php';
    }

    /**
     * Xem chi tiết phiếu
     */
    public function view(): void
    {
        $phieu = $_GET['phieu'] ?? '';
        
        if (empty($phieu)) {
            $_SESSION['error'] = 'Số phiếu không hợp lệ';
            header('Location: /iso2/phieuyeucau.php');
            exit;
        }

        $detail = $this->model->getPhieuDetail($phieu);
        
        if (!$detail) {
            $_SESSION['error'] = 'Không tìm thấy phiếu này';
            header('Location: /iso2/phieuyeucau.php');
            exit;
        }

        require_once __DIR__ . '/../views/phieuyeucau/view.php';
    }

    /**
     * Tạo phiếu mới
     */
    public function create(): void
    {
        if (!hasPermission('phieuyeucau.create')) {
            $_SESSION['error'] = 'Bạn không có quyền tạo phiếu';
            header('Location: /iso2/phieuyeucau.php');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = $this->handleCreatePost();
            
            if (empty($errors)) {
                return; // Redirect đã được xử lý trong handleCreatePost
            }
            
            $error = implode('<br>', $errors);
        }

        $donViList = $this->donViModel->getAllSimple();
        $nextPhieu = $this->model->getNextPhieuNumber();
        
        require_once __DIR__ . '/../views/phieuyeucau/create.php';
    }

    /**
     * Xử lý tạo phiếu mới
     */
    private function handleCreatePost(): array
    {
        $errors = [];
        
        // Lấy thông tin chung của phiếu
        $commonData = [
            'phieu' => trim($_POST['phieu'] ?? ''),
            'ngayyc' => $_POST['ngayyc'] ?? '',
            'madv' => trim($_POST['madv'] ?? ''),
            'ngyeucau' => trim($_POST['ngyeucau'] ?? ''),
            'ngnhyeucau' => trim($_POST['ngnhyeucau'] ?? ''),
            'dienthoai' => trim($_POST['dienthoai'] ?? ''),
            'cv' => trim($_POST['cv'] ?? ''),
            'ycthemkh' => trim($_POST['ycthemkh'] ?? ''),
            'nhomsc' => trim($_POST['nhomsc'] ?? '')
        ];
        
        // Validate thông tin chung
        if (empty($commonData['phieu'])) {
            $errors[] = 'Số phiếu không được để trống';
        } elseif ($this->model->phieuExists($commonData['phieu'])) {
            $errors[] = 'Số phiếu đã tồn tại, vui lòng chọn số khác';
        }
        
        if (empty($commonData['ngayyc'])) {
            $errors[] = 'Ngày yêu cầu không được để trống';
        }
        
        if (empty($commonData['madv'])) {
            $errors[] = 'Đơn vị không được để trống';
        }
        
        // Lấy danh sách thiết bị
        $devicesData = $this->getDevicesPostData();
        
        if (empty($devicesData)) {
            $errors[] = 'Phải nhập ít nhất 1 thiết bị';
        }
        
        // Validate từng thiết bị
        foreach ($devicesData as $index => $device) {
            $deviceErrors = $this->validateDevice($device, $index);
            if (!empty($deviceErrors)) {
                $errors = array_merge($errors, $deviceErrors);
            }
        }
        
        if (!empty($errors)) {
            return $errors;
        }
        
        // Tạo hồ sơ cho từng thiết bị
        // Lấy index lớn nhất hiện có trong phiếu để tính số hồ sơ tiếp tục
        $maxExistingIndex = $this->hosoModel->getMaxHosoIndexForPhieu($commonData['phieu']);
        $hosoCounter = $maxExistingIndex; // Bắt đầu từ số hiện có, sẽ tăng lên trước khi dùng
        
        $successCount = 0;
        try {
            foreach ($devicesData as $device) {
                $data = array_merge($commonData, $device);
                
                // Auto-generate maql và hoso
                $data['maql'] = $this->generateMaQL($data['madv'], $data['phieu']);
                
                // Generate hoso number: tăng counter và tạo số thứ tự
                $hosoCounter++;
                $data['hoso'] = $this->generateHoSo($data['phieu'], $hosoCounter);
                
                $id = $this->hosoModel->create($data);
                if ($id) {
                    $successCount++;
                }
            }
            
            if ($successCount > 0) {
                $_SESSION['success'] = "Tạo phiếu thành công với {$successCount} thiết bị";
                header("Location: /iso2/phieuyeucau.php?action=view&phieu=" . urlencode($commonData['phieu']));
                exit;
            }
            
            $errors[] = 'Có lỗi xảy ra khi tạo phiếu';
        } catch (PDOException $e) {
            error_log("PhieuYeuCau create error: " . $e->getMessage());
            $errors[] = 'Lỗi cơ sở dữ liệu: ' . $e->getMessage();
        }
        
        return $errors;
    }

    /**
     * Sửa thông tin chung của phiếu
     */
    public function edit(): void
    {
        if (!hasPermission('phieuyeucau.edit')) {
            $_SESSION['error'] = 'Bạn không có quyền sửa phiếu';
            header('Location: /iso2/phieuyeucau.php');
            exit;
        }
        
        $phieu = $_GET['phieu'] ?? '';
        
        if (empty($phieu)) {
            $_SESSION['error'] = 'Số phiếu không hợp lệ';
            header('Location: /iso2/phieuyeucau.php');
            exit;
        }

        $detail = $this->model->getPhieuDetail($phieu);
        
        if (!$detail) {
            $_SESSION['error'] = 'Không tìm thấy phiếu này';
            header('Location: /iso2/phieuyeucau.php');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'ngayyc' => $_POST['ngayyc'] ?? '',
                'madv' => trim($_POST['madv'] ?? ''),
                'ngyeucau' => trim($_POST['ngyeucau'] ?? ''),
                'ngnhyeucau' => trim($_POST['ngnhyeucau'] ?? ''),
                'dienthoai' => trim($_POST['dienthoai'] ?? ''),
                'cv' => trim($_POST['cv'] ?? ''),
                'ycthemkh' => trim($_POST['ycthemkh'] ?? ''),
                'nhomsc' => trim($_POST['nhomsc'] ?? '')
            ];
            
            $errors = [];
            
            if (empty($data['ngayyc'])) {
                $errors[] = 'Ngày yêu cầu không được để trống';
            }
            
            if (empty($data['madv'])) {
                $errors[] = 'Đơn vị không được để trống';
            }
            
            if (empty($errors)) {
                $success = $this->model->updatePhieuCommonInfo($phieu, $data);
                
                if ($success) {
                    $_SESSION['success'] = 'Cập nhật thông tin phiếu thành công';
                    header("Location: /iso2/phieuyeucau.php?action=view&phieu=" . urlencode($phieu));
                    exit;
                }
                
                $errors[] = 'Không có thay đổi nào được lưu';
            }
            
            $error = implode('<br>', $errors);
        }

        $donViList = $this->donViModel->getAllSimple();
        
        require_once __DIR__ . '/../views/phieuyeucau/edit.php';
    }

    /**
     * Xóa phiếu
     */
    public function delete(): void
    {
        if (!hasPermission('phieuyeucau.delete')) {
            $_SESSION['error'] = 'Bạn không có quyền xóa phiếu';
            header('Location: /iso2/phieuyeucau.php');
            exit;
        }

        $phieu = $_POST['phieu'] ?? '';
        
        if (empty($phieu)) {
            $_SESSION['error'] = 'Số phiếu không hợp lệ';
            header('Location: /iso2/phieuyeucau.php');
            exit;
        }

        $success = $this->model->deletePhieu($phieu);
        
        if ($success) {
            $_SESSION['success'] = "Xóa phiếu {$phieu} thành công";
        } else {
            $_SESSION['error'] = 'Không thể xóa phiếu (có thể đã có thiết bị đang thực hiện)';
        }
        
        header('Location: /iso2/phieuyeucau.php');
        exit;
    }

    /**
     * Export phiếu yêu cầu ra Word
     */
    public function exportWord(): void
    {
        $phieu = $_GET['phieu'] ?? '';
        
        if (empty($phieu)) {
            $_SESSION['error'] = 'Số phiếu không hợp lệ';
            header('Location: /iso2/phieuyeucau.php');
            exit;
        }

        $detail = $this->model->getPhieuDetail($phieu);
        
        if (!$detail) {
            $_SESSION['error'] = 'Không tìm thấy phiếu này';
            header('Location: /iso2/phieuyeucau.php');
            exit;
        }

        // Chuẩn bị dữ liệu
        $summary = $detail['summary'];
        $devices = $detail['devices'];
        
        $sohoso = $summary['phieu'];
        $ngay = date('d/m/Y', strtotime($summary['ngayyc']));
        $khachhang = $summary['ngyeucau'];
        $donvi = $summary['tendv'];
        $dienthoai = $summary['dienthoai'];
        $nhanvien = $summary['ngnhyeucau'];
        $ycthemkh = $summary['ycthemkh'];
        $cv = $summary['cv'];
        
        // Chuẩn bị mảng thiết bị
        $thietbi = [];
        $model = [];
        $somay = [];
        $tinhtrang = [];
        $yeucau = [];
        $vitri = [];
        
        $solan = count($devices);
        
        for ($i = 1; $i <= $solan; $i++) {
            if (isset($devices[$i-1])) {
                $device = $devices[$i-1];
                $thietbi[$i] = $device['tenvt'] ?? $device['mavt'];
                $model[$i] = $device['model'] ?? '';
                $somay[$i] = $device['somay'] ?? '';
                $tinhtrang[$i] = $device['honghoc'] ?? '';
                $yeucau[$i] = $device['cv'] ?? '';
                $vitri[$i] = $device['vitrimaybd'] ?? '';
            }
        }
        
        // Các trường bổ sung (có thể lấy từ devices nếu có)
        $lo = $devices[0]['lo'] ?? '';
        $mo = $devices[0]['mo'] ?? '';
        $gieng = $devices[0]['gieng'] ?? '';
        $xemxetxuong = $devices[0]['xemxetxuong'] ?? '';

        // Xuất Word
        require_once __DIR__ . '/../views/phieuyeucau/export_word.php';
    }

    /**
     * Export phiếu yêu cầu ra PDF
     */
    public function exportPdf(): void
    {
        $phieu = $_GET['phieu'] ?? '';
        
        if (empty($phieu)) {
            $_SESSION['error'] = 'Số phiếu không hợp lệ';
            header('Location: /iso2/phieuyeucau.php');
            exit;
        }

        $detail = $this->model->getPhieuDetail($phieu);
        
        if (!$detail) {
            $_SESSION['error'] = 'Không tìm thấy phiếu này';
            header('Location: /iso2/phieuyeucau.php');
            exit;
        }

        // Chuẩn bị dữ liệu
        $summary = $detail['summary'];
        $devices = $detail['devices'];
        
        // Xuất PDF
        require_once __DIR__ . '/../views/phieuyeucau/export_pdf.php';
    }

    /**
     * Lấy dữ liệu thiết bị từ POST
     */
    private function getDevicesPostData(): array
    {
        $devices = [];
        
        // Đọc dữ liệu các thiết bị từ form (dạng array)
        $mavtArr = $_POST['mavt'] ?? [];
        $somayArr = $_POST['somay'] ?? [];
        $modelArr = $_POST['model'] ?? [];
        $solgArr = $_POST['solg'] ?? [];
        $vitrimaybd = $_POST['vitrimaybd'] ?? [];
        $honghocArr = $_POST['honghoc'] ?? [];
        
        $count = count($mavtArr);
        
        for ($i = 0; $i < $count; $i++) {
            $mavt = trim($mavtArr[$i] ?? '');
            $somay = trim($somayArr[$i] ?? '');
            
            if (!empty($mavt) || !empty($somay)) {
                $devices[] = [
                    'mavt' => $mavt,
                    'somay' => $somay,
                    'model' => trim($modelArr[$i] ?? ''),
                    'solg' => (int)($solgArr[$i] ?? 1),
                    'vitrimaybd' => trim($vitrimaybd[$i] ?? ''),
                    'honghoc' => trim($honghocArr[$i] ?? '')
                ];
            }
        }
        
        return $devices;
    }

    /**
     * Validate thông tin thiết bị
     */
    private function validateDevice(array $device, int $index): array
    {
        $errors = [];
        $deviceNum = $index + 1;
        
        if (empty($device['mavt'])) {
            $errors[] = "Thiết bị #{$deviceNum}: Mã vật tư không được để trống";
        }
        
        if (empty($device['somay'])) {
            $errors[] = "Thiết bị #{$deviceNum}: Số máy không được để trống";
        }
        
        return $errors;
    }

    /**
     * Tạo mã quản lý (MAVT.DV.PHIEU)
     */
    private function generateMaQL(string $madv, string $phieu): string
    {
        return "{$madv}.{$phieu}";
    }

    /**
     * Tạo mã hồ sơ (PHIEU-INDEX)
     */
    private function generateHoSo(string $phieu, int $index): string
    {
        $num = str_pad((string)($index + 1), 3, '0', STR_PAD_LEFT);
        return "{$phieu}-{$num}";
    }
}
