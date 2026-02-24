<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/CongViecSuaChua.php';
require_once __DIR__ . '/../models/CapDoBaoCuong.php';
require_once __DIR__ . '/../models/ThietBiCapDoKPI.php';
require_once __DIR__ . '/../models/Resume.php';
require_once __DIR__ . '/../models/HoSoSCBD.php';

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
    private HoSoSCBD $hosoModel;
    private $thietbiModel;

    public function __construct()
    {
        $this->congviecModel = new CongViecSuaChua();
        $this->capdoModel = new CapDoBaoCuong();
        $this->kpiModel = new ThietBiCapDoKPI();
        $this->resumeModel = new Resume();
        $this->hosoModel = new HoSoSCBD();
        
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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Invalid request method'];
        }

        // Lấy dữ liệu từ POST
        $nhanvienStt = (int)($_POST['nhanvien_stt'] ?? 0);
        $ngayLamViec = $_POST['ngay_lam_viec'] ?? date('Y-m-d');
        $hososcbdStt = (int)($_POST['hososcbd_stt'] ?? 0);
        $capdoStt = (int)($_POST['capdo_stt'] ?? 0);
        $soGioLam = (float)($_POST['so_gio_lam'] ?? 0);
        $gioBatDau = $_POST['gio_bat_dau'] ?? null;
        $gioKetThuc = $_POST['gio_ket_thuc'] ?? null;
        $noiDungCongViec = trim($_POST['noi_dung_congviec'] ?? '');
        $ghiChu = trim($_POST['ghi_chu'] ?? '');

        // Validate required fields
        if (!$nhanvienStt) {
            return ['success' => false, 'message' => 'Vui lòng chọn nhân viên'];
        }
        
        if (!$hososcbdStt) {
            return ['success' => false, 'message' => 'Vui lòng chọn hồ sơ SCBD'];
        }
        
        if (!$capdoStt) {
            return ['success' => false, 'message' => 'Vui lòng chọn cấp độ bảo dưỡng'];
        }

        // Lấy thông tin hồ sơ SCBD
        $hoso = $this->hosoModel->getHoSoWithThietBi($hososcbdStt);
        if (!$hoso) {
            return ['success' => false, 'message' => 'Không tìm thấy hồ sơ SCBD'];
        }

        // Lấy thông tin cấp độ để lấy KPI chuẩn
        $capdo = $this->capdoModel->find($capdoStt);
        if (!$capdo) {
            return ['success' => false, 'message' => 'Không tìm thấy cấp độ bảo dưỡng'];
        }

        // Lấy thietbi_stt (nếu có) để cache
        $thietbiStt = null;
        if ($this->thietbiModel && !empty($hoso['mavt']) && !empty($hoso['somay'])) {
            $thietbi = $this->thietbiModel->findByMaVtAndSoMay($hoso['mavt'], $hoso['somay']);
            if ($thietbi) {
                $thietbiStt = $thietbi['stt'];
            }
        }

        // Chuẩn bị dữ liệu
        $data = [
            'nhanvien_stt' => $nhanvienStt,
            'ngay_lam_viec' => $ngayLamViec,
            'hososcbd_stt' => $hososcbdStt,
            'thietbi_stt' => $thietbiStt,
            'capdo_stt' => $capdoStt,
            'kpi_gio_chuan' => $capdo['kpi_gio_chuan'],
            'so_gio_lam' => $soGioLam,
            'gio_bat_dau' => $gioBatDau,
            'gio_ket_thuc' => $gioKetThuc,
            'noi_dung_congviec' => $noiDungCongViec,
            'ghi_chu' => $ghiChu,
            'trang_thai' => 'Hoàn thành'
        ];

        // Tạo công việc với validation
        return $this->congviecModel->createWithValidation($data);
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

    /**
     * Lấy danh sách hồ sơ SCBD (cho dropdown)
     */
    public function getHoSoList(): array
    {
        $keyword = $_GET['keyword'] ?? '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
        
        if ($keyword) {
            // Tìm kiếm theo từ khóa
            return $this->hosoModel->searchByMaOrPhieu($keyword, $limit);
        } else {
            // Lấy hồ sơ gần đây (6 tháng)
            return $this->hosoModel->getActiveHoSo($limit);
        }
    }

    /**
     * Lấy thông tin hồ sơ + thiết bị (AJAX)
     */
    public function getHoSoInfo(): array
    {
        $hososcbdStt = isset($_GET['hososcbd_stt']) ? (int)$_GET['hososcbd_stt'] : 0;
        
        if (!$hososcbdStt) {
            return ['success' => false, 'message' => 'Thiếu hososcbd_stt'];
        }
        
        $hoso = $this->hosoModel->getHoSoWithThietBi($hososcbdStt);
        
        if (!$hoso) {
            return ['success' => false, 'message' => 'Không tìm thấy hồ sơ'];
        }
        
        return [
            'success' => true,
            'data' => [
                'stt' => $hoso['stt'],
                'ma_hoso' => $hoso['hoso'],
                'so_phieu' => $hoso['phieu'],
                'mavt' => $hoso['mavt'],
                'somay' => $hoso['somay'],
                'ten_thietbi' => $hoso['ten_thietbi'] ?? 'N/A',
                'model' => $hoso['model_thietbi'] ?? '',
                'ten_donvi' => $hoso['ten_donvi'] ?? '',
                'ngay_yeu_cau' => $hoso['ngayyc'],
                'display_text' => sprintf(
                    '%s - %s | %s | HS: %s',
                    $hoso['mavt'],
                    $hoso['somay'],
                    $hoso['ten_thietbi'] ?? 'N/A',
                    $hoso['hoso']
                )
            ]
        ];
    }
}
