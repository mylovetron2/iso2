<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/HoSoSCBD.php';
require_once __DIR__ . '/../models/DonVi.php';
require_once __DIR__ . '/../models/ThietBi.php';
require_once __DIR__ . '/../models/LichSuDN.php';

class HoSoScBdController
{
    private HoSoSCBD $model;
    private DonVi $donViModel;
    private ThietBi $thietBiModel;
    private LichSuDN $logModel;

    public function __construct()
    {
        $this->model = new HoSoSCBD();
        $this->donViModel = new DonVi();
        $this->thietBiModel = new ThietBi();
        $this->logModel = new LichSuDN();
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
            $commonData = $this->getCommonPostData();
            $devicesData = $this->getDevicesPostData();
            
            // Validate common data
            $errors = $this->validateCommonData($commonData);
            
            // Validate each device
            if (empty($errors) && empty($devicesData)) {
                $errors[] = 'Phải nhập ít nhất 1 thiết bị';
            }
            
            foreach ($devicesData as $index => $device) {
                $deviceErrors = $this->validateDevice($device, $index, null);
                if (!empty($deviceErrors)) {
                    $errors = array_merge($errors, $deviceErrors);
                }
            }

            if (empty($errors)) {
                // Auto generate phieu number
                if (empty($commonData['phieu'])) {
                    $commonData['phieu'] = $this->model->getNextPhieuNumber();
                }
                
                // Insert each device
                $successCount = 0;
                foreach ($devicesData as $index => $device) {
                    $data = array_merge($commonData, $device);
                    
                    // Auto-generate maql with index suffix (N1, N2, N3, etc.)
                    $data['maql'] = $this->generateMaQL($data['madv'], $data['phieu'], $index);
                    $data['hoso'] = $this->generateHoSo($data['madv'], $data['phieu'], $data['mavt'], $data['somay']);
                    
                    $id = $this->model->create($data);
                    if ($id) {
                        $successCount++;
                        
                        // Log the creation
                        $this->logHistory('CREATE', [
                            'record_id' => $id,
                            'maql' => $data['maql'],
                            'phieu' => $data['phieu'],
                            'mavt' => $data['mavt'],
                            'somay' => $data['somay'],
                            'madv' => $data['madv'],
                            'description' => "Tạo hồ sơ mới: {$data['maql']} - Thiết bị {$index}/{count($devicesData)}"
                        ]);
                    }
                }
                
                if ($successCount > 0) {
                    header("Location: /iso2/hososcbd.php?success=created&count={$successCount}");
                    exit;
                }
                $errors[] = 'Có lỗi xảy ra khi tạo hồ sơ';
            }

            $error = implode('<br>', $errors);
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
            
            $errors = $this->validate($data, $stt);

            if (empty($errors)) {
                // Auto-generate maql and hoso for edit
                $data['maql'] = $this->generateMaQL($data['madv'], $data['phieu']);
                $data['hoso'] = $this->generateHoSo($data['madv'], $data['phieu'], $data['mavt'], $data['somay']);
                
                $success = $this->model->update($stt, $data);
                if ($success) {
                    // Log the update
                    $this->logHistory('UPDATE', [
                        'record_id' => $stt,
                        'maql' => $data['maql'],
                        'phieu' => $data['phieu'],
                        'mavt' => $data['mavt'],
                        'somay' => $data['somay'],
                        'madv' => $data['madv'],
                        'description' => "Cập nhật hồ sơ: {$data['maql']}"
                    ]);
                    
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

        // Get record info before deleting for logging
        $item = $this->model->findById($stt);
        
        $success = $this->model->delete($stt);
        if ($success) {
            // Log the deletion
            if ($item) {
                $this->logHistory('DELETE', [
                    'record_id' => $stt,
                    'maql' => $item['maql'] ?? null,
                    'phieu' => $item['phieu'] ?? null,
                    'mavt' => $item['mavt'] ?? null,
                    'somay' => $item['somay'] ?? null,
                    'madv' => $item['madv'] ?? null,
                    'description' => "Xóa hồ sơ: {$item['maql']}"
                ]);
            }
            
            header('Location: /iso2/hososcbd.php?success=deleted');
        } else {
            header('Location: /iso2/hososcbd.php?error=delete_failed');
        }
        exit;
    }

    /**
     * Get common data (shared across all devices in batch)
     */
    private function getCommonPostData(): array
    {
        return [
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
            'dienthoai' => trim($_POST['dienthoai'] ?? ''),
            'ycthemkh' => trim($_POST['ycthemkh'] ?? ''),
            'xemxetxuong' => trim($_POST['xemxetxuong'] ?? ''),
            'slbg' => (int)($_POST['slbg'] ?? 0),
            'ghichufinal' => trim($_POST['ghichufinal'] ?? '')
        ];
    }
    
    /**
     * Get devices data (array of 1-5 devices)
     */
    private function getDevicesPostData(): array
    {
        $devices = [];
        
        if (isset($_POST['devices']) && is_array($_POST['devices'])) {
            foreach ($_POST['devices'] as $index => $device) {
                // Only add device if at least mavt and somay are filled
                if (!empty($device['mavt']) && !empty($device['somay'])) {
                    $devices[(int)$index] = [
                        'mavt' => trim($device['mavt'] ?? ''),
                        'somay' => trim($device['somay'] ?? ''),
                        'model' => trim($device['model'] ?? ''),
                        'vitrimaybd' => trim($device['vitrimaybd'] ?? ''),
                        'lo' => trim($device['lo'] ?? ''),
                        'gieng' => trim($device['gieng'] ?? ''),
                        'mo' => trim($device['mo'] ?? '')
                    ];
                }
            }
        }
        
        return $devices;
    }
    
    /**
     * Get single device POST data (for edit)
     */
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

    /**
     * Validate common data (shared fields)
     */
    private function validateCommonData(array $data): array
    {
        $errors = [];
        
        if (empty($data['ngayyc'])) $errors[] = 'Ngày yêu cầu không được để trống';
        if (empty($data['madv'])) $errors[] = 'Mã đơn vị không được để trống';
        if (empty($data['cv'])) $errors[] = 'Công việc không được để trống';
        if (empty($data['nhomsc'])) $errors[] = 'Nhóm sửa chữa không được để trống';
        
        return $errors;
    }
    
    /**
     * Validate single device data
     */
    private function validateDevice(array $device, int $index, ?int $excludeStt = null): array
    {
        $errors = [];
        $label = "Thiết bị {$index}";
        
        // Required fields
        if (empty($device['mavt'])) $errors[] = "{$label}: Mã vật tư không được để trống";
        if (empty($device['somay'])) $errors[] = "{$label}: Số máy không được để trống";
        if (empty($device['model'])) $errors[] = "{$label}: Model không được để trống";
        if (empty($device['vitrimaybd'])) $errors[] = "{$label}: Vị trí máy bàn dịch không được để trống";
        if (empty($device['lo'])) $errors[] = "{$label}: Lô không được để trống";
        if (empty($device['gieng'])) $errors[] = "{$label}: Giếng không được để trống";
        if (empty($device['mo'])) $errors[] = "{$label}: Mỏ không được để trống";
        
        // Check device availability (bg=0 means device is busy)
        if (!empty($device['mavt']) && !empty($device['somay'])) {
            if (!$this->model->isDeviceAvailable($device['mavt'], $device['somay'], $excludeStt)) {
                $errors[] = "{$label}: {$device['mavt']} - {$device['somay']} đang được sử dụng trong phiếu khác (chưa bàn giao)";
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate single record (for edit)
     */
    private function validate(array $data, ?int $excludeStt = null): array
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
        
        // Check device availability (bg=0 means device is busy)
        if (!empty($data['mavt']) && !empty($data['somay'])) {
            if (!$this->model->isDeviceAvailable($data['mavt'], $data['somay'], $excludeStt)) {
                $errors[] = "Thiết bị {$data['mavt']} - {$data['somay']} đang được sử dụng trong phiếu khác (chưa bàn giao)";
            }
        }

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
    
    /**
     * Log history action
     * 
     * @param string $action CREATE/UPDATE/DELETE/HANDOVER
     * @param array $data Additional data to log
     */
    private function logHistory(string $action, array $data): void
    {
        try {
            $this->logModel->log($action, $data);
        } catch (Exception $e) {
            // Silent fail - don't break the main operation if logging fails
            error_log("Logging failed: " . $e->getMessage());
        }
    }
}
