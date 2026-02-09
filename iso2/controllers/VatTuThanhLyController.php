<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/VatTuThanhLy.php';

class VatTuThanhLyController
{
    private VatTuThanhLy $model;

    public function __construct()
    {
        $this->model = new VatTuThanhLy();
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
                header('Location: vattuthanhly.php?success=created');
                exit;
            } else {
                header('Location: vattuthanhly.php?error=create_failed');
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
                header('Location: vattuthanhly.php?error=missing_id');
                exit;
            }
            
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
                
                if ($this->model->update((int)$id, $data)) {
                    header('Location: vattuthanhly.php?success=updated');
                    exit;
                } else {
                    header('Location: vattuthanhly.php?error=update_failed');
                    exit;
                }
            }
            
            $item = $this->model->findById((int)$id);
            if (!$item) {
                header('Location: vattuthanhly.php?error=not_found');
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
            header('Location: vattuthanhly.php');
            exit;
        }
        
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            header('Location: vattuthanhly.php?error=missing_id');
            exit;
        }
        
        if ($this->model->delete((int)$id)) {
            header('Location: vattuthanhly.php?success=deleted');
        } else {
            header('Location: vattuthanhly.php?error=delete_failed');
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
        
        $vattu_stt = $_POST['vattu_stt'] ?? null;
        $soluong_thanhly = floatval($_POST['soluong'] ?? 0);
        
        // Kiểm tra số lượng thanh lý có hợp lệ không
        if ($soluong_thanhly <= 0) {
            echo json_encode(['error' => 'Số lượng thanh lý phải lớn hơn 0']);
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
            'nguoisudung' => $_POST['nguoisudung'] ?? null,
            'ngaysd_nhan' => $_POST['ngaysd_nhan'] ?? null,
            'soluong' => $soluong_thanhly,
            'bophan' => $_POST['bophan'] ?? null,
            'mucdich_sudung' => $_POST['mucdich_sudung'] ?? null,
            'trangthai' => $_POST['trangthai'] ?? 'dangdung',
            'ghichu' => $_POST['ghichu'] ?? null,
        ];
        
        if ($this->model->addChiTietSuDung($data)) {
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
        
        // Không cho phép sửa số lượng để tránh inconsistency
        $data = [
            'nguoisudung' => $_POST['nguoisudung'] ?? null,
            'ngaysd_nhan' => $_POST['ngaysd_nhan'] ?? null,
            'bophan' => $_POST['bophan'] ?? null,
            'mucdich_sudung' => $_POST['mucdich_sudung'] ?? null,
            'ghichu' => $_POST['ghichu'] ?? null,
        ];
        
        if ($this->model->updateChiTietSuDung((int)$id, $data)) {
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
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            header('Location: vattuthanhly.php?error=missing_id');
            exit;
        }
        
        // Lấy thông tin vật tư với stats
        $where = "WHERE v.stt = :stt";
        $params = [':stt' => (int)$id];
        $items = $this->model->getAllWithStats($where, $params);
        
        if (empty($items)) {
            header('Location: vattuthanhly.php?error=not_found');
            exit;
        }
        
        $vattu = $items[0];
        
        // Lấy chi tiết sử dụng
        $chiTietList = $this->model->getChiTietSuDung((int)$id);
        
        // Load danh sách đơn vị
        $db = getDBConnection();
        $stmtDonVi = $db->query("SELECT madv, tendv FROM donvi_iso ORDER BY tendv ASC");
        $donViList = $stmtDonVi->fetchAll(PDO::FETCH_ASSOC);
        
        // Render inline HTML (bypass view file)
        $this->renderViewHTML($vattu, $chiTietList, $donViList);
    }
    
    private function renderViewHTML($vattu, $chiTietList, $donViList): void
    {
        ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết vật tư #<?php echo $vattu['stt']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-6 max-w-7xl">
        <!-- Header -->
        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-800">
                    <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                    Chi tiết vật tư #<?php echo $vattu['stt']; ?>
                </h1>
                <div class="space-x-2">
                    <a href="vattuthanhly.php" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                        <i class="fas fa-arrow-left mr-2"></i>Quay lại
                    </a>
                    <?php if (hasPermission('vattu.edit')): ?>
                    <a href="vattuthanhly.php?action=edit&id=<?php echo $vattu['stt']; ?>" class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600">
                        <i class="fas fa-edit mr-2"></i>Sửa
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Info -->
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="bg-blue-600 text-white px-4 py-3">
                        <h2 class="text-xl font-semibold"><i class="fas fa-clipboard-list mr-2"></i>Thông tin cơ bản</h2>
                    </div>
                    <div class="p-4">
                        <table class="w-full text-sm border border-gray-200">
                            <tr class="border-b">
                                <th class="py-2 px-3 text-left bg-gray-50 w-1/3">STT</th>
                                <td class="py-2 px-3"><strong class="text-blue-600">#<?php echo $vattu['stt']; ?></strong></td>
                            </tr>
                            <tr class="border-b">
                                <th class="py-2 px-3 text-left bg-gray-50">Mã vật tư</th>
                                <td class="py-2 px-3">
                                    <code class="<?php echo htmlspecialchars($vattu['phanloai_mau_sac'] ?? 'bg-blue-100 text-blue-800'); ?> px-4 py-2 rounded font-semibold">
                                        <?php echo htmlspecialchars($vattu['mavattu']); ?>
                                    </code>
                                </td>
                            </tr>
                            <?php if (!empty($vattu['so_serial'])): ?>
                            <tr class="border-b">
                                <th class="py-2 px-3 text-left bg-gray-50">Số Serial</th>
                                <td class="py-2 px-3">
                                    <span class="bg-gray-200 px-3 py-1 rounded"><?php echo htmlspecialchars($vattu['so_serial']); ?></span>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <tr class="border-b">
                                <th class="py-2 px-3 text-left bg-gray-50">Phân loại</th>
                                <td class="py-2 px-3">
                                    <?php if (!empty($vattu['ten_phanloai'])): ?>
                                        <span class="<?php echo htmlspecialchars($vattu['phanloai_mau_sac'] ?? 'bg-gray-100 text-gray-800'); ?> px-3 py-1 rounded">
                                            <?php echo htmlspecialchars($vattu['ten_phanloai']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-500">Chưa phân loại</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr class="border-b">
                                <th class="py-2 px-3 text-left bg-gray-50">Tên (Tiếng Việt)</th>
                                <td class="py-2 px-3 text-green-700 font-semibold"><?php echo htmlspecialchars($vattu['ten_tiengviet'] ?? '-'); ?></td>
                            </tr>
                            <tr class="border-b">
                                <th class="py-2 px-3 text-left bg-gray-50">Tên (Tiếng Anh)</th>
                                <td class="py-2 px-3"><?php echo htmlspecialchars($vattu['ten_tienganh'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th class="py-2 px-3 text-left bg-gray-50">Tên (Tiếng Nga)</th>
                                <td class="py-2 px-3 text-blue-600"><?php echo htmlspecialchars($vattu['ten_tiengnga'] ?? '-'); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- History -->
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="bg-green-600 text-white px-4 py-3 flex justify-between items-center">
                        <h2 class="text-xl font-semibold"><i class="fas fa-history mr-2"></i>Lịch sử sử dụng (<?php echo count($chiTietList); ?>)</h2>
                    </div>
                    <div class="p-4">
                        <?php if (empty($chiTietList)): ?>
                            <p class="text-gray-500 text-center py-8">
                                <i class="fas fa-inbox text-4xl mb-2 block text-gray-300"></i>
                                Chưa có lịch sử sử dụng
                            </p>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-100 border-b-2">
                                        <tr>
                                            <th class="px-3 py-2 text-left">Người SD</th>
                                            <th class="px-3 py-2 text-left">Ngày nhận</th>
                                            <th class="px-3 py-2 text-right">SL</th>
                                            <th class="px-3 py-2 text-left">Bộ phận</th>
                                            <th class="px-3 py-2 text-left">Mục đích</th>
                                            <th class="px-3 py-2 text-center">Trạng thái</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        <?php foreach ($chiTietList as $ct): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2"><?php echo htmlspecialchars($ct['nguoisudung'] ?? '-'); ?></td>
                                            <td class="px-3 py-2"><?php echo $ct['ngaysd_nhan'] ? date('d/m/Y', strtotime($ct['ngaysd_nhan'])) : '-'; ?></td>
                                            <td class="px-3 py-2 text-right font-semibold"><?php echo number_format($ct['soluong'] ?? 0, 0); ?></td>
                                            <td class="px-3 py-2"><?php echo htmlspecialchars($ct['bophan'] ?? '-'); ?></td>
                                            <td class="px-3 py-2"><?php echo htmlspecialchars($ct['mucdich_sudung'] ?? '-'); ?></td>
                                            <td class="px-3 py-2 text-center">
                                                <?php if ($ct['trangthai'] === 'dangdung'): ?>
                                                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">Đang dùng</span>
                                                <?php elseif ($ct['trangthai'] === 'datra'): ?>
                                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">Đã trả</span>
                                                <?php else: ?>
                                                    <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs"><?php echo htmlspecialchars($ct['trangthai']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Quantity & Price -->
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="bg-cyan-600 text-white px-4 py-3">
                        <h2 class="text-lg font-semibold"><i class="fas fa-calculator mr-2"></i>Số lượng & Giá trị</h2>
                    </div>
                    <div class="p-4">
                        <table class="w-full text-sm">
                            <tr class="border-b">
                                <th class="py-2 text-left text-gray-700">ĐVT</th>
                                <td class="py-2 text-right font-semibold">
                                    <?php echo htmlspecialchars($vattu['dvt_tiengviet'] ?? $vattu['dvt_tiengnga'] ?? '-'); ?>
                                </td>
                            </tr>
                            <tr class="border-b">
                                <th class="py-2 text-left text-gray-700">SL còn lại</th>
                                <td class="py-2 text-right">
                                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded font-bold text-lg">
                                        <?php echo number_format($vattu['soluong_conlai'] ?? 0, 0); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr class="border-b">
                                <th class="py-2 text-left text-gray-700">Đơn giá</th>
                                <td class="py-2 text-right">
                                    <?php echo $vattu['dongia'] ? number_format($vattu['dongia'], 0) . ' đ' : '-'; ?>
                                </td>
                            </tr>
                            <tr class="border-b">
                                <th class="py-2 text-left text-gray-700">Tổng giá trị</th>
                                <td class="py-2 text-right">
                                    <strong class="text-blue-600 text-lg">
                                        <?php echo number_format($vattu['tong_tien'] ?? 0, 0); ?> đ
                                    </strong>
                                </td>
                            </tr>
                            <tr class="border-b">
                                <th class="py-2 text-left text-gray-700">SL đang dùng</th>
                                <td class="py-2 text-right"><?php echo number_format($vattu['soluong_dangdung'] ?? 0, 0); ?></td>
                            </tr>
                            <tr>
                                <th class="py-2 text-left text-gray-700">Số lần TL</th>
                                <td class="py-2 text-right">
                                    <span class="px-2 py-1 <?php echo ($vattu['so_lan_sudung'] ?? 0) > 0 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?> rounded">
                                        <?php echo $vattu['so_lan_sudung'] ?? 0; ?> lần
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Contract Info -->
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="bg-yellow-500 text-white px-4 py-3">
                        <h2 class="text-lg font-semibold"><i class="fas fa-file-contract mr-2"></i>Hợp đồng & Quản lý</h2>
                    </div>
                    <div class="p-4">
                        <table class="w-full text-sm">
                            <tr class="border-b">
                                <th class="py-2 text-left text-gray-700">Ngày nhận</th>
                                <td class="py-2 text-right"><?php echo $vattu['ngaynhan'] ? date('d/m/Y', strtotime($vattu['ngaynhan'])) : '-'; ?></td>
                            </tr>
                            <tr class="border-b">
                                <th class="py-2 text-left text-gray-700">Số HĐ</th>
                                <td class="py-2 text-right"><?php echo htmlspecialchars($vattu['sohd'] ?? '-'); ?></td>
                            </tr>
                            <tr class="border-b">
                                <th class="py-2 text-left text-gray-700">Ngày ký HĐ</th>
                                <td class="py-2 text-right"><?php echo !empty($vattu['ngaykyhd']) ? date('d/m/Y', strtotime($vattu['ngaykyhd'])) : '-'; ?></td>
                            </tr>
                            <tr class="border-b">
                                <th class="py-2 text-left text-gray-700">Người QL</th>
                                <td class="py-2 text-right"><?php echo htmlspecialchars($vattu['nguoiquanly'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th class="py-2 text-left text-gray-700">Vị trí kho</th>
                                <td class="py-2 text-right"><?php echo htmlspecialchars($vattu['vitri_luukho'] ?? '-'); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <?php if (!empty($vattu['ghichu'])): ?>
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="bg-gray-600 text-white px-4 py-3">
                        <h2 class="text-lg font-semibold"><i class="fas fa-sticky-note mr-2"></i>Ghi chú</h2>
                    </div>
                    <div class="p-4">
                        <p class="text-gray-700 whitespace-pre-wrap"><?php echo htmlspecialchars($vattu['ghichu']); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
        <?php
    }
}
