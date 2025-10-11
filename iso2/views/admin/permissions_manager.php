<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/permissions.php';

requireAuth();
requireRole(ROLE_ADMIN);


$roleModel = new BaseModel('roles');
$roles = $roleModel->all();

// Danh sách quyền hệ thống (có thể mở rộng)
$allPermissions = [
    'project.view' => 'Xem project',
    'project.create' => 'Tạo project',
    'project.edit' => 'Sửa project',
    'project.delete' => 'Xóa project',
    'project.manage' => 'Quản lý toàn bộ project',
];

// Cập nhật quyền cho role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role_id'])) {
    $roleId = (int)$_POST['role_id'];
    $permissions = isset($_POST['permissions']) && is_array($_POST['permissions']) ? $_POST['permissions'] : [];
    $permissionsStr = implode(',', $permissions);
    $roleModel->update($roleId, ['permissions' => $permissionsStr]);
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
                            <div class="flex flex-wrap gap-2">
                                <?php 
                                $rolePerms = array_map('trim', explode(',', $role['permissions']));
                                foreach ($allPermissions as $permKey => $permLabel): ?>
                                    <label class="inline-flex items-center mr-4">
                                        <input type="checkbox" name="permissions[]" value="<?php echo $permKey; ?>" <?php if (in_array($permKey, $rolePerms)) echo 'checked'; ?>>
                                        <span class="ml-1 text-sm"><?php echo $permLabel; ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
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
