<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/permissions.php';

requireAuth();
requireRole(ROLE_ADMIN);

$roleModel = new BaseModel('roles');
$roles = $roleModel->all();

// Cập nhật quyền cho role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role_id'], $_POST['permissions'])) {
    $roleId = (int)$_POST['role_id'];
    $permissions = trim($_POST['permissions']);
    $roleModel->update($roleId, ['permissions' => $permissions]);
    header('Location: permissions_manager.php?success=1');
    exit;
}

$title = 'Quản lý quyền Role';
require_once __DIR__ . '/../layouts/header.php';
?>
<div class="max-w-3xl mx-auto bg-white rounded-lg shadow-md p-8 mt-8">
    <h2 class="text-2xl font-bold mb-6 text-center">Quản lý quyền Role</h2>
    <?php if (isset($_GET['success'])): ?>
        <div class="mb-4 text-green-600 text-center">Cập nhật quyền thành công!</div>
    <?php endif; ?>
    <table class="min-w-full bg-white border mb-8">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-4 py-2 border">Role</th>
                <th class="px-4 py-2 border">Quyền (permissions)</th>
                <th class="px-4 py-2 border">Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($roles as $role): ?>
                <tr>
                    <form method="POST">
                        <td class="px-4 py-2 border font-semibold"><?php echo htmlspecialchars($role['name']); ?></td>
                        <td class="px-4 py-2 border">
                            <input type="hidden" name="role_id" value="<?php echo $role['id']; ?>">
                            <input type="text" name="permissions" value="<?php echo htmlspecialchars($role['permissions']); ?>" class="w-full px-2 py-1 border rounded">
                            <div class="text-xs text-gray-500 mt-1">Các quyền cách nhau bằng dấu phẩy, ví dụ: <code>project.view,project.create</code></div>
                        </td>
                        <td class="px-4 py-2 border text-center">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1 rounded">Lưu</button>
                        </td>
                    </form>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
