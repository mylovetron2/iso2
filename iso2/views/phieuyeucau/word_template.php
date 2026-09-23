<?php
header('Content-Type: text/html; charset=UTF-8');
$title = 'Mẫu in Phiếu yêu cầu dịch vụ';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="bg-white rounded-lg shadow-md p-6 max-w-4xl">
    <div class="flex items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold"><i class="fas fa-file-word text-blue-600 mr-2"></i>Mẫu in Phiếu yêu cầu dịch vụ</h1>
        <a href="phieuyeucau.php" class="text-gray-600 hover:text-gray-800"><i class="fas fa-arrow-left mr-1"></i>Phiếu yêu cầu</a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded mb-5"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded mb-5"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <section class="border border-slate-200 rounded-lg p-5 mb-6">
        <h2 class="font-semibold text-lg mb-2">Mẫu hiện hành</h2>
        <?php if ($template): ?>
            <p class="text-slate-700"><i class="fas fa-file-word text-blue-600 mr-2"></i><?php echo htmlspecialchars($template['ten_hien_thi']); ?></p>
            <p class="text-sm text-slate-500 mt-1">Cập nhật: <?php echo htmlspecialchars((string)$template['updated_at']); ?>, dung lượng <?php echo number_format(((int)$template['kich_thuoc']) / 1024, 0, ',', '.'); ?> KB.</p>
        <?php else: ?>
            <p class="text-amber-700">Chưa có mẫu Word. Chức năng in Phiếu YC sẽ tạm dùng mẫu cũ đến khi bạn tải lên mẫu `.docx`.</p>
        <?php endif; ?>
    </section>

    <form method="post" action="phieuyeucau_template.php?action=upload" enctype="multipart/form-data" class="border border-blue-200 bg-blue-50 rounded-lg p-5">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <label class="block font-semibold">Tải mẫu Word mới (.docx)
            <input type="file" name="template" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required class="block mt-2 w-full border rounded bg-white px-3 py-2 font-normal">
        </label>
        <p class="text-sm text-slate-600 mt-3">Mỗi lần tải lên sẽ thay thế mẫu hiện hành. Các biến có thể dùng: <code>${so_ho_so}</code>, <code>${ngay}</code>, <code>${nguoi_yeu_cau}</code>, <code>${don_vi}</code>, <code>${dien_thoai}</code>, <code>${nguoi_nhan}</code>, <code>${noi_dung}</code>, <code>${yeu_cau_them}</code>.</p>
        <p class="text-sm text-slate-600 mt-2">Trong một dòng bảng thiết bị, dùng <code>${stt}</code>, <code>${ten_thiet_bi}</code>, <code>${model}</code>, <code>${serial}</code>, <code>${tinh_trang}</code>, <code>${noi_dung_yeu_cau}</code>, <code>${tra_ve_xuong}</code>. Dòng này sẽ tự lặp theo số thiết bị.</p>
        <button type="submit" class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded"><i class="fas fa-upload mr-1"></i>Tải lên và dùng mẫu này</button>
    </form>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>