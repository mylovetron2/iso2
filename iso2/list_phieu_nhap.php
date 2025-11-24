<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/PhieuBanGiao.php';

requireAuth();

$phieuModel = new PhieuBanGiao();

// Lấy danh sách phiếu nháp (có thể xóa)
echo "<h2>Danh sách phiếu bàn giao có thể xóa (Nháp)</h2>";

$sql = "SELECT stt, sophieu, phieuyc, ngaybg, nguoigiao, nguoinhan, trangthai 
        FROM phieubangiao_iso 
        WHERE trangthai = 0
        ORDER BY stt DESC 
        LIMIT 20";

$stmt = $phieuModel->query($sql);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($items)) {
    echo "<p>❌ Không có phiếu nháp nào</p>";
} else {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr>
            <th>STT (ID)</th>
            <th>Số phiếu</th>
            <th>Phiếu YC</th>
            <th>Ngày BG</th>
            <th>Người giao</th>
            <th>Người nhận</th>
            <th>Trạng thái</th>
            <th>Thao tác</th>
          </tr>";
    
    foreach ($items as $item) {
        $status = $item['trangthai'] == 0 ? '<span style="color: orange;">Nháp</span>' : '<span style="color: green;">Đã duyệt</span>';
        echo "<tr>
                <td>{$item['stt']}</td>
                <td>{$item['sophieu']}</td>
                <td>{$item['phieuyc']}</td>
                <td>{$item['ngaybg']}</td>
                <td>{$item['nguoigiao']}</td>
                <td>{$item['nguoinhan']}</td>
                <td>$status</td>
                <td>
                    <a href='test_delete_phieu.php?id={$item['stt']}' 
                       onclick='return confirm(\"Test xóa phiếu {$item['sophieu']}?\")'>
                       🗑️ Test xóa
                    </a>
                </td>
              </tr>";
    }
    
    echo "</table>";
}

echo "<br><a href='phieubangiao.php'>← Quay lại</a>";
?>
