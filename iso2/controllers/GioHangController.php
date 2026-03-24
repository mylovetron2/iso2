<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/ActivityLogger.php';

/**
 * Controller quản lý Giỏ hàng vật tư thanh lý
 * Cho phép user chọn vật tư tạm, lưu qua nhiều phiên
 */
class GioHangController
{
    private PDO $db;
    private ActivityLogger $logger;
    private int $userId;

    public function __construct()
    {
        $this->db = getDBConnection();
        $this->logger = new ActivityLogger($this->db);
        
        // Lấy user ID từ session
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('Chưa đăng nhập');
        }
        $this->userId = (int)$_SESSION['user_id'];
    }

    /**
     * Hiển thị giỏ hàng của user
     */
    public function index(): void
    {
        try {
            $items = $this->getCartItems();
            $total = count($items);
            
            require_once __DIR__ . '/../views/giohang/index.php';
        } catch (Exception $e) {
            error_log("Error in GioHangController::index: " . $e->getMessage());
            $items = [];
            $total = 0;
            $error = 'Có lỗi xảy ra: ' . $e->getMessage();
            require_once __DIR__ . '/../views/giohang/index.php';
        }
    }

    /**
     * Thêm vật tư vào giỏ hàng
     */
    public function add(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $vattu_stt = isset($_POST['vattu_stt']) ? (int)$_POST['vattu_stt'] : 0;
            $so_luong = isset($_POST['so_luong']) ? (int)$_POST['so_luong'] : 1;
            $ghi_chu = $_POST['ghi_chu'] ?? null;

            if ($vattu_stt <= 0) {
                throw new Exception('Vật tư không hợp lệ');
            }

            if ($so_luong <= 0) {
                throw new Exception('Số lượng phải lớn hơn 0');
            }

            // Kiểm tra vật tư có tồn tại không
            $stmt = $this->db->prepare("SELECT stt, ten_tiengviet, soluong_conlai FROM vattu_thanh_ly_iso WHERE stt = ?");
            $stmt->execute([$vattu_stt]);
            $vattu = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$vattu) {
                throw new Exception('Vật tư không tồn tại');
            }

            // Kiểm tra xem đã có trong giỏ chưa
            $stmt = $this->db->prepare("SELECT id, so_luong FROM cart_vattu_thanh_ly WHERE user_id = ? AND vattu_stt = ?");
            $stmt->execute([$this->userId, $vattu_stt]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Cập nhật số lượng
                $new_quantity = $existing['so_luong'] + $so_luong;
                $stmt = $this->db->prepare("UPDATE cart_vattu_thanh_ly SET so_luong = ?, ghi_chu = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$new_quantity, $ghi_chu, $existing['id']]);
                
                $this->logger->log(
                    'cart_vattu_thanh_ly',
                    'UPDATE',
                    $existing['id'],
                    ['so_luong' => $existing['so_luong']],
                    ['so_luong' => $new_quantity, 'description' => "Cập nhật số lượng giỏ hàng: {$vattu['ten_tiengviet']} ({$existing['so_luong']} -> {$new_quantity})"]
                );
                
                $message = 'Đã cập nhật số lượng trong giỏ hàng';
            } else {
                // Thêm mới
                $stmt = $this->db->prepare("INSERT INTO cart_vattu_thanh_ly (user_id, vattu_stt, so_luong, ghi_chu) VALUES (?, ?, ?, ?)");
                $stmt->execute([$this->userId, $vattu_stt, $so_luong, $ghi_chu]);
                
                $this->logger->log(
                    'cart_vattu_thanh_ly',
                    'INSERT',
                    (int)$this->db->lastInsertId(),
                    null,
                    ['vattu_stt' => $vattu_stt, 'so_luong' => $so_luong, 'description' => "Thêm vào giỏ hàng: {$vattu['ten_tiengviet']} (SL: {$so_luong})"]
                );
                
                $message = 'Đã thêm vào giỏ hàng';
            }

            // Lấy tổng số items trong giỏ
            $count = $this->getCartCount();

            echo json_encode([
                'success' => true,
                'message' => $message,
                'cart_count' => $count
            ]);

        } catch (Exception $e) {
            error_log("Error in GioHangController::add: " . $e->getMessage());
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Cập nhật số lượng trong giỏ hàng
     */
    public function update(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
            $so_luong = isset($_POST['so_luong']) ? (int)$_POST['so_luong'] : 0;
            $ghi_chu = $_POST['ghi_chu'] ?? null;

            if ($cart_id <= 0) {
                throw new Exception('ID không hợp lệ');
            }

            if ($so_luong <= 0) {
                throw new Exception('Số lượng phải lớn hơn 0');
            }

            // Kiểm tra item có thuộc user không
            $stmt = $this->db->prepare("SELECT id FROM cart_vattu_thanh_ly WHERE id = ? AND user_id = ?");
            $stmt->execute([$cart_id, $this->userId]);
            
            if (!$stmt->fetch()) {
                throw new Exception('Không tìm thấy item trong giỏ hàng');
            }

            // Cập nhật
            $stmt = $this->db->prepare("UPDATE cart_vattu_thanh_ly SET so_luong = ?, ghi_chu = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$so_luong, $ghi_chu, $cart_id]);

            $this->logger->log(
                'cart_vattu_thanh_ly',
                'UPDATE',
                $cart_id,
                ['so_luong' => $item['so_luong']],
                ['so_luong' => $so_luong, 'description' => "Cập nhật giỏ hàng: SL = {$so_luong}"]
            );

            echo json_encode([
                'success' => true,
                'message' => 'Đã cập nhật số lượng'
            ]);

        } catch (Exception $e) {
            error_log("Error in GioHangController::update: " . $e->getMessage());
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Xóa item khỏi giỏ hàng
     */
    public function delete(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;

            if ($cart_id <= 0) {
                throw new Exception('ID không hợp lệ');
            }

            // Kiểm tra item có thuộc user không
            $stmt = $this->db->prepare("SELECT c.id, v.ten_tiengviet FROM cart_vattu_thanh_ly c 
                                        LEFT JOIN vattu_thanh_ly_iso v ON c.vattu_stt = v.stt
                                        WHERE c.id = ? AND c.user_id = ?");
            $stmt->execute([$cart_id, $this->userId]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$item) {
                throw new Exception('Không tìm thấy item trong giỏ hàng');
            }

            // Xóa
            $stmt = $this->db->prepare("DELETE FROM cart_vattu_thanh_ly WHERE id = ?");
            $stmt->execute([$cart_id]);

            $this->logger->log(
                'cart_vattu_thanh_ly',
                'DELETE',
                $cart_id,
                ['vattu_stt' => $item['vattu_stt'], 'so_luong' => $item['so_luong']],
                ['description' => "Xóa khỏi giỏ hàng: {$item['ten_tiengviet']}"]
            );

            // Lấy tổng số items còn lại
            $count = $this->getCartCount();

            echo json_encode([
                'success' => true,
                'message' => 'Đã xóa khỏi giỏ hàng',
                'cart_count' => $count
            ]);

        } catch (Exception $e) {
            error_log("Error in GioHangController::delete: " . $e->getMessage());
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Xóa toàn bộ giỏ hàng
     */
    public function clear(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $stmt = $this->db->prepare("DELETE FROM cart_vattu_thanh_ly WHERE user_id = ?");
            $stmt->execute([$this->userId]);
            
            $deletedCount = $stmt->rowCount();

            $this->logger->log(
                'cart_vattu_thanh_ly',
                'DELETE',
                null,
                null,
                ['description' => "Xóa toàn bộ giỏ hàng ({$deletedCount} items)"]
            );

            echo json_encode([
                'success' => true,
                'message' => 'Đã xóa toàn bộ giỏ hàng',
                'cart_count' => 0
            ]);

        } catch (Exception $e) {
            error_log("Error in GioHangController::clear: " . $e->getMessage());
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Lấy số lượng items trong giỏ (cho badge)
     */
    public function getCount(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $count = $this->getCartCount();
            
            echo json_encode([
                'success' => true,
                'count' => $count
            ]);

        } catch (Exception $e) {
            error_log("Error in GioHangController::getCount: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'count' => 0
            ]);
        }
    }

    /**
     * Helper: Lấy tất cả items trong giỏ hàng
     */
    private function getCartItems(): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                c.id as cart_id,
                c.vattu_stt,
                c.so_luong,
                c.ghi_chu,
                c.ngay_them,
                v.mavattu,
                v.ten_tienganh,
                v.ten_tiengnga,
                v.ten_tiengviet,
                v.dactinhkt_tiengviet,
                v.dvt_tiengviet,
                v.soluong_conlai,
                v.dongia,
                p.ten_phanloai,
                p.mau_sac
            FROM cart_vattu_thanh_ly c
            LEFT JOIN vattu_thanh_ly_iso v ON c.vattu_stt = v.stt
            LEFT JOIN phanloai_vattu_thanh_ly_iso p ON v.phanloai_id = p.id
            WHERE c.user_id = ?
            ORDER BY c.ngay_them DESC
        ");
        $stmt->execute([$this->userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Helper: Đếm số items trong giỏ
     */
    private function getCartCount(): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM cart_vattu_thanh_ly WHERE user_id = ?");
        $stmt->execute([$this->userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Xóa vật tư khỏi giỏ hàng theo vattu_stt (dùng cho checkbox uncheck)
     */
    public function removeByVattu(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $vattu_stt = isset($_POST['vattu_stt']) ? (int)$_POST['vattu_stt'] : 0;

            if ($vattu_stt <= 0) {
                throw new Exception('Vật tư không hợp lệ');
            }

            $stmt = $this->db->prepare("DELETE FROM cart_vattu_thanh_ly WHERE user_id = ? AND vattu_stt = ?");
            $stmt->execute([$this->userId, $vattu_stt]);

            if ($stmt->rowCount() > 0) {
                $this->logger->log(
                    'cart_vattu_thanh_ly',
                    'DELETE',
                    $vattu_stt,
                    null,
                    ['description' => "Xóa vật tư khỏi giỏ hàng: STT {$vattu_stt}"]
                );
            }

            $count = $this->getCartCount();

            echo json_encode([
                'success' => true,
                'message' => 'Đã xóa khỏi giỏ hàng',
                'cart_count' => $count
            ]);

        } catch (Exception $e) {
            error_log("Error in GioHangController::removeByVattu: " . $e->getMessage());
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Cập nhật số lượng vật tư trong giỏ theo vattu_stt (dùng cho quantity input)
     */
    public function updateByVattu(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $vattu_stt = isset($_POST['vattu_stt']) ? (int)$_POST['vattu_stt'] : 0;
            $so_luong = isset($_POST['so_luong']) ? (int)$_POST['so_luong'] : 1;

            if ($vattu_stt <= 0) {
                throw new Exception('Vật tư không hợp lệ');
            }

            if ($so_luong <= 0) {
                throw new Exception('Số lượng phải lớn hơn 0');
            }

            $stmt = $this->db->prepare("
                UPDATE cart_vattu_thanh_ly 
                SET so_luong = ?, updated_at = NOW() 
                WHERE user_id = ? AND vattu_stt = ?
            ");
            $stmt->execute([$so_luong, $this->userId, $vattu_stt]);

            if ($stmt->rowCount() > 0) {
                $this->logger->log(
                    'cart_vattu_thanh_ly',
                    'UPDATE',
                    $vattu_stt,
                    null,
                    ['so_luong' => $so_luong, 'description' => "Cập nhật số lượng vật tư STT {$vattu_stt}: {$so_luong}"]
                );
            }

            echo json_encode([
                'success' => true,
                'message' => 'Đã cập nhật số lượng'
            ]);

        } catch (Exception $e) {
            error_log("Error in GioHangController::updateByVattu: " . $e->getMessage());
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
