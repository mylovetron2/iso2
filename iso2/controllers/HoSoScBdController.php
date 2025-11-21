<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/HoSoSCBD.php';
require_once __DIR__ . '/../models/DonVi.php';
require_once __DIR__ . '/../models/ThietBi.php';

class HoSoScBdController
{
    private HoSoSCBD $model;
    private DonVi $donViModel;
    private ThietBi $thietBiModel;

    public function __construct()
    {
        $this->model = new HoSoSCBD();
        $this->donViModel = new DonVi();
        $this->thietBiModel = new ThietBi();
    }

    public function index(): void
    {
        $search = $_GET['search'] ?? '';
        $madv = $_GET['madv'] ?? '';
        $nhomsc = $_GET['nhomsc'] ?? '';
        $trangthai = $_GET['trangthai'] ?? '';
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $items = $this->model->getList($search, $nhomsc, $trangthai, $madv, $offset, $limit);
        $total = $this->model->countList($search, $nhomsc, $trangthai, $madv);
        $totalPages = ceil($total / $limit);

        $stats = $this->model->getStats($nhomsc);
        $donViList = $this->donViModel->getAllSimple();

        require_once __DIR__ . '/../views/hososcbd/index.php';
    }

    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->getPostData();
            
            $errors = $this->validate($data);

            if (empty($errors)) {
                // Auto generate phieu number
                if (empty($data['phieu'])) {
                    $data['phieu'] = $this->model->getNextPhieuNumber();
                }
                
                // Auto-generate maql and hoso
                $data['maql'] = $this->generateMaQL($data['madv'], $data['phieu']);
                $data['hoso'] = $this->generateHoSo($data['madv'], $data['phieu'], $data['mavt'], $data['somay']);
                
                $id = $this->model->create($data);
                if ($id) {
                    header('Location: /iso2/hososcbd.php?success=created');
                    exit;
                }
                $errors[] = 'Có lỗi xảy ra khi tạo hồ sơ';
            }

            $error = implode(', ', $errors);
        }

        $donViList = $this->donViModel->getAllSimple();
        $nextPhieu = $this->model->getNextPhieuNumber();
        
        require_once __DIR__ . '/../views/hososcbd/create.php';
    }

    public function edit(): void
    {
        $stt = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$stt) {
            header('Location: /iso2/hososcbd.php?error=invalid');
            exit;
        }

        $item = $this->model->findById($stt);
        if (!$item) {
            header('Location: /iso2/hososcbd.php?error=notfound');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->getPostData();
            
            $errors = $this->validate($data);

            if (empty($errors)) {
                // Auto-generate maql and hoso for edit
                $data['maql'] = $this->generateMaQL($data['madv'], $data['phieu']);
                $data['hoso'] = $this->generateHoSo($data['madv'], $data['phieu'], $data['mavt'], $data['somay']);
                
                $success = $this->model->update($stt, $data);
                if ($success) {
                    header('Location: /iso2/hososcbd.php?success=updated');
                    exit;
                }
                $errors[] = 'Có lỗi xảy ra khi cập nhật hồ sơ';
            }

            $error = implode(', ', $errors);
        }

        $donViList = $this->donViModel->getAllSimple();
        require_once __DIR__ . '/../views/hososcbd/edit.php';
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /iso2/hososcbd.php');
            exit;
        }

        $stt = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if (!$stt) {
            header('Location: /iso2/hososcbd.php?error=invalid');
            exit;
        }

        $success = $this->model->delete($stt);
        if ($success) {
            header('Location: /iso2/hososcbd.php?success=deleted');
        } else {
            header('Location: /iso2/hososcbd.php?error=delete_failed');
        }
        exit;
    }

    private function getPostData(): array
    {
        return [
            'mavt' => trim($_POST['mavt'] ?? ''),
            'somay' => trim($_POST['somay'] ?? ''),
            'ngayyc' => trim($_POST['ngayyc'] ?? date('Y-m-d')),
            'madv' => trim($_POST['madv'] ?? ''),
            'phieu' => trim($_POST['phieu'] ?? ''),
            'solg' => (int)($_POST['solg'] ?? 0),
            'cv' => trim($_POST['cv'] ?? ''),
            'ngyeucau' => trim($_POST['ngyeucau'] ?? ''),
            'ngnhyeucau' => trim($_POST['ngnhyeucau'] ?? ''),
            'ngaykt' => trim($_POST['ngaykt'] ?? ''),
            'ttktbefore' => trim($_POST['ttktbefore'] ?? ''),
            'honghoc' => trim($_POST['honghoc'] ?? ''),
            'khacphuc' => trim($_POST['khacphuc'] ?? ''),
            'ttktafter' => trim($_POST['ttktafter'] ?? ''),
            'ghichu' => trim($_POST['ghichu'] ?? ''),
            'ngayth' => trim($_POST['ngayth'] ?? date('Y-m-d')),
            'tbdosc' => trim($_POST['tbdosc'] ?? ''),
            'serialtbdosc' => trim($_POST['serialtbdosc'] ?? ''),
            'tbdosc1' => trim($_POST['tbdosc1'] ?? ''),
            'serialtbdosc1' => trim($_POST['serialtbdosc1'] ?? ''),
            'tbdosc2' => trim($_POST['tbdosc2'] ?? ''),
            'serialtbdosc2' => trim($_POST['serialtbdosc2'] ?? ''),
            'tbdosc3' => trim($_POST['tbdosc3'] ?? ''),
            'serialtbdosc3' => trim($_POST['serialtbdosc3'] ?? ''),
            'tbdosc4' => trim($_POST['tbdosc4'] ?? ''),
            'serialtbdosc4' => trim($_POST['serialtbdosc4'] ?? ''),
            'nhomsc' => trim($_POST['nhomsc'] ?? ''),
            'bg' => (int)($_POST['bg'] ?? 0),
            'ngaybdtt' => trim($_POST['ngaybdtt'] ?? date('Y-m-d')),
            'dong' => trim($_POST['dong'] ?? ''),
            'noidung' => trim($_POST['noidung'] ?? ''),
            'ketluan' => trim($_POST['ketluan'] ?? ''),
            'vitrimaybd' => trim($_POST['vitrimaybd'] ?? ''),
            'dienthoai' => trim($_POST['dienthoai'] ?? ''),
            'ycthemkh' => trim($_POST['ycthemkh'] ?? ''),
            'xemxetxuong' => trim($_POST['xemxetxuong'] ?? ''),
            'model' => trim($_POST['model'] ?? ''),
            'slbg' => (int)($_POST['slbg'] ?? 0),
            'lo' => trim($_POST['lo'] ?? ''),
            'gieng' => trim($_POST['gieng'] ?? ''),
            'mo' => trim($_POST['mo'] ?? ''),
            'ghichufinal' => trim($_POST['ghichufinal'] ?? '')
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        
        // Required fields (maql and hoso will be auto-generated)
        if (empty($data['mavt'])) $errors[] = 'Mã vật tư không được để trống';
        if (empty($data['somay'])) $errors[] = 'Số máy không được để trống';
        if (empty($data['ngayyc'])) $errors[] = 'Ngày yêu cầu không được để trống';
        if (empty($data['madv'])) $errors[] = 'Mã đơn vị không được để trống';
        if (empty($data['cv'])) $errors[] = 'Công việc không được để trống';
        if (empty($data['nhomsc'])) $errors[] = 'Nhóm sửa chữa không được để trống';
        if (empty($data['vitrimaybd'])) $errors[] = 'Vị trí máy bàn dịch không được để trống';
        if (empty($data['model'])) $errors[] = 'Model không được để trống';
        if (empty($data['lo'])) $errors[] = 'Lô không được để trống';
        if (empty($data['gieng'])) $errors[] = 'Giếng không được để trống';
        if (empty($data['mo'])) $errors[] = 'Mỏ không được để trống';

        return $errors;
    }

    /**
     * Generate mã quản lý (maql)
     * Format: YYYYMMDD-MADV-PHIEU-N1
     * Example: 20251121-XDT-0126-N1
     */
    private function generateMaQL(string $madv, string $phieu, int $index = 1): string
    {
        $date = date('Ymd');
        return "{$date}-{$madv}-{$phieu}-N{$index}";
    }

    /**
     * Generate mã hồ sơ (hoso)
     * Format: MADV-PHIEU-MAVT-SOMAY
     * Example: XDT-0126-PM001-SN12345
     */
    private function generateHoSo(string $madv, string $phieu, string $mavt, string $somay): string
    {
        return "{$madv}-{$phieu}-{$mavt}-{$somay}";
    }
}
