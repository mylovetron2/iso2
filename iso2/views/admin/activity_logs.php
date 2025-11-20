<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/ActivityLogger.php';
require_once __DIR__ . '/../../config/database.php';

// Check if user is logged in and has admin role
requireLogin();
if (!hasRole('admin')) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$db = getDBConnection();
$logger = new ActivityLogger($db);

// Get filter parameters
$filterTable = $_GET['table'] ?? '';
$filterAction = $_GET['action'] ?? '';
$filterUserId = $_GET['user_id'] ?? '';
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo = $_GET['date_to'] ?? '';

// Build filters array
$filters = [];
if ($filterTable) $filters['table_name'] = $filterTable;
if ($filterAction) $filters['action'] = $filterAction;
if ($filterUserId) $filters['user_id'] = (int)$filterUserId;
if ($filterDateFrom) $filters['date_from'] = $filterDateFrom;
if ($filterDateTo) $filters['date_to'] = $filterDateTo;

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Get logs and total count
$logs = $logger->getLogs($limit, $offset, $filters);
$totalLogs = $logger->countLogs($filters);
$totalPages = max(1, (int)ceil($totalLogs / $limit));

// Get unique tables for filter dropdown
$tablesStmt = $db->query("SELECT DISTINCT table_name FROM activity_logs ORDER BY table_name");
$tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

// Get users for filter dropdown
$usersStmt = $db->query("SELECT DISTINCT user_id, username FROM activity_logs ORDER BY username");
$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Nhật ký hoạt động';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">
            <i class="fas fa-history mr-2"></i>Nhật ký hoạt động
        </h1>
        <p class="text-gray-600 mt-2">Theo dõi tất cả các thao tác trên hệ thống</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
            <!-- Table Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Bảng</label>
                <select name="table" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                    <option value="">Tất cả</option>
                    <?php foreach ($tables as $table): ?>
                        <option value="<?= htmlspecialchars($table) ?>" <?= $filterTable === $table ? 'selected' : '' ?>>
                            <?= htmlspecialchars($table) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Action Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Hành động</label>
                <select name="action" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                    <option value="">Tất cả</option>
                    <option value="INSERT" <?= $filterAction === 'INSERT' ? 'selected' : '' ?>>INSERT</option>
                    <option value="UPDATE" <?= $filterAction === 'UPDATE' ? 'selected' : '' ?>>UPDATE</option>
                    <option value="DELETE" <?= $filterAction === 'DELETE' ? 'selected' : '' ?>>DELETE</option>
                </select>
            </div>

            <!-- User Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Người dùng</label>
                <select name="user_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                    <option value="">Tất cả</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['user_id'] ?>" <?= $filterUserId == $user['user_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['username']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Date From -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Từ ngày</label>
                <input type="date" name="date_from" value="<?= htmlspecialchars($filterDateFrom) ?>" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Date To -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Đến ngày</label>
                <input type="date" name="date_to" value="<?= htmlspecialchars($filterDateTo) ?>" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Submit Buttons -->
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    <i class="fas fa-filter mr-1"></i>Lọc
                </button>
                <a href="activity_logs.php" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">
                    <i class="fas fa-redo mr-1"></i>Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Tổng số log</div>
            <div class="text-2xl font-bold text-gray-800"><?= number_format($totalLogs) ?></div>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Người dùng</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bảng</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hành động</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Record ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Chi tiết</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-2"></i>
                                <p>Không có dữ liệu</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-900"><?= $log['id'] ?></td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    <?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <span class="font-medium"><?= htmlspecialchars($log['username']) ?></span>
                                    <span class="text-gray-500 text-xs block">ID: <?= $log['user_id'] ?></span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs font-mono">
                                        <?= htmlspecialchars($log['table_name']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <?php
                                    $badgeColors = [
                                        'INSERT' => 'bg-green-100 text-green-800',
                                        'UPDATE' => 'bg-blue-100 text-blue-800',
                                        'DELETE' => 'bg-red-100 text-red-800'
                                    ];
                                    $color = $badgeColors[$log['action']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-2 py-1 <?= $color ?> rounded text-xs font-semibold">
                                        <?= $log['action'] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    <?= $log['record_id'] ?? '-' ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 font-mono text-xs">
                                    <?= htmlspecialchars($log['ip_address']) ?>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <button onclick="showLogDetails(<?= $log['id'] ?>)" 
                                        class="text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-eye"></i> Xem
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="bg-gray-50 px-4 py-3 border-t border-gray-200 sm:px-6">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Trang <span class="font-medium"><?= $page ?></span> / 
                        <span class="font-medium"><?= $totalPages ?></span>
                    </div>
                    <div class="flex gap-2">
                        <?php
                        // Build query string for pagination
                        $queryParams = $_GET;
                        unset($queryParams['page']);
                        $queryString = http_build_query($queryParams);
                        $baseUrl = 'activity_logs.php?' . ($queryString ? $queryString . '&' : '');
                        
                        // Previous button
                        if ($page > 1): ?>
                            <a href="<?= $baseUrl ?>page=<?= $page - 1 ?>" 
                                class="px-3 py-2 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                Trước
                            </a>
                        <?php endif;
                        
                        // Page numbers
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        
                        if ($start > 1): ?>
                            <a href="<?= $baseUrl ?>page=1" class="px-3 py-2 bg-white border border-gray-300 rounded-md hover:bg-gray-50">1</a>
                            <?php if ($start > 2): ?>
                                <span class="px-3 py-2">...</span>
                            <?php endif;
                        endif;
                        
                        for ($i = $start; $i <= $end; $i++):
                            if ($i == $page): ?>
                                <span class="px-3 py-2 bg-blue-600 text-white rounded-md"><?= $i ?></span>
                            <?php else: ?>
                                <a href="<?= $baseUrl ?>page=<?= $i ?>" 
                                    class="px-3 py-2 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                    <?= $i ?>
                                </a>
                            <?php endif;
                        endfor;
                        
                        if ($end < $totalPages):
                            if ($end < $totalPages - 1): ?>
                                <span class="px-3 py-2">...</span>
                            <?php endif; ?>
                            <a href="<?= $baseUrl ?>page=<?= $totalPages ?>" 
                                class="px-3 py-2 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                <?= $totalPages ?>
                            </a>
                        <?php endif;
                        
                        // Next button
                        if ($page < $totalPages): ?>
                            <a href="<?= $baseUrl ?>page=<?= $page + 1 ?>" 
                                class="px-3 py-2 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                Sau
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal for log details -->
<div id="logModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex justify-between items-start mb-4">
                <h2 class="text-2xl font-bold text-gray-800">Chi tiết log</h2>
                <button onclick="closeLogModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <div id="logModalContent" class="space-y-4">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function showLogDetails(logId) {
    const modal = document.getElementById('logModal');
    const content = document.getElementById('logModalContent');
    
    // Find the log data
    const logs = <?= json_encode($logs, JSON_UNESCAPED_UNICODE) ?>;
    const log = logs.find(l => l.id == logId);
    
    if (!log) return;
    
    let html = `
        <div class="grid grid-cols-2 gap-4">
            <div>
                <span class="font-semibold">ID:</span> ${log.id}
            </div>
            <div>
                <span class="font-semibold">Thời gian:</span> ${new Date(log.created_at).toLocaleString('vi-VN')}
            </div>
            <div>
                <span class="font-semibold">Người dùng:</span> ${log.username} (ID: ${log.user_id})
            </div>
            <div>
                <span class="font-semibold">Bảng:</span> <code class="bg-gray-100 px-2 py-1 rounded">${log.table_name}</code>
            </div>
            <div>
                <span class="font-semibold">Hành động:</span> <span class="px-2 py-1 rounded text-xs font-semibold ${
                    log.action === 'INSERT' ? 'bg-green-100 text-green-800' :
                    log.action === 'UPDATE' ? 'bg-blue-100 text-blue-800' :
                    'bg-red-100 text-red-800'
                }">${log.action}</span>
            </div>
            <div>
                <span class="font-semibold">Record ID:</span> ${log.record_id || '-'}
            </div>
            <div class="col-span-2">
                <span class="font-semibold">IP Address:</span> <code>${log.ip_address}</code>
            </div>
            <div class="col-span-2">
                <span class="font-semibold">User Agent:</span> <code class="text-xs">${log.user_agent || '-'}</code>
            </div>
        </div>
    `;
    
    if (log.old_data) {
        html += `
            <div class="mt-4">
                <h3 class="font-semibold mb-2">Dữ liệu cũ:</h3>
                <pre class="bg-gray-100 p-4 rounded overflow-x-auto text-sm">${JSON.stringify(JSON.parse(log.old_data), null, 2)}</pre>
            </div>
        `;
    }
    
    if (log.new_data) {
        html += `
            <div class="mt-4">
                <h3 class="font-semibold mb-2">Dữ liệu mới:</h3>
                <pre class="bg-gray-100 p-4 rounded overflow-x-auto text-sm">${JSON.stringify(JSON.parse(log.new_data), null, 2)}</pre>
            </div>
        `;
    }
    
    content.innerHTML = html;
    modal.classList.remove('hidden');
}

function closeLogModal() {
    document.getElementById('logModal').classList.add('hidden');
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeLogModal();
    }
});

// Close modal on backdrop click
document.getElementById('logModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeLogModal();
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
