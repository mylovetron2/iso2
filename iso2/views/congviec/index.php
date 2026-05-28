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
                
                <!-- Danh sách hồ sơ nhúng vào JS -->
                <script>
                window.hosoList = <?= json_encode(array_map(function($hs) {
                    return [
                        'stt'   => (int)$hs['stt'],
                        'maql'  => $hs['maql'] ?? $hs['phieu'] ?? '',
                        'phieu' => $hs['phieu'] ?? '',
                        'mavt'  => $hs['mavt'] ?? '',
                        'somay' => $hs['somay'] ?? '',
                        'tenvt' => $hs['tenvt'] ?? '',
                    ];
                }, $formData['hososcbds']), JSON_UNESCAPED_UNICODE) ?>;
                </script>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Hồ sơ SC/BĐ -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-2">
                            Hồ sơ SC/BĐ <span class="text-red-500">*</span>
                        </label>
                        <input type="hidden" name="hososcbd_stt" id="selectedHosoStt" required>

                        <!-- Hiển thị hồ sơ đã chọn + nút mở search -->
                        <div class="flex gap-2 items-center">
                            <div id="hosoDisplay"
                                 class="flex-1 border rounded px-3 py-2 bg-gray-50 text-gray-500 min-h-[38px] cursor-pointer"
                                 onclick="openHosoSearch()">
                                -- Chưa chọn hồ sơ --
                            </div>
                            <button type="button" onclick="openHosoSearch()"
                                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded font-semibold flex items-center gap-1 whitespace-nowrap">
                                <i class="fas fa-search"></i> Chọn hồ sơ
                            </button>
                        </div>

                        <!-- Quick Search Panel -->
                        <div id="hosoSearchPanel" class="hidden mt-2 border-2 border-yellow-400 rounded-lg p-4 bg-yellow-50 shadow-lg">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-semibold text-yellow-700"><i class="fas fa-search mr-1"></i>Tìm hồ sơ SC/BĐ</span>
                                <span id="hosoResultCount" class="text-sm text-gray-600 bg-white px-3 py-1 rounded-full border border-yellow-300">
                                    <?= count($formData['hososcbds']) ?> hồ sơ
                                </span>
                            </div>
                            <div class="relative mb-2">
                                <input type="text" id="hosoSearchInput"
                                       placeholder="🔍 Gõ số phiếu, mã QL, mã vật tư, số máy..."
                                       class="w-full px-4 py-2 pl-10 pr-10 border-2 border-yellow-400 rounded-lg focus:outline-none focus:ring-2 focus:border-yellow-600"
                                       autocomplete="off"
                                       oninput="filterHosoResults(this.value)">
                                <i class="fas fa-search absolute left-3 top-3 text-yellow-500"></i>
                                <button type="button" onclick="closeHosoSearch()"
                                        class="absolute right-2 top-2 text-gray-500 hover:text-gray-700 bg-white px-2 py-1 rounded">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div id="hosoSearchResults" class="max-h-72 overflow-y-auto space-y-1">
                                <!-- populated by JS -->
                            </div>
                        </div>

                        <!-- KPI hồ sơ (hiện sau khi chọn) -->
                        <div id="hosoKpiInfo" class="hidden mt-2 flex items-center gap-3 bg-blue-50 border border-blue-200 rounded-lg px-4 py-2">
                            <i class="fas fa-clock text-blue-500 text-lg"></i>
                            <div>
                                <span class="text-xs text-gray-500 uppercase tracking-wide">KPI giờ chuẩn</span><br>
                                <span class="font-bold text-blue-700 text-lg" id="hosoKpiGio">6</span>
                                <span class="text-blue-600 text-sm">giờ</span>
                            </div>
                            <div class="ml-4 text-xs text-gray-400 italic">*(demo – sẽ cập nhật số liệu thật)</div>
                        </div>
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
        <?php
        $kpiGioChuan = 6; // demo – sẽ cập nhật số liệu thật
        function hieuSuatBadge(float $soGio, float $kpi): string {
            if ($kpi <= 0 || $soGio <= 0) return '<span class="text-gray-400">–</span>';
            // Hiệu suất = KPI / thực tế × 100%
            // VD: KPI=6h, làm 3h → 200% (vượt kế hoạch)
            $pct = round($kpi / $soGio * 100);
            if ($pct >= 100) {
                $cls = 'bg-green-100 text-green-700 border-green-300';
                $icon = '✔';
                $label = $pct > 100 ? "Vượt ({$pct}%)" : "Đạt ({$pct}%)";
            } elseif ($pct >= 80) {
                $cls = 'bg-blue-100 text-blue-700 border-blue-300';
                $icon = '↗';
                $label = "Gần đạt ({$pct}%)";
            } elseif ($pct >= 50) {
                $cls = 'bg-yellow-100 text-yellow-700 border-yellow-300';
                $icon = '↘';
                $label = "Chưa đạt ({$pct}%)";
            } else {
                $cls = 'bg-red-100 text-red-700 border-red-300';
                $icon = '✖';
                $label = "Kém ({$pct}%)";
            }
            return "<span class=\"inline-block border rounded-full px-2 py-0.5 text-xs font-bold {$cls}\">{$icon} {$label}</span>";
        }
        ?>
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
                                <th class="border px-4 py-2">Hồ sơ</th>
                                <th class="border px-4 py-2">Nội dung</th>
                                <th class="border px-4 py-2">Số giờ</th>
                                <th class="border px-4 py-2">Hiệu suất<br><span class="text-xs font-normal text-gray-500">KPI <?= $kpiGioChuan ?>h*</span></th>
                                <th class="border px-4 py-2">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $stt = 1;
                            foreach ($viewData['congviecs'] as $cv): 
                            ?>
                            <tr>
                                <td class="border px-4 py-2 text-center"><?= $stt++ ?></td>
                                <?php
                                    $cvMaqlParts = explode('-', $cv['maql'] ?? $cv['phieu'] ?? '');
                                    $cvMaqlNum   = end($cvMaqlParts);
                                ?>
                                <td class="border px-4 py-2 whitespace-nowrap">
                                    <span class="font-semibold"><?= htmlspecialchars($cvMaqlNum) ?></span>
                                    <?= htmlspecialchars($cv['mavt'] ?? '') ?>/ <?= htmlspecialchars($cv['somay'] ?? '') ?>
                                    – <span class="text-blue-600 text-xs">KPI <?= $kpiGioChuan ?>h</span>
                                </td>
                                <td class="border px-4 py-2">
                                    <?= nl2br(htmlspecialchars(substr($cv['noi_dung'], 0, 100))) ?>
                                    <?= strlen($cv['noi_dung']) > 100 ? '...' : '' ?>
                                </td>
                                <td class="border px-4 py-2 text-center">
                                    <strong><?= number_format($cv['so_gio_lam'], 2) ?>h</strong>
                                </td>
                                <td class="border px-4 py-2 text-center">
                                    <?= hieuSuatBadge((float)$cv['so_gio_lam'], $kpiGioChuan) ?>
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

        <!-- Danh sách công việc trong tháng -->
        <?php if ($nhanvienStt): ?>
        <div class="bg-white rounded-lg shadow-md p-6 mt-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold">
                    <i class="fas fa-calendar-alt"></i>
                    Danh Sách Công Việc Trong Tháng
                    <?= date_format(date_create($ngayLam) ?: date_create(), 'n/Y') ?>
                </h3>
                <?php if (!empty($thangData)): ?>
                <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm font-semibold">
                    Tổng: <?= number_format($tongGioThang, 2) ?>h /
                    <?= count($thangData) ?> công việc
                </span>
                <?php endif; ?>
            </div>

            <?php if (empty($thangData)): ?>
                <p class="text-gray-500 text-center py-8">
                    <i class="fas fa-inbox text-4xl mb-2"></i><br>
                    Chưa có công việc nào trong tháng này
                </p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full border text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border px-3 py-2 text-left w-10">STT</th>
                                <th class="border px-3 py-2 text-left">Ngày</th>
                                <th class="border px-3 py-2 text-left">Hồ sơ</th>
                                <th class="border px-3 py-2 text-left">Nội dung</th>
                                <th class="border px-3 py-2 text-center">Số giờ</th>
                                <th class="border px-3 py-2 text-center">Hiệu suất<br><span class="text-xs font-normal text-gray-500">KPI <?= $kpiGioChuan ?>h*</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sttThang = 1;
                            $prevNgay = '';
                            foreach ($thangData as $cv):
                                $isToday = ($cv['ngay_lam'] === $ngayLam);
                                $rowClass = $isToday ? 'bg-blue-50' : '';
                                $maqlRaw  = $cv['maql'] ?? $cv['phieu'] ?? '';
                                $maqlParts = explode('-', $maqlRaw);
                                $maqlNum  = end($maqlParts);
                            ?>
                            <tr class="<?= $rowClass ?> hover:bg-gray-50">
                                <td class="border px-3 py-1.5 text-center text-gray-500"><?= $sttThang++ ?></td>
                                <td class="border px-3 py-1.5 whitespace-nowrap font-medium <?= $isToday ? 'text-blue-700' : '' ?>">
                                    <?= date('d/m', strtotime($cv['ngay_lam'])) ?>
                                    <?php if ($isToday): ?><span class="text-xs text-blue-500">(hôm nay)</span><?php endif; ?>
                                </td>
                                <td class="border px-3 py-1.5 whitespace-nowrap">
                                    <span class="font-semibold"><?= htmlspecialchars($maqlNum) ?></span>
                                    <?= htmlspecialchars($cv['mavt'] ?? '') ?>/ <?= htmlspecialchars($cv['somay'] ?? '') ?>
                                    – <span class="text-blue-600 text-xs">KPI <?= $kpiGioChuan ?>h</span>
                                </td>
                                <td class="border px-3 py-1.5">
                                    <?= nl2br(htmlspecialchars(mb_substr($cv['noi_dung'], 0, 120))) ?>
                                    <?= mb_strlen($cv['noi_dung']) > 120 ? '…' : '' ?>
                                </td>
                                <td class="border px-3 py-1.5 text-center font-semibold">
                                    <?= number_format($cv['so_gio_lam'], 2) ?>h
                                </td>
                                <td class="border px-3 py-1.5 text-center">
                                    <?= hieuSuatBadge((float)$cv['so_gio_lam'], $kpiGioChuan) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-gray-100 font-bold">
                            <tr>
                                <td colspan="5" class="border px-3 py-2 text-right">Tổng giờ trong tháng:</td>
                                <td class="border px-3 py-2 text-center text-blue-700">
                                    <?= number_format($tongGioThang, 2) ?>h
                                </td>
                            </tr>
                        </tfoot>
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
            document.getElementById('selectedHosoStt').value = '';
            const display = document.getElementById('hosoDisplay');
            display.textContent = '-- Chưa chọn hồ sơ --';
            display.classList.add('text-gray-500');
            display.classList.remove('text-gray-800', 'font-medium');
            document.getElementById('hosoKpiInfo').classList.add('hidden');
            closeHosoSearch();
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

        // ===== Hồ sơ Quick Search =====
        function escapeHosoHtml(text) {
            const d = document.createElement('div');
            d.textContent = String(text);
            return d.innerHTML;
        }

        // "20260527-KTKT-2054" → "2054 - KTKT"
        function formatMaql(maql) {
            if (!maql) return '';
            const parts = maql.split('-');
            if (parts.length >= 3) {
                return parts[parts.length - 1] + ' - ' + parts[parts.length - 2];
            }
            return maql;
        }

        function highlightHoso(text, q) {
            if (!q) return escapeHosoHtml(text);
            const re = new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
            return escapeHosoHtml(text).replace(re, '<mark class="bg-yellow-300 font-semibold">$1</mark>');
        }

        function openHosoSearch() {
            const panel = document.getElementById('hosoSearchPanel');
            panel.classList.remove('hidden');
            const input = document.getElementById('hosoSearchInput');
            input.value = '';
            filterHosoResults('');
            setTimeout(() => input.focus(), 80);
        }

        function closeHosoSearch() {
            document.getElementById('hosoSearchPanel').classList.add('hidden');
        }

        function filterHosoResults(q) {
            const list = window.hosoList || [];
            const lower = q.toLowerCase().trim();
            const filtered = lower
                ? list.filter(h =>
                    String(h.stt).includes(lower) ||
                    h.maql.toLowerCase().includes(lower) ||
                    h.phieu.toLowerCase().includes(lower) ||
                    h.mavt.toLowerCase().includes(lower) ||
                    h.somay.toLowerCase().includes(lower) ||
                    h.tenvt.toLowerCase().includes(lower)
                  )
                : list;

            document.getElementById('hosoResultCount').textContent = filtered.length + ' hồ sơ';

            const container = document.getElementById('hosoSearchResults');
            if (filtered.length === 0) {
                container.innerHTML = '<div class="text-center py-6 text-gray-500"><i class="fas fa-inbox text-3xl mb-2"></i><br>Không tìm thấy hồ sơ nào</div>';
                return;
            }

            container.innerHTML = filtered.map(h => `
                <div class="bg-white border-2 border-gray-200 hover:border-green-500 hover:shadow-md rounded-lg px-3 py-2 cursor-pointer transition-all group"
                     onclick="selectHoso(${h.stt}, '${escapeHosoHtml(h.maql || h.phieu)}', '${escapeHosoHtml(h.mavt)}', '${escapeHosoHtml(h.somay)}', '${escapeHosoHtml(h.tenvt)}')">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="font-semibold text-green-700">${highlightHoso(formatMaql(h.maql || h.phieu), q)}</span>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400 group-hover:text-green-500 transition-colors"></i>
                    </div>
                    <div class="text-sm text-gray-600 mt-0.5">
                        ${highlightHoso(h.mavt, q)}
                        ${h.somay ? ' / SN: ' + highlightHoso(h.somay, q) : ''}
                        ${h.tenvt ? ' &nbsp;·&nbsp; ' + highlightHoso(h.tenvt, q) : ''}
                    </div>
                </div>
            `).join('');
        }

        function selectHoso(stt, maql, mavt, somay, tenvt) {
            document.getElementById('selectedHosoStt').value = stt;
            const label = formatMaql(maql) + (mavt ? ` | ${mavt}` : '') + (somay ? ` / SN:${somay}` : '') + (tenvt ? ` (${tenvt})` : '');
            const display = document.getElementById('hosoDisplay');
            display.textContent = label;
            display.classList.remove('text-gray-500');
            display.classList.add('text-gray-800', 'font-medium');
            // Hiện KPI (demo 6h)
            document.getElementById('hosoKpiInfo').classList.remove('hidden');
            closeHosoSearch();
        }

        // Đóng search panel khi click ngoài
        document.addEventListener('click', function(e) {
            const panel = document.getElementById('hosoSearchPanel');
            const btn = e.target.closest('[onclick="openHosoSearch()"]');
            const display = document.getElementById('hosoDisplay');
            if (panel && !panel.classList.contains('hidden') && !panel.contains(e.target) && e.target !== display && !btn) {
                closeHosoSearch();
            }
        });
    </script>
</body>
</html>
