<?php
session_start();
// Kết nối CSDL
$password_file = __DIR__ . '/password.txt';
if (!file_exists($password_file)) file_put_contents($password_file, 'admin1234');
$current_password = trim(file_get_contents($password_file));
$servername = "localhost";
$username = "mapselli676e_iso2";
$password = "cntt2019@cntt2025";
$dbname = "mapselli676e_iso2";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Xử lý xác thực mật khẩu
if (isset($_POST['auth_pw'])) {
    $pw = isset($_POST['pw']) ? $_POST['pw'] : '';
    if ($pw === $current_password) {
        $_SESSION['authenticated'] = true;
        $_SESSION['auth_time'] = time();
        $msg = "Xác thực thành công!";
    } else {
        $msg = "Sai mật khẩu!";
    }
}

// Xử lý đăng xuất
if (isset($_POST['logout'])) {
    unset($_SESSION['authenticated']);
    unset($_SESSION['auth_time']);
    $msg = "Đã đăng xuất!";
}

// Kiểm tra session đã xác thực
$is_authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;

// Tự tạo bảng thư mục và cột folder_id nếu chưa có
$conn->query("CREATE TABLE IF NOT EXISTS link_folder_iso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    UNIQUE KEY uq_link_folder_name (name)
) DEFAULT CHARSET=utf8mb4");
$col = $conn->query("SHOW COLUMNS FROM link_iso LIKE 'folder_id'");
if ($col && $col->num_rows === 0) {
    $conn->query("ALTER TABLE link_iso ADD COLUMN folder_id INT NOT NULL DEFAULT 0");
}

// Kiểm tra thư mục tồn tại (0 = chưa phân loại)
function valid_folder_id($conn, $fid) {
    $fid = intval($fid);
    if ($fid <= 0) return 0;
    $stmt = $conn->prepare("SELECT id FROM link_folder_iso WHERE id=?");
    $stmt->bind_param("i", $fid);
    $stmt->execute();
    $stmt->store_result();
    $ok = $stmt->num_rows > 0;
    $stmt->close();
    return $ok ? $fid : 0;
}

// Thư mục đang xem: 'all', '0' (chưa phân loại) hoặc id
$current_folder = isset($_GET['folder']) ? $_GET['folder'] : 'all';
if ($current_folder !== 'all') $current_folder = (string)intval($current_folder);

// Xử lý tạo thư mục
if (isset($_POST['add_folder']) && $is_authenticated) {
    $fname = trim(isset($_POST['folder_name']) ? $_POST['folder_name'] : '');
    if ($fname === '') {
        $msg = "Tên thư mục không được để trống!";
    } else {
        $stmt = $conn->prepare("INSERT IGNORE INTO link_folder_iso (name) VALUES (?)");
        $stmt->bind_param("s", $fname);
        $stmt->execute();
        $msg = $stmt->affected_rows > 0 ? "Tạo thư mục thành công!" : "Thư mục đã tồn tại!";
        $stmt->close();
    }
}

// Xử lý đổi tên thư mục
if (isset($_POST['rename_folder']) && $is_authenticated) {
    $fid = intval($_POST['folder_id']);
    $fname = trim(isset($_POST['folder_name']) ? $_POST['folder_name'] : '');
    if ($fname === '') {
        $msg = "Tên thư mục không được để trống!";
    } else {
        $stmt = $conn->prepare("UPDATE IGNORE link_folder_iso SET name=? WHERE id=?");
        $stmt->bind_param("si", $fname, $fid);
        $stmt->execute();
        $msg = $stmt->affected_rows > 0 ? "Đổi tên thư mục thành công!" : "Không đổi được tên (trùng tên hoặc không đổi)!";
        $stmt->close();
    }
}

// Xử lý xóa thư mục (các link trong đó chuyển về chưa phân loại)
if (isset($_POST['delete_folder']) && $is_authenticated) {
    $fid = intval($_POST['folder_id']);
    $stmt = $conn->prepare("UPDATE link_iso SET folder_id=0 WHERE folder_id=?");
    $stmt->bind_param("i", $fid);
    $stmt->execute();
    $stmt->close();
    $stmt = $conn->prepare("DELETE FROM link_folder_iso WHERE id=?");
    $stmt->bind_param("i", $fid);
    $stmt->execute();
    $stmt->close();
    if ($current_folder === (string)$fid) $current_folder = 'all';
    $msg = "Đã xóa thư mục!";
}

// Xử lý chuyển một tiêu đề vào thư mục
if (isset($_POST['move_link']) && $is_authenticated) {
    $id = intval(isset($_POST['link_id']) ? $_POST['link_id'] : 0);
    $target = valid_folder_id($conn, isset($_POST['target_folder']) ? $_POST['target_folder'] : 0);
    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE link_iso SET folder_id=? WHERE id=?");
        $stmt->bind_param("ii", $target, $id);
        $stmt->execute();
        $msg = "Đã chuyển tiêu đề vào thư mục!";
        $stmt->close();
    }
}

// Xử lý thêm mới
if (isset($_POST['add']) && $is_authenticated) {
    $title = isset($_POST['title']) ? $_POST['title'] : '';
    $link = isset($_POST['link']) ? $_POST['link'] : '';
    $folder_id = valid_folder_id($conn, isset($_POST['folder_id']) ? $_POST['folder_id'] : 0);
    $sql = "INSERT INTO link_iso (title, link, folder_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $title, $link, $folder_id);
    $stmt->execute();
    $stmt->close();
    $msg = "Thêm mới thành công!";
}

// Xử lý xóa
if (isset($_POST['delete']) && $is_authenticated) {
    $id = intval($_POST['delete']);
    $sql = "DELETE FROM link_iso WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    $msg = "Xóa thành công!";
}

// Xử lý cập nhật
if (isset($_POST['update']) && $is_authenticated) {
    $id = intval($_POST['id']);
    $title = isset($_POST['title']) ? $_POST['title'] : '';
    $link = isset($_POST['link']) ? $_POST['link'] : '';
    $folder_id = valid_folder_id($conn, isset($_POST['folder_id']) ? $_POST['folder_id'] : 0);
    $sql = "UPDATE link_iso SET title=?, link=?, folder_id=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $title, $link, $folder_id, $id);
    $stmt->execute();
    $stmt->close();
    $msg = "Cập nhật thành công!";
}
// Xử lý nút Hủy: không kiểm tra mật khẩu, chỉ reset về trạng thái thêm mới
// Xử lý đổi mật khẩu
if (isset($_POST['change_pw']) && $is_authenticated) {
    $old_pw = isset($_POST['old_pw']) ? $_POST['old_pw'] : '';
    $new_pw = isset($_POST['new_pw']) ? $_POST['new_pw'] : '';
    if ($old_pw !== $current_password) {
        $msg = "Mật khẩu cũ không đúng!";
    } elseif (strlen($new_pw) < 6) {
        $msg = "Mật khẩu mới phải từ 6 ký tự!";
    } else {
        file_put_contents($password_file, $new_pw);
        $msg = "Đổi mật khẩu thành công!";
        $current_password = $new_pw;
    }
}
if (isset($_POST['cancel'])) {
    $edit = null;
}

// Lấy dữ liệu để sửa
$edit = null;
if (isset($_POST['edit_id'])) {
    $id = intval($_POST['edit_id']);
    $sql = "SELECT * FROM link_iso WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit = $result->fetch_assoc();
    $stmt->close();
}

// Danh sách thư mục kèm số lượng link
$folders = [];
$fres = $conn->query("SELECT f.id, f.name, COUNT(l.id) AS cnt FROM link_folder_iso f LEFT JOIN link_iso l ON l.folder_id=f.id GROUP BY f.id, f.name ORDER BY f.name");
while ($fres && ($f = $fres->fetch_assoc())) $folders[] = $f;
$total_links = (int)$conn->query("SELECT COUNT(*) FROM link_iso")->fetch_row()[0];
$uncat_links = (int)$conn->query("SELECT COUNT(*) FROM link_iso WHERE folder_id=0 OR folder_id NOT IN (SELECT id FROM link_folder_iso)")->fetch_row()[0];

// Lấy danh sách link_iso theo thư mục đang xem
if ($current_folder === 'all') {
    $result = $conn->query("SELECT l.*, f.name AS folder_name FROM link_iso l LEFT JOIN link_folder_iso f ON f.id=l.folder_id ORDER BY l.id DESC");
} elseif ($current_folder === '0') {
    $result = $conn->query("SELECT l.*, NULL AS folder_name FROM link_iso l LEFT JOIN link_folder_iso f ON f.id=l.folder_id WHERE f.id IS NULL ORDER BY l.id DESC");
} else {
    $cf = intval($current_folder);
    $stmt = $conn->prepare("SELECT l.*, f.name AS folder_name FROM link_iso l JOIN link_folder_iso f ON f.id=l.folder_id WHERE l.folder_id=? ORDER BY l.id DESC");
    $stmt->bind_param("i", $cf);
    $stmt->execute();
    $result = $stmt->get_result();
}
$current_folder_name = '';
foreach ($folders as $f) if ((string)$f['id'] === $current_folder) $current_folder_name = $f['name'];

function get_drive_preview_url($url) {
    $parts = parse_url($url);
    if (!$parts || empty($parts['host']) || empty($parts['scheme']) || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
        return null;
    }

    $host = strtolower($parts['host']);
    $file_id = null;
    if ($host === 'drive.google.com') {
        if (preg_match('~^/file/d/([A-Za-z0-9_-]+)~', isset($parts['path']) ? $parts['path'] : '', $matches)) {
            $file_id = $matches[1];
        } elseif (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
            if (!empty($query['id']) && preg_match('/^[A-Za-z0-9_-]+$/', $query['id'])) {
                $file_id = $query['id'];
            }
        }
        if ($file_id !== null) {
            return 'https://drive.google.com/file/d/' . $file_id . '/preview';
        }
    } elseif ($host === 'docs.google.com' && preg_match('~^/document/d/([A-Za-z0-9_-]+)~', isset($parts['path']) ? $parts['path'] : '', $matches)) {
        return 'https://docs.google.com/document/d/' . $matches[1] . '/preview';
    }

    return null;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <script>
    function showDeletePwModal(formId) {
        document.getElementById('deletePwModalFormId').value = formId;
        document.getElementById('deletePwModalInput').value = '';
        document.getElementById('deletePwModal').style.display = 'flex';
        document.getElementById('deletePwModalInput').focus();
    }
    function hideDeletePwModal() {
        document.getElementById('deletePwModal').style.display = 'none';
    }
    function submitDeletePwModal() {
        var pw = document.getElementById('deletePwModalInput').value;
        var formId = document.getElementById('deletePwModalFormId').value;
        var form = document.getElementById(formId);
        if (form) {
            var oldPw = form.querySelector('input[name="pw"]');
            if (oldPw) oldPw.remove();
            var pwInput = document.createElement('input');
            pwInput.type = 'hidden';
            pwInput.name = 'pw';
            pwInput.value = pw;
            form.appendChild(pwInput);
            form.submit();
        }
        hideDeletePwModal();
    }
    function showFolderModal() {
        document.getElementById('folderModal').style.display = 'flex';
    }
    function hideFolderModal() {
        document.getElementById('folderModal').style.display = 'none';
    }
    function showMoveFolderModal(linkId, folderId) {
        document.getElementById('moveFolderLinkId').value = linkId;
        document.getElementById('moveFolderTarget').value = folderId;
        document.getElementById('moveFolderModal').style.display = 'flex';
    }
    function hideMoveFolderModal() {
        document.getElementById('moveFolderModal').style.display = 'none';
    }
    function showDrivePreview(url, title) {
        document.getElementById('drivePreviewTitle').textContent = title;
        document.getElementById('drivePreviewFrame').src = url;
        document.getElementById('drivePreviewModal').style.display = 'flex';
    }
    function hideDrivePreview() {
        document.getElementById('drivePreviewModal').style.display = 'none';
        document.getElementById('drivePreviewFrame').src = '';
    }
    </script>
    <meta charset="UTF-8">
    <title>Quản lý các đường Link</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        body { background: #f4f6f8; }
    .container { max-width: 1100px; margin: 40px auto; background: #fff; border-radius: 10px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); padding: 32px 40px 24px 40px; }
        h2 { text-align: center; color: #2d3a4b; margin-bottom: 32px; }
        .table th { background: #1976d2; color: #fff; }
        .table td.link-col { word-break: break-all; max-width: 320px; }
        .msg { color: #d32f2f; text-align: center; margin-bottom: 12px; font-weight: bold; font-size: 18px; }
        .folder-modal { display:none; position:fixed; inset:0; z-index:1050; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; padding:16px; }
        .folder-modal-dialog { background:#fff; width:100%; max-width:600px; max-height:90vh; overflow-y:auto; border-radius:8px; box-shadow:0 4px 24px rgba(0,0,0,0.2); }
    </style>
</head>
<body>
        <!-- Modal nhập mật khẩu khi xóa -->
        <div id="deletePwModal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.25);z-index:9999;align-items:center;justify-content:center;">
            <div style="background:#fff;padding:32px 28px;border-radius:10px;box-shadow:0 4px 24px rgba(0,0,0,0.12);max-width:320px;margin:auto;position:relative;top:20vh;">
                <h3 style="margin-bottom:18px;font-size:1.15rem;color:#d32f2f;text-align:center;">Nhập mật khẩu để xóa</h3>
                <input type="password" id="deletePwModalInput" placeholder="Mật khẩu" style="width:100%;padding:8px 12px;font-size:16px;margin-bottom:18px;">
                <input type="hidden" id="deletePwModalFormId">
                <div style="display:flex;justify-content:space-between;">
                    <button type="button" onclick="submitDeletePwModal()" style="background:#d32f2f;color:#fff;padding:8px 18px;border:none;border-radius:5px;">Xác nhận</button>
                    <button type="button" onclick="hideDeletePwModal()" style="background:#b0bec5;color:#333;padding:8px 18px;border:none;border-radius:5px;">Hủy</button>
                </div>
            </div>
        </div>
    <div class="container">
        <div class="mb-3">
            <a href="https://iso2.diavatly.com/" class="btn btn-outline-primary">Trang chủ ISO2</a>
        </div>
        <?php if ($is_authenticated): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <div>
                <button type="button" class="btn btn-warning" onclick="showChangePwModal()">Đổi mật khẩu</button>
                <form method="post" style="display:inline;margin-left:8px;">
                    <button type="submit" name="logout" class="btn btn-secondary">Đăng xuất</button>
                </form>
            </div>
        </div>
        <!-- Modal đổi mật khẩu -->
        <div id="changePwModal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.25);z-index:9999;align-items:center;justify-content:center;">
            <div style="background:#fff;padding:32px 28px;border-radius:10px;box-shadow:0 4px 24px rgba(0,0,0,0.12);max-width:320px;margin:auto;position:relative;top:20vh;">
                <h3 style="margin-bottom:18px;font-size:1.15rem;color:#1976d2;text-align:center;">Đổi mật khẩu quản trị</h3>
                <form method="post">
                    <input type="password" name="old_pw" placeholder="Mật khẩu cũ" style="width:100%;padding:8px 12px;font-size:16px;margin-bottom:12px;" required>
                    <input type="password" name="new_pw" placeholder="Mật khẩu mới (>=6 ký tự)" style="width:100%;padding:8px 12px;font-size:16px;margin-bottom:18px;" required>
                    <div style="display:flex;justify-content:space-between;">
                        <button type="submit" name="change_pw" class="btn btn-success">Xác nhận</button>
                        <button type="button" onclick="hideChangePwModal()" class="btn btn-secondary">Hủy</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
        function showChangePwModal() {
                document.getElementById('changePwModal').style.display = 'flex';
        }
        function hideChangePwModal() {
                document.getElementById('changePwModal').style.display = 'none';
        }
        </script>
        <h2>Quản lý các đường Link</h2>
        <?php if (!empty($msg)): ?><div class="msg"><?php echo $msg; ?></div><?php endif; ?>
        <form method="post" class="mb-4">
            <?php if ($edit): ?>
                <input type="hidden" name="id" value="<?php echo $edit['id']; ?>">
            <?php endif; ?>
            <div class="form-group">
                <input type="text" name="title" class="form-control" placeholder="Tiêu đề" required value="<?php echo $edit ? htmlspecialchars($edit['title']) : ''; ?>">
            </div>
            <div class="form-group">
                <input type="text" name="link" class="form-control" placeholder="Link" required value="<?php echo $edit ? htmlspecialchars($edit['link']) : ''; ?>">
            </div>
            <div class="form-group">
                <select name="folder_id" class="form-control">
                    <option value="0">-- Chưa phân loại --</option>
                    <?php
                    $sel_folder = $edit ? (int)$edit['folder_id'] : (ctype_digit($current_folder) ? (int)$current_folder : 0);
                    foreach ($folders as $f): ?>
                        <option value="<?php echo $f['id']; ?>" <?php echo $sel_folder === (int)$f['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($f['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group text-right">
                <?php if (!$edit): ?>
                    <button type="submit" name="add" class="btn btn-primary">Thêm mới</button>
                <?php endif; ?>
                <?php if ($edit): ?>
                    <button type="submit" name="update" class="btn btn-success">Cập nhật</button>
                    <button type="submit" name="cancel" class="btn btn-secondary" formnovalidate>Hủy</button>
                <?php endif; ?>
            </div>
        </form>

        <div class="text-right mb-3">
            <button type="button" class="btn btn-outline-primary" onclick="showFolderModal()">Quản lý thư mục</button>
        </div>
        <!-- Modal quản lý thư mục -->
        <div id="folderModal" class="folder-modal" onclick="if (event.target === this) hideFolderModal()">
            <div class="folder-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="folderModalTitle">
                <div class="modal-header">
                    <h5 class="modal-title" id="folderModalTitle">Quản lý thư mục</h5>
                    <button type="button" class="close" aria-label="Đóng" onclick="hideFolderModal()"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                <form method="post" class="form-inline mb-3">
                    <input type="text" name="folder_name" class="form-control mr-2" placeholder="Tên thư mục mới" required maxlength="255">
                    <button type="submit" name="add_folder" class="btn btn-primary">Tạo thư mục</button>
                </form>
                <?php foreach ($folders as $f): ?>
                <form method="post" class="form-inline mb-2">
                    <input type="hidden" name="folder_id" value="<?php echo $f['id']; ?>">
                    <input type="text" name="folder_name" class="form-control form-control-sm mr-2" value="<?php echo htmlspecialchars($f['name']); ?>" required maxlength="255">
                    <button type="submit" name="rename_folder" class="btn btn-sm btn-info mr-1">Đổi tên</button>
                    <button type="submit" name="delete_folder" class="btn btn-sm btn-danger" onclick="return confirm('Xóa thư mục này? Các link bên trong sẽ chuyển về Chưa phân loại.')">Xóa</button>
                    <span class="ml-2 text-muted">(<?php echo (int)$f['cnt']; ?> link)</span>
                </form>
                <?php endforeach; ?>
                <?php if (!$folders): ?><div class="text-muted">Chưa có thư mục nào.</div><?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="hideFolderModal()">Đóng</button>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Phần xem cho tất cả người dùng -->
        <h2>Danh sách các đường Link</h2>
        <?php if (!$is_authenticated): ?>
            <div style="background:#fff3cd;padding:12px;border-radius:5px;margin-bottom:16px;border:1px solid #ffc107;">
                <strong>Chế độ chỉ xem.</strong> 
                <form method="post" style="display:inline;margin-left:8px;">
                    <input type="password" name="pw" placeholder="Nhập mật khẩu" style="padding:4px 8px;border:1px solid #ccc;border-radius:4px;" required>
                    <button type="submit" name="auth_pw" class="btn btn-sm btn-warning">Đăng nhập để chỉnh sửa</button>
                </form>
            </div>
            <?php if (!empty($msg)): ?><div class="msg"><?php echo $msg; ?></div><?php endif; ?>
        <?php endif; ?>

        <!-- Lọc theo thư mục -->
        <ul class="nav nav-pills mb-3">
            <li class="nav-item"><a class="nav-link <?php echo $current_folder === 'all' ? 'active' : ''; ?>" href="?folder=all">Tất cả (<?php echo $total_links; ?>)</a></li>
            <?php foreach ($folders as $f): ?>
            <li class="nav-item"><a class="nav-link <?php echo $current_folder === (string)$f['id'] ? 'active' : ''; ?>" href="?folder=<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['name']); ?> (<?php echo (int)$f['cnt']; ?>)</a></li>
            <?php endforeach; ?>
            <li class="nav-item"><a class="nav-link <?php echo $current_folder === '0' ? 'active' : ''; ?>" href="?folder=0">Chưa phân loại (<?php echo $uncat_links; ?>)</a></li>
        </ul>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Tiêu đề</th>
                    <th>Link</th>
                    <th>Thư mục</th>
                    <?php if ($is_authenticated): ?><th>Hành động</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>
                        <?php echo htmlspecialchars($row['title']); ?>
                        <?php $preview_url = get_drive_preview_url($row['link']); ?>
                        <?php if ($preview_url !== null): ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary ml-1" data-preview-url="<?php echo htmlspecialchars($preview_url, ENT_QUOTES, 'UTF-8'); ?>" data-preview-title="<?php echo htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'); ?>" onclick="showDrivePreview(this.dataset.previewUrl, this.dataset.previewTitle)">Xem trước</button>
                        <?php endif; ?>
                    </td>
                    <td class="link-col">
                        <a href="<?php echo htmlspecialchars($row['link']); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($row['link']); ?></a>
                    </td>
                    <td><?php echo $row['folder_name'] !== null ? htmlspecialchars($row['folder_name']) : '<span class="text-muted">Chưa phân loại</span>'; ?></td>
                    <?php if ($is_authenticated): ?>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary" title="Chuyển thư mục" aria-label="Chuyển thư mục" onclick="showMoveFolderModal(<?php echo (int)$row['id']; ?>, <?php echo $row['folder_name'] !== null ? (int)$row['folder_id'] : 0; ?>)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                <path d="M1.5 3A1.5 1.5 0 0 1 3 1.5h3.086a1.5 1.5 0 0 1 1.06.44L8.707 3.5H13A1.5 1.5 0 0 1 14.5 5v1h-1V5a.5.5 0 0 0-.5-.5H8.293a1.5 1.5 0 0 1-1.06-.44L6.44 3.207a.5.5 0 0 0-.354-.147H3a.5.5 0 0 0-.5.5v1h-1V3z"/>
                                <path d="M1 5h14l-1.5 8.5a1 1 0 0 1-1 .825h-9a1 1 0 0 1-.986-.835L1 5zm5.646 4.146a.5.5 0 0 0-.707.708L7.293 11.2H5.5a.5.5 0 0 0 0 1h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-1 0v1.793L6.646 9.146z"/>
                            </svg>
                        </button>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="edit_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-info">Sửa</button>
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="delete" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Xác nhận xóa?')">Xóa</button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <div id="drivePreviewModal" class="folder-modal" onclick="if (event.target === this) hideDrivePreview()">
            <div class="folder-modal-dialog" style="max-width:1000px;" role="dialog" aria-modal="true" aria-labelledby="drivePreviewTitle">
                <div class="modal-header">
                    <h5 class="modal-title" id="drivePreviewTitle">Xem trước tài liệu</h5>
                    <button type="button" class="close" aria-label="Đóng" onclick="hideDrivePreview()"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body p-0">
                    <iframe id="drivePreviewFrame" src="" title="Bản xem trước tài liệu Google Drive" style="width:100%;height:75vh;border:0;" allow="autoplay" allowfullscreen></iframe>
                </div>
            </div>
        </div>
        <?php if ($is_authenticated): ?>
        <div id="moveFolderModal" class="folder-modal" onclick="if (event.target === this) hideMoveFolderModal()">
            <div class="folder-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="moveFolderModalTitle">
                <div class="modal-header">
                    <h5 class="modal-title" id="moveFolderModalTitle">Chuyển tiêu đề vào thư mục</h5>
                    <button type="button" class="close" aria-label="Đóng" onclick="hideMoveFolderModal()"><span aria-hidden="true">&times;</span></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="link_id" id="moveFolderLinkId">
                        <div class="form-group mb-0">
                            <label for="moveFolderTarget">Thư mục đích</label>
                            <select name="target_folder" id="moveFolderTarget" class="form-control">
                                <option value="0">-- Chưa phân loại --</option>
                                <?php foreach ($folders as $f): ?>
                                    <option value="<?php echo (int)$f['id']; ?>"><?php echo htmlspecialchars($f['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="hideMoveFolderModal()">Hủy</button>
                        <button type="submit" name="move_link" class="btn btn-primary">Lưu</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>
