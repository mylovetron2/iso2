<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

// Load models
require_once __DIR__ . '/../../models/HoSoSCBD.php';
$model = new HoSoSCBD();

// Get selected record ID from URL or session
$stt = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Load hososcbd list for combobox (only unfinished records - chưa kết thúc)
$db = getDBConnection();
$stmtList = $db->query("
    SELECT h.stt, h.phieu, h.mavt, h.somay, h.cv, h.ngayyc, h.nhomsc, d.tendv
    FROM hososcbd_iso h
    LEFT JOIN donvi_iso d ON h.madv = d.madv
    WHERE h.ngaykt IS NULL OR h.ngaykt = '0000-00-00'
    ORDER BY h.ngayyc DESC, h.phieu DESC
    LIMIT 100
");
$hososcbdList = $stmtList->fetchAll(PDO::FETCH_ASSOC);

// Load selected item if ID provided
$item = null;
if ($stt) {
    $item = $model->findById($stt);
}

require_once __DIR__ . '/../layouts/header.php'; 
?>

<!-- Choices.js CSS for searchable select -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css">

<!-- Mobile Optimized Styles -->
<style>
    /* Mobile-first responsive design */
    .mobile-container {
        max-width: 100%;
        padding: 0.5rem;
    }
    
    .mobile-header {
        position: sticky;
        top: 0;
        z-index: 100;
        background: white;
        padding: 0.75rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 1rem;
    }
    
    .mobile-select {
        width: 100%;
        padding: 0.75rem;
        font-size: 1rem;
        border: 2px solid #9333ea;
        border-radius: 0.5rem;
        background-color: white;
    }
    
    /* Choices.js customization for mobile */
    .choices {
        margin-bottom: 0;
    }
    
    .choices__inner {
        background: white;
        border: 2px solid #9333ea !important;
        border-radius: 0.5rem;
        padding: 0.75rem;
        font-size: 1rem;
        min-height: 3rem;
    }
    
    .choices__list--dropdown {
        z-index: 200;
        border: 2px solid #9333ea;
        border-radius: 0.5rem;
        margin-top: 0.25rem;
    }
    
    .choices__list--dropdown .choices__item--selectable {
        padding: 0.75rem;
        font-size: 0.95rem;
    }
    
    .choices__input {
        background: white;
        font-size: 1rem;
        padding: 0.5rem;
    }
    
    .choices[data-type*=select-one] .choices__input {
        background: white;
        border-bottom: 1px solid #e5e7eb;
        margin-bottom: 0.5rem;
    }
    
    .choices__list--dropdown .choices__item--selectable.is-highlighted {
        background-color: #9333ea;
    }
    
    .mobile-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 1rem;
        border-radius: 0.75rem;
        color: white;
        margin-bottom: 1rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .mobile-info-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.5rem;
        margin-top: 0.75rem;
    }
    
    .mobile-info-item {
        display: flex;
        align-items: center;
        padding: 0.5rem;
        background: rgba(255,255,255,0.1);
        border-radius: 0.5rem;
        backdrop-filter: blur(10px);
    }
    
    .mobile-info-label {
        font-weight: 600;
        margin-right: 0.5rem;
        min-width: 80px;
        font-size: 0.875rem;
    }
    
    .mobile-info-value {
        font-size: 0.875rem;
        flex: 1;
    }
    
    .mobile-widget {
        background: white;
        border-radius: 0.75rem;
        padding: 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #6b7280;
    }
    
    .empty-state-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
    
    /* Responsive table for mobile */
    @media (max-width: 768px) {
        .mobile-container {
            padding: 0.25rem;
        }
        
        /* Make tables scroll horizontally on mobile */
        .mobile-widget table {
            display: block;
            overflow-x: auto;
            white-space: nowrap;
            font-size: 0.875rem;
        }
        
        .mobile-widget td, 
        .mobile-widget th {
            padding: 0.5rem 0.25rem !important;
        }
        
        /* Stack modals vertically on mobile */
        .mobile-widget .fixed {
            padding: 0.5rem;
        }
        
        .mobile-widget .max-w-2xl {
            max-width: 100% !important;
            margin: 0;
        }
    }
    
    /* Loading spinner */
    .loader {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #9333ea;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
        margin: 2rem auto;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<div class="mobile-container">
    <!-- Sticky Header with Select -->
    <div class="mobile-header">
        <div class="flex items-center justify-between mb-3">
            <h1 class="text-xl font-bold text-purple-700 flex items-center">
                <i class="fas fa-mobile-alt mr-2"></i>
                Công việc Mobile
            </h1>
            <a href="hososcbd.php" class="text-purple-600 hover:text-purple-800">
                <i class="fas fa-desktop text-xl"></i>
            </a>
        </div>
        
        <select id="hososcbdSelect" class="mobile-select" onchange="loadCongViec(this.value)">
            <option value="">-- Chọn hồ sơ SC/BĐ --</option>
            <?php foreach ($hososcbdList as $hs): ?>
                <option value="<?= $hs['stt'] ?>" 
                        data-phieu="<?= htmlspecialchars($hs['phieu']) ?>"
                        data-mavt="<?= htmlspecialchars($hs['mavt']) ?>"
                        data-somay="<?= htmlspecialchars($hs['somay'] ?? '') ?>"
                        data-cv="<?= htmlspecialchars($hs['cv']) ?>"
                        <?= ($stt == $hs['stt']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($hs['phieu']) ?> - 
                    <?= htmlspecialchars($hs['mavt']) ?>
                    <?php if (!empty($hs['somay'])): ?>
                        (#<?= htmlspecialchars($hs['somay']) ?>)
                    <?php endif; ?>
                    - <?= htmlspecialchars(mb_substr($hs['cv'], 0, 30)) ?>...
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Selected Item Info Card -->
    <?php if ($item): ?>
    <div class="mobile-card">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold flex items-center">
                <i class="fas fa-file-alt mr-2"></i>
                Thông tin hồ sơ
            </h2>
            <span class="bg-white text-purple-700 px-2 py-1 rounded text-xs font-bold">
                #<?= $item['stt'] ?>
            </span>
        </div>
        
        <div class="mobile-info-grid">
            <div class="mobile-info-item">
                <span class="mobile-info-label">
                    <i class="fas fa-file-invoice mr-1"></i>Phiếu:
                </span>
                <span class="mobile-info-value font-bold">
                    <?= htmlspecialchars($item['phieu']) ?>
                </span>
            </div>
            
            <div class="mobile-info-item">
                <span class="mobile-info-label">
                    <i class="fas fa-cog mr-1"></i>Thiết bị:
                </span>
                <span class="mobile-info-value">
                    <?= htmlspecialchars($item['mavt']) ?>
                    <?php if (!empty($item['somay'])): ?>
                        <span class="text-yellow-200"> - #<?= htmlspecialchars($item['somay']) ?></span>
                    <?php endif; ?>
                </span>
            </div>
            
            <div class="mobile-info-item">
                <span class="mobile-info-label">
                    <i class="fas fa-clipboard-list mr-1"></i>Công việc:
                </span>
                <span class="mobile-info-value">
                    <?= htmlspecialchars($item['cv']) ?>
                </span>
            </div>
            
            <?php if (!empty($item['tendv'])): ?>
            <div class="mobile-info-item">
                <span class="mobile-info-label">
                    <i class="fas fa-building mr-1"></i>Đơn vị:
                </span>
                <span class="mobile-info-value">
                    <?= htmlspecialchars($item['tendv']) ?>
                </span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($item['ngayyc'])): ?>
            <div class="mobile-info-item">
                <span class="mobile-info-label">
                    <i class="fas fa-calendar mr-1"></i>Ngày YC:
                </span>
                <span class="mobile-info-value">
                    <?= date('d/m/Y', strtotime($item['ngayyc'])) ?>
                </span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Congviec Widget Container -->
    <div class="mobile-widget" id="congviecWidget">
        <?php 
        // Include the congviec widget 
        include __DIR__ . '/components/congviec_widget_mobile.php'; 
        ?>
    </div>
    
    <?php else: ?>
    <!-- Empty State -->
    <div class="empty-state">
        <div class="empty-state-icon">
            <i class="fas fa-clipboard-list"></i>
        </div>
        <h3 class="text-lg font-semibold mb-2">Chọn hồ sơ để xem công việc</h3>
        <p class="text-sm">Vui lòng chọn một hồ sơ SC/BĐ từ danh sách bên trên</p>
    </div>
    <?php endif; ?>
</div>

<!-- Choices.js for searchable select -->
<script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>

<!-- AJAX Loading Script -->
<script>
// Initialize Choices.js for searchable select
const selectElement = document.getElementById('hososcbdSelect');
const choices = new Choices(selectElement, {
    searchEnabled: true,
    searchPlaceholderValue: 'Tìm kiếm theo phiếu, thiết bị, số máy...',
    itemSelectText: 'Nhấn để chọn',
    noResultsText: 'Không tìm thấy kết quả',
    noChoicesText: 'Không có lựa chọn nào',
    position: 'bottom',
    searchResultLimit: 50,
    shouldSort: false,
    removeItemButton: false,
    placeholder: true,
    placeholderValue: '-- Chọn hồ sơ SC/BĐ --',
    searchFields: ['label', 'customProperties.phieu', 'customProperties.mavt', 'customProperties.somay', 'customProperties.cv'],
    fuseOptions: {
        threshold: 0.4,
        distance: 500,
        keys: [
            { name: 'label', weight: 0.3 },
            { name: 'customProperties.phieu', weight: 0.3 },
            { name: 'customProperties.mavt', weight: 0.2 },
            { name: 'customProperties.somay', weight: 0.1 },
            { name: 'customProperties.cv', weight: 0.1 }
        ]
    }
});

function loadCongViec(stt) {
    if (!stt) {
        window.location.href = 'hososcbd_congviec_mobile.php';
        return;
    }
    
    // Redirect with ID parameter
    window.location.href = 'hososcbd_congviec_mobile.php?id=' + stt;
}

// Add pull-to-refresh functionality for mobile
let touchstartY = 0;
let touchendY = 0;

document.addEventListener('touchstart', e => {
    touchstartY = e.changedTouches[0].screenY;
}, {passive: true});

document.addEventListener('touchend', e => {
    touchendY = e.changedTouches[0].screenY;
    
    // If pull down from top > 100px, reload
    if (window.scrollY === 0 && touchendY > touchstartY + 100) {
        location.reload();
    }
}, {passive: true});

// Show loading indicator when navigating
window.addEventListener('beforeunload', function() {
    document.body.insertAdjacentHTML('beforeend', '<div class="loader"></div>');
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
