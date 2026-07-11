<?php
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/permissions.php';

requireAuth();
requireRole(ROLE_ADMIN);



$userModel = new User();
$roleModel = new BaseModel('roles');
$allUsers = $userModel->all();
$roles = $roleModel->all();

$donViList = [];
try {
    $db = getDBConnection();
    $stmtDonVi = $db->query("SELECT madv, tendv FROM donvi_iso ORDER BY tendv ASC");
    $donViList = $stmtDonVi->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $donViList = [];
}

// Tìm kiếm username
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$selectedMadv = isset($_GET['madv']) ? trim($_GET['madv']) : '';

$users = array_filter($allUsers, function($u) use ($search, $selectedMadv) {
    $matchedSearch = true;
    $matchedDonVi = true;

    if ($search !== '') {
        $matchedSearch = stripos($u['username'], $search) !== false;
    }

    if ($selectedMadv !== '') {
        $matchedDonVi = isset($u['madv']) && trim((string)$u['madv']) === $selectedMadv;
    }

    return $matchedSearch && $matchedDonVi;
});

// Phân trang
$perPage = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$totalUsers = count($users);
$totalPages = max(1, ceil($totalUsers / $perPage));
$usersPage = array_slice(array_values($users), ($page-1)*$perPage, $perPage);

// Tạo user mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_user') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $roleId = (int)($_POST['role_id'] ?? 0);
    
    $errors = [];
    
    if (empty($username)) {
        $errors[] = 'Username không được để trống';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username phải có ít nhất 3 ký tự';
    }
    
    if (empty($password)) {
        $errors[] = 'Password không được để trống';
    } elseif (strlen($password) < 5) {
        $errors[] = 'Password phải có ít nhất 5 ký tự';
    }
    
    if (empty($name)) {
        $errors[] = 'Tên không được để trống';
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ';
    }
    
    if ($roleId <= 0) {
        $errors[] = 'Vui lòng chọn role';
    }
    
    // Kiểm tra username đã tồn tại
    if (empty($errors)) {
        $db = getDBConnection();
        $stmt = $db->prepare('SELECT stt FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors[] = 'Username đã tồn tại';
        }
    }
    
    if (empty($errors)) {
        try {
            $db = getDBConnection();
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user - sử dụng các cột thực tế của bảng users
            $stmt = $db->prepare('INSERT INTO users (username, password, hoten, email, madv, nhom, phanquyen) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$username, $hashedPassword, $name, $email, '', '', 0]);
            $newUserId = $db->lastInsertId();
            
            // Gán role
            $stmt = $db->prepare('INSERT INTO role_user (user_id, role_id) VALUES (?, ?)');
            $stmt->execute([$newUserId, $roleId]);
            
            header('Location: /iso2/admin_user_permissions.php?success=created');
            exit;
        } catch (Exception $e) {
            $errors[] = 'Lỗi khi tạo user: ' . $e->getMessage();
        }
    }
    
    if (!empty($errors)) {
        $errorMsg = implode('<br>', $errors);
    }
}

// Gán role cho user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['role_id']) && !isset($_POST['action'])) {
    $userId = (int)$_POST['user_id'];
    $roleId = (int)$_POST['role_id'];
    $returnSearch = trim($_POST['return_search'] ?? '');
    $returnMadv = trim($_POST['return_madv'] ?? '');
    $returnPage = max(1, (int)($_POST['return_page'] ?? 1));
    $returnOpenUser = max(0, (int)($_POST['return_open_user'] ?? $userId));

    $db = getDBConnection();
    // Xóa role cũ
    $db->prepare('DELETE FROM role_user WHERE user_id = ?')->execute([$userId]);
    // Gán role mới
    $db->prepare('INSERT INTO role_user (user_id, role_id) VALUES (?, ?)')->execute([$userId, $roleId]);

    $redirectParams = [
        'success' => 1,
        'page' => $returnPage,
        'open_user' => $returnOpenUser
    ];
    if ($returnSearch !== '') {
        $redirectParams['search'] = $returnSearch;
    }
    if ($returnMadv !== '') {
        $redirectParams['madv'] = $returnMadv;
    }

    header('Location: /iso2/admin_user_permissions.php?' . http_build_query($redirectParams));
    exit;
}

$openUserId = isset($_GET['open_user']) ? max(0, (int)$_GET['open_user']) : 0;

$title = 'Phân quyền User';
require_once __DIR__ . '/../layouts/header.php';
?>
<div class="max-w-6xl mx-auto bg-white rounded-lg shadow-md p-4 md:p-8 mt-4 md:mt-8">
    <!-- Admin Navigation -->
    <div class="mb-6 pb-4 border-b">
        <div class="flex flex-wrap gap-2 items-center">
            <h2 class="text-xl md:text-2xl font-bold flex items-center mr-4">
                <i class="fas fa-user-shield mr-2"></i> Phân quyền User
            </h2>
            <div class="flex flex-wrap gap-2 ml-auto">
                <a href="/iso2/views/admin/permissions_manager.php" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded text-sm">
                    <i class="fas fa-key mr-1"></i> Quản lý quyền
                </a>
                <a href="/iso2/views/admin/activity_logs.php" class="bg-purple-500 hover:bg-purple-600 text-white px-3 py-2 rounded text-sm">
                    <i class="fas fa-history mr-1"></i> Nhật ký
                </a>
                <a href="/iso2/admin_database_switch.php" class="bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-2 rounded text-sm">
                    <i class="fas fa-database mr-1"></i> Chuyển DB
                </a>
                <a href="/iso2/admin_backup.php" class="bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded text-sm">
                    <i class="fas fa-download mr-1"></i> Backup
                </a>
                <a href="/iso2/hososcbd.php" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded text-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Trang chủ
                </a>
            </div>
        </div>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
        <?php if ($_GET['success'] === 'created'): ?>
            <div class="mb-4 text-green-600 text-center bg-green-50 p-3 rounded"><i class="fas fa-check-circle mr-2"></i>Tạo user mới thành công!</div>
        <?php else: ?>
            <div class="mb-4 text-green-600 text-center bg-green-50 p-3 rounded"><i class="fas fa-check-circle mr-2"></i>Cập nhật phân quyền thành công!</div>
        <?php endif; ?>
    <?php endif; ?>
    
    <?php if (isset($errorMsg)): ?>
        <div class="mb-4 text-red-600 text-center bg-red-50 p-3 rounded"><i class="fas fa-exclamation-circle mr-2"></i><?php echo $errorMsg; ?></div>
    <?php endif; ?>

    <!-- Form tạo user mới -->
    <div class="mb-8 border border-blue-200 rounded-lg p-4 md:p-6 bg-blue-50">
        <h3 class="text-base md:text-lg font-semibold mb-4 text-blue-800"><i class="fas fa-user-plus mr-2"></i>Tạo User Mới</h3>
        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <input type="hidden" name="action" value="create_user">
            
            <div>
                <label class="block mb-2 font-semibold text-sm">
                    Username <span class="text-red-500">*</span>
                </label>
                <input type="text" name="username" required minlength="3" 
                       placeholder="Tên đăng nhập" 
                       class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-blue-500"
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                <small class="text-gray-600 text-xs">Ít nhất 3 ký tự</small>
            </div>
            
            <div>
                <label class="block mb-2 font-semibold text-sm">
                    Password <span class="text-red-500">*</span>
                </label>
                <input type="password" name="password" required minlength="5" 
                       placeholder="Mật khẩu" 
                       class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-blue-500">
                <small class="text-gray-600 text-xs">Ít nhất 5 ký tự</small>
            </div>
            
            <div>
                <label class="block mb-2 font-semibold text-sm">
                    Tên hiển thị <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" required 
                       placeholder="Họ và tên" 
                       class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-blue-500"
                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>
            
            <div>
                <label class="block mb-2 font-semibold text-sm">Email</label>
                <input type="email" name="email" 
                       placeholder="email@example.com" 
                       class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-blue-500"
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div>
                <label class="block mb-2 font-semibold text-sm">
                    Role <span class="text-red-500">*</span>
                </label>
                <select name="role_id" required class="w-full px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Chọn role --</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded text-sm">
                    <i class="fas fa-plus-circle mr-2"></i>Tạo User
                </button>
            </div>
        </form>
    </div>

    <!-- Danh sách user và role + tìm kiếm + phân trang -->
    <div class="mb-8">
        <h3 class="text-base md:text-lg font-semibold mb-3">Danh sách User & Role</h3>
        <form method="GET" class="mb-4 flex flex-col md:flex-row gap-2">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tìm username..." class="px-3 py-2 border rounded w-full md:w-64 text-sm md:text-base">
            <select name="madv" class="px-3 py-2 border rounded w-full md:w-72 text-sm md:text-base">
                <option value="">-- Chọn bộ phận --</option>
                <?php foreach ($donViList as $donVi): ?>
                    <option value="<?php echo htmlspecialchars($donVi['madv']); ?>" <?php echo $selectedMadv === (string)$donVi['madv'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($donVi['madv'] . ' - ' . $donVi['tendv']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm md:text-base w-full md:w-auto"><i class="fas fa-search mr-2"></i>Tìm kiếm</button>
            <?php if($search || $selectedMadv): ?>
            <a href="/iso2/admin_user_permissions.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm md:text-base text-center w-full md:w-auto"><i class="fas fa-times mr-2"></i>Xóa lọc</a>
            <?php endif; ?>
        </form>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-2 md:px-4 py-2 border text-xs md:text-sm">Username</th>
                        <th class="px-2 md:px-4 py-2 border text-xs md:text-sm hidden md:table-cell">Tên</th>
                        <th class="px-2 md:px-4 py-2 border text-xs md:text-sm hidden lg:table-cell">Email</th>
                        <th class="px-2 md:px-4 py-2 border text-xs md:text-sm">Role</th>
                        <th class="px-2 md:px-4 py-2 border text-xs md:text-sm">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usersPage as $user): ?>
                        <?php $userRoles = $userModel->getRoles($user['stt']); ?>
                        <?php $currentRoleId = count($userRoles) > 0 ? (int)$userRoles[0]['id'] : 0; ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-2 md:px-4 py-2 border text-xs md:text-sm font-semibold"><?php echo htmlspecialchars($user['username']); ?></td>
                            <td class="px-2 md:px-4 py-2 border text-xs md:text-sm hidden md:table-cell"><?php echo htmlspecialchars($user['hoten'] ?? $user['username']); ?></td>
                            <td class="px-2 md:px-4 py-2 border text-xs md:text-sm hidden lg:table-cell"><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                            <td class="px-2 md:px-4 py-2 border text-xs md:text-sm">
                                <?php
                                if (count($userRoles) > 0) {
                                    foreach($userRoles as $r) {
                                        echo '<span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs mr-1 mb-1">' . htmlspecialchars($r['name']) . '</span>';
                                    }
                                } else {
                                    echo '<span class="text-gray-400 italic">Chưa có</span>';
                                }
                                ?>
                            </td>
                            <td class="px-2 md:px-4 py-2 border text-center">
                                <button type="button" onclick="togglePermissionForm(<?php echo (int)$user['stt']; ?>)" class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 md:px-3 py-1 rounded text-xs md:text-sm">
                                    <i class="fas fa-edit mr-1"></i>Phân quyền
                                </button>
                            </td>
                        </tr>
                        <tr id="permission-row-<?php echo (int)$user['stt']; ?>" class="hidden bg-yellow-50">
                            <td colspan="5" class="px-2 md:px-4 py-3 border">
                                <form method="POST" class="js-inline-permission-form flex flex-col md:flex-row md:items-end gap-2 md:gap-3">
                                    <input type="hidden" name="user_id" value="<?php echo (int)$user['stt']; ?>">
                                    <input type="hidden" name="return_search" value="<?php echo htmlspecialchars($search); ?>">
                                    <input type="hidden" name="return_madv" value="<?php echo htmlspecialchars($selectedMadv); ?>">
                                    <input type="hidden" name="return_page" value="<?php echo (int)$page; ?>">
                                    <input type="hidden" name="return_open_user" value="<?php echo (int)$user['stt']; ?>">
                                    <div class="w-full md:w-72">
                                        <label class="block mb-1 font-semibold text-xs md:text-sm">Chọn role cho user: <?php echo htmlspecialchars($user['username']); ?></label>
                                        <select name="role_id" required class="w-full px-3 py-2 border rounded text-sm">
                                            <option value="">-- Chọn role --</option>
                                            <?php foreach ($roles as $role): ?>
                                                <option value="<?php echo (int)$role['id']; ?>" <?php echo $currentRoleId === (int)$role['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($role['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold px-3 py-2 rounded text-sm">
                                            <i class="fas fa-save mr-1"></i>Lưu quyền
                                        </button>
                                        <button type="button" onclick="togglePermissionForm(<?php echo (int)$user['stt']; ?>)" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-3 py-2 rounded text-sm">
                                            Đóng
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <!-- Phân trang -->
        <?php if($totalPages > 1): ?>
        <div class="flex flex-wrap justify-center mt-4 gap-1 md:gap-2">
            <?php 
            $range = 2;
            // Previous
            if($page > 1):
                $params = array_merge($_GET, ['page' => $page - 1]);
            ?>
                <a href="?<?php echo http_build_query($params); ?>" class="px-2 md:px-3 py-1 rounded bg-gray-200 hover:bg-blue-200 text-xs md:text-sm">‹</a>
            <?php endif; ?>
            
            <?php if($page > $range + 2): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" class="px-2 md:px-3 py-1 rounded bg-gray-200 hover:bg-blue-200 text-xs md:text-sm">1</a>
                <span class="px-2 py-1 text-xs md:text-sm">...</span>
            <?php endif; ?>
            
            <?php for($i = max(1, $page - $range); $i <= min($totalPages, $page + $range); $i++): ?>
                <?php if($i == $page): ?>
                    <span class="px-2 md:px-3 py-1 rounded bg-blue-600 text-white text-xs md:text-sm"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="px-2 md:px-3 py-1 rounded bg-gray-200 hover:bg-blue-200 text-xs md:text-sm"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if($page < $totalPages - $range - 1): ?>
                <span class="px-2 py-1 text-xs md:text-sm">...</span>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>" class="px-2 md:px-3 py-1 rounded bg-gray-200 hover:bg-blue-200 text-xs md:text-sm"><?php echo $totalPages; ?></a>
            <?php endif; ?>
            
            <?php if($page < $totalPages): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="px-2 md:px-3 py-1 rounded bg-gray-200 hover:bg-blue-200 text-xs md:text-sm">›</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

</div>

<script>
function togglePermissionForm(userId) {
    var row = document.getElementById('permission-row-' + userId);
    if (!row) return;

    document.querySelectorAll('tr[id^="permission-row-"]').forEach(function(item) {
        if (item.id !== 'permission-row-' + userId) {
            item.classList.add('hidden');
        }
    });

    row.classList.toggle('hidden');
}

document.addEventListener('DOMContentLoaded', function () {
    var openUserId = <?php echo (int)$openUserId; ?>;
    if (openUserId > 0) {
        var openRow = document.getElementById('permission-row-' + openUserId);
        if (openRow) {
            openRow.classList.remove('hidden');
        }
    }

    var savedScrollY = sessionStorage.getItem('adminUserPermissionsScrollY');
    if (savedScrollY !== null) {
        window.scrollTo({ top: parseInt(savedScrollY, 10) || 0, behavior: 'auto' });
        sessionStorage.removeItem('adminUserPermissionsScrollY');
    }

    document.querySelectorAll('.js-inline-permission-form').forEach(function(form) {
        form.addEventListener('submit', function() {
            sessionStorage.setItem('adminUserPermissionsScrollY', String(window.scrollY || 0));
        });
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
