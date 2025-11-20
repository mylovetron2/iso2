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

// Tìm kiếm username
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search !== '') {
    $users = array_filter($allUsers, function($u) use ($search) {
        return stripos($u['username'], $search) !== false;
    });
} else {
    $users = $allUsers;
}

// Phân trang
$perPage = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$totalUsers = count($users);
$totalPages = max(1, ceil($totalUsers / $perPage));
$usersPage = array_slice(array_values($users), ($page-1)*$perPage, $perPage);

// Gán role cho user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['role_id'])) {
    $userId = (int)$_POST['user_id'];
    $roleId = (int)$_POST['role_id'];
    $db = getDBConnection();
    // Xóa role cũ
    $db->prepare('DELETE FROM role_user WHERE user_id = ?')->execute([$userId]);
    // Gán role mới
    $db->prepare('INSERT INTO role_user (user_id, role_id) VALUES (?, ?)')->execute([$userId, $roleId]);
    header('Location: /iso2/admin_user_permissions.php?success=1');
    exit;
}

$title = 'Phân quyền User';
require_once __DIR__ . '/../layouts/header.php';
?>
<div class="max-w-6xl mx-auto bg-white rounded-lg shadow-md p-4 md:p-8 mt-4 md:mt-8">
    <h2 class="text-xl md:text-2xl font-bold mb-4 md:mb-6 flex items-center"><i class="fas fa-user-shield mr-2"></i> Phân quyền User</h2>
    <?php if (isset($_GET['success'])): ?>
        <div class="mb-4 text-green-600 text-center bg-green-50 p-3 rounded"><i class="fas fa-check-circle mr-2"></i>Cập nhật phân quyền thành công!</div>
    <?php endif; ?>


    <!-- Danh sách user và role + tìm kiếm + phân trang -->
    <div class="mb-8">
        <h3 class="text-base md:text-lg font-semibold mb-3">Danh sách User & Role</h3>
        <form method="GET" class="mb-4 flex flex-col md:flex-row gap-2">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tìm username..." class="px-3 py-2 border rounded w-full md:w-64 text-sm md:text-base">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm md:text-base w-full md:w-auto"><i class="fas fa-search mr-2"></i>Tìm kiếm</button>
            <?php if($search): ?>
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
                        <tr class="hover:bg-gray-50">
                            <td class="px-2 md:px-4 py-2 border text-xs md:text-sm font-semibold"><?php echo htmlspecialchars($user['username']); ?></td>
                            <td class="px-2 md:px-4 py-2 border text-xs md:text-sm hidden md:table-cell"><?php echo htmlspecialchars($user['name']); ?></td>
                            <td class="px-2 md:px-4 py-2 border text-xs md:text-sm hidden lg:table-cell"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="px-2 md:px-4 py-2 border text-xs md:text-sm">
                                <?php
                                $userRoles = $userModel->getRoles($user['stt']);
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
                                <button onclick="editUser(<?php echo $user['stt']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 md:px-3 py-1 rounded text-xs md:text-sm"><i class="fas fa-edit"></i></button>
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

    <div class="border-t pt-6">
        <h3 class="text-base md:text-lg font-semibold mb-3">Gán quyền nhanh</h3>
        <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4">
            <div>
                <label class="block mb-2 font-semibold text-sm md:text-base">Chọn User</label>
                <select name="user_id" id="quickUserId" class="w-full px-3 py-2 border rounded text-sm md:text-base">
                    <option value="">-- Chọn user --</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['stt']; ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block mb-2 font-semibold text-sm md:text-base">Chọn Role</label>
                <select name="role_id" class="w-full px-3 py-2 border rounded text-sm md:text-base">
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 rounded text-sm md:text-base"><i class="fas fa-save mr-2"></i>Cập nhật quyền</button>
            </div>
        </form>
    </div>
</div>

<script>
function editUser(userId, username) {
    document.getElementById('quickUserId').value = userId;
    window.scrollTo({top: document.body.scrollHeight, behavior: 'smooth'});
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
