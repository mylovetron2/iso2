<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/ActivityLogger.php';

/**
 * Controller quản lý Phiếu đặt hàng vật tư
 * Workflow: Tạo -> Duyệt -> Nhận hàng -> Nhập kho
 */
class PhieuDatHangController
{
    private PDO $db;
    private ActivityLogger $logger;
    private int $userId;

    public function __construct()
    {
        $this->db = getDBConnection();
        $this->logger = new ActivityLogger($this->db);
        
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('Chưa đăng nhập');
        }
        $this->userId = (int)$_SESSION['user_id'];
    }

    /**
     * Danh sách phiếu đặt hàng
     */
    public function index(): void
    {
        try {
            $search = $_GET['search'] ?? '';
            $trang_thai = $_GET['trang_thai'] ?? '';
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = 20;
            $offset = ($page - 1) * $limit;

            $conditions = [];
            $params = [];

            if ($search) {
                $conditions[] = "(p.ma_phieu LIKE :search OR p.nha_cung_cap LIKE :search OR u.username LIKE :search)";
                $params[':search'] = "%$search%";
            }

            if ($trang_thai) {
                $conditions[] = "p.trang_thai = :trang_thai";
                $params[':trang_thai'] = $trang_thai;
            }

            $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

            // Lấy danh sách phiếu
            $sql = "SELECT 
                        p.*,
                        u.username as ten_nguoi_lap,
                        ud.username as ten_nguoi_duyet,
                        COUNT(ct.id) as so_item,
                        SUM(ct.so_luong_dat) as tong_sl_dat,
                        SUM(ct.so_luong_nhan) as tong_sl_nhan
                    FROM phieu_dat_hang p
                    LEFT JOIN users u ON p.nguoi_lap = u.stt
                    LEFT JOIN users ud ON p.nguoi_duyet = ud.stt
                    LEFT JOIN phieu_dat_hang_chi_tiet ct ON p.id = ct.phieu_id
                    $where
                    GROUP BY p.id
                    ORDER BY p.ngay_lap DESC
                    LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Đếm tổng
            $countSql = "SELECT COUNT(DISTINCT p.id) FROM phieu_dat_hang p 
                         LEFT JOIN users u ON p.nguoi_lap = u.stt
                         $where";
            $countStmt = $this->db->prepare($countSql);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $total = (int)$countStmt->fetchColumn();
            $totalPages = ceil($total / $limit);

            require_once __DIR__ . '/../views/phieudathang/index.php';

        } catch (Exception $e) {
            error_log("Error in PhieuDatHangController::index: " . $e->getMessage());
            $items = [];
            $total = 0;
            $page = 1;
            $totalPages = 0;
            $error = 'Có lỗi xảy ra: ' . $e->getMessage();
            require_once __DIR__ . '/../views/phieudathang/index.php';
        }
    }

    /**
     * Tạo phiếu đặt hàng - Multi-step form
     * Step 1: Chọn vật tư từ danh sách (checkbox + auto-add vào giỏ)
     * Step 2: Nhập thông tin NCC và submit
     */
    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Hiển thị form
            try {
                $step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

                if ($step === 1) {
                    // BƯỚC 1: Hiển thị danh sách vật tư để chọn
                    $search = $_GET['search'] ?? '';
                    $phanloai_id = isset($_GET['phanloai_id']) ? (int)$_GET['phanloai_id'] : 0;
                    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                    $limit = 20;
                    $offset = ($page - 1) * $limit;

                    // Lấy danh sách vật tư
                    $result = $this->getAllVatTu($search, $phanloai_id, $limit, $offset);
                    $vattuList = $result['items'];
                    $total = $result['total'];
                    $totalPages = ceil($total / $limit);

                    // Lấy danh sách phân loại cho filter
                    $phanLoaiList = $this->getPhanLoaiList();

                    // Lấy số lượng items trong giỏ
                    $cartCount = $this->getCartCount();

                    require_once __DIR__ . '/../views/phieudathang/create.php';

                } else {
                    // BƯỚC 2: Hiển thị form nhập thông tin NCC
                    // Lấy items từ giỏ hàng
                    $cartItems = $this->getCartItemsForOrder();
                    
                    // Nếu giỏ hàng trống → redirect về step 1
                    if (empty($cartItems)) {
                        $_SESSION['error'] = 'Giỏ hàng trống. Vui lòng chọn vật tư trước.';
                        header('Location: phieudathang.php?action=create&step=1');
                        exit;
                    }

                    require_once __DIR__ . '/../views/phieudathang/create.php';
                }

            } catch (Exception $e) {
                error_log("Error in PhieuDatHangController::create (GET): " . $e->getMessage());
                $_SESSION['error'] = $e->getMessage();
                header('Location: vattuthanhly.php');
                exit;
            }

        } else {
            // Xử lý POST không còn dùng ở đây - Chuyển sang store()
            $_SESSION['error'] = 'Method not allowed';
            header('Location: phieudathang.php?action=create&step=1');
            exit;
        }
    }

    /**
     * Lưu phiếu đặt hàng (từ form step 2)
     */
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Method not allowed';
            header('Location: phieudathang.php?action=create&step=1');
            exit;
        }

        try {
            // Lấy items từ giỏ hàng
            $cartItems = $this->getCartItemsForOrder();
            
            if (empty($cartItems)) {
                throw new Exception('Giỏ hàng trống. Vui lòng chọn vật tư trước.');
            }

            $this->db->beginTransaction();

            // Tạo mã phiếu
            $ma_phieu = $this->generateMaPhieu();

            // Insert header phiếu (tự động duyệt luôn, bỏ qua draft)
            $stmt = $this->db->prepare("
                INSERT INTO phieu_dat_hang (
                    ma_phieu, nguoi_lap, trang_thai, ghi_chu, 
                    nha_cung_cap, so_hd_ncc, ngay_du_kien_nhan,
                    nguoi_duyet, ngay_duyet
                ) VALUES (?, ?, 'ordered', ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $ma_phieu,
                $this->userId,
                $_POST['ghi_chu'] ?? null,
                $_POST['nha_cung_cap'] ?? null,
                $_POST['so_hd_ncc'] ?? null,
                $_POST['ngay_du_kien_nhan'] ?? date('Y-m-d', strtotime('+7 days')),
                $this->userId  // Người tạo cũng là người duyệt
            ]);

            $phieu_id = (int)$this->db->lastInsertId();

            // Insert chi tiết từ giỏ hàng
            foreach ($cartItems as $item) {
                $thanh_tien = $item['so_luong'] * $item['dongia'];
                
                $stmt = $this->db->prepare("
                    INSERT INTO phieu_dat_hang_chi_tiet (
                        phieu_id, vattu_stt, ten_tieng_anh, ten_tieng_nga, ten_tieng_viet,
                        dac_tinh_ky_thuat, don_vi, so_luong_dat, so_luong_nhan, 
                        don_gia, thanh_tien, ghi_chu
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?)
                ");
                $stmt->execute([
                    $phieu_id,
                    $item['stt'],
                    $item['ten_tienganh'],
                    $item['ten_tiengnga'],
                    $item['ten_tiengviet'],
                    $item['dactinhkt_tiengviet'],
                    $item['dvt_tiengviet'],
                    $item['so_luong'], // Từ giỏ hàng
                    $item['dongia'],
                    $thanh_tien,
                    $item['ghi_chu']
                ]);
            }

            // ✅ LUÔN XÓA GIỎ HÀNG SAU KHI TẠO PHIẾU THÀNH CÔNG
            $stmtClear = $this->db->prepare("DELETE FROM cart_vattu_thanh_ly WHERE user_id = ?");
            $stmtClear->execute([$this->userId]);

            $this->db->commit();

            $this->logger->log(
                'phieu_dat_hang',
                'INSERT',
                $phieu_id,
                null,
                ['ma_phieu' => $ma_phieu, 'item_count' => count($cartItems), 'status' => 'ordered', 'description' => "Tạo và duyệt phiếu đặt hàng: {$ma_phieu} với " . count($cartItems) . " vật tư"]
            );

            $_SESSION['success'] = "✅ Đã tạo và duyệt phiếu đặt hàng {$ma_phieu} thành công";
            header("Location: phieudathang.php?action=view&id=$phieu_id");
            exit;

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error in PhieuDatHangController::store: " . $e->getMessage());
            $_SESSION['error'] = 'Lỗi: ' . $e->getMessage();
            header('Location: phieudathang.php?action=create&step=2');
            exit;
        }
    }

    /**
     * Xem chi tiết phiếu đặt hàng
     */
    public function view(): void
    {
        try {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            
            if ($id <= 0) {
                throw new Exception('ID phiếu không hợp lệ');
            }

            // Lấy thông tin phiếu
            $stmt = $this->db->prepare("
                SELECT 
                    p.*,
                    u.username as ten_nguoi_lap,
                    ud.username as ten_nguoi_duyet,
                    un.username as ten_nguoi_nhan,
                    uk.username as ten_nguoi_nhap_kho
                FROM phieu_dat_hang p
                LEFT JOIN users u ON p.nguoi_lap = u.stt
                LEFT JOIN users ud ON p.nguoi_duyet = ud.stt
                LEFT JOIN users un ON p.nguoi_nhan_hang = un.stt
                LEFT JOIN users uk ON p.nguoi_nhap_kho = uk.stt
                WHERE p.id = ?
            ");
            $stmt->execute([$id]);
            $phieu = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$phieu) {
                throw new Exception('Không tìm thấy phiếu');
            }

            // Lấy chi tiết vật tư
            $stmt = $this->db->prepare("
                SELECT 
                    ct.*,
                    v.mavattu,
                    v.soluong_conlai
                FROM phieu_dat_hang_chi_tiet ct
                LEFT JOIN vattu_thanh_ly_iso v ON ct.vattu_stt = v.stt
                WHERE ct.phieu_id = ?
                ORDER BY ct.id
            ");
            $stmt->execute([$id]);
            $chi_tiet = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Lấy lịch sử nhập kho
            $stmt = $this->db->prepare("
                SELECT 
                    ls.*,
                    u.username as ten_nguoi_nhap,
                    v.ten_tiengviet
                FROM lich_su_nhap_kho ls
                LEFT JOIN users u ON ls.nguoi_nhap = u.stt
                LEFT JOIN vattu_thanh_ly_iso v ON ls.vattu_stt = v.stt
                WHERE ls.phieu_dat_hang_id = ?
                ORDER BY ls.ngay_nhap DESC
            ");
            $stmt->execute([$id]);
            $lich_su = $stmt->fetchAll(PDO::FETCH_ASSOC);

            require_once __DIR__ . '/../views/phieudathang/view.php';

        } catch (Exception $e) {
            error_log("Error in PhieuDatHangController::view: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header('Location: phieudathang.php');
            exit;
        }
    }

    /**
     * Duyệt phiếu đặt hàng (chuyển sang trạng thái ordered)
     */
    public function approve(): void
    {
        try {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            
            if ($id <= 0) {
                throw new Exception('ID phiếu không hợp lệ');
            }

            $stmt = $this->db->prepare("
                UPDATE phieu_dat_hang 
                SET trang_thai = 'ordered', nguoi_duyet = ?, ngay_duyet = NOW()
                WHERE id = ? AND trang_thai = 'draft'
            ");
            $stmt->execute([$this->userId, $id]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Không thể duyệt phiếu (phiếu không tồn tại hoặc đã được duyệt)');
            }

            $this->logger->log(
                'phieu_dat_hang',
                'UPDATE',
                $id,
                null,
                ['description' => "Duyệt phiếu đặt hàng"]
            );

            $_SESSION['success'] = 'Đã duyệt phiếu đặt hàng';
            header("Location: phieudathang.php?action=view&id=$id");
            exit;

        } catch (Exception $e) {
            error_log("Error in PhieuDatHangController::approve: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header('Location: phieudathang.php');
            exit;
        }
    }

    /**
     * Form nhận hàng
     */
    public function receive(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            try {
                $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
                
                if ($id <= 0) {
                    throw new Exception('ID phiếu không hợp lệ');
                }

                // Lấy thông tin phiếu và chi tiết
                $stmt = $this->db->prepare("SELECT * FROM phieu_dat_hang WHERE id = ? AND trang_thai IN ('ordered', 'partial_received')");
                $stmt->execute([$id]);
                $phieu = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$phieu) {
                    throw new Exception('Phiếu không tồn tại hoặc chưa được duyệt');
                }

                // Lấy chi tiết
                $stmt = $this->db->prepare("
                    SELECT ct.*, v.ten_tiengviet, v.mavattu
                    FROM phieu_dat_hang_chi_tiet ct
                    LEFT JOIN vattu_thanh_ly_iso v ON ct.vattu_stt = v.stt
                    WHERE ct.phieu_id = ?
                ");
                $stmt->execute([$id]);
                $chi_tiet = $stmt->fetchAll(PDO::FETCH_ASSOC);

                require_once __DIR__ . '/../views/phieudathang/receive.php';

            } catch (Exception $e) {
                error_log("Error in PhieuDatHangController::receive (GET): " . $e->getMessage());
                $_SESSION['error'] = $e->getMessage();
                header('Location: phieudathang.php');
                exit;
            }

        } else {
            // POST - xác nhận nhận hàng và nhập kho tự động
            try {
                $this->db->beginTransaction();

                $phieu_id = isset($_POST['phieu_id']) ? (int)$_POST['phieu_id'] : 0;
                $chi_tiet_ids = $_POST['chi_tiet_id'] ?? [];
                $so_luong_nhan_list = $_POST['so_luong_nhan'] ?? [];
                $vi_tri_kho = $_POST['vi_tri_kho'] ?? null;
                $ghi_chu = $_POST['ghi_chu'] ?? null;

                // Cập nhật số lượng nhận
                $totalReceived = 0;
                $totalOrdered = 0;

                foreach ($chi_tiet_ids as $index => $chi_tiet_id) {
                    $chi_tiet_id = (int)$chi_tiet_id;
                    $so_luong_nhan = (int)($so_luong_nhan_list[$index] ?? 0);

                    if ($so_luong_nhan > 0) {
                        // Lấy thông tin hiện tại
                        $stmt = $this->db->prepare("SELECT so_luong_dat, so_luong_nhan, vattu_stt FROM phieu_dat_hang_chi_tiet WHERE id = ?");
                        $stmt->execute([$chi_tiet_id]);
                        $current = $stmt->fetch(PDO::FETCH_ASSOC);

                        $new_so_luong_nhan = $current['so_luong_nhan'] + $so_luong_nhan;

                        // Cập nhật số lượng nhận trong chi tiết
                        $stmt = $this->db->prepare("UPDATE phieu_dat_hang_chi_tiet SET so_luong_nhan = ? WHERE id = ?");
                        $stmt->execute([$new_so_luong_nhan, $chi_tiet_id]);

                        // --- NHẬP KHO TỰ ĐỘNG ---
                        // Lấy số lượng tồn kho hiện tại
                        $stmt = $this->db->prepare("SELECT soluong_conlai FROM vattu_thanh_ly_iso WHERE stt = ?");
                        $stmt->execute([$current['vattu_stt']]);
                        $current_stock = (float)$stmt->fetchColumn();

                        $new_stock = $current_stock + $so_luong_nhan;

                        // Cập nhật tồn kho
                        $stmt = $this->db->prepare("UPDATE vattu_thanh_ly_iso SET soluong_conlai = ? WHERE stt = ?");
                        $stmt->execute([$new_stock, $current['vattu_stt']]);

                        // Ghi log lịch sử nhập kho
                        $stmt = $this->db->prepare("
                            INSERT INTO lich_su_nhap_kho (
                                phieu_dat_hang_id, phieu_chi_tiet_id, vattu_stt, so_luong,
                                so_luong_truoc, so_luong_sau, nguoi_nhap, vi_tri_kho, ghi_chu
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $phieu_id,
                            $chi_tiet_id,
                            $current['vattu_stt'],
                            $so_luong_nhan,
                            $current_stock,
                            $new_stock,
                            $this->userId,
                            $vi_tri_kho,
                            $ghi_chu
                        ]);

                        $totalReceived += $new_so_luong_nhan;
                        $totalOrdered += $current['so_luong_dat'];
                    }
                }

                // Cập nhật trạng thái phiếu - nếu nhận đủ thì chuyển sang 'stocked'
                $new_status = ($totalReceived >= $totalOrdered) ? 'stocked' : 'partial_received';
                $stmt = $this->db->prepare("
                    UPDATE phieu_dat_hang 
                    SET trang_thai = ?, 
                        nguoi_nhan_hang = ?, 
                        ngay_nhan_hang = NOW(),
                        nguoi_nhap_kho = ?,
                        ngay_nhap_kho = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$new_status, $this->userId, $this->userId, $phieu_id]);

                $this->db->commit();

                $this->logger->log(
                    'phieu_dat_hang',
                    'UPDATE',
                    $phieu_id,
                    null,
                    ['received' => $totalReceived, 'ordered' => $totalOrdered, 'description' => "Nhận hàng và nhập kho: {$totalReceived}/{$totalOrdered}"]
                );

                $_SESSION['success'] = 'Đã xác nhận nhận hàng và nhập kho thành công';
                header("Location: phieudathang.php?action=view&id=$phieu_id");
                exit;

            } catch (Exception $e) {
                $this->db->rollBack();
                error_log("Error in PhieuDatHangController::receive (POST): " . $e->getMessage());
                $_SESSION['error'] = $e->getMessage();
                header('Location: phieudathang.php');
                exit;
            }
        }
    }

    /**
     * Nhập kho - cập nhật số lượng tồn kho  
     */
    public function stock(): void
    {
        try {
            $this->db->beginTransaction();

            $phieu_id = isset($_POST['phieu_id']) ? (int)$_POST['phieu_id'] : 0;
            $vi_tri_kho = $_POST['vi_tri_kho'] ?? null;
            $ghi_chu = $_POST['ghi_chu'] ?? null;

            // Lấy chi tiết phiếu
            $stmt = $this->db->prepare("
                SELECT id, vattu_stt, so_luong_nhan, so_luong_dat
                FROM phieu_dat_hang_chi_tiet
                WHERE phieu_id = ? AND so_luong_nhan > 0
            ");
            $stmt->execute([$phieu_id]);
            $chi_tiet_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($chi_tiet_list as $item) {
                // Lấy số lượng tồn hiện tại
                $stmt = $this->db->prepare("SELECT soluong_conlai FROM vattu_thanh_ly_iso WHERE stt = ?");
                $stmt->execute([$item['vattu_stt']]);
                $current_stock = (float)$stmt->fetchColumn();

                $new_stock = $current_stock + $item['so_luong_nhan'];

                // Cập nhật tồn kho
                $stmt = $this->db->prepare("UPDATE vattu_thanh_ly_iso SET soluong_conlai = ? WHERE stt = ?");
                $stmt->execute([$new_stock, $item['vattu_stt']]);

                // Ghi log lịch sử nhập kho
                $stmt = $this->db->prepare("
                    INSERT INTO lich_su_nhap_kho (
                        phieu_dat_hang_id, phieu_chi_tiet_id, vattu_stt, so_luong,
                        so_luong_truoc, so_luong_sau, nguoi_nhap, vi_tri_kho, ghi_chu
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $phieu_id,
                    $item['id'],
                    $item['vattu_stt'],
                    $item['so_luong_nhan'],
                    $current_stock,
                    $new_stock,
                    $this->userId,
                    $vi_tri_kho,
                    $ghi_chu
                ]);
            }

            // Cập nhật trạng thái phiếu
            $stmt = $this->db->prepare("
                UPDATE phieu_dat_hang 
                SET trang_thai = 'stocked', nguoi_nhap_kho = ?, ngay_nhap_kho = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$this->userId, $phieu_id]);

            $this->db->commit();

            $this->logger->log(
                'phieu_dat_hang',
                'UPDATE',
                $phieu_id,
                null,
                ['description' => "Nhập kho phiếu đặt hàng"]
            );

            $_SESSION['success'] = 'Đã nhập kho thành công';
            header("Location: phieudathang.php?action=view&id=$phieu_id");
            exit;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error in PhieuDatHangController::stock: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header('Location: phieudathang.php');
            exit;
        }
    }

    /**
     * Hủy phiếu
     */
    public function cancel(): void
    {
        try {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $ly_do = $_POST['ly_do'] ?? 'Hủy bởi ' . $_SESSION['username'];

            $stmt = $this->db->prepare("
                UPDATE phieu_dat_hang 
                SET trang_thai = 'cancelled', ghi_chu = CONCAT(COALESCE(ghi_chu, ''), '\n[HỦY] ', ?)
                WHERE id = ? AND trang_thai IN ('draft', 'ordered')
            ");
            $stmt->execute([$ly_do, $id]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Không thể hủy phiếu');
            }

            $this->logger->log(
                'phieu_dat_hang',
                'UPDATE',
                $id,
                null,
                ['ly_do' => $ly_do, 'description' => "Hủy phiếu: {$ly_do}"]
            );

            $_SESSION['success'] = 'Đã hủy phiếu';
            header("Location: phieudathang.php?action=view&id=$id");
            exit;

        } catch (Exception $e) {
            error_log("Error in PhieuDatHangController::cancel: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header('Location: phieudathang.php');
            exit;
        }
    }

    // ===== Helper Methods =====

    private function generateMaPhieu(): string
    {
        $date = date('Ymd');
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM phieu_dat_hang WHERE DATE(ngay_lap) = CURDATE()");
        $stmt->execute();
        $count = (int)$stmt->fetchColumn() + 1;
        
        return sprintf('PDH-%s-%03d', $date, $count);
    }

    private function getCartItemsForOrder(): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                c.vattu_stt,
                c.so_luong,
                c.ghi_chu,
                v.*
            FROM cart_vattu_thanh_ly c
            LEFT JOIN vattu_thanh_ly_iso v ON c.vattu_stt = v.stt
            WHERE c.user_id = ?
        ");
        $stmt->execute([$this->userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getVatTuBySTTList(array $sttList): array
    {
        if (empty($sttList)) {
            return [];
        }

        $placeholders = str_repeat('?,', count($sttList) - 1) . '?';
        $sql = "SELECT * FROM vattu_thanh_ly_iso WHERE stt IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($sttList);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getVatTuInfo(int $stt): array
    {
        $stmt = $this->db->prepare("SELECT * FROM vattu_thanh_ly_iso WHERE stt = ?");
        $stmt->execute([$stt]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            throw new Exception("Vật tư STT {$stt} không tồn tại");
        }

        return $result;
    }

    /**
     * Lấy danh sách tất cả vật tư với filter/search/pagination
     */
    private function getAllVatTu(string $search, int $phanloai_id, int $limit, int $offset): array
    {
        $conditions = [];
        $params = [];

        if ($search) {
            $conditions[] = "(v.mavattu LIKE :search OR v.ten_tiengviet LIKE :search OR v.ten_tienganh LIKE :search)";
            $params[':search'] = "%$search%";
        }

        if ($phanloai_id > 0) {
            $conditions[] = "v.phanloai_id = :phanloai_id";
            $params[':phanloai_id'] = $phanloai_id;
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        // Lấy danh sách vật tư
        $sql = "SELECT 
                    v.*,
                    p.ten_phanloai,
                    p.mau_sac
                FROM vattu_thanh_ly_iso v
                LEFT JOIN phanloai_vattu_thanh_ly p ON v.phanloai_id = p.id
                $where
                ORDER BY v.mavattu ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Đếm tổng
        $countSql = "SELECT COUNT(*) FROM vattu_thanh_ly_iso v $where";
        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        return [
            'items' => $items,
            'total' => $total
        ];
    }

    /**
     * Lấy danh sách phân loại vật tư
     */
    private function getPhanLoaiList(): array
    {
        $stmt = $this->db->prepare("SELECT id, ten_phanloai, mau_sac FROM phanloai_vattu_thanh_ly ORDER BY ten_phanloai ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Đếm số lượng items trong giỏ hàng
     */
    private function getCartCount(): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM cart_vattu_thanh_ly WHERE user_id = ?");
        $stmt->execute([$this->userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Xuất phiếu đặt hàng ra Excel theo mẫu specification
     */
    public function exportExcel(): void
    {
        try {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            
            if ($id <= 0) {
                throw new Exception('ID phiếu không hợp lệ');
            }

            // Lấy thông tin phiếu
            $stmt = $this->db->prepare("
                SELECT 
                    p.*,
                    u.username as ten_nguoi_lap,
                    ud.username as ten_nguoi_duyet
                FROM phieu_dat_hang p
                LEFT JOIN users u ON p.nguoi_lap = u.stt
                LEFT JOIN users ud ON p.nguoi_duyet = ud.stt
                WHERE p.id = ?
            ");
            $stmt->execute([$id]);
            $phieu = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$phieu) {
                throw new Exception('Không tìm thấy phiếu');
            }

            // Lấy chi tiết vật tư
            $stmt = $this->db->prepare("
                SELECT 
                    ct.*,
                    v.mavattu
                FROM phieu_dat_hang_chi_tiet ct
                LEFT JOIN vattu_thanh_ly_iso v ON ct.vattu_stt = v.stt
                WHERE ct.phieu_id = ?
                ORDER BY ct.id
            ");
            $stmt->execute([$id]);
            $chi_tiet = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Tạo Excel file
            require_once __DIR__ . '/../vendor/autoload.php';
            
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Set default font to Times New Roman
            $spreadsheet->getDefaultStyle()->getFont()->setName('Times New Roman');
            $spreadsheet->getDefaultStyle()->getFont()->setSize(11);
            
            // Set page orientation to landscape
            $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
            
            // Column widths
            $sheet->getColumnDimension('A')->setWidth(5);
            $sheet->getColumnDimension('B')->setWidth(25);
            $sheet->getColumnDimension('C')->setWidth(25);
            $sheet->getColumnDimension('D')->setWidth(25);
            $sheet->getColumnDimension('E')->setWidth(25);
            $sheet->getColumnDimension('F')->setWidth(12);
            $sheet->getColumnDimension('G')->setWidth(10);
            $sheet->getColumnDimension('H')->setWidth(15);
            
            $currentRow = 1;
            
            // Title row
            $sheet->mergeCells("A{$currentRow}:H{$currentRow}");
            $sheet->setCellValue("A{$currentRow}", "СПЕЦИФИКАЦИЯ - Danh mục vật tư thiết bị");
            $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $currentRow++;
            
            // Subtitle row
            $year = date('Y', strtotime($phieu['ngay_lap']));
            $sheet->mergeCells("A{$currentRow}:H{$currentRow}");
            $sheet->setCellValue("A{$currentRow}", "ИЗМЕРИТЕЛЬНЫЕ ПРИБОРЫ НА {$year} ГОД");
            $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $currentRow++;
            
            // Department row
            $sheet->mergeCells("A{$currentRow}:H{$currentRow}");
            $sheet->setCellValue("A{$currentRow}", "Подразделение: КПГ");
            $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);
            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $currentRow++;
            
            // Empty row
            $currentRow++;
            
            // Header row 1 - Main headers
            $headerRow1 = $currentRow;
            $sheet->mergeCells("A{$headerRow1}:A" . ($headerRow1 + 1));
            $sheet->setCellValue("A{$headerRow1}", "П/п\n(Stt)");
            
            $sheet->mergeCells("B{$headerRow1}:D{$headerRow1}");
            $sheet->setCellValue("B{$headerRow1}", "Наименование (Tên hàng hóa)");
            
            $sheet->mergeCells("E{$headerRow1}:E" . ($headerRow1 + 1));
            $sheet->setCellValue("E{$headerRow1}", "Тех. Характеристики\n(Đặc tính kỹ thuật)");
            
            $sheet->mergeCells("F{$headerRow1}:F" . ($headerRow1 + 1));
            $sheet->setCellValue("F{$headerRow1}", "Eдапи Доп vị\ntính");
            
            $sheet->mergeCells("G{$headerRow1}:G" . ($headerRow1 + 1));
            $sheet->setCellValue("G{$headerRow1}", "Oбъем\n(Số lượng)");
            
            $sheet->mergeCells("H{$headerRow1}:H" . ($headerRow1 + 1));
            $sheet->setCellValue("H{$headerRow1}", "Примечание\n(Ghi chú)");
            
            // Header row 2 - Sub headers for name columns
            $headerRow2 = $currentRow + 1;
            $sheet->setCellValue("B{$headerRow2}", "На Англ. Языке (Tiếng Anh)");
            $sheet->setCellValue("C{$headerRow2}", "На Русс. языке (Tiếng Nga)");
            $sheet->setCellValue("D{$headerRow2}", "На Вьетнам. Языке (Tiếng Việt)");
            
            // Style header rows
            $headerRange = "A{$headerRow1}:H{$headerRow2}";
            $sheet->getStyle($headerRange)->getFont()->setBold(true);
            $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($headerRange)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle($headerRange)->getAlignment()->setWrapText(true);
            $sheet->getStyle($headerRange)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E0E0');
            $sheet->getStyle($headerRange)->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            
            $currentRow = $headerRow2 + 1;
            
            // Data rows
            $stt = 1;
            foreach ($chi_tiet as $item) {
                $sheet->setCellValue("A{$currentRow}", $stt);
                $sheet->setCellValue("B{$currentRow}", $item['ten_tieng_anh'] ?? '');
                $sheet->setCellValue("C{$currentRow}", $item['ten_tieng_nga'] ?? '');
                $sheet->setCellValue("D{$currentRow}", $item['ten_tieng_viet'] ?? '');
                $sheet->setCellValue("E{$currentRow}", $item['dac_tinh_ky_thuat'] ?? '');
                $sheet->setCellValue("F{$currentRow}", $item['don_vi'] ?? '');
                $sheet->setCellValue("G{$currentRow}", $item['so_luong_dat']);
                $sheet->setCellValue("H{$currentRow}", $item['ghi_chu'] ?? '');
                
                // Style data row
                $sheet->getStyle("A{$currentRow}:H{$currentRow}")->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("F{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("G{$currentRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                
                // Wrap text for long content
                $sheet->getStyle("B{$currentRow}:E{$currentRow}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("H{$currentRow}")->getAlignment()->setWrapText(true);
                
                $currentRow++;
                $stt++;
            }
            
            // Footer section
            $currentRow += 2;
            
            $sheet->setCellValue("B{$currentRow}", "Зам. директора КПГ");
            $sheet->setCellValue("F{$currentRow}", "Нгуен Зун Нгок");
            $sheet->getStyle("B{$currentRow}")->getFont()->setBold(true);
            $sheet->getStyle("F{$currentRow}")->getFont()->setBold(true);
            
            $currentRow++;
            $sheet->setCellValue("B{$currentRow}", "Кý tắt - Виппь:");
            $currentRow++;
            $sheet->setCellValue("C{$currentRow}", "Хướнg trường ХССТВРУЛ / Начальник ЦPГO");
            $sheet->setCellValue("F{$currentRow}", "Данг Ван Туэ");
            
            // Set filename
            $filename = "Phieu_Dat_Hang_{$phieu['ma_phieu']}_" . date('YmdHis') . ".xlsx";
            
            // Output file
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;

        } catch (Exception $e) {
            error_log("Error in PhieuDatHangController::exportExcel: " . $e->getMessage());
            $_SESSION['error'] = 'Lỗi khi xuất Excel: ' . $e->getMessage();
            header("Location: phieudathang.php?action=view&id=" . ($id ?? 0));
            exit;
        }
    }
}
