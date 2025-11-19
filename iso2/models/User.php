<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class User extends BaseModel {
    public function __construct() {
        parent::__construct('users');
    }
    
    public function findByUsername(string $username): array|false {
        $stmt = $this->query("SELECT * FROM users WHERE username = ?", [$username]);
        return $stmt->fetch();
    }
    
    public function getRoles(int $userStt): array {
        $sql = "SELECT r.* FROM roles r INNER JOIN role_user ru ON r.id = ru.role_id WHERE ru.user_id = ?";
        $stmt = $this->query($sql, [$userStt]);
        return $stmt->fetchAll();
    }
    
    public function hasRole(int $userStt, string $roleName): bool {
        $sql = "SELECT COUNT(*) as count FROM roles r INNER JOIN role_user ru ON r.id = ru.role_id WHERE ru.user_id = ? AND r.name = ?";
        $stmt = $this->query($sql, [$userStt, $roleName]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }
    
    public function hasPermission(int $userStt, string $permission): bool {
        $sql = "SELECT COUNT(*) as count FROM role_user ru INNER JOIN roles r ON ru.role_id = r.id WHERE ru.user_id = ? AND FIND_IN_SET(?, REPLACE(REPLACE(r.permissions, '[', ''), ']', ''))";
        $stmt = $this->query($sql, [$userStt, $permission]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }
}
