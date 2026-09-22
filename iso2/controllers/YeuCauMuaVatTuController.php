<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/YeuCauMuaVatTu.php';

class YeuCauMuaVatTuController
{
    private const MAX_TEXT_LENGTH = 4000;
    private YeuCauMuaVatTu $model;

    public function __construct()
    {
        $this->model = new YeuCauMuaVatTu();
    }

    public function index(bool $pendingOnly = false): void
    {
        $this->requireViewPermission();
        $yearInput = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);
        $year = ($yearInput !== false && $yearInput >= 2000 && $yearInput <= 2100) ? $yearInput : null;
        $statusInput = trim((string)($_GET['status'] ?? ''));
        $status = isset(YeuCauMuaVatTu::TRANG_THAI[$statusInput]) ? $statusInput : null;
        $databaseError = false;
        try {
            $items = $this->model->allOrdered($pendingOnly, $year, $status);
            $pendingCount = $this->model->pendingCount();
            $availableYears = $this->model->availableYears();
        } catch (Throwable $e) {
            error_log('YeuCauMuaVatTu index error: ' . $e->getMessage());
            $items = [];
            $pendingCount = 0;
            $availableYears = [];
            $databaseError = true;
        }
        $isManager = $this->canManage();
        require __DIR__ . '/../views/yeu_cau_mua_vat_tu/index.php';
    }

    public function create(): void
    {
        $this->requireCreatePermission();
        $this->verifyCsrf();
        $tenVatTu = trim((string)($_POST['ten_vat_tu'] ?? ''));
        if ($tenVatTu === '' || mb_strlen($tenVatTu) > 255) {
            $this->redirect('yeu_cau_mua_vat_tu.php', 'Tên vật tư không hợp lệ.');
        }
        $soLuong = (float)($_POST['so_luong'] ?? 0);
        if ($soLuong <= 0) {
            $this->redirect('yeu_cau_mua_vat_tu.php', 'Số lượng phải lớn hơn 0.');
        }
        $linkMua = trim((string)($_POST['link_mua'] ?? ''));
        if ($linkMua !== '' && (mb_strlen($linkMua) > 1000 || !filter_var($linkMua, FILTER_VALIDATE_URL))) {
            $this->redirect('yeu_cau_mua_vat_tu.php', 'Link mua không hợp lệ.');
        }
        $thoiGian = trim((string)($_POST['thoi_gian_yeu_cau'] ?? ''));
        $timestamp = $thoiGian !== '' ? strtotime($thoiGian) : false;
        if ($timestamp === false) {
            $this->redirect('yeu_cau_mua_vat_tu.php', 'Thời gian yêu cầu không hợp lệ.');
        }
        $ghiChu = trim((string)($_POST['ghi_chu'] ?? ''));
        $this->model->create([
            'ten_vat_tu' => $tenVatTu,
            'so_luong' => $soLuong,
            'don_vi_tinh' => trim((string)($_POST['don_vi_tinh'] ?? '')) ?: null,
            'link_mua' => $linkMua !== '' ? $linkMua : null,
            'thoi_gian_yeu_cau' => date('Y-m-d H:i:s', $timestamp),
            'ghi_chu' => $ghiChu !== '' ? mb_substr($ghiChu, 0, self::MAX_TEXT_LENGTH) : null,
            'nguoi_gui_id' => (int)($_SESSION['user_id'] ?? 0) ?: null,
            'nguoi_gui_ten' => (string)($_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Ẩn danh'),
            'trang_thai' => 'moi',
        ]);
        $this->redirect('yeu_cau_mua_vat_tu.php', '', 'Đã gửi yêu cầu mua vật tư.');
    }

    public function update(): void
    {
        $this->requireManagePermission();
        $this->verifyCsrf();
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0 || !$this->model->find($id)) {
            $this->redirect('yeu_cau_mua_vat_tu.php', 'Không tìm thấy yêu cầu.');
        }
        $trangThai = (string)($_POST['trang_thai'] ?? 'moi');
        if (!isset(YeuCauMuaVatTu::TRANG_THAI[$trangThai])) {
            $trangThai = 'moi';
        }
        $phanHoi = trim((string)($_POST['phan_hoi'] ?? ''));
        $this->model->update($id, [
            'trang_thai' => $trangThai,
            'phan_hoi' => $phanHoi !== '' ? mb_substr($phanHoi, 0, self::MAX_TEXT_LENGTH) : null,
            'nguoi_xu_ly_id' => (int)($_SESSION['user_id'] ?? 0) ?: null,
            'nguoi_xu_ly_ten' => (string)($_SESSION['user_name'] ?? $_SESSION['username'] ?? ''),
            'ngay_xu_ly' => date('Y-m-d H:i:s'),
        ]);
        $target = (string)($_POST['redirect'] ?? 'yeu_cau_mua_vat_tu.php?action=pending');
        $this->redirect($target, '', 'Đã cập nhật yêu cầu mua vật tư.');
    }

    public function edit(): void
    {
        $this->requireCreatePermission();
        $this->verifyCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $item = $id > 0 ? $this->model->find($id) : false;
        $currentUserId = (int)($_SESSION['user_id'] ?? 0);
        if (!$item || $currentUserId <= 0 || (int)($item['nguoi_gui_id'] ?? 0) !== $currentUserId) {
            $this->redirect('yeu_cau_mua_vat_tu.php', 'Bạn chỉ được sửa yêu cầu do chính mình tạo.');
        }
        if (in_array((string)$item['trang_thai'], ['da_xu_ly', 'tu_choi'], true)) {
            $this->redirect('yeu_cau_mua_vat_tu.php', 'Yêu cầu đã xử lý hoặc không duyệt nên không thể chỉnh sửa.');
        }

        $tenVatTu = trim((string)($_POST['ten_vat_tu'] ?? ''));
        $soLuong = (float)($_POST['so_luong'] ?? 0);
        $linkMua = trim((string)($_POST['link_mua'] ?? ''));
        $thoiGian = trim((string)($_POST['thoi_gian_yeu_cau'] ?? ''));
        $timestamp = $thoiGian !== '' ? strtotime($thoiGian) : false;
        if ($tenVatTu === '' || mb_strlen($tenVatTu) > 255) {
            $this->redirect('yeu_cau_mua_vat_tu.php', 'Tên vật tư không hợp lệ.');
        }
        if ($soLuong <= 0) {
            $this->redirect('yeu_cau_mua_vat_tu.php', 'Số lượng phải lớn hơn 0.');
        }
        if ($linkMua !== '' && (mb_strlen($linkMua) > 1000 || !filter_var($linkMua, FILTER_VALIDATE_URL))) {
            $this->redirect('yeu_cau_mua_vat_tu.php', 'Link mua không hợp lệ.');
        }
        if ($timestamp === false) {
            $this->redirect('yeu_cau_mua_vat_tu.php', 'Thời gian yêu cầu không hợp lệ.');
        }
        $ghiChu = trim((string)($_POST['ghi_chu'] ?? ''));
        $this->model->update($id, [
            'ten_vat_tu' => $tenVatTu,
            'so_luong' => $soLuong,
            'don_vi_tinh' => trim((string)($_POST['don_vi_tinh'] ?? '')) ?: null,
            'link_mua' => $linkMua !== '' ? $linkMua : null,
            'thoi_gian_yeu_cau' => date('Y-m-d H:i:s', $timestamp),
            'ghi_chu' => $ghiChu !== '' ? mb_substr($ghiChu, 0, self::MAX_TEXT_LENGTH) : null,
        ]);
        $target = (string)($_POST['redirect'] ?? 'yeu_cau_mua_vat_tu.php');
        $this->redirect($target, '', 'Đã cập nhật yêu cầu mua vật tư.');
    }

    public function delete(): void
    {
        $this->requireManagePermission();
        $this->verifyCsrf();
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $this->model->delete($id);
        }
        $this->redirect('yeu_cau_mua_vat_tu.php', '', 'Đã xóa yêu cầu.');
    }

    private function canManage(): bool
    {
        return hasRole(ROLE_ADMIN) || hasPermission('yeucaumuavattu.manage');
    }

    private function requireViewPermission(): void
    {
        if (!hasRole(ROLE_ADMIN) && !hasPermission('yeucaumuavattu.view')) {
            http_response_code(403);
            exit('Bạn không có quyền xem yêu cầu mua vật tư.');
        }
    }

    private function requireCreatePermission(): void
    {
        if (!hasRole(ROLE_ADMIN) && !hasPermission('yeucaumuavattu.create')) {
            http_response_code(403);
            exit('Bạn không có quyền tạo yêu cầu mua vật tư.');
        }
    }

    private function requireManagePermission(): void
    {
        if (!$this->canManage()) {
            http_response_code(403);
            exit('Bạn không có quyền xử lý yêu cầu mua vật tư.');
        }
    }

    private function verifyCsrf(): void
    {
        if (!hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)($_POST['csrf_token'] ?? ''))) {
            http_response_code(419);
            exit('Phiên thao tác không hợp lệ. Vui lòng tải lại trang.');
        }
    }

    private function redirect(string $target, string $error = '', string $success = ''): void
    {
        $separator = str_contains($target, '?') ? '&' : '?';
        if ($success !== '') {
            $target .= $separator . 'success=' . rawurlencode($success);
        } elseif ($error !== '') {
            $target .= $separator . 'error=' . rawurlencode($error);
        }
        header('Location: ' . $target);
        exit;
    }
}