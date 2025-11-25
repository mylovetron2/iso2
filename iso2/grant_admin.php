<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';

requireAuth();

require_once __DIR__ . '/models/User.php';

$userModel = new User();
$currentUserId = $_SESSION['user_id'];

echo "<h2>Grant Admin Permissions</h2>";

// Kiểm tra xem có role admin chưa
$sql = "SELECT * FROM roles WHERE name = 'admin'";
$stmt = $userModel->query($sql);
$adminRole = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$adminRole) {
    echo "<h3>Tạo role Admin</h3>";
    $permissions = json_encode([
        'phieubangiao.view',
        'phieubangiao.create',
        'phieubangiao.edit',
        'phieubangiao.delete',
        'tiendocongviec.view',
        'tiendocongviec.create',
        'tiendocongviec.edit',
        'tiendocongviec.delete',
        'admin.access'
    ]);
    
    $sql = "INSERT INTO roles (name, permissions) VALUES ('admin', ?)";
    $stmt = $userModel->db->prepare($sql);
    if ($stmt->execute([$permissions])) {
        echo "✅ Đã tạo role Admin<br>";
        // Lấy lại role vừa tạo
        $adminRole = ['id' => $userModel->db->lastInsertId()];
    } else {
        die("❌ Không tạo được role Admin");
    }
}

echo "Role Admin ID: {$adminRole['id']}<br>";

// Kiểm tra user hiện tại đã có role admin chưa
$sql = "SELECT * FROM role_user WHERE user_id = ? AND role_id = ?";
$stmt = $userModel->query($sql, [$currentUserId, $adminRole['id']]);
$hasAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($hasAdmin) {
    echo "<h3>✅ User #{$currentUserId} đã có quyền Admin</h3>";
} else {
    echo "<h3>Gán quyền Admin cho user hiện tại</h3>";
    
    if (isset($_POST['grant'])) {
        $sql = "INSERT INTO role_user (user_id, role_id) VALUES (?, ?)";
        $stmt = $userModel->db->prepare($sql);
        if ($stmt->execute([$currentUserId, $adminRole['id']])) {
            echo "✅ Đã gán quyền Admin cho user #{$currentUserId}<br>";
            echo "<p><strong>Vui lòng đăng xuất và đăng nhập lại!</strong></p>";
            echo "<a href='logout.php' style='background:blue;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Đăng xuất ngay</a>";
        } else {
            echo "❌ Không gán được quyền";
        }
    } else {
        echo "<form method='POST'>
                <button type='submit' name='grant' style='background:green;color:white;padding:10px 20px;border:none;cursor:pointer;border-radius:5px;'>
                    Gán quyền Admin cho tôi
                </button>
              </form>";
    }
}

echo "<br><br><hr>";
echo "<h3>Danh sách users:</h3>";
$sql = "SELECT u.stt, u.username, GROUP_CONCAT(r.name) as roles 
        FROM users u 
        LEFT JOIN role_user ru ON u.stt = ru.user_id 
        LEFT JOIN roles r ON ru.role_id = r.id 
        GROUP BY u.stt
        LIMIT 20";
$stmt = $userModel->query($sql);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>STT</th><th>Username</th><th>Roles</th></tr>";
foreach ($users as $user) {
    $highlight = ($user['stt'] == $currentUserId) ? 'background:yellow;' : '';
    echo "<tr style='$highlight'>
            <td>{$user['stt']}</td>
            <td>{$user['username']}</td>
            <td>" . ($user['roles'] ?? '<em>Chưa có role</em>') . "</td>
          </tr>";
}
echo "</table>";

echo "<br><a href='phieubangiao.php'>← Quay lại</a>";
?>
