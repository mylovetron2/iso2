<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/HoSoSCBD.php';
require_once __DIR__ . '/../models/HoSoSCBDDinhMuc.php';
require_once __DIR__ . '/../models/DonVi.php';
require_once __DIR__ . '/../models/ThietBi.php';
require_once __DIR__ . '/../models/LichSuDN.php';
require_once __DIR__ . '/../models/PhieuYeuCau.php';

class HoSoScBdController
{
    private HoSoSCBD $model;
    private DonVi $donViModel;
    private ThietBi $thietBiModel;
    private LichSuDN $logModel;
    private PhieuYeuCau $phieuModel;

    public function __construct()
    {
        $this->model = new HoSoSCBD();
        $this->donViModel = new DonVi();
        $this->thietBiModel = new ThietBi();
        $this->logModel = new LichSuDN();
        $this->phieuModel = new PhieuYeuCau();
    }

    public function index(): void
    {
        $search = $_GET['search'] ?? '';
        $madv = $_GET['madv'] ?? '';
        $nhomsc = $_GET['nhomsc'] ?? '';
        $cv = $_GET['cv'] ?? '';
        $trangthai = $_GET['trangthai'] ?? '';

        $ngayYcFrom = $_GET['ngayyc_from'] ?? '';
        $ngayYcTo = $_GET['ngayyc_to'] ?? '';
        $ngayThFrom = $_GET['ngayth_from'] ?? '';
        $ngayThTo = $_GET['ngayth_to'] ?? '';
        $ngayKtFrom = $_GET['ngaykt_from'] ?? '';
        $ngayKtTo = $_GET['ngaykt_to'] ?? '';

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $items = $this->model->getList(
            $search,
            $nhomsc,
            $trangthai,
            $madv,
            $cv,
            $offset,
            $limit,
            $ngayYcFrom,
            $ngayYcTo,
            $ngayThFrom,
            $ngayThTo,
            $ngayKtFrom,
            $ngayKtTo
        );
        $total = $this->model->countList(
            $search,
            $nhomsc,
            $trangthai,
            $madv,
            $cv,
            $ngayYcFrom,
            $ngayYcTo,
            $ngayThFrom,
            $ngayThTo,
            $ngayKtFrom,
            $ngayKtTo
        );
        $totalPages = ceil($total / $limit);

        $stats = $this->model->getStats($nhomsc);
        $donViList = $this->donViModel->getAllSimple();

        require_once __DIR__ . '/../views/hososcbd/index.php';
    }

    /**
     * AJAX endpoint: trả về số liệu thống kê (lazy-load để không block page load).
     * GET ?action=ajax_stats&nhomsc=...
     */
    public function ajaxStats(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $nhomsc = $_GET['nhomsc'] ?? '';
        $data = $this->model->getStats($nhomsc);
        echo json_encode($data ?: (object)[]);
        exit;
    }

    /**
     * AJAX endpoint: trả về dữ liệu BDDK & HC/KĐ cho trang danh sách (lazy-load).
     * Nhận POST JSON: { items: [{stt, thietbi_stt, ngayyc}, ...] }
     * Trả về JSON keyed by stt.
     */
    public function ajaxBddkHckd(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);

        if (!is_array($input) || empty($input['items']) || !is_array($input['items'])) {
            echo json_encode((object)[]);
            exit;
        }

        // Sanitize — chỉ cho phép integer ID và chuỗi ngày đơn giản
        $items = [];
        foreach ($input['items'] as $item) {
            $stt = (int)($item['stt'] ?? 0);
            if ($stt <= 0) continue;
            $items[] = [
                'stt'         => $stt,
                'thietbi_stt' => (int)($item['thietbi_stt'] ?? 0),
                'ngayyc'      => preg_replace('/[^0-9\-]/', '', (string)($item['ngayyc'] ?? '')),
            ];
        }

        if (empty($items)) {
            echo json_encode((object)[]);
            exit;
        }

        $data = $this->model->getBddkHckdBatch($items);
        echo json_encode($data);
        exit;
    }

    public function create(): void
    {
        $prefillPhieu = trim($_GET['phieu'] ?? '');

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
                
                // Lấy index lớn nhất hiện có trong phiếu để tính số hồ sơ tiếp tục
                // Ví dụ: phiếu 1997 đã có 1997-1, 1997-2 -> maxIndex = 2
                // Thiết bị mới sẽ bắt đầu từ 1997-3, 1997-4...
                $maxExistingIndex = $this->model->getMaxHosoIndexForPhieu($commonData['phieu']);
                $hosoCounter = $maxExistingIndex; // Bắt đầu từ số hiện có, sẽ tăng lên trước khi dùng
                
                // Insert each device
                $successCount = 0;
                $createdDevices = []; // Store created devices info for batch logging
                $db = $this->model->getDb();
                
                try {
                    $db->beginTransaction();
                    $dinhMucModel = new HoSoSCBDDinhMuc();

                    foreach ($devicesData as $device) {
                        $data = array_merge($commonData, $device);
                        
                        // Auto-generate maql (same for all devices in same phieu)
                        $data['maql'] = $this->generateMaQL($data['madv'], $data['phieu'], $data['ngayyc']);
                        
                        // Generate hoso number: tăng counter và tạo số thứ tự
                        $hosoCounter++;
                        $data['hoso'] = $this->generateHoSo($data['phieu'], $hosoCounter);
                        
                        $id = $this->model->create($data);
                        if ($id !== '') {
                            $successCount++;
                            
                            $dinhMucModel->autoAssignDefaultByDevice(
                                (int)$id,
                                (string)$data['mavt'],
                                (string)$data['somay'],
                                $_SESSION['username'] ?? null
                            );
                            
                            // Store device info for batch logging later
                            $createdDevices[] = [
                                'id' => (int)$id,
                                'maql' => $data['maql'],
                                'mavt' => $data['mavt'],
                                'somay' => $data['somay'],
                                'hoso' => $data['hoso']
                            ];
                        }
                    }

                    $db->commit();
                    
                    // Batch log after all devices created (much faster than logging each device)
                    if ($successCount > 0) {
                        $deviceList = array_map(function($d) {
                            return "{$d['hoso']} ({$d['mavt']}/{$d['somay']})";
                        }, $createdDevices);
                        
                        $this->logHistory('CREATE', [
                            'record_id' => $createdDevices[0]['id'],
                            'maql' => $createdDevices[0]['maql'],
                            'phieu' => $commonData['phieu'],
                            'madv' => $commonData['madv'],
                            'description' => "Tạo {$successCount} hồ sơ mới: " . implode(', ', $deviceList)
                        ]);
                        
                        header("Location: /iso2/phieuyeucau.php?action=view&phieu=" . urlencode($commonData['phieu']));
                        exit;
                    }
                    $errors[] = 'Có lỗi xảy ra khi tạo hồ sơ';
                } catch (PDOException $e) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    error_log("HoSoScBd create error: " . $e->getMessage());
                    error_log("Data: " . print_r($data ?? [], true));
                    $errors[] = 'Lỗi cơ sở dữ liệu: ' . $e->getMessage();
                }
            }

            $error = implode('<br>', $errors);
        }

        $donViList = $this->donViModel->getAllSimple();
        $nextPhieu = $this->model->getNextPhieuNumber();
        $prefillData = null;
        
        // Nếu thêm thiết bị vào phiếu có sẵn, lấy thông tin phiếu để prefill
        if (!empty($prefillPhieu)) {
            $nextPhieu = $prefillPhieu;
            $phieuDetail = $this->phieuModel->getPhieuDetail($prefillPhieu);
            if ($phieuDetail && isset($phieuDetail['summary'])) {
                $prefillData = $phieuDetail['summary'];
            }
        }
        
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
                // Auto-generate maql for edit
                $data['maql'] = $this->generateMaQL($data['madv'], $data['phieu'], $data['ngayyc']);
                
                // Giữ nguyên mã hồ sơ hiện có khi edit.
                // Chỉ sinh lại trong trường hợp dữ liệu cũ bị thiếu hoso.
                $data['hoso'] = trim((string)($item['hoso'] ?? ''));
                if ($data['hoso'] === '') {
                    $maxIndex = $this->model->getMaxHosoIndexForPhieu($data['phieu']);
                    $data['hoso'] = $this->generateHoSo($data['phieu'], $maxIndex + 1);
                }
                
                try {
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
                } catch (PDOException $e) {
                    error_log("HoSoScBd edit error: " . $e->getMessage());
                    error_log("Data: " . print_r($data, true));
                    $errors[] = 'Lỗi cơ sở dữ liệu: ' . $e->getMessage();
                }
            }

            $error = implode(', ', $errors);
        }

        $donViList = $this->donViModel->getAllSimple();
        
        // Load thiết bị hỗ trợ list
        require_once __DIR__ . '/../models/ThietBiHoTro.php';
        $thietBiHoTroModel = new ThietBiHoTro();
        $thietBiHoTroList = $thietBiHoTroModel->getAllSimple();
        
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
            'ngaykt' => empty(trim($_POST['ngaykt'] ?? '')) ? '0000-00-00' : trim($_POST['ngaykt']),
            'ttktbefore' => trim($_POST['ttktbefore'] ?? ''),
            'honghoc' => trim($_POST['honghoc'] ?? ''),
            'khacphuc' => trim($_POST['khacphuc'] ?? ''),
            'ttktafter' => trim($_POST['ttktafter'] ?? ''),
            'ghichu' => trim($_POST['ghichu'] ?? ''),
            'ngayth' => empty(trim($_POST['ngayth'] ?? '')) ? '0000-00-00' : trim($_POST['ngayth']),
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
            'nhomsc' => trim($_POST['nhomsc'] ?? 'RDNGA'),
            'bg' => (int)($_POST['bg'] ?? 0),
            'ngaybdtt' => empty(trim($_POST['ngaybdtt'] ?? '')) ? '0000-00-00' : trim($_POST['ngaybdtt']),
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
                        'honghoc' => trim($device['honghoc'] ?? ''),
                        'noidung' => trim($device['noidungyc'] ?? ''),
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
            'ngaykt' => empty(trim($_POST['ngaykt'] ?? '')) ? '0000-00-00' : trim($_POST['ngaykt']),
            'ttktbefore' => trim($_POST['ttktbefore'] ?? ''),
            'honghoc' => trim($_POST['honghoc'] ?? ''),
            'khacphuc' => trim($_POST['khacphuc'] ?? ''),
            'ttktafter' => trim($_POST['ttktafter'] ?? ''),
            'ghichu' => trim($_POST['ghichu'] ?? ''),
            'ngayth' => empty(trim($_POST['ngayth'] ?? '')) ? '0000-00-00' : trim($_POST['ngayth']),
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
            'nhomsc' => trim($_POST['nhomsc'] ?? 'RDNGA'),
            'bg' => (int)($_POST['bg'] ?? 0),
            'ngaybdtt' => empty(trim($_POST['ngaybdtt'] ?? '')) ? '0000-00-00' : trim($_POST['ngaybdtt']),
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
        // nhomsc is optional now - can be filled in repair_details page
        
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
        // model, vitrimaybd, lo, gieng, mo are optional - no validation
        
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
        // model, vitrimaybd, lo, gieng, mo, nhomsc are optional - no validation
        
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
     * Format: YYYYMMDD-MADV-PHIEU
     * Example: 20251121-XDT-0126
     */
    private function generateMaQL(string $madv, string $phieu, string $ngayyc): string
    {
        // Convert ngayyc from YYYY-MM-DD to YYYYMMDD
        $date = str_replace('-', '', $ngayyc);
        return "{$date}-{$madv}-{$phieu}";
    }

    /**
     * Generate mã hồ sơ (hoso)
     * Format: PHIEU-1, PHIEU-2, PHIEU-3...
     * Example: 0126-1, 0126-2, 0126-3
     */
    private function generateHoSo(string $phieu, int $index): string
    {
        return "{$phieu}-{$index}";
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

    /**
     * Export single record as PDF
     */
    public function exportPdf(): void
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

        require_once __DIR__ . '/../views/hososcbd/export_pdf.php';
    }

    /**
     * Export single record as Word document
     */
    public function exportWord(): void
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

        require_once __DIR__ . '/../views/hososcbd/export_word.php';
    }

    /**
     * Export Phieu SC (Phiếu Thực Hiện Công Việc SC/BD/KT)
     */
    public function exportPhieuSC(): void
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

        require_once __DIR__ . '/../views/hososcbd/export_phieu_sc.php';
    }

    /**
     * Export list as PDF with filters
     */
    public function exportListPdf(): void
    {
        $search = $_GET['search'] ?? '';
        $madv = $_GET['madv'] ?? '';
        $nhomsc = $_GET['nhomsc'] ?? '';
        $cv = $_GET['cv'] ?? '';
        $trangthai = $_GET['trangthai'] ?? '';

        $ngayYcFrom = $_GET['ngayyc_from'] ?? '';
        $ngayYcTo = $_GET['ngayyc_to'] ?? '';
        $ngayThFrom = $_GET['ngayth_from'] ?? '';
        $ngayThTo = $_GET['ngayth_to'] ?? '';
        $ngayKtFrom = $_GET['ngaykt_from'] ?? '';
        $ngayKtTo = $_GET['ngaykt_to'] ?? '';

        $items = $this->model->getList(
            $search,
            $nhomsc,
            $trangthai,
            $madv,
            $cv,
            0,
            1000,
            $ngayYcFrom,
            $ngayYcTo,
            $ngayThFrom,
            $ngayThTo,
            $ngayKtFrom,
            $ngayKtTo
        ); // Max 1000 records
        $stats = $this->model->getStats($nhomsc);
        $donViList = $this->donViModel->getAllSimple();
        $bddkHckdData = $this->model->getBddkHckdBatch($items);

        require_once __DIR__ . '/../views/hososcbd/export_list_pdf.php';
    }

    /**
     * Export list as Excel with filters
     */
    public function exportListExcel(): void
    {
        $search = $_GET['search'] ?? '';
        $madv = $_GET['madv'] ?? '';
        $nhomsc = $_GET['nhomsc'] ?? '';
        $cv = $_GET['cv'] ?? '';
        $trangthai = $_GET['trangthai'] ?? '';

        $ngayYcFrom = $_GET['ngayyc_from'] ?? '';
        $ngayYcTo = $_GET['ngayyc_to'] ?? '';
        $ngayThFrom = $_GET['ngayth_from'] ?? '';
        $ngayThTo = $_GET['ngayth_to'] ?? '';
        $ngayKtFrom = $_GET['ngaykt_from'] ?? '';
        $ngayKtTo = $_GET['ngaykt_to'] ?? '';

        $items = $this->model->getList(
            $search,
            $nhomsc,
            $trangthai,
            $madv,
            $cv,
            0,
            1000,
            $ngayYcFrom,
            $ngayYcTo,
            $ngayThFrom,
            $ngayThTo,
            $ngayKtFrom,
            $ngayKtTo
        );

        $filename = 'HoSoSCBD_DanhSach_' . date('YmdHis') . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        echo "\xEF\xBB\xBF";
        echo "<html><meta charset='utf-8'><body><table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr style='font-weight:bold; background:#dfeaf7;'>";
        echo "<th>STT</th><th>Phiếu</th><th>Số hồ sơ</th><th>Mã VT</th><th>Số máy</th><th>Ngày YC</th><th>Ngày TH</th><th>Ngày KT</th><th>Đơn vị</th><th>CV</th><th>Nhóm</th><th>Trạng thái</th>";
        echo "</tr>";

        if (empty($items)) {
            echo "<tr><td colspan='12' align='center'>Không có dữ liệu</td></tr>";
        } else {
            foreach ($items as $index => $item) {
                $ngayYc = !empty($item['ngayyc']) && $item['ngayyc'] !== '0000-00-00' ? date('d/m/Y', strtotime($item['ngayyc'])) : '-';
                $ngayTh = !empty($item['ngayth']) && $item['ngayth'] !== '0000-00-00' ? date('d/m/Y', strtotime($item['ngayth'])) : '-';
                $ngayKt = !empty($item['ngaykt']) && $item['ngaykt'] !== '0000-00-00' ? date('d/m/Y', strtotime($item['ngaykt'])) : '-';

                if ((int)($item['bg'] ?? 0) === 1) {
                    $status = 'Đã BG';
                } elseif (!empty($item['ngaykt']) && $item['ngaykt'] !== '0000-00-00') {
                    $status = 'Hoàn thành';
                } elseif (!empty($item['ngayth']) && $item['ngayth'] !== '0000-00-00') {
                    $status = 'Đang làm';
                } else {
                    $status = 'Chưa TH';
                }

                echo "<tr>";
                echo "<td>" . ($index + 1) . "</td>";
                echo "<td>" . htmlspecialchars((string)($item['phieu'] ?? '')) . "</td>";
                echo "<td>" . htmlspecialchars((string)($item['hoso'] ?? '')) . "</td>";
                echo "<td>" . htmlspecialchars((string)($item['mavt'] ?? '')) . "</td>";
                echo "<td>" . htmlspecialchars((string)($item['somay'] ?? '')) . "</td>";
                echo "<td>" . htmlspecialchars($ngayYc) . "</td>";
                echo "<td>" . htmlspecialchars($ngayTh) . "</td>";
                echo "<td>" . htmlspecialchars($ngayKt) . "</td>";
                echo "<td>" . htmlspecialchars((string)($item['tendv'] ?? $item['madv'] ?? '')) . "</td>";
                echo "<td>" . htmlspecialchars((string)($item['cv'] ?? '')) . "</td>";
                echo "<td>" . htmlspecialchars((string)($item['nhomsc'] ?? '')) . "</td>";
                echo "<td>" . htmlspecialchars($status) . "</td>";
                echo "</tr>";
            }
        }

        echo "</table></body></html>";
        exit;
    }
}
