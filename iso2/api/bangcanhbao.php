<?php
declare(strict_types=1);

header('Content-Type: application/json');

// Session and Authentication
session_start();
require_once __DIR__ . '/../includes/auth.php';

// Kiểm tra đăng nhập cho tất cả API
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized - Vui lòng đăng nhập'
    ]);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ThietBiHCKD.php';
require_once __DIR__ . '/../models/HoSoHCKD.php';

try {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_thietbi_info':
            // Lấy thông tin thiết bị theo mã vật tư
            $mavattu = $_GET['mavattu'] ?? '';
            if (empty($mavattu)) {
                throw new Exception('Mã vật tư không được để trống');
            }
            
            $model = new ThietBiHCKD();
            $thietbi = $model->getByMaVatTu($mavattu);
            
            if (!$thietbi) {
                throw new Exception('Không tìm thấy thiết bị');
            }
            
            echo json_encode([
                'success' => true,
                'data' => $thietbi
            ]);
            break;
            
        case 'get_danchuan_list':
            // Lấy danh sách thiết bị dẫn chuẩn
            $model = new ThietBiHCKD();
            $list = $model->getDanhChuan();
            
            echo json_encode([
                'success' => true,
                'data' => $list
            ]);
            break;
            
        case 'get_hoso_latest':
            // Lấy hồ sơ mới nhất của thiết bị
            $mavattu = $_GET['mavattu'] ?? '';
            if (empty($mavattu)) {
                throw new Exception('Mã vật tư không được để trống');
            }
            
            $model = new HoSoHCKD();
            $hoso = $model->getLatestByDevice($mavattu);
            
            echo json_encode([
                'success' => true,
                'data' => $hoso
            ]);
            break;
            
        case 'generate_sohs':
            // Tạo số hồ sơ tự động
            $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
            $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
            
            $model = new HoSoHCKD();
            $sohs = $model->generateSoHS($month, $year);
            
            echo json_encode([
                'success' => true,
                'sohs' => $sohs
            ]);
            break;
            
        case 'check_duplicate':
            // Kiểm tra hồ sơ trùng lặp
            $mavattu = $_POST['mavattu'] ?? '';
            $ngayhc = $_POST['ngayhc'] ?? '';
            
            if (empty($mavattu) || empty($ngayhc)) {
                throw new Exception('Thiếu thông tin');
            }
            
            $model = new HoSoHCKD();
            $existing = $model->getByDeviceAndDate($mavattu, $ngayhc);
            
            echo json_encode([
                'success' => true,
                'exists' => !empty($existing),
                'data' => $existing
            ]);
            break;
            
        default:
            throw new Exception('Action không hợp lệ');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
