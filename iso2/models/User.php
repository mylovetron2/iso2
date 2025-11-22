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
    
    public function saveRememberToken(int $userStt, string $token): bool {
        // Kiểm tra xem cột remember_token đã tồn tại chưa
        try {
            $checkCol = $this->db->query("SHOW COLUMNS FROM users LIKE 'remember_token'");
            if ($checkCol->rowCount() == 0) {
                // Thêm cột nếu chưa có
                $this->db->exec("ALTER TABLE users ADD COLUMN remember_token VARCHAR(64) NULL UNIQUE COMMENT 'Token để ghi nhớ đăng nhập'");
            }
        } catch (Exception $e) {
            // Nếu lỗi thì bỏ qua, có thể cột đã tồn tại
        }
        
        $sql = "UPDATE users SET remember_token = ? WHERE stt = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$token, $userStt]);
    }
    
    public function findByRememberToken(string $token): array|false {
        // Kiểm tra cột tồn tại
        try {
            $checkCol = $this->db->query("SHOW COLUMNS FROM users LIKE 'remember_token'");
            if ($checkCol->rowCount() == 0) {
                return false; // Cột chưa có, trả về false
            }
        } catch (Exception $e) {
            return false;
        }
        
        $stmt = $this->query("SELECT * FROM users WHERE remember_token = ?", [$token]);
        return $stmt->fetch();
    }
    
    public function clearRememberToken(int $userStt): bool {
        try {
            $checkCol = $this->db->query("SHOW COLUMNS FROM users LIKE 'remember_token'");
            if ($checkCol->rowCount() == 0) {
                return true; // Cột chưa có, không cần xóa
            }
        } catch (Exception $e) {
            return true;
        }
        
        $sql = "UPDATE users SET remember_token = NULL WHERE stt = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$userStt]);
    }
}
