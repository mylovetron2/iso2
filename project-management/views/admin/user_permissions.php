<?php
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
<div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-8 mt-8">
    <h2 class="text-2xl font-bold mb-6 text-center">Phân quyền User</h2>
    <?php if (isset($_GET['success'])): ?>
        <div class="mb-4 text-green-600 text-center">Cập nhật phân quyền thành công!</div>
    <?php endif; ?>


    <!-- Danh sách user và role + tìm kiếm + phân trang -->
    <div class="mb-8">
        <h3 class="text-lg font-semibold mb-2">Danh sách User & Role</h3>
        <form method="GET" class="mb-4 flex gap-2">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tìm username..." class="px-3 py-2 border rounded w-64">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Tìm kiếm</button>
        </form>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 border">Username</th>
                        <th class="px-4 py-2 border">Tên</th>
                        <th class="px-4 py-2 border">Email</th>
                        <th class="px-4 py-2 border">Role</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usersPage as $user): ?>
                        <tr>
                            <td class="px-4 py-2 border"><?php echo htmlspecialchars($user['username']); ?></td>
                            <td class="px-4 py-2 border"><?php echo htmlspecialchars($user['name']); ?></td>
                            <td class="px-4 py-2 border"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="px-4 py-2 border">
                                <?php
                                $userRoles = $userModel->getRoles($user['stt']);
                                if (count($userRoles) > 0) {
                                    echo implode(', ', array_map(function($r) { return htmlspecialchars($r['name']); }, $userRoles));
                                } else {
                                    echo '<span class="text-gray-400">Chưa có</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <!-- Phân trang -->
        <div class="flex justify-center mt-4 gap-2">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"
                   class="px-3 py-1 rounded border <?php echo $i == $page ? 'bg-blue-600 text-white' : 'bg-white text-blue-700'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>

    <form method="POST" class="space-y-4">
        <div>
            <label class="block mb-2 font-semibold">Chọn User</label>
            <select name="user_id" class="w-full px-3 py-2 border rounded">
                <?php foreach ($users as $user): ?>
                    <option value="<?php echo $user['stt']; ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block mb-2 font-semibold">Chọn Role</label>
            <select name="role_id" class="w-full px-3 py-2 border rounded">
                <?php foreach ($roles as $role): ?>
                    <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded">Cập nhật phân quyền</button>
    </form>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
