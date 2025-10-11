<?php
require_once __DIR__ . '/BaseModel.php';
class Project extends BaseModel {
    public function __construct() {
        parent::__construct('projects');
    }
    public function getAllWithUser($userId = null, $search = '', $status = '') {
    $sql = "SELECT p.*, u.username as user_name FROM projects p LEFT JOIN users u ON p.user_id = u.stt WHERE 1=1";
        $params = [];
        if ($userId && !$this->userHasPermission($userId, PERMISSION_PROJECT_MANAGE)) {
            $sql .= " AND p.user_id = ?";
            $params[] = $userId;
        }
        if (!empty($search)) {
            $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if (!empty($status)) {
            $sql .= " AND p.status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY p.created_at DESC";
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    private function userHasPermission($userId, $permission) {
        $userModel = new User();
        return $userModel->hasPermission($userId, $permission);
    }
}
