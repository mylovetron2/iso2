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
    
    'tiendocongviec.view' => 'Xem tiến độ công việc',
    'tiendocongviec.create' => 'Tạo tiến độ công việc',
    'tiendocongviec.edit' => 'Sửa tiến độ công việc',
    'tiendocongviec.delete' => 'Xóa tiến độ công việc',
    'tiendocongviec.pause' => 'Tạm dừng công việc',
    
    'thietbi.view' => 'Xem thiết bị',
    'thietbi.create' => 'Tạo thiết bị',
    'thietbi.edit' => 'Sửa thiết bị',
    'thietbi.delete' => 'Xóa thiết bị',
    
    'thietbihotro.view' => 'Xem thiết bị hỗ trợ',
    'thietbihotro.create' => 'Tạo thiết bị hỗ trợ',
    'thietbihotro.edit' => 'Sửa thiết bị hỗ trợ',
    'thietbihotro.delete' => 'Xóa thiết bị hỗ trợ',
    
    'donvi.view' => 'Xem đơn vị khách hàng',
    'donvi.create' => 'Tạo đơn vị khách hàng',
    'donvi.edit' => 'Sửa đơn vị khách hàng',
    'donvi.delete' => 'Xóa đơn vị khách hàng',
    
    'hososcbd.view' => 'Xem hồ sơ SCBĐ',
    'hososcbd.create' => 'Tạo hồ sơ SCBĐ',
    'hososcbd.edit' => 'Sửa hồ sơ SCBĐ',
    'hososcbd.delete' => 'Xóa hồ sơ SCBĐ',
    
    'phieubangiao.view' => 'Xem phiếu bàn giao',
    'phieubangiao.create' => 'Tạo phiếu bàn giao',
    'phieubangiao.edit' => 'Sửa phiếu bàn giao',
    'phieubangiao.delete' => 'Xóa phiếu bàn giao',
    'phieubangiao.approve' => 'Duyệt phiếu bàn giao',
    
    'activitylogs.view' => 'Xem nhật ký hoạt động',
    'activitylogs.export' => 'Xuất nhật ký hoạt động',
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

// Nhóm permissions theo module
$permissionGroups = [
    'Project' => ['project.view', 'project.create', 'project.edit', 'project.delete', 'project.manage'],
    'Tiến độ công việc' => ['tiendocongviec.view', 'tiendocongviec.create', 'tiendocongviec.edit', 'tiendocongviec.delete', 'tiendocongviec.pause'],
    'Thiết bị' => ['thietbi.view', 'thietbi.create', 'thietbi.edit', 'thietbi.delete'],
    'Đơn vị khách hàng' => ['donvi.view', 'donvi.create', 'donvi.edit', 'donvi.delete'],
    'Thiết bị hỗ trợ' => ['thietbihotro.view', 'thietbihotro.create', 'thietbihotro.edit', 'thietbihotro.delete'],
    'Hồ sơ SCBĐ' => ['hososcbd.view', 'hososcbd.create', 'hososcbd.edit', 'hososcbd.delete'],
    'Phiếu bàn giao' => ['phieubangiao.view', 'phieubangiao.create', 'phieubangiao.edit', 'phieubangiao.delete', 'phieubangiao.approve'],
    'Nhật ký hoạt động' => ['activitylogs.view', 'activitylogs.export'],
];
?>
<div class="max-w-6xl mx-auto bg-white rounded-lg shadow-md p-4 md:p-8 mt-4 md:mt-8">
    <h2 class="text-xl md:text-2xl font-bold mb-4 md:mb-6 flex items-center"><i class="fas fa-shield-alt mr-2"></i> Quản lý quyền Role</h2>
    <?php if (isset($_GET['success'])): ?>
        <div class="mb-4 text-green-600 text-center bg-green-50 p-3 rounded"><i class="fas fa-check-circle mr-2"></i>Cập nhật quyền thành công!</div>
    <?php endif; ?>
    
    <?php foreach ($roles as $role): ?>
        <div class="border rounded-lg p-4 mb-4">
            <form method="POST">
                <input type="hidden" name="role_id" value="<?php echo $role['id']; ?>">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4">
                    <h3 class="text-lg font-bold mb-2 md:mb-0"><i class="fas fa-user-tag mr-2"></i><?php echo htmlspecialchars($role['name']); ?></h3>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm md:text-base w-full md:w-auto"><i class="fas fa-save mr-2"></i>Lưu quyền</button>
                </div>
                
                <?php 
                $rolePerms = array_map('trim', explode(',', $role['permissions']));
                ?>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($permissionGroups as $groupName => $groupPerms): ?>
                        <div class="border rounded p-3 bg-gray-50">
                            <h4 class="font-semibold text-sm mb-2 text-blue-700"><i class="fas fa-folder-open mr-2"></i><?php echo $groupName; ?></h4>
                            <div class="space-y-2">
                                <?php foreach ($groupPerms as $permKey): ?>
                                    <?php if (isset($allPermissions[$permKey])): ?>
                                        <label class="flex items-center text-sm cursor-pointer hover:bg-white p-1 rounded">
                                            <input type="checkbox" name="permissions[]" value="<?php echo $permKey; ?>" <?php if (in_array($permKey, $rolePerms)) echo 'checked'; ?> class="mr-2">
                                            <span><?php echo $allPermissions[$permKey]; ?></span>
                                        </label>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>
    <?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
