<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/CongViecSuaChua.php';
require_once __DIR__ . '/../models/CapDoBaoCuong.php';
require_once __DIR__ . '/../models/ThietBiCapDoKPI.php';
require_once __DIR__ . '/../models/Resume.php';

/**
 * Controller: CongViecSuaChuaController
 * Xử lý nghiệp vụ quản lý công việc sửa chữa hàng ngày
 */
class CongViecSuaChuaController
{
    private CongViecSuaChua $congviecModel;
    private CapDoBaoCuong $capdoModel;
    private ThietBiCapDoKPI $kpiModel;
    private Resume $resumeModel;
    private $thietbiModel;

    public function __construct()
    {
        $this->congviecModel = new CongViecSuaChua();
        $this->capdoModel = new CapDoBaoCuong();
        $this->kpiModel = new ThietBiCapDoKPI();
        $this->resumeModel = new Resume();
        
        // ThietBi model (nếu có)
        if (file_exists(__DIR__ . '/../models/ThietBi.php')) {
            require_once __DIR__ . '/../models/ThietBi.php';
            $this->thietbiModel = new ThietBi();
        } else {
            $this->thietbiModel = null;
        }
    }

    /**
     * Lấy danh sách công việc theo điều kiện
     */
    public function index(): array
    {
        $nhanvienStt = isset($_GET['nhanvien_stt']) ? (int)$_GET['nhanvien_stt'] : null;
        $ngayLam = $_GET['ngay_lam'] ?? date('Y-m-d');
        $mavt = $_GET['mavt'] ?? null;
        $from = $_GET['from'] ?? null;
        $to = $_GET['to'] ?? null;

        $data = [
            'success' => true,
            'congviecs' => [],
            'tong_gio' => 0,
            'gio_con_lai' => 8,
            'filters' => compact('nhanvienStt', 'ngayLam', 'mavt', 'from', 'to')
        ];

        // Nếu có nhanvien_stt và ngay_lam, lấy công việc trong ngày
        if ($nhanvienStt && $ngayLam) {
            $data['congviecs'] = $this->congviecModel->getByNhanVienNgay($nhanvienStt, $ngayLam);
            $data['tong_gio'] = $this->congviecModel->getTongGioTrongNgay($nhanvienStt, $ngayLam);
            $data['gio_con_lai'] = max(0, 8 - $data['tong_gio']);
        }
        // Nếu có mavt, lấy lịch sử thiết bị
        elseif ($mavt) {
            $somay = $_GET['somay'] ?? '';
            $data['congviecs'] = $this->congviecModel->getLichSuThietBi($mavt, $somay);
        }
        // Nếu có khoảng thời gian, lấy báo cáo tổng quan
        elseif ($from && $to) {
            $data['congviecs'] = $this->congviecModel->getBaoCaoTongQuan($from, $to);
        }

        return $data;
    }

    /**
     * Tạo công việc mới
     */
    public function create(): array
    {
        error_log("CongViecSuaChuaController::create() called");
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
            return ['success' => false, 'message' => 'Invalid request method'];
        }

        // Lấy dữ liệu từ POST
        $nhanvienStt = (int)($_POST['nhanvien_stt'] ?? 0);
        $ngayLam = $_POST['ngay_lam'] ?? date('Y-m-d');
        $mavt = $_POST['mavt'] ?? '';
        $somay = $_POST['somay'] ?? '';
        $capdoStt = (int)($_POST['capdo_stt'] ?? 0);
        $noiDung = trim($_POST['noi_dung'] ?? '');
        $soGioLam = (float)($_POST['so_gio_lam'] ?? 0);
        $gioBatDau = $_POST['gio_bat_dau'] ?? null;
        $gioKetThuc = $_POST['gio_ket_thuc'] ?? null;
        $ghiChu = trim($_POST['ghi_chu'] ?? '');
        $hososcbdStt = isset($_POST['hososcbd_stt']) ? (int)$_POST['hososcbd_stt'] : null;
        
        error_log("POST data extracted - nhanvien: $nhanvienStt, capdo: $capdoStt");
        
        // Validate required fields
        if ($nhanvienStt <= 0) {
            error_log("Validation failed: nhanvien_stt missing or invalid");
            return ['success' => false, 'message' => 'Vui lòng chọn nhân viên'];
        }
        
        if ($capdoStt <= 0) {
            error_log("Validation failed: capdo_stt missing or invalid");
            return ['success' => false, 'message' => 'Vui lòng chọn cấp độ bảo dưỡng'];
        }
        
        if (empty($noiDung)) {
            error_log("Validation failed: noi_dung empty");
            return ['success' => false, 'message' => 'Vui lòng nhập nội dung công việc'];
        }
        
        if ($soGioLam <= 0) {
            error_log("Validation failed: so_gio_lam invalid");
            return ['success' => false, 'message' => 'Số giờ làm phải lớn hơn 0'];
        }

        // Lấy thông tin nhân viên
        $nhanvien = $this->resumeModel->find($nhanvienStt);
        if (!$nhanvien || $nhanvien === false) {
            error_log("Không tìm thấy nhân viên với stt: $nhanvienStt");
            return ['success' => false, 'message' => 'Không tìm thấy nhân viên'];
        }

        // Lấy thông tin cấp độ
        $capdo = $this->capdoModel->find($capdoStt);
        if (!$capdo || $capdo === false) {
            error_log("Không tìm thấy cấp độ với stt: $capdoStt");
            return ['success' => false, 'message' => 'Không tìm thấy cấp độ bảo dưỡng'];
        }

        // Lấy tên thiết bị (nếu có model ThietBi)
        $tenThietBi = '';
        if ($this->thietbiModel) {
            $thietbi = $this->thietbiModel->findByMaVtAndSoMay($mavt, $somay);
            if ($thietbi) {
                $tenThietBi = $thietbi['tenvt'] ?? '';
            }
        }

        // Chuẩn bị dữ liệu
        $data = [
            'nhanvien_stt' => $nhanvienStt,
            'nhanvien_ten' => $nhanvien['hoten'],
            'ngay_lam' => $ngayLam,
            'mavt' => $mavt,
            'somay' => $somay,
            'ten_thietbi' => $tenThietBi,
            'capdo_stt' => $capdoStt,
            'capdo_ten' => $capdo['ten_capdo'],
            'kpi_gio_chuan' => $capdo['kpi_gio_chuan'],
            'noi_dung' => $noiDung,
            'so_gio_lam' => $soGioLam,
            'gio_bat_dau' => $gioBatDau,
            'gio_ket_thuc' => $gioKetThuc,
            'ghi_chu' => $ghiChu,
            'hososcbd_stt' => $hososcbdStt, // Link to hososcbd record
            'trang_thai' => 'Đang thực hiện'
        ];

        // Tạo công việc với validation
        return $this->congviecModel->createWithValidation($data);
    }

    /**
     * Lấy thông tin 1 công việc để edit
     */
    public function get(int $stt): array
    {
        try {
            error_log("Controller get() - stt: $stt");
            
            // Get basic record
            $congviec = $this->congviecModel->find($stt);
            error_log("Controller get() - find result: " . print_r($congviec, true));
            
            if (!$congviec) {
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy công việc #' . $stt
                ];
            }
            
            // Return data - widget chỉ cần raw data để populate form
            return [
                'success' => true,
                'data' => $congviec
            ];
        } catch (\Exception $e) {
            error_log("Controller get() - Exception: " . $e->getMessage());
            error_log("Controller get() - Trace: " . $e->getTraceAsString());
            
            return [
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage(),
                'debug' => [
                    'exception' => $e->getMessage(),
                    'trace' => explode("\n", $e->getTraceAsString())
                ]
            ];
        }
    }

    /**
     * Cập nhật công việc
     */
    public function update(): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Invalid request method'];
        }

        $stt = (int)($_POST['stt'] ?? 0);
        if (!$stt) {
            return ['success' => false, 'message' => 'Thiếu ID công việc'];
        }

        // Lấy các trường cần update
        $data = [];
        
        if (isset($_POST['nhanvien_stt'])) {
            $data['nhanvien_stt'] = (int)$_POST['nhanvien_stt'];
        }
        
        if (isset($_POST['ngay_lam'])) {
            $data['ngay_lam'] = $_POST['ngay_lam'];
        }
        
        if (isset($_POST['capdo_stt'])) {
            $data['capdo_stt'] = (int)$_POST['capdo_stt'];
        }
        
        if (isset($_POST['noi_dung'])) {
            $data['noi_dung'] = trim($_POST['noi_dung']);
        }
        
        if (isset($_POST['so_gio_lam'])) {
            $data['so_gio_lam'] = (float)$_POST['so_gio_lam'];
        }
        
        if (isset($_POST['gio_bat_dau'])) {
            $data['gio_bat_dau'] = $_POST['gio_bat_dau'];
        }
        
        if (isset($_POST['gio_ket_thuc'])) {
            $data['gio_ket_thuc'] = $_POST['gio_ket_thuc'];
        }
        
        if (isset($_POST['trang_thai'])) {
            $data['trang_thai'] = $_POST['trang_thai'];
        }
        
        if (isset($_POST['ghi_chu'])) {
            $data['ghi_chu'] = trim($_POST['ghi_chu']);
        }

        if (empty($data)) {
            return ['success' => false, 'message' => 'Không có dữ liệu để cập nhật'];
        }

        // Update với validation
        return $this->congviecModel->updateWithValidation($stt, $data);
    }

    /**
     * Xóa công việc
     */
    public function delete(): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Invalid request method'];
        }

        $stt = (int)($_POST['stt'] ?? 0);
        if (!$stt) {
            return ['success' => false, 'message' => 'Thiếu ID công việc'];
        }

        $deletedRows = $this->congviecModel->delete($stt);
        
        return [
            'success' => $deletedRows > 0,
            'message' => $deletedRows > 0 ? 'Xóa công việc thành công' : 'Lỗi khi xóa công việc'
        ];
    }

    /**
     * Lấy form data (nhân viên, cấp độ, thiết bị)
     */
    public function getFormData(): array
    {
        return [
            'nhanviens' => $this->resumeModel->getActiveEmployees(),
            'capdos' => $this->capdoModel->getActiveLevels(),
            'ngay_hom_nay' => date('Y-m-d')
        ];
    }

    /**
     * Kiểm tra giờ còn lại trong ngày
     */
    public function checkGioConLai(): array
    {
        $nhanvienStt = (int)($_GET['nhanvien_stt'] ?? 0);
        $ngayLam = $_GET['ngay_lam'] ?? date('Y-m-d');
        $soGio = (float)($_GET['so_gio'] ?? 0);
        $excludeStt = isset($_GET['exclude_stt']) ? (int)$_GET['exclude_stt'] : null;

        if (!$nhanvienStt) {
            return ['success' => false, 'message' => 'Thiếu thông tin nhân viên'];
        }

        $result = $this->congviecModel->canAddGio($nhanvienStt, $ngayLam, $soGio, $excludeStt);
        $result['success'] = true;
        
        return $result;
    }

    /**
     * Lấy lịch sử sửa chữa của thiết bị
     */
    public function getLichSuThietBi(): array
    {
        $mavt = $_GET['mavt'] ?? '';
        $somay = $_GET['somay'] ?? '';
        $limit = (int)($_GET['limit'] ?? 10);

        if (!$mavt || !$somay) {
            return ['success' => false, 'message' => 'Thiếu thông tin thiết bị'];
        }

        $lichsu = $this->congviecModel->getLichSuThietBi($mavt, $somay, $limit);
        
        return [
            'success' => true,
            'data' => $lichsu,
            'total' => count($lichsu)
        ];
    }

    /**
     * Báo cáo KPI thiết bị
     */
    public function getBaoCaoKPIThietBi(): array
    {
        $mavt = $_GET['mavt'] ?? '';
        $somay = $_GET['somay'] ?? '';

        if (!$mavt || !$somay) {
            return ['success' => false, 'message' => 'Thiếu thông tin thiết bị'];
        }

        $kpiData = $this->congviecModel->getKPIThietBi($mavt, $somay);
        
        return [
            'success' => true,
            'data' => $kpiData,
            'mavt' => $mavt,
            'somay' => $somay
        ];
    }

    /**
     * Báo cáo tổng quan
     */
    public function getBaoCaoTongQuan(): array
    {
        $from = $_GET['from'] ?? date('Y-m-01'); // Đầu tháng
        $to = $_GET['to'] ?? date('Y-m-d'); // Hôm nay

        $baocao = $this->congviecModel->getBaoCaoTongQuan($from, $to);
        $thongkeCapDo = $this->capdoModel->getStatistics();
        
        return [
            'success' => true,
            'bao_cao_nhan_vien' => $baocao,
            'thong_ke_cap_do' => $thongkeCapDo,
            'from' => $from,
            'to' => $to
        ];
    }

    /**
     * Xuất Excel báo cáo
     */
    public function exportExcel(): void
    {
        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-d');

        $baocao = $this->congviecModel->getBaoCaoTongQuan($from, $to);

        // Set headers để download file
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="bao-cao-kpi-' . date('Y-m-d') . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Xuất ra HTML table (Excel sẽ đọc được)
        echo "\xEF\xBB\xBF"; // UTF-8 BOM
        echo "<html><head><meta charset='utf-8'></head><body>";
        echo "<h2>BÁO CÁO KPI SỬA CHỮA</h2>";
        echo "<p>Từ ngày: $from đến $to</p>";
        
        echo "<table border='1'>";
        echo "<tr>";
        echo "<th>STT</th>";
        echo "<th>Nhân viên</th>";
        echo "<th>Số công việc</th>";
        echo "<th>Tổng giờ</th>";
        echo "<th>Giờ TB</th>";
        echo "<th>Số ngày làm</th>";
        echo "<th>Số thiết bị sửa</th>";
        echo "</tr>";

        $stt = 1;
        foreach ($baocao as $row) {
            echo "<tr>";
            echo "<td>$stt</td>";
            echo "<td>{$row['nhanvien_ten']}</td>";
            echo "<td>{$row['so_cong_viec']}</td>";
            echo "<td>{$row['tong_gio']}</td>";
            echo "<td>{$row['gio_trung_binh']}</td>";
            echo "<td>{$row['so_ngay_lam']}</td>";
            echo "<td>{$row['so_thietbi_sua']}</td>";
            echo "</tr>";
            $stt++;
        }

        echo "</table>";
        echo "</body></html>";
        exit;
    }
}
