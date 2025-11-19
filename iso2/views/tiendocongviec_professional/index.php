<?php 
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/../layouts/header.php'; 
?>
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h1 class="text-2xl font-bold mb-4 flex items-center"><i class="fas fa-business-time mr-2"></i> Tiến độ công việc</h1>
    <!-- Thống kê nhanh -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-100 rounded p-4 text-center">
            <div class="text-2xl font-bold text-blue-700"><?php echo $stats['total']; ?></div>
            <div class="text-gray-600">Tổng số công việc</div>
        </div>
        <div class="bg-green-100 rounded p-4 text-center">
            <div class="text-2xl font-bold text-green-700"><?php echo $stats['completed']; ?></div>
            <div class="text-gray-600">Hoàn thành</div>
        </div>
        <div class="bg-yellow-100 rounded p-4 text-center">
            <div class="text-2xl font-bold text-yellow-700"><?php echo $stats['working']; ?></div>
            <div class="text-gray-600">Đang thực hiện</div>
        </div>
        <div class="bg-red-100 rounded p-4 text-center">
            <div class="text-2xl font-bold text-red-700"><?php echo $stats['pending']; ?></div>
            <div class="text-gray-600">Chờ thực hiện</div>
        </div>
    </div>
    <!-- Filter & Search -->
    <form method="get" class="flex flex-wrap gap-2 mb-4">
        <input type="text" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" placeholder="Tìm kiếm tên công việc, trạng thái..." class="border rounded px-3 py-2 flex-1 min-w-[200px]">
        <select name="status" class="border rounded px-3 py-2">
            <option value="">Tất cả trạng thái</option>
            <option value="Hoàn thành" <?php if(isset($_GET['status']) && $_GET['status']==='Hoàn thành') echo 'selected'; ?>>Hoàn thành</option>
            <option value="Đang thực hiện" <?php if(isset($_GET['status']) && $_GET['status']==='Đang thực hiện') echo 'selected'; ?>>Đang thực hiện</option>
            <option value="Chờ thực hiện" <?php if(isset($_GET['status']) && $_GET['status']==='Chờ thực hiện') echo 'selected'; ?>>Chờ thực hiện</option>
        </select>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Lọc</button>
    <a href="tiendocongviec2.php" class="ml-2 text-gray-600 hover:underline">Xóa lọc</a>
    <a href="tiendocongviec2.php?action=create" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded ml-auto">Thêm mới</a>
    </form>
    <!-- Bảng dữ liệu -->
    <div class="overflow-x-auto">
    <table class="min-w-full bg-white border">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-2 border">STT</th>
                <th class="px-4 py-2 border">Hồ sơ</th>
                <th class="px-4 py-2 border">Mã VT</th>
                <th class="px-4 py-2 border">Số máy</th>
                <th class="px-4 py-2 border">Model</th>
                <th class="px-4 py-2 border">Nhóm SC</th>
                <th class="px-4 py-2 border">Ngày TH</th>
                <th class="px-4 py-2 border">Ngày KT</th>
                <th class="px-4 py-2 border">Trạng thái</th>
                <th class="px-4 py-2 border">Tạm dừng</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($workProgressList as $item): ?>
            <tr>
                <td class="border px-4 py-2"><?php echo $item['stt']; ?></td>
                <td class="border px-4 py-2"><?php echo htmlspecialchars($item['hoso']); ?></td>
                <td class="border px-4 py-2"><?php echo htmlspecialchars($item['mavt']); ?></td>
                <td class="border px-4 py-2"><?php echo htmlspecialchars($item['somay']); ?></td>
                <td class="border px-4 py-2"><?php echo htmlspecialchars($item['model']); ?></td>
                <td class="border px-4 py-2"><?php echo htmlspecialchars($item['nhomsc']); ?></td>
                <td class="border px-4 py-2"><?php echo htmlspecialchars($item['ngayth']); ?></td>
                <td class="border px-4 py-2"><?php echo htmlspecialchars($item['ngaykt']); ?></td>
                <td class="border px-4 py-2"><?php echo htmlspecialchars($item['ttktafter']); ?></td>
                <td class="border px-4 py-2 text-center">
                    <button class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded pause-btn" data-workid="<?php echo $item['stt']; ?>">Tạm dừng</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <!-- Phân trang -->
    <?php if($totalPages > 1): ?>
    <div class="flex justify-center mt-4 gap-2">
        <?php for($i=1;$i<=$totalPages;$i++): ?>
            <?php $params = $_GET; $params['page']=$i; $url = 'tiendocongviec2.php?'.http_build_query($params); ?>
            <?php if($i == (isset($_GET['page']) ? (int)$_GET['page'] : 1)): ?>
                <span class="px-3 py-1 rounded bg-blue-600 text-white"><?php echo $i; ?></span>
            <?php else: ?>
                <a href="<?php echo $url; ?>" class="px-3 py-1 rounded bg-gray-200 hover:bg-blue-200"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>
<!-- Modal tạm dừng -->
<div id="pauseModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg relative">
        <button id="pauseModalClose" class="absolute top-2 right-2 text-gray-500 hover:text-red-600"><i class="fas fa-times"></i></button>
        <h2 class="text-xl font-bold mb-4">Quản lý tạm dừng công việc</h2>
        <div id="pauseList"></div>
        <button id="pauseAddBtn" class="mt-4 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Thêm tạm dừng</button>
        <form id="pauseForm" class="mt-4 hidden">
            <input type="hidden" name="work_id" id="pauseWorkId">
            <input type="text" name="lydo" id="pauseLydo" placeholder="Lý do tạm dừng" class="border rounded px-3 py-2 w-full mb-2">
            <input type="date" name="ngaybatdau" id="pauseNgaybatdau" class="border rounded px-3 py-2 w-full mb-2">
            <input type="date" name="ngayketthuc" id="pauseNgayketthuc" class="border rounded px-3 py-2 w-full mb-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Lưu</button>
            <button type="button" id="pauseCancelBtn" class="ml-2 px-4 py-2 rounded border">Hủy</button>
        </form>
    </div>
</div>
<script src="/iso2/assets/js/tiendocongviec_pause.js"></script>
<script>
let currentWorkId = null;
document.querySelectorAll('.pause-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        currentWorkId = this.dataset.workid;
        document.getElementById('pauseModal').classList.remove('hidden');
        loadPauseList();
    });
});
document.getElementById('pauseModalClose').onclick = () => {
    document.getElementById('pauseModal').classList.add('hidden');
    document.getElementById('pauseForm').classList.add('hidden');
};
document.getElementById('pauseAddBtn').onclick = () => {
    document.getElementById('pauseForm').reset();
    document.getElementById('pauseForm').classList.remove('hidden');
    document.getElementById('pauseWorkId').value = currentWorkId;
};
document.getElementById('pauseCancelBtn').onclick = () => {
    document.getElementById('pauseForm').classList.add('hidden');
};
document.getElementById('pauseForm').onsubmit = function(e) {
    e.preventDefault();
    PauseAPI.add(Object.fromEntries(new FormData(this)), function(res) {
        if(res.success) {
            loadPauseList();
            document.getElementById('pauseForm').classList.add('hidden');
        }
    });
};
function loadPauseList() {
    PauseAPI.list(currentWorkId, function(res) {
        if(res.success) {
            let html = '<ul>';
            res.data.forEach(function(item) {
                html += `<li class='flex justify-between items-center border-b py-1'>
                    <span><b>${item.lydo||''}</b> (${item.ngaybatdau||''} - ${item.ngayketthuc||''})</span>
                    <button onclick="deletePause(${item.id})" class='text-red-600 hover:underline'>Xóa</button>
                </li>`;
            });
            html += '</ul>';
            document.getElementById('pauseList').innerHTML = html;
        }
    });
}
function deletePause(id) {
    if(confirm('Xóa tạm dừng này?')) {
        PauseAPI.delete(id, function(res) {
            if(res.success) loadPauseList();
        });
    }
}
</script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
