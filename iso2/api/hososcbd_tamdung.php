<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/HoSoScBdTamDung.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$model = new HoSoScBdTamDung();

try {
    switch ($action) {
        case 'tam_dung':
            // Tạm dừng hồ sơ
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed');
            }

            if (!hasPermission('hososcbd.edit')) {
                throw new Exception('Không có quyền thực hiện');
            }

            $hoso = trim($_POST['hoso'] ?? '');
            $lydoTamDung = trim($_POST['lydo_tamdung'] ?? '');

            if (empty($hoso)) {
                throw new Exception('Mã hồ sơ không được để trống');
            }

            if (empty($lydoTamDung)) {
                throw new Exception('Lý do tạm dừng là bắt buộc');
            }

            $nguoiThucHien = $_SESSION['user']['username'] ?? 'Unknown';
            $result = $model->tamDungHoSo($hoso, $nguoiThucHien, $lydoTamDung);

            // Kiểm tra result - có thể là int (success) hoặc array (error)
            if (is_array($result)) {
                // Trả về error message cụ thể
                throw new Exception($result['message'] ?? 'Không thể tạm dừng hồ sơ');
            }
            
            if (!$result) {
                throw new Exception('Không thể tạm dừng hồ sơ. Vui lòng thử lại.');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Tạm dừng hồ sơ thành công',
                'id' => $result
            ]);
            break;

        case 'tiep_tuc':
            // Tiếp tục hồ sơ đã tạm dừng
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed');
            }

            if (!hasPermission('hososcbd.edit')) {
                throw new Exception('Không có quyền thực hiện');
            }

            $hoso = trim($_POST['hoso'] ?? '');
            $ghichuTiepTuc = trim($_POST['ghichu_tieptuc'] ?? '');

            if (empty($hoso)) {
                throw new Exception('Mã hồ sơ không được để trống');
            }

            $nguoiThucHien = $_SESSION['user']['username'] ?? 'Unknown';
            $result = $model->tiepTucHoSo($hoso, $nguoiThucHien, $ghichuTiepTuc);

            // Kiểm tra result - có thể là int (success) hoặc array (error)
            if (is_array($result)) {
                // Trả về error message cụ thể
                throw new Exception($result['message'] ?? 'Không thể tiếp tục hồ sơ');
            }
            
            if (!$result) {
                throw new Exception('Không thể tiếp tục hồ sơ. Vui lòng thử lại.');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Tiếp tục hồ sơ thành công',
                'id' => $result
            ]);
            break;

        case 'check_status':
            // Kiểm tra trạng thái tạm dừng
            $hoso = trim($_GET['hoso'] ?? '');

            if (empty($hoso)) {
                throw new Exception('Mã hồ sơ không được để trống');
            }

            $isTamDung = $model->isTamDung($hoso);
            $info = $isTamDung ? $model->getTamDungInfo($hoso) : null;

            echo json_encode([
                'success' => true,
                'is_tamdung' => $isTamDung,
                'info' => $info
            ]);
            break;

        case 'lich_su':
            // Lấy lịch sử tạm dừng/tiếp tục của hồ sơ
            $hoso = trim($_GET['hoso'] ?? '');

            if (empty($hoso)) {
                throw new Exception('Mã hồ sơ không được để trống');
            }

            $lichSu = $model->getLichSu($hoso);

            echo json_encode([
                'success' => true,
                'data' => $lichSu
            ]);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
