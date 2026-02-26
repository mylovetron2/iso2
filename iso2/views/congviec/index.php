<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Công Việc Sửa Chữa - KPI</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .badge-green { background: #4CAF50; color: white; padding: 4px 8px; border-radius: 4px; }
        .badge-orange { background: #FF9800; color: white; padding: 4px 8px; border-radius: 4px; }
        .badge-red { background: #F44336; color: white; padding: 4px 8px; border-radius: 4px; }
        .gio-display { font-size: 1.5rem; font-weight: bold; }
    </style>
</head>
<body class="bg-gray-100">
    
    <!-- Header -->
    <div class="bg-blue-600 text-white p-4 shadow-md">
        <div class="container mx-auto">
            <h1 class="text-2xl font-bold">
                <i class="fas fa-tools"></i> Quản Lý Công Việc Sửa Chữa Hàng Ngày
            </h1>
            <p class="text-sm mt-1">Theo dõi công việc và KPI của nhân viên</p>
        </div>
    </div>

    <div class="container mx-auto p-6">
        
        <!-- Filter Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold mb-4"><i class="fas fa-filter"></i> Lọc Công Việc</h2>
            
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Nhân viên -->
                <div>
                    <label class="block text-sm font-medium mb-2">
                        <i class="fas fa-user"></i> Nhân viên
                    </label>
                    <select name="nhanvien_stt" class="w-full border rounded px-3 py-2" onchange="this.form.submit()">
                        <option value="">-- Chọn nhân viên --</option>
                        <?php foreach ($formData['nhanviens'] as $nv): ?>
                            <option value="<?= $nv['stt'] ?>" 
                                <?= ($nhanvienStt == $nv['stt']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($nv['hoten']) ?> - <?= htmlspecialchars($nv['chucdanh']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Ngày làm -->
                <div>
                    <label class="block text-sm font-medium mb-2">
                        <i class="fas fa-calendar"></i> Ngày làm
                    </label>
                    <input type="date" name="ngay_lam" value="<?= $ngayLam ?>" 
                           class="w-full border rounded px-3 py-2" onchange="this.form.submit()">
                </div>

                <!-- Nút filter -->
                <div class="flex items-end">
                    <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                        <i class="fas fa-search"></i> Lọc
                    </button>
                    <a href="congviec_suachua.php" class="ml-2 bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <?php if ($nhanvienStt && $ngayLam): ?>
        
        <!-- Thông tin giờ làm -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <div class="text-gray-600 mb-2"><i class="fas fa-clock"></i> Tổng giờ đã làm</div>
                <div class="gio-display text-blue-600"><?= number_format($viewData['tong_gio'], 2) ?> / 8h</div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <div class="text-gray-600 mb-2"><i class="fas fa-hourglass-half"></i> Giờ còn lại</div>
                <div class="gio-display <?= ($viewData['gio_con_lai'] > 0) ? 'text-green-600' : 'text-red-600' ?>">
                    <?= number_format($viewData['gio_con_lai'], 2) ?>h
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <div class="text-gray-600 mb-2"><i class="fas fa-tasks"></i> Số công việc</div>
                <div class="gio-display text-gray-700"><?= count($viewData['congviecs']) ?></div>
            </div>
        </div>

        <!-- Nút thêm công việc -->
        <?php if ($viewData['gio_con_lai'] > 0): ?>
        <div class="mb-4">
            <button onclick="showAddForm()" class="bg-green-500 text-white px-6 py-3 rounded hover:bg-green-600">
                <i class="fas fa-plus"></i> Thêm Công Việc Mới
            </button>
        </div>
        <?php else: ?>
        <div class="bg-yellow-100 border-l-4 border-yellow-500 p-4 mb-4">
            <p class="text-yellow-700">
                <i class="fas fa-exclamation-triangle"></i> 
                Đã sử dụng hết 8 giờ trong ngày. Không thể thêm công việc mới.
            </p>
        </div>
        <?php endif; ?>

        <!-- Form thêm công việc (ẩn mặc định) -->
        <div id="addFormContainer" class="bg-white rounded-lg shadow-md p-6 mb-6" style="display: none;">
            <h3 class="text-lg font-bold mb-4">
                <i class="fas fa-plus-circle"></i> Thêm Công Việc Mới
            </h3>
            
            <form id="formAddCongViec" onsubmit="return submitAddForm(event)">
                <input type="hidden" name="nhanvien_stt" value="<?= $nhanvienStt ?>">
                <input type="hidden" name="ngay_lam" value="<?= $ngayLam ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Mã thiết bị -->
                    <div>
                        <label class="block text-sm font-medium mb-2">
                            Mã thiết bị <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="mavt" required 
                               class="w-full border rounded px-3 py-2" 
                               placeholder="Nhập mã vật tư">
                    </div>

                    <!-- Serial -->
                    <div>
                        <label class="block text-sm font-medium mb-2">
                            Serial / Số máy <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="somay" required 
                               class="w-full border rounded px-3 py-2" 
                               placeholder="Nhập số máy">
                    </div>

                    <!-- Cấp độ BD -->
                    <div>
                        <label class="block text-sm font-medium mb-2">
                            Cấp độ bảo dưỡng <span class="text-red-500">*</span>
                        </label>
                        <select name="capdo_stt" required class="w-full border rounded px-3 py-2">
                            <option value="">-- Chọn cấp độ --</option>
                            <?php foreach ($formData['capdos'] as $cd): ?>
                                <option value="<?= $cd['stt'] ?>" 
                                        data-kpi="<?= $cd['kpi_gio_chuan'] ?>"
                                        data-ten="<?= htmlspecialchars($cd['ten_capdo']) ?>">
                                    <?= htmlspecialchars($cd['ten_capdo']) ?> 
                                    (KPI: <?= $cd['kpi_gio_chuan'] ?>h)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Số giờ làm -->
                    <div>
                        <label class="block text-sm font-medium mb-2">
                            Số giờ làm <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="so_gio_lam" 
                               required min="0.5" max="<?= $viewData['gio_con_lai'] ?>" 
                               step="0.5" class="w-full border rounded px-3 py-2" 
                               placeholder="VD: 2.5">
                        <small class="text-gray-500">Còn lại: <?= number_format($viewData['gio_con_lai'], 2) ?>h</small>
                    </div>

                    <!-- Giờ bắt đầu -->
                    <div>
                        <label class="block text-sm font-medium mb-2">Giờ bắt đầu</label>
                        <input type="time" name="gio_bat_dau" class="w-full border rounded px-3 py-2">
                    </div>

                    <!-- Giờ kết thúc -->
                    <div>
                        <label class="block text-sm font-medium mb-2">Giờ kết thúc</label>
                        <input type="time" name="gio_ket_thuc" class="w-full border rounded px-3 py-2">
                    </div>
                </div>

                <!-- Nội dung công việc -->
                <div class="mt-4">
                    <label class="block text-sm font-medium mb-2">
                        Nội dung công việc <span class="text-red-500">*</span>
                    </label>
                    <textarea name="noi_dung" required rows="3" 
                              class="w-full border rounded px-3 py-2" 
                              placeholder="Mô tả chi tiết công việc đã thực hiện..."></textarea>
                </div>

                <!-- Ghi chú -->
                <div class="mt-4">
                    <label class="block text-sm font-medium mb-2">Ghi chú</label>
                    <textarea name="ghi_chu" rows="2" 
                              class="w-full border rounded px-3 py-2" 
                              placeholder="Ghi chú thêm (nếu có)..."></textarea>
                </div>

                <!-- Buttons -->
                <div class="mt-6 flex gap-2">
                    <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                        <i class="fas fa-save"></i> Lưu
                    </button>
                    <button type="button" onclick="hideAddForm()" 
                            class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                </div>
            </form>
        </div>

        <!-- Danh sách công việc -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-bold mb-4">
                <i class="fas fa-list"></i> Danh Sách Công Việc Trong Ngày
            </h3>
            
            <?php if (empty($viewData['congviecs'])): ?>
                <p class="text-gray-500 text-center py-8">
                    <i class="fas fa-inbox text-4xl mb-2"></i><br>
                    Chưa có công việc nào trong ngày này
                </p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full border">
                        <thead class="bg-gray-200">
                            <tr>
                                <th class="border px-4 py-2">STT</th>
                                <th class="border px-4 py-2">Thiết bị</th>
                                <th class="border px-4 py-2">Cấp độ</th>
                                <th class="border px-4 py-2">Nội dung</th>
                                <th class="border px-4 py-2">Số giờ</th>
                                <th class="border px-4 py-2">KPI</th>
                                <th class="border px-4 py-2">Hiệu suất</th>
                                <th class="border px-4 py-2">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $stt = 1;
                            foreach ($viewData['congviecs'] as $cv): 
                                $hieuSuat = ($cv['kpi_gio_chuan'] / $cv['so_gio_lam']) * 100;
                                $hieuSuatClass = $hieuSuat >= 100 ? 'text-green-600' : ($hieuSuat >= 80 ? 'text-orange-600' : 'text-red-600');
                            ?>
                            <tr>
                                <td class="border px-4 py-2 text-center"><?= $stt++ ?></td>
                                <td class="border px-4 py-2">
                                    <strong><?= htmlspecialchars($cv['mavt']) ?></strong><br>
                                    <small class="text-gray-600">SN: <?= htmlspecialchars($cv['somay']) ?></small>
                                </td>
                                <td class="border px-4 py-2">
                                    <span class="badge-<?= $cv['capdo_stt'] == 1 ? 'green' : ($cv['capdo_stt'] == 2 ? 'orange' : 'red') ?>">
                                        <?= htmlspecialchars($cv['capdo_ten']) ?>
                                    </span>
                                </td>
                                <td class="border px-4 py-2">
                                    <?= nl2br(htmlspecialchars(substr($cv['noi_dung'], 0, 100))) ?>
                                    <?= strlen($cv['noi_dung']) > 100 ? '...' : '' ?>
                                </td>
                                <td class="border px-4 py-2 text-center">
                                    <strong><?= number_format($cv['so_gio_lam'], 2) ?>h</strong>
                                </td>
                                <td class="border px-4 py-2 text-center">
                                    <?= number_format($cv['kpi_gio_chuan'], 2) ?>h
                                </td>
                                <td class="border px-4 py-2 text-center">
                                    <span class="<?= $hieuSuatClass ?> font-bold">
                                        <?= number_format($hieuSuat, 1) ?>%
                                    </span>
                                </td>
                                <td class="border px-4 py-2 text-center">
                                    <button onclick="deleteCongViec(<?= $cv['stt'] ?>)" 
                                            class="text-red-600 hover:text-red-800">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <?php endif; ?>

        <!-- Link đến báo cáo -->
        <div class="mt-6">
            <a href="baocao_kpi.php" class="bg-purple-500 text-white px-6 py-3 rounded hover:bg-purple-600 inline-block">
                <i class="fas fa-chart-bar"></i> Xem Báo Cáo KPI
            </a>
        </div>
    </div>

    <script>
        function showAddForm() {
            document.getElementById('addFormContainer').style.display = 'block';
        }

        function hideAddForm() {
            document.getElementById('addFormContainer').style.display = 'none';
            document.getElementById('formAddCongViec').reset();
        }

        function submitAddForm(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            
            fetch('congviec_suachua.php?action=create', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    window.location.reload();
                } else {
                    alert('❌ ' + data.message);
                }
            })
            .catch(err => {
                alert('❌ Lỗi: ' + err.message);
            });
            
            return false;
        }

        function deleteCongViec(stt) {
            if (!confirm('Bạn có chắc muốn xóa công việc này?')) return;
            
            const formData = new FormData();
            formData.append('stt', stt);
            
            fetch('congviec_suachua.php?action=delete', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    window.location.reload();
                } else {
                    alert('❌ ' + data.message);
                }
            });
        }
    </script>
</body>
</html>
