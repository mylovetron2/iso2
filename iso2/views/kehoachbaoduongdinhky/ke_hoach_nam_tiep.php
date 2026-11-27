<?php
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
require_once __DIR__ . '/../../includes/permissions.php';
$title = 'Lập kế hoạch bảo dưỡng định kỳ năm ' . $namKeHoach;
require_once __DIR__ . '/../layouts/header.php';

$canEdit = hasPermission('kehoachbaoduong.edit');
$canAdd = $canEdit || hasPermission('kehoachbaoduong.create');
$canDelete = ($_SESSION['username'] ?? '') === 'admin';
$quarterColors = [1 => '#16a34a', 2 => '#ca8a04', 3 => '#ea580c', 4 => '#dc2626'];
$formatDate = static function ($date): string {
    $date = trim((string)$date);
    if ($date === '' || strpos($date, '0000') === 0) {
        return '';
    }
    $time = strtotime($date);
    return $time ? date('d/m/Y', $time) : '';
};
$formatYear = static function ($date): string {
    $date = trim((string)$date);
    $time = $date === '' || strpos($date, '0000') === 0 ? false : strtotime($date);
    $year = $time ? (int)date('Y', $time) : 0;
    return $year > 1970 ? (string)$year : '';
};
$renderUnitSelect = static function (string $class, string $current, array $options): string {
    $current = trim($current);
    if ($current !== '' && !in_array($current, $options, true)) {
        $options[] = $current;
    }
    $html = '<select class="' . $class . ' w-full border border-gray-300 rounded px-1 py-1 text-sm"><option value="">--</option>';
    foreach ($options as $option) {
        $selected = $option === $current ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars($option, ENT_QUOTES, 'UTF-8') . '"' . $selected . '>'
            . htmlspecialchars($option, ENT_QUOTES, 'UTF-8') . '</option>';
    }
    return $html . '</select>';
};
?>

<style>
    #plan-table td, #plan-table th { white-space: nowrap; }
    #plan-table td.name-cell { white-space: normal; overflow-wrap: anywhere; min-width: 240px; max-width: 300px; }
    #plan-table th.q-col, #plan-table td.q-cell { width: 110px; min-width: 110px; text-align: center; padding: 4px; }
    .q-wrap { display: flex; align-items: center; gap: 4px; justify-content: center; }
    .ref-mark { width: 22px; text-align: center; flex: none; font-size: 0.9rem; }
    .q-btn {
        flex: 1; height: 28px; border-radius: 4px; font-size: 0.8rem; font-weight: 600;
        border: 1px dashed #d1d5db; background: #fff; color: #9ca3af; cursor: pointer;
    }
    .q-btn:disabled { cursor: default; opacity: 0.6; }
    .q-btn:not(:disabled):hover { border-color: #6b7280; color: #374151; }
    .q-btn.active { border-style: solid; color: #fff; }
    .q-btn.q1.active { background: #16a34a; border-color: #16a34a; }
    .q-btn.q2.active { background: #ca8a04; border-color: #ca8a04; }
    .q-btn.q3.active { background: #ea580c; border-color: #ea580c; }
    .q-btn.q4.active { background: #dc2626; border-color: #dc2626; }
    #plan-table tr.saving { opacity: 0.6; }
    #plan-table tr.pending .q-btn.active { border-style: dashed; opacity: 0.75; }
    #plan-table tr.just-added { background-color: #d1fae5; }
</style>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold flex items-center">
            <i class="fas fa-calendar-plus mr-2 text-blue-600"></i>
            Lập kế hoạch bảo dưỡng định kỳ năm <?php echo (int)$namKeHoach; ?>
        </h1>
        <a href="kehoachbaoduongdinhky.php?nam=<?php echo (int)$namThamKhao; ?>" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Kế hoạch <?php echo (int)$namThamKhao; ?>
        </a>
    </div>

    <form method="GET" class="bg-white rounded-lg shadow p-4 mb-4">
        <div class="flex flex-col gap-4 md:flex-row md:items-end">
            <div class="flex-1 min-w-0">
                <label class="block text-sm font-medium mb-1">Tìm kiếm:</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                       placeholder="Tên thiết bị hoặc số S/N..." class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Kế hoạch <?php echo (int)$namKeHoach; ?>:</label>
                <select name="trangthai" class="border rounded px-3 py-2" onchange="this.form.submit()">
                    <option value="" <?php echo $trangthai === '' ? 'selected' : ''; ?>>Tất cả</option>
                    <option value="chualap" <?php echo $trangthai === 'chualap' ? 'selected' : ''; ?>>Chưa chọn quý</option>
                    <option value="dalap" <?php echo $trangthai === 'dalap' ? 'selected' : ''; ?>>Đã chọn quý</option>
                </select>
            </div>
            <div class="flex shrink-0">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded mr-2">
                    <i class="fas fa-search mr-1"></i> Tìm
                </button>
                <a href="kehoachbaoduong2027.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                    <i class="fas fa-redo mr-1"></i> Reset
                </a>
            </div>
        </div>
    </form>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-4">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
            <p class="text-sm text-blue-600 font-medium">Tổng thiết bị</p>
            <p class="text-2xl font-bold text-blue-800"><?php echo number_format($total); ?></p>
        </div>
        <?php foreach ($quarterColors as $q => $color): ?>
        <div class="rounded-lg p-3 border" style="border-color:<?php echo $color; ?>;background:<?php echo $color; ?>15">
            <p class="text-sm font-medium" style="color:<?php echo $color; ?>">Quý <?php echo $q; ?>/<?php echo (int)$namKeHoach; ?></p>
            <p class="text-2xl font-bold" style="color:<?php echo $color; ?>" id="total-q<?php echo $q; ?>"><?php echo (int)$quarterTotals[$q]; ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="mb-4 text-sm text-gray-600 flex flex-wrap items-center gap-4">
        <span><i class="fas fa-check text-blue-600"></i> Đã hoàn thành năm <?php echo (int)$namThamKhao; ?></span>
        <span>Bấm vào ô Q1–Q4 để chọn quý kế hoạch <?php echo (int)$namKeHoach; ?> (chọn được nhiều quý, mỗi quý một màu).</span>
        <span>Quý đã có tích năm <?php echo (int)$namThamKhao; ?> được chọn sẵn (viền nét đứt = chưa lưu).</span>
        <?php if ($canEdit && $pendingCount > 0): ?>
            <button type="button" id="apply-defaults" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1 rounded text-sm">
                <i class="fas fa-save mr-1"></i> Lưu mặc định cho <span id="pending-count"><?php echo (int)$pendingCount; ?></span> thiết bị
            </button>
        <?php endif; ?>
        <?php if ($canAdd): ?>
            <button type="button" id="add-row" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded text-sm">
                <i class="fas fa-plus mr-1"></i> Thêm dòng
            </button>
        <?php endif; ?>
        <?php if (!$canEdit): ?>
            <span class="text-red-600 font-semibold">Bạn chỉ có quyền xem.</span>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table id="plan-table" class="w-full divide-y divide-gray-200" style="width:max-content;">
                <thead class="bg-gray-50">
                    <tr class="text-xs font-medium text-gray-500 uppercase">
                        <th class="px-2 py-2 text-left">Tên thiết bị</th>
                        <th class="px-2 py-2 text-left">Số S/N</th>
                        <th class="px-2 py-2 text-left border-l border-gray-300">Năm SX</th>
                        <?php foreach ($quarterColors as $q => $color): ?>
                            <th class="q-col border-l border-gray-300">Q<?php echo $q; ?></th>
                        <?php endforeach; ?>
                        <th class="px-2 py-2 text-left border-l border-gray-300" title="Ngày thực hiện năm <?php echo (int)$namThamKhao; ?>">Ngày <?php echo (int)$namThamKhao; ?></th>
                        <th class="px-2 py-2 text-left border-l border-gray-300" style="min-width:110px">ĐV Chính</th>
                        <th class="px-2 py-2 text-left border-l border-gray-300" style="min-width:110px">ĐV Phụ</th>
                        <th class="px-2 py-2 text-left border-l border-gray-300" style="min-width:200px">Ghi chú</th>
                        <?php if ($canDelete): ?>
                            <th class="px-2 py-2 text-center border-l border-gray-300">Thao tác</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($canAdd): ?>
                    <tr id="new-row" class="bg-emerald-50" style="display:none">
                        <td class="px-2 py-1">
                            <input type="text" id="new-name" maxlength="255" placeholder="Tên thiết bị *" class="w-full border border-gray-300 rounded px-2 py-1 text-sm" style="min-width:240px">
                        </td>
                        <td class="px-2 py-1">
                            <input type="text" id="new-serial" maxlength="100" placeholder="Số S/N" class="w-full border border-gray-300 rounded px-2 py-1 text-sm" style="min-width:120px">
                        </td>
                        <td class="px-2 py-1 border-l border-gray-300">
                            <input type="number" id="new-year" min="1000" max="9999" step="1"
                                   placeholder="Năm SX" aria-label="Năm sản xuất"
                                   class="w-full border border-gray-300 rounded px-2 py-1 text-sm text-center">
                        </td>
                        <?php foreach ($quarterColors as $q => $color): ?>
                            <td class="q-cell border-l border-gray-300">
                                <div class="q-wrap">
                                    <span class="ref-mark"></span>
                                    <button type="button" class="q-btn q<?php echo $q; ?>" data-q="<?php echo $q; ?>" aria-pressed="false">Q<?php echo $q; ?></button>
                                </div>
                            </td>
                        <?php endforeach; ?>
                        <td class="px-2 py-1 border-l border-gray-300">
                            <button type="button" id="new-save" class="bg-emerald-600 hover:bg-emerald-700 text-white px-2 py-1 rounded text-sm" title="Lưu dòng mới"><i class="fas fa-check"></i> Lưu</button>
                            <button type="button" id="new-cancel" class="bg-gray-400 hover:bg-gray-500 text-white px-2 py-1 rounded text-sm" title="Hủy"><i class="fas fa-times"></i></button>
                        </td>
                        <td class="px-2 py-1 border-l border-gray-300"><?php echo $renderUnitSelect('new-chinh w-full', '', $donViOptions); ?></td>
                        <td class="px-2 py-1 border-l border-gray-300"><?php echo $renderUnitSelect('new-phu w-full', '', $donViOptions); ?></td>
                        <td class="px-2 py-1 border-l border-gray-300">
                            <input type="text" id="new-note" maxlength="255" placeholder="Ghi chú" class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                        </td>
                        <?php if ($canDelete): ?>
                            <td class="border-l border-gray-300"></td>
                        <?php endif; ?>
                    </tr>
                <?php endif; ?>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="<?php echo $canDelete ? 12 : 11; ?>" class="px-4 py-8 text-center text-gray-500">Không có thiết bị nào.</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $row):
                    $ref = $row['ref'];
                    $plan = $row['plan'];
                    $source = $plan ?? $ref;
                    $donViChinh = (string)($plan['donvi_lam_chinh'] ?? $ref['donvi_lam_chinh'] ?? '');
                    $donViPhu = (string)($plan['donvi_lam_phu'] ?? $ref['donvi_lam_phu'] ?? '');
                    $ghiChu = (string)($plan['ghi_chu'] ?? $ref['ghi_chu'] ?? '');
                    ?>
                    <tr<?php if ((int)($plan['id'] ?? 0) > 0): ?> id="plan-row-<?php echo (int)$plan['id']; ?>"<?php endif; ?>
                        class="plan-row hover:bg-gray-50<?php echo $row['pending'] ? ' pending' : ''; ?>"
                        data-ref-id="<?php echo (int)($ref['id'] ?? 0); ?>"
                        data-plan-id="<?php echo (int)($plan['id'] ?? 0); ?>">
                        <td class="name-cell px-2 py-1 text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars((string)$source['ten_thietbi']); ?>
                            <?php if ($ref === null): ?>
                                <span class="ml-1 text-xs text-gray-500">(không có trong KH <?php echo (int)$namThamKhao; ?>)</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-2 py-1 text-sm font-mono text-blue-700"><?php echo htmlspecialchars((string)($source['so_serial'] ?? '')); ?></td>
                        <td class="px-2 py-1 text-sm text-gray-700 text-center border-l border-gray-300"><?php echo $formatYear($source['ngayktsd'] ?? ''); ?></td>
                        <?php foreach ($quarterColors as $q => $color):
                            $refDone = $ref !== null && !empty($ref['qui_' . $q . '_hoantat']);
                            $active = $row['quarters'][$q];
                            ?>
                            <td class="q-cell border-l border-gray-300">
                                <div class="q-wrap">
                                    <span class="ref-mark" title="Năm <?php echo (int)$namThamKhao; ?>: đã hoàn thành">
                                        <?php if ($refDone): ?>
                                            <i class="fas fa-check text-blue-600"></i>
                                        <?php endif; ?>
                                    </span>
                                    <button type="button" class="q-btn q<?php echo $q; ?><?php echo $active ? ' active' : ''; ?>"
                                            data-q="<?php echo $q; ?>" aria-pressed="<?php echo $active ? 'true' : 'false'; ?>"
                                            <?php echo $canEdit ? '' : 'disabled'; ?>>Q<?php echo $q; ?></button>
                                </div>
                            </td>
                        <?php endforeach; ?>
                        <td class="px-2 py-1 text-sm text-gray-600 border-l border-gray-300">
                            <?php echo htmlspecialchars($formatDate($ref['ngay_thuc_hien'] ?? '')); ?>
                        </td>
                        <td class="px-2 py-1 border-l border-gray-300">
                            <?php echo $renderUnitSelect('unit-select unit-chinh', $donViChinh, $donViOptions); ?>
                        </td>
                        <td class="px-2 py-1 border-l border-gray-300">
                            <?php echo $renderUnitSelect('unit-select unit-phu', $donViPhu, $donViOptions); ?>
                        </td>
                        <td class="px-2 py-1 border-l border-gray-300">
                            <input type="text" maxlength="255" value="<?php echo htmlspecialchars($ghiChu, ENT_QUOTES, 'UTF-8'); ?>"
                                   class="note-input w-full border border-gray-300 rounded px-2 py-1 text-sm" style="min-width:200px"
                                   <?php echo $canEdit ? '' : 'disabled'; ?>>
                        </td>
                        <?php if ($canDelete): ?>
                            <td class="px-2 py-1 text-center border-l border-gray-300">
                                <?php if ((int)($plan['id'] ?? 0) > 0): ?>
                                    <button type="button" class="delete-plan-row text-red-600 hover:text-red-800"
                                            data-plan-id="<?php echo (int)$plan['id']; ?>"
                                            title="Xóa kế hoạch năm <?php echo (int)$namKeHoach; ?>"
                                            aria-label="Xóa kế hoạch năm <?php echo (int)$namKeHoach; ?>">
                                        <i class="fas fa-trash" aria-hidden="true"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function () {
    const csrfToken = <?php echo json_encode($csrfToken); ?>;
    const endpoint = 'kehoachbaoduong2027.php?action=';
    const canEdit = <?php echo $canEdit ? 'true' : 'false'; ?>;
    const newPlanRowId = window.location.hash.slice(1);
    if (newPlanRowId.startsWith('plan-row-')) {
        const newPlanRow = document.getElementById(newPlanRowId);
        if (newPlanRow) {
            requestAnimationFrame(() => newPlanRow.scrollIntoView({ behavior: 'smooth', block: 'center' }));
            newPlanRow.classList.add('just-added');
            window.setTimeout(() => newPlanRow.classList.remove('just-added'), 4000);
            window.history.replaceState(null, '', window.location.pathname + window.location.search);
        }
    }

    document.querySelectorAll('.delete-plan-row').forEach(button => {
        button.addEventListener('click', async () => {
            if (!confirm('Xóa dòng kế hoạch năm <?php echo (int)$namKeHoach; ?> này? Dữ liệu tham khảo năm <?php echo (int)$namThamKhao; ?> sẽ được giữ lại.')) {
                return;
            }
            button.disabled = true;
            try {
                await post('deleteRow', { plan_id: parseInt(button.dataset.planId, 10) });
                window.location.reload();
            } catch (error) {
                button.disabled = false;
                alert(error.message);
            }
        });
    });

    function updateTotals() {
        for (let q = 1; q <= 4; q++) {
            document.getElementById('total-q' + q).textContent =
                document.querySelectorAll('.plan-row .q-btn.q' + q + '.active').length;
        }
    }

    function setRowBusy(row, busy) {
        row.classList.toggle('saving', busy);
        row.querySelectorAll('.q-btn, .unit-select, .note-input').forEach(el => { el.disabled = busy || !canEdit; });
    }

    async function post(action, payload) {
        const response = await fetch(endpoint + action, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
            body: JSON.stringify(payload)
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Không thể lưu, vui lòng thử lại');
        }
        return data;
    }

    function rowIds(row) {
        return { ref_id: parseInt(row.dataset.refId, 10) || 0, plan_id: parseInt(row.dataset.planId, 10) || 0 };
    }

    function rowQuarters(row, override) {
        const quarters = {};
        row.querySelectorAll('.q-btn').forEach(btn => {
            const q = parseInt(btn.dataset.q, 10);
            quarters[q] = override && override.q === q ? override.selected : btn.classList.contains('active');
        });
        return quarters;
    }

    function markSaved(row, data) {
        row.dataset.planId = data.plan_id;
        if (row.classList.contains('pending')) {
            row.classList.remove('pending');
            updatePendingCount();
        }
    }

    function updatePendingCount() {
        const count = document.querySelectorAll('.plan-row.pending').length;
        const counter = document.getElementById('pending-count');
        if (counter) { counter.textContent = count; }
        const button = document.getElementById('apply-defaults');
        if (button && count === 0) { button.remove(); }
    }

    document.querySelectorAll('.plan-row .q-btn').forEach(button => {
        button.addEventListener('click', async () => {
            const row = button.closest('.plan-row');
            const selected = !button.classList.contains('active');
            setRowBusy(row, true);
            try {
                const data = await post('saveQuarter', Object.assign(rowIds(row), {
                    quarters: rowQuarters(row, { q: parseInt(button.dataset.q, 10), selected })
                }));
                markSaved(row, data);
                button.classList.toggle('active', selected);
                button.setAttribute('aria-pressed', selected ? 'true' : 'false');
                updateTotals();
            } catch (error) {
                alert(error.message);
            } finally {
                setRowBusy(row, false);
            }
        });
    });

    document.querySelectorAll('.plan-row .unit-select').forEach(select => {
        let previous = select.value;
        select.addEventListener('change', async () => {
            const row = select.closest('.plan-row');
            setRowBusy(row, true);
            try {
                const data = await post('saveDonVi', Object.assign(rowIds(row), {
                    quarters: rowQuarters(row),
                    donvi_lam_chinh: row.querySelector('.unit-chinh').value,
                    donvi_lam_phu: row.querySelector('.unit-phu').value
                }));
                markSaved(row, data);
                previous = select.value;
            } catch (error) {
                select.value = previous;
                alert(error.message);
            } finally {
                setRowBusy(row, false);
            }
        });
    });

    document.querySelectorAll('.plan-row .note-input').forEach(input => {
        let saved = input.value;
        input.addEventListener('change', async () => {
            const row = input.closest('.plan-row');
            const value = input.value;
            setRowBusy(row, true);
            try {
                const data = await post('saveGhiChu', Object.assign(rowIds(row), {
                    quarters: rowQuarters(row),
                    ghi_chu: value
                }));
                markSaved(row, data);
                saved = value;
            } catch (error) {
                input.value = saved;
                alert(error.message);
            } finally {
                setRowBusy(row, false);
            }
        });
    });

    const newRow = document.getElementById('new-row');
    if (newRow) {
        const nameInput = document.getElementById('new-name');
        const serialInput = document.getElementById('new-serial');
        const yearInput = document.getElementById('new-year');
        const saveButton = document.getElementById('new-save');

        newRow.querySelectorAll('.q-btn').forEach(button => {
            button.addEventListener('click', () => {
                const selected = !button.classList.contains('active');
                button.classList.toggle('active', selected);
                button.setAttribute('aria-pressed', selected ? 'true' : 'false');
            });
        });

        document.getElementById('add-row').addEventListener('click', () => {
            newRow.style.display = '';
            nameInput.focus();
        });
        document.getElementById('new-cancel').addEventListener('click', () => {
            newRow.style.display = 'none';
        });
        newRow.addEventListener('keydown', event => {
            if (event.key === 'Enter') { event.preventDefault(); saveButton.click(); }
            if (event.key === 'Escape') { document.getElementById('new-cancel').click(); }
        });

        saveButton.addEventListener('click', async () => {
            const name = nameInput.value.trim();
            if (!name) {
                alert('Vui lòng nhập tên thiết bị');
                nameInput.focus();
                return;
            }
            saveButton.disabled = true;
            try {
                const data = await post('addRow', {
                    ten_thietbi: name,
                    so_serial: serialInput.value.trim(),
                    nam_sx: yearInput.value.trim(),
                    quarters: rowQuarters(newRow),
                    donvi_lam_chinh: newRow.querySelector('.new-chinh').value,
                    donvi_lam_phu: newRow.querySelector('.new-phu').value,
                    ghi_chu: document.getElementById('new-note').value.trim()
                });
                const url = new URL(window.location.href);
                url.searchParams.delete('search');
                url.searchParams.delete('trangthai');
                url.hash = 'plan-row-' + data.plan_id;
                window.location.assign(url.toString());
            } catch (error) {
                saveButton.disabled = false;
                alert(error.message);
            }
        });
    }

    const applyButton = document.getElementById('apply-defaults');
    if (applyButton) {
        applyButton.addEventListener('click', async () => {
            applyButton.disabled = true;
            try {
                await post('applyDefaults', { search: <?php echo json_encode($search); ?> });
                window.location.reload();
            } catch (error) {
                applyButton.disabled = false;
                alert(error.message);
            }
        });
    }
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
