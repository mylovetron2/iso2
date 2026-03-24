<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiểm Tra Giao Diện Đặt Hàng</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { color: #22c55e; font-weight: bold; }
        .info { color: #3b82f6; }
        .warning { color: #f59e0b; }
        h1 { color: #1e40af; }
        h2 { color: #059669; border-bottom: 2px solid #059669; padding-bottom: 10px; }
        ul { line-height: 2; }
        .test-link {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 10px 5px;
        }
        .test-link:hover { background: #2563eb; }
        .checklist { background: #f0fdf4; padding: 15px; border-left: 4px solid #22c55e; }
        .checklist li { margin: 10px 0; }
        code { background: #f1f5f9; padding: 2px 6px; border-radius: 3px; color: #dc2626; }
    </style>
</head>
<body>
    <h1>✅ ĐÃ SỬA XONG GIAO DIỆN!</h1>

    <div class="card">
        <h2>🔧 Vấn đề đã khắc phục</h2>
        <ul>
            <li class="success">✅ Xóa code Bootstrap duplicate trong <code>chon_dathang.php</code> (giảm từ 337 → 179 dòng)</li>
            <li class="success">✅ Xóa code Bootstrap duplicate trong <code>phieu_dathang.php</code> (giảm từ 464 → 241 dòng)</li>
            <li class="success">✅ Giờ chỉ còn 1 giao diện duy nhất: <strong>Tailwind CSS</strong></li>
        </ul>
    </div>

    <div class="card">
        <h2>🧪 Hướng dẫn test</h2>
        
        <h3>Bước 1: Xóa cache trình duyệt</h3>
        <div class="warning">⚠️ Rất quan trọng!</div>
        <ul>
            <li><strong>Chrome/Edge</strong>: Ctrl + Shift + Del → Xóa "Cached images and files" → Clear data</li>
            <li><strong>Firefox</strong>: Ctrl + Shift + Del → Chọn "Cache" → Clear Now</li>
            <li><strong>Hoặc</strong>: Bấm <code>Ctrl + Shift + R</code> (hard refresh)</li>
        </ul>

        <h3>Bước 2: Test các trang</h3>
        
        <?php
        // Kiểm tra môi trường
        $base_url = 'http://localhost/iso2';
        if (strpos($_SERVER['HTTP_HOST'], 'diavatly') !== false) {
            $base_url = 'https://diavatly.cloud/iso2';
        }
        ?>
        
        <div style="background: #eff6ff; padding: 15px; border-radius: 6px;">
            <p><strong>Test URL:</strong></p>
            <a href="<?= $base_url ?>/vattuthanhly.php" class="test-link" target="_blank">
                📋 1. Trang Vật Tư Thanh Lý
            </a>
            <a href="<?= $base_url ?>/vattuthanhly.php?action=taophieudathang" class="test-link" target="_blank">
                🛒 2. Chọn Vật Tư Đặt Hàng
            </a>
            <a href="<?= $base_url ?>/vattuthanhly.php?action=taophieudathang&ids=1,2,3" class="test-link" target="_blank">
                📝 3. Phiếu Đặt Hàng (demo)
            </a>
        </div>
    </div>

    <div class="card checklist">
        <h2>✔️ Checklist kiểm tra</h2>
        <ul>
            <li>☐ <strong>Trang chọn vật tư</strong>: Có header xanh dương, nút màu xanh lá/xám</li>
            <li>☐ <strong>Bảng</strong>: Viền border đen, không có style Bootstrap (table-bordered, etc.)</li>
            <li>☐ <strong>Badge tồn kho</strong>: Màu xanh (có hàng) / đỏ (hết hàng)</li>
            <li>☐ <strong>Button</strong>: Dùng Tailwind: <code>bg-green-600 hover:bg-green-700</code></li>
            <li>☐ <strong>Responsive</strong>: Thu nhỏ cửa sổ trình duyệt → layout tự động điều chỉnh</li>
            <li>☐ <strong>Không có giao diện cũ</strong>: Không thấy card, form-control, btn-success (Bootstrap)</li>
        </ul>
    </div>

    <div class="card">
        <h2>🎨 Giao diện mới (Tailwind CSS)</h2>
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="background: #f8fafc;">
                <th style="border: 1px solid #ddd; padding: 10px;">Thành phần</th>
                <th style="border: 1px solid #ddd; padding: 10px;">Style cũ (Bootstrap)</th>
                <th style="border: 1px solid #ddd; padding: 10px;">Style mới (Tailwind)</th>
            </tr>
            <tr>
                <td style="border: 1px solid #ddd; padding: 10px;">Container</td>
                <td style="border: 1px solid #ddd; padding: 10px;"><code>container-fluid</code></td>
                <td style="border: 1px solid #ddd; padding: 10px;"><code>container mx-auto px-4</code></td>
            </tr>
            <tr style="background: #f8fafc;">
                <td style="border: 1px solid #ddd; padding: 10px;">Card</td>
                <td style="border: 1px solid #ddd; padding: 10px;"><code>card</code></td>
                <td style="border: 1px solid #ddd; padding: 10px;"><code>bg-white rounded-lg shadow-md</code></td>
            </tr>
            <tr>
                <td style="border: 1px solid #ddd; padding: 10px;">Button Success</td>
                <td style="border: 1px solid #ddd; padding: 10px;"><code>btn btn-success</code></td>
                <td style="border: 1px solid #ddd; padding: 10px;"><code>bg-green-600 hover:bg-green-700</code></td>
            </tr>
            <tr style="background: #f8fafc;">
                <td style="border: 1px solid #ddd; padding: 10px;">Input</td>
                <td style="border: 1px solid #ddd; padding: 10px;"><code>form-control</code></td>
                <td style="border: 1px solid #ddd; padding: 10px;"><code>w-full border rounded px-3 py-2</code></td>
            </tr>
            <tr>
                <td style="border: 1px solid #ddd; padding: 10px;">Badge</td>
                <td style="border: 1px solid #ddd; padding: 10px;"><code>badge bg-success</code></td>
                <td style="border: 1px solid #ddd; padding: 10px;"><code>bg-green-500 text-xs rounded</code></td>
            </tr>
        </table>
    </div>

    <div class="card" style="background: #fef3c7;">
        <h2>📌 Lưu ý quan trọng</h2>
        <ul>
            <li><strong>Nếu vẫn thấy giao diện cũ</strong>:
                <ol>
                    <li>Xóa cache trình duyệt (Ctrl + Shift + Del)</li>
                    <li>Hard refresh: <code>Ctrl + Shift + R</code></li>
                    <li>Thử trình duyệt ẩn danh (Incognito): <code>Ctrl + Shift + N</code></li>
                    <li>Kiểm tra DevTools (F12) → Network tab → Disable cache</li>
                </ol>
            </li>
            <li><strong>Nếu server production</strong>: Upload lại 2 files:
                <ul>
                    <li><code>views/vattuthanhly/chon_dathang.php</code></li>
                    <li><code>views/vattuthanhly/phieu_dathang.php</code></li>
                </ul>
            </li>
        </ul>
    </div>

    <div class="card" style="background: #dcfce7;">
        <h2>✨ Kết quả mong đợi</h2>
        <ul>
            <li>✅ Giao diện sạch, hiện đại với Tailwind CSS</li>
            <li>✅ Responsive: tự động co giãn trên mọi kích thước màn hình</li>
            <li>✅ Không có duplicate HTML</li>
            <li>✅ Màu sắc: Xanh dương (header), Xanh lá (nút action), Xám (nút phụ)</li>
            <li>✅ Bảng phiếu đặt hàng: 3 ngôn ngữ (Việt - Anh - Nga) như mẫu</li>
        </ul>
    </div>

    <div style="text-align: center; margin: 40px 0;">
        <h2 style="color: #059669;">🎉 Hoàn thành!</h2>
        <p style="font-size: 18px; color: #666;">
            Giao diện đã được sửa xong. Hãy test ngay!
        </p>
        <a href="<?= $base_url ?>/vattuthanhly.php" class="test-link" style="font-size: 18px; padding: 15px 30px;">
            🚀 BẮT ĐẦU TEST
        </a>
    </div>

    <hr style="margin: 40px 0; border: none; border-top: 2px solid #e5e7eb;">

    <div style="text-align: center; color: #999; font-size: 14px;">
        <p>📅 <?= date('d/m/Y H:i:s') ?></p>
        <p>🏠 Server: <?= $_SERVER['HTTP_HOST'] ?></p>
    </div>
</body>
</html>
