<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/VatTuThanhLy.php';
require_once __DIR__ . '/../includes/ActivityLogger.php';

class VatTuThanhLyController
{
    private VatTuThanhLy $model;
    private ActivityLogger $logger;

    public function __construct()
    {
        $this->model = new VatTuThanhLy();
        $db = getDBConnection();
        $this->logger = new ActivityLogger($db);
    }

    public function index(): void
    {
        try {
            $search = $_GET['search'] ?? '';
            $phanloai_id = !empty($_GET['phanloai_id']) ? (int)$_GET['phanloai_id'] : null;
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = 20;
            $offset = ($page - 1) * $limit;

            $conditions = [];
            $params = [];

            if ($search) {
                // Tạo 2 biến thể: lowercase và capitalized (chữ đầu viết hoa)
                $searchLower = mb_strtolower($search, 'UTF-8');
                $searchCap = mb_strtoupper(mb_substr($search, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($searchLower, 1);
                
                $conditions[] = "(
                    v.mavattu LIKE :search1a OR v.mavattu LIKE :search1b OR
                    v.ten_tienganh LIKE :search2a OR v.ten_tienganh LIKE :search2b OR
                    v.ten_tiengnga LIKE :search3a OR v.ten_tiengnga LIKE :search3b OR
                    v.ten_tiengviet LIKE :search4a OR v.ten_tiengviet LIKE :search4b OR
                    v.nguoiquanly LIKE :search5a OR v.nguoiquanly LIKE :search5b
                )";
                
                $params[':search1a'] = "%$searchLower%";
                $params[':search1b'] = "%$searchCap%";
                $params[':search2a'] = "%$searchLower%";
                $params[':search2b'] = "%$searchCap%";
                $params[':search3a'] = "%$searchLower%";
                $params[':search3b'] = "%$searchCap%";
                $params[':search4a'] = "%$searchLower%";
                $params[':search4b'] = "%$searchCap%";
                $params[':search5a'] = "%$searchLower%";
                $params[':search5b'] = "%$searchCap%";
            }
            
            if ($phanloai_id) {
                $conditions[] = "v.phanloai_id = :phanloai_id";
                $params[':phanloai_id'] = $phanloai_id;
            }

            $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            $items = $this->model->getAllWithStats($where, $params, $limit, $offset);
            $total = $this->model->count($where, $params);
            $totalPages = ceil($total / $limit);
            
            // Load danh sách đơn vị
            $db = getDBConnection();
            $stmtDonVi = $db->query("SELECT madv, tendv FROM donvi_iso ORDER BY tendv ASC");
            $donViList = $stmtDonVi->fetchAll(PDO::FETCH_ASSOC);
            
            // Load danh sách phân loại
            $stmtPhanLoai = $db->query("SELECT id, ma_phanloai, ten_phanloai, mau_sac FROM phanloai_vattu_thanh_ly_iso ORDER BY thu_tu ASC");
            $phanLoaiList = $stmtPhanLoai->fetchAll(PDO::FETCH_ASSOC);

            require_once __DIR__ . '/../views/vattuthanhly/index.php';
        } catch (Exception $e) {
            error_log("Error in VatTuThanhLyController::index: " . $e->getMessage());
            
            $items = [];
            $total = 0;
            $page = 1;
            $totalPages = 0;
            $donViList = [];
            $phanLoaiList = [];
            $error = 'Có lỗi xảy ra: ' . $e->getMessage();
            
            require_once __DIR__ . '/../views/vattuthanhly/index.php';
        }
    }
    
    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Xử lý dữ liệu, convert empty string thành NULL cho các trường số
            $data = [
                'mavattu' => $_POST['mavattu'] ?? null,
                'so_serial' => $_POST['so_serial'] ?? null,
                'phanloai_id' => !empty($_POST['phanloai_id']) ? (int)$_POST['phanloai_id'] : 1,
                'vi_tri_sap_xep' => !empty($_POST['vi_tri_sap_xep']) ? (int)$_POST['vi_tri_sap_xep'] : 999,
                'ten_tienganh' => $_POST['ten_tienganh'] ?? null,
                'ten_tiengnga' => $_POST['ten_tiengnga'] ?? null,
                'ten_tiengviet' => $_POST['ten_tiengviet'] ?? null,
                'dactinhkt_tiengnga' => $_POST['dactinhkt_tiengnga'] ?? null,
                'dactinhkt_tiengviet' => $_POST['dactinhkt_tiengviet'] ?? null,
                'dvt_tiengnga' => $_POST['dvt_tiengnga'] ?? null,
                'dvt_tiengviet' => $_POST['dvt_tiengviet'] ?? null,
                'soluong_conlai' => !empty($_POST['soluong_conlai']) ? $_POST['soluong_conlai'] : null,
                'dongia' => !empty($_POST['dongia']) ? $_POST['dongia'] : null,
                'ngaynhan' => !empty($_POST['ngaynhan']) ? $_POST['ngaynhan'] : null,
                'sohd' => $_POST['sohd'] ?? null,
                'ngaykyhd' => !empty($_POST['ngaykyhd']) ? $_POST['ngaykyhd'] : null,
                'nguoiquanly' => $_POST['nguoiquanly'] ?? null,
                'vitribaoquan' => $_POST['vitribaoquan'] ?? null,
                'ghichu' => $_POST['ghichu'] ?? null,
            ];
            
            if ($this->model->create($data)) {
                $db = getDBConnection();
                $insertId = (int)$db->lastInsertId();
                
                // Log vật tư creation
                $this->logger->log(
                    'vattu_thanh_ly_iso',
                    'INSERT',
                    $insertId,
                    null,
                    [
                        'mavattu' => $data['mavattu'],
                        'ten_tiengviet' => $data['ten_tiengviet'],
                        'soluong_conlai' => $data['soluong_conlai'],
                        'phanloai_id' => $data['phanloai_id']
                    ]
                );
                
                header('Location: /iso2/vattuthanhly.php?success=created');
                exit;
            } else {
                header('Location: /iso2/vattuthanhly.php?error=create_failed');
                exit;
            }
        }
        
        // Load danh sách phân loại
        $db = getDBConnection();
        $stmtPhanLoai = $db->query("SELECT id, ma_phanloai, ten_phanloai FROM phanloai_vattu_thanh_ly_iso ORDER BY thu_tu ASC");
        $phanLoaiList = $stmtPhanLoai->fetchAll(PDO::FETCH_ASSOC);
        
        require_once __DIR__ . '/../views/vattuthanhly/create.php';
    }
    
    public function edit(): void
    {
        try {
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                header('Location: /iso2/vattuthanhly.php?error=missing_id');
                exit;
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Lấy dữ liệu cũ trước khi update
                $oldData = $this->model->findById((int)$id);
                
                // Xử lý dữ liệu, convert empty string thành NULL cho các trường số
                $data = [
                    'mavattu' => $_POST['mavattu'] ?? null,
                    'so_serial' => $_POST['so_serial'] ?? null,
                    'phanloai_id' => !empty($_POST['phanloai_id']) ? (int)$_POST['phanloai_id'] : 1,
                    'vi_tri_sap_xep' => !empty($_POST['vi_tri_sap_xep']) ? (int)$_POST['vi_tri_sap_xep'] : 999,
                    'ten_tienganh' => $_POST['ten_tienganh'] ?? null,
                    'ten_tiengnga' => $_POST['ten_tiengnga'] ?? null,
                    'ten_tiengviet' => $_POST['ten_tiengviet'] ?? null,
                    'dactinhkt_tiengnga' => $_POST['dactinhkt_tiengnga'] ?? null,
                    'dactinhkt_tiengviet' => $_POST['dactinhkt_tiengviet'] ?? null,
                    'dvt_tiengnga' => $_POST['dvt_tiengnga'] ?? null,
                    'dvt_tiengviet' => $_POST['dvt_tiengviet'] ?? null,
                    'soluong_conlai' => !empty($_POST['soluong_conlai']) ? $_POST['soluong_conlai'] : null,
                    'dongia' => !empty($_POST['dongia']) ? $_POST['dongia'] : null,
                    'ngaynhan' => !empty($_POST['ngaynhan']) ? $_POST['ngaynhan'] : null,
                    'sohd' => $_POST['sohd'] ?? null,
                    'ngaykyhd' => !empty($_POST['ngaykyhd']) ? $_POST['ngaykyhd'] : null,
                    'nguoiquanly' => $_POST['nguoiquanly'] ?? null,
                    'vitribaoquan' => $_POST['vitribaoquan'] ?? null,
                    'ghichu' => $_POST['ghichu'] ?? null,
                ];
                
                if ($this->model->update((int)$id, $data)) {
                    // Log vật tư update
                    $this->logger->log(
                        'vattu_thanh_ly_iso',
                        'UPDATE',
                        (int)$id,
                        [
                            'mavattu' => $oldData['mavattu'] ?? null,
                            'ten_tiengviet' => $oldData['ten_tiengviet'] ?? null,
                            'soluong_conlai' => $oldData['soluong_conlai'] ?? null
                        ],
                        [
                            'mavattu' => $data['mavattu'],
                            'ten_tiengviet' => $data['ten_tiengviet'],
                            'soluong_conlai' => $data['soluong_conlai']
                        ]
                    );
                    
                    header('Location: /iso2/vattuthanhly.php?success=updated');
                    exit;
                } else {
                    header('Location: /iso2/vattuthanhly.php?error=update_failed');
                    exit;
                }
            }
            
            $item = $this->model->findById((int)$id);
            if (!$item) {
                header('Location: /iso2/vattuthanhly.php?error=not_found');
                exit;
            }
            
            // Load danh sách phân loại
            $db = getDBConnection();
            $stmtPhanLoai = $db->query("SELECT id, ma_phanloai, ten_phanloai FROM phanloai_vattu_thanh_ly_iso ORDER BY thu_tu ASC");
            $phanLoaiList = $stmtPhanLoai->fetchAll(PDO::FETCH_ASSOC);
            
            require_once __DIR__ . '/../views/vattuthanhly/edit.php';
        } catch (Exception $e) {
            error_log("Error in VatTuThanhLyController::edit: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            $error = 'Có lỗi xảy ra: ' . $e->getMessage();
            $item = [];
            
            require_once __DIR__ . '/../views/vattuthanhly/edit.php';
        }
    }
    
    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /iso2/vattuthanhly.php');
            exit;
        }
        
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            header('Location: /iso2/vattuthanhly.php?error=missing_id');
            exit;
        }
        
        // Lấy dữ liệu cũ trước khi xóa
        $oldData = $this->model->findById((int)$id);
        
        if ($this->model->delete((int)$id)) {
            // Log vật tư deletion
            if ($oldData) {
                $this->logger->log(
                    'vattu_thanh_ly_iso',
                    'DELETE',
                    (int)$id,
                    [
                        'mavattu' => $oldData['mavattu'] ?? null,
                        'ten_tiengviet' => $oldData['ten_tiengviet'] ?? null,
                        'soluong_conlai' => $oldData['soluong_conlai'] ?? null
                    ],
                    null
                );
            }
            
            header('Location: /iso2/vattuthanhly.php?success=deleted');
        } else {
            header('Location: /iso2/vattuthanhly.php?error=delete_failed');
        }
        exit;
    }
    
    /**
     * API: Lấy chi tiết sử dụng của vật tư
     */
    public function getChiTiet(): void
    {
        header('Content-Type: application/json');
        
        $vattuStt = $_GET['vattu_stt'] ?? null;
        
        if (!$vattuStt) {
            echo json_encode(['error' => 'Missing vattu_stt']);
            exit;
        }
        
        $chiTiet = $this->model->getChiTietSuDung((int)$vattuStt);
        echo json_encode(['success' => true, 'data' => $chiTiet]);
        exit;
    }
    
    /**
     * API: Thêm chi tiết sử dụng
     */
    public function addChiTiet(): void
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Invalid request method']);
            exit;
        }
        
        // Đọc dữ liệu JSON từ request body
        $input = file_get_contents('php://input');
        $jsonData = json_decode($input, true);
        
        // Fallback sang $_POST nếu không phải JSON
        $data_source = $jsonData ?? $_POST;
        
        $vattu_stt = $data_source['vattu_stt'] ?? null;
        $soluong_thanhly = floatval($data_source['soluong'] ?? 0);
        
        // Kiểm tra số lượng thanh lý có hợp lệ không
        if ($soluong_thanhly <= 0) {
            echo json_encode([
                'error' => 'Số lượng thanh lý phải lớn hơn 0',
                'debug_info' => [
                    'soluong_received' => $data_source['soluong'] ?? 'null',
                    'soluong_parsed' => $soluong_thanhly,
                    'data_source_type' => $jsonData ? 'JSON' : 'POST'
                ]
            ]);
            exit;
        }
        
        // Lấy thông tin vật tư master
        $vattu = $this->model->findById((int)$vattu_stt);
        if (!$vattu) {
            echo json_encode(['error' => 'Không tìm thấy vật tư']);
            exit;
        }
        
        $soluong_conlai = floatval($vattu['soluong_conlai'] ?? 0);
        
        // Kiểm tra số lượng thanh lý không được lớn hơn số lượng còn lại
        if ($soluong_thanhly > $soluong_conlai) {
            echo json_encode([
                'error' => 'Số lượng thanh lý (' . number_format($soluong_thanhly, 2) . 
                          ') không được lớn hơn số lượng còn lại (' . number_format($soluong_conlai, 2) . ')'
            ]);
            exit;
        }
        
        $data = [
            'vattu_stt' => $vattu_stt,
            'nguoisudung' => $data_source['nguoisudung'] ?? null,
            'ngaysd_nhan' => $data_source['ngaysd_nhan'] ?? null,
            'soluong' => $soluong_thanhly,
            'bophan' => $data_source['bophan'] ?? null,
            'mucdich_sudung' => $data_source['mucdich_sudung'] ?? null,
            'trangthai' => $data_source['trangthai'] ?? 'dangdung',
            'ghichu' => $data_source['ghichu'] ?? null,
        ];
        
        if ($this->model->addChiTietSuDung($data)) {
            // Lấy ID của bản ghi vừa insert
            $db = getDBConnection();
            $insertId = (int)$db->lastInsertId();
            
            // Log chi tiết sử dụng creation
            $this->logger->log(
                'vattu_thanh_ly_sudung_iso',
                'INSERT',
                $insertId,
                null,
                [
                    'vattu_stt' => $vattu_stt,
                    'soluong' => $soluong_thanhly,
                    'nguoisudung' => $data['nguoisudung'],
                    'bophan' => $data['bophan'],
                    'mucdich_sudung' => $data['mucdich_sudung']
                ]
            );
            
            // Lấy số lượng còn lại mới sau khi cập nhật
            $vattu_updated = $this->model->findById((int)$vattu_stt);
            
            // Đếm số lần thanh lý
            $chiTietList = $this->model->getChiTietSuDung((int)$vattu_stt);
            $so_lan_sudung = count($chiTietList);
            
            echo json_encode([
                'success' => true,
                'soluong_conlai_moi' => floatval($vattu_updated['soluong_conlai'] ?? 0),
                'so_lan_sudung' => $so_lan_sudung
            ]);
        } else {
            echo json_encode(['error' => 'Failed to add chi tiet']);
        }
        exit;
    }
    
    /**
     * API: Xóa chi tiết sử dụng
     */
    public function deleteChiTiet(): void
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Invalid request method']);
            exit;
        }
        
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            echo json_encode(['error' => 'Missing id']);
            exit;
        }
        
        // Lấy thông tin chi tiết trước khi xóa để lấy vattu_stt
        $detail = $this->model->getChiTietById((int)$id);
        
        if ($this->model->deleteChiTietSuDung((int)$id)) {
            // Log chi tiết sử dụng deletion
            if ($detail) {
                $this->logger->log(
                    'vattu_thanh_ly_sudung_iso',
                    'DELETE',
                    (int)$id,
                    [
                        'vattu_stt' => $detail['vattu_stt'] ?? null,
                        'soluong' => $detail['soluong'] ?? null,
                        'nguoisudung' => $detail['nguoisudung'] ?? null,
                        'bophan' => $detail['bophan'] ?? null
                    ],
                    null
                );
            }
            
            // Lấy số lượng còn lại mới sau khi xóa (đã được cộng lại)
            if ($detail) {
                $vattu_updated = $this->model->findById((int)$detail['vattu_stt']);
                
                // Đếm số lần thanh lý
                $chiTietList = $this->model->getChiTietSuDung((int)$detail['vattu_stt']);
                $so_lan_sudung = count($chiTietList);
                
                echo json_encode([
                    'success' => true,
                    'soluong_conlai_moi' => floatval($vattu_updated['soluong_conlai'] ?? 0),
                    'so_lan_sudung' => $so_lan_sudung
                ]);
            } else {
                echo json_encode(['success' => true]);
            }
        } else {
            echo json_encode(['error' => 'Failed to delete chi tiet']);
        }
        exit;
    }
    
    /**
     * API: Sửa chi tiết sử dụng
     */
    public function editChiTiet(): void
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Invalid request method']);
            exit;
        }
        
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            echo json_encode(['error' => 'Missing id']);
            exit;
        }
        
        // Lấy dữ liệu cũ trước khi update
        $oldDetail = $this->model->getChiTietById((int)$id);
        
        // Không cho phép sửa số lượng để tránh inconsistency
        $data = [
            'nguoisudung' => $_POST['nguoisudung'] ?? null,
            'ngaysd_nhan' => $_POST['ngaysd_nhan'] ?? null,
            'bophan' => $_POST['bophan'] ?? null,
            'mucdich_sudung' => $_POST['mucdich_sudung'] ?? null,
            'ghichu' => $_POST['ghichu'] ?? null,
        ];
        
        if ($this->model->updateChiTietSuDung((int)$id, $data)) {
            // Log chi tiết sử dụng update
            if ($oldDetail) {
                $this->logger->log(
                    'vattu_thanh_ly_sudung_iso',
                    'UPDATE',
                    (int)$id,
                    [
                        'nguoisudung' => $oldDetail['nguoisudung'] ?? null,
                        'bophan' => $oldDetail['bophan'] ?? null,
                        'mucdich_sudung' => $oldDetail['mucdich_sudung'] ?? null
                    ],
                    [
                        'nguoisudung' => $data['nguoisudung'],
                        'bophan' => $data['bophan'],
                        'mucdich_sudung' => $data['mucdich_sudung']
                    ]
                );
            }
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Failed to update chi tiet']);
        }
        exit;
    }
    
    /**
     * Xem chi tiết một vật tư
     */
    public function view(): void
    {
        try {
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                header('Location: /iso2/vattuthanhly.php?error=missing_id');
                exit;
            }
            
            // Lấy thông tin vật tư với stats
            $where = "WHERE v.stt = :stt";
            $params = [':stt' => (int)$id];
            $items = $this->model->getAllWithStats($where, $params);
            
            if (empty($items)) {
                header('Location: /iso2/vattuthanhly.php?error=not_found');
                exit;
            }
            
            $vattu = $items[0];
            
            // Lấy chi tiết sử dụng
            $chiTietList = $this->model->getChiTietSuDung((int)$id);
            
            // Lấy lịch sử thay đổi số lượng
            $lichSuList = $this->model->getLichSuThayDoi((int)$id);
            
            // Load danh sách đơn vị cho dropdown (nếu cần thêm chi tiết)
            $db = getDBConnection();
            $stmtDonVi = $db->query("SELECT madv, tendv FROM donvi_iso ORDER BY tendv ASC");
            $donViList = $stmtDonVi->fetchAll(PDO::FETCH_ASSOC);
            
            require_once __DIR__ . '/../views/vattuthanhly/view.php';
        } catch (Exception $e) {
            error_log("Error in VatTuThanhLyController::view: " . $e->getMessage());
            header('Location: /iso2/vattuthanhly.php?error=view_failed');
            exit;
        }
    }
    
    /**
     * Tạo phiếu đặt hàng (order form)
     */
    public function taophieudathang(): void
    {
        try {
            // Lấy danh sách vật tư đã chọn
            $selectedIds = $_GET['ids'] ?? '';
            $ids = $selectedIds ? array_map('intval', explode(',', $selectedIds)) : [];
            
            // Nếu không có IDs, hiển thị trang chọn
            if (empty($ids)) {
                // Lấy tất cả vật tư để chọn
                $items = $this->model->getAllWithStats('', [], 1000, 0);
                
                // Load danh sách phân loại
                $db = getDBConnection();
                $stmtPhanLoai = $db->query("SELECT id, ma_phanloai, ten_phanloai, mau_sac FROM phanloai_vattu_thanh_ly_iso ORDER BY thu_tu ASC");
                $phanLoaiList = $stmtPhanLoai->fetchAll(PDO::FETCH_ASSOC);
                
                require_once __DIR__ . '/../views/vattuthanhly/chon_dathang.php';
                return;
            }
            
            // Lấy thông tin vật tư đã chọn
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $db = getDBConnection();
            $stmt = $db->prepare("
                SELECT 
                    stt,
                    mavattu,
                    ten_tienganh,
                    ten_tiengnga,
                    ten_tiengviet,
                    dactinhkt_tiengnga,
                    dactinhkt_tiengviet,
                    dvt_tiengnga,
                    dvt_tiengviet,
                    soluong_conlai
                FROM vattu_thanh_ly_iso
                WHERE stt IN ($placeholders)
                ORDER BY FIELD(stt, " . implode(',', $ids) . ")
            ");
            $stmt->execute($ids);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            require_once __DIR__ . '/../views/vattuthanhly/phieu_dathang.php';
        } catch (Exception $e) {
            error_log("Error in VatTuThanhLyController::taophieudathang: " . $e->getMessage());
            header('Location: /iso2/vattuthanhly.php?error=order_failed');
            exit;
        }
    }
    
    /**
     * Xuất phiếu đặt hàng ra Excel
     */
    public function xuatphieudathang(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: /iso2/vattuthanhly.php');
                exit;
            }
            
            // Lấy dữ liệu từ form
            $items = $_POST['items'] ?? [];
            
            if (empty($items)) {
                header('Location: /iso2/vattuthanhly.php?error=no_items');
                exit;
            }
            
            // Tạo file Excel
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="Phieu_Dat_Hang_' . date('Y-m-d_His') . '.xls"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            echo "\xEF\xBB\xBF"; // UTF-8 BOM
            
            ?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" 
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid black; padding: 5px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .center { text-align: center; }
        .header-row th { background-color: #4472C4; color: white; font-weight: bold; }
    </style>
</head>
<body>
    <h2 style="text-align: center;">PHIẾU ĐẶT HÀNG / ORDER FORM / ЗАКАЗ</h2>
    <p style="text-align: center;">Ngày / Date / Дата: <?= date('d/m/Y') ?></p>
    
    <table>
        <thead>
            <tr class="header-row">
                <th rowspan="2" class="center">П/п<br>(Stt)</th>
                <th colspan="3" class="center">Наименование (Tên hàng hóa)</th>
                <th rowspan="2">Тех. Характеристики<br>(Đặc tính kỹ thuật)</th>
                <th rowspan="2" class="center">Ед.изм<br>Đơn vị tính</th>
                <th rowspan="2" class="center">Объем<br>(Số lượng)</th>
                <th rowspan="2">Примечание<br>(Ghi chú)</th>
            </tr>
            <tr class="header-row">
                <th>На Англ. Языке<br>(Tiếng Anh)</th>
                <th>На Русс. языке<br>(Tiếng Nga)</th>
                <th>На Вьетнам. Языке<br>(Tiếng Việt)</th>
            </tr>
            <tr>
                <th class="center">1</th>
                <th class="center">2</th>
                <th class="center">3</th>
                <th class="center">4</th>
                <th class="center">5</th>
                <th class="center">6</th>
                <th class="center">7</th>
                <th class="center">8</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $stt = 1;
            foreach ($items as $item): 
                $dactinhkt = !empty($item['dactinhkt_tiengnga']) || !empty($item['dactinhkt_tiengviet']) 
                    ? 'Xem YCKT/ Просмотр TT' 
                    : '';
            ?>
            <tr>
                <td class="center"><?= $stt++ ?></td>
                <td><?= htmlspecialchars($item['ten_tienganh'] ?? '') ?></td>
                <td><?= htmlspecialchars($item['ten_tiengnga'] ?? '') ?></td>
                <td><?= htmlspecialchars($item['ten_tiengviet'] ?? '') ?></td>
                <td><?= htmlspecialchars($dactinhkt) ?></td>
                <td class="center"><?= htmlspecialchars($item['dvt_tiengviet'] ?? $item['dvt_tiengnga'] ?? '') ?></td>
                <td class="center"><?= htmlspecialchars($item['soluong'] ?? '') ?></td>
                <td><?= htmlspecialchars($item['ghichu'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <br><br>
    <table style="border: none;">
        <tr style="border: none;">
            <td style="border: none; width: 50%;">
                <strong>Người lập phiếu / Prepared by / Подготовлено:</strong><br><br>
                _______________________________
            </td>
            <td style="border: none; width: 50%;">
                <strong>Phê duyệt / Approved by / Утверждено:</strong><br><br>
                _______________________________
            </td>
        </tr>
    </table>
</body>
</html>
            <?php
            exit;
        } catch (Exception $e) {
            error_log("Error in VatTuThanhLyController::xuatphieudathang: " . $e->getMessage());
            header('Location: /iso2/vattuthanhly.php?error=export_failed');
            exit;
        }
    }
}
