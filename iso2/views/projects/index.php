<?php
require_once __DIR__ . '/../layouts/header.php';
$title = "Projects";
?>
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Projects</h1>
        <?php if (hasPermission(PERMISSION_PROJECT_CREATE)): ?>
        <a href="projects.php?action=create" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
            <i class="fas fa-plus mr-2"></i>New Project
        </a>
        <?php endif; ?>
    </div>
    <form method="GET" class="mb-6 bg-gray-50 p-4 rounded-lg">
        <div class="flex flex-wrap gap-4">
            <div class="flex-1">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search projects..." class="w-full px-3 py-2 border rounded-lg">
            </div>
            <select name="status" class="px-3 py-2 border rounded-lg">
                <option value="">All Status</option>
                <option value="planning" <?php echo ($status === 'planning') ? 'selected' : ''; ?>>Planning</option>
                <option value="active" <?php echo ($status === 'active') ? 'selected' : ''; ?>>Active</option>
                <option value="completed" <?php echo ($status === 'completed') ? 'selected' : ''; ?>>Completed</option>
            </select>
            <button type="submit" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                <i class="fas fa-search mr-2"></i>Search
            </button>
        </div>
    </form>
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 border-b text-left text-sm font-medium text-gray-500">Name</th>
                    <th class="px-6 py-3 border-b text-left text-sm font-medium text-gray-500">Status</th>
                    <th class="px-6 py-3 border-b text-left text-sm font-medium text-gray-500">Created By</th>
                    <th class="px-6 py-3 border-b text-left text-sm font-medium text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projects as $project): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 border-b">
                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($project['name']); ?></div>
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($project['description'], 0, 100)); ?>...</div>
                    </td>
                    <td class="px-6 py-4 border-b">
                        <?php
                        $statusColors = [
                            'planning' => 'bg-blue-100 text-blue-800',
                            'active' => 'bg-green-100 text-green-800', 
                            'completed' => 'bg-gray-100 text-gray-800'
                        ];
                        $colorClass = isset($statusColors[$project['status']]) ? $statusColors[$project['status']] : 'bg-gray-100';
                        ?>
                        <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $colorClass; ?>">
                            <?php echo ucfirst($project['status']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 border-b"><?php echo htmlspecialchars($project['user_name']); ?></td>
                    <td class="px-6 py-4 border-b">
                        <div class="flex space-x-2">
                            <a href="projects.php?action=show&id=<?php echo $project['id']; ?>" 
                               class="text-blue-600 hover:text-blue-900">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if (hasPermission(PERMISSION_PROJECT_EDIT) && 
                                     ($project['user_id'] == $_SESSION['user_id'] || hasPermission(PERMISSION_PROJECT_MANAGE))): ?>
                            <a href="projects.php?action=edit&id=<?php echo $project['id']; ?>" 
                               class="text-green-600 hover:text-green-900">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (hasPermission(PERMISSION_PROJECT_DELETE) && 
                                     ($project['user_id'] == $_SESSION['user_id'] || hasPermission(PERMISSION_PROJECT_MANAGE))): ?>
                            <a href="projects.php?action=delete&id=<?php echo $project['id']; ?>" 
                               class="text-red-600 hover:text-red-900"
                               onclick="return confirm('Are you sure?')">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
