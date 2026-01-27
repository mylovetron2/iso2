<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

$db = getDBConnection();

echo "<h2>Kiểm tra cột chusohuu trong các bảng</h2>";

// Check kehoach_iso
echo "<h3>1. Bảng kehoach_iso</h3>";
$stmt = $db->query("SHOW COLUMNS FROM kehoach_iso WHERE Field LIKE '%chu%' OR Field LIKE '%owner%'");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!empty($cols)) {
    echo "<ul>";
    foreach ($cols as $col) {
        echo "<li><strong>{$col['Field']}</strong> - {$col['Type']}</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Không tìm thấy cột liên quan đến chủ sở hữu</p>";
}

// Check thietbihckd_iso
echo "<h3>2. Bảng thietbihckd_iso</h3>";
$stmt = $db->query("SHOW COLUMNS FROM thietbihckd_iso WHERE Field LIKE '%chu%' OR Field LIKE '%owner%'");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!empty($cols)) {
    echo "<ul>";
    foreach ($cols as $col) {
        echo "<li><strong>{$col['Field']}</strong> - {$col['Type']}</li>";
    }
    echo "</ul>";
    
    // Sample data
    echo "<h4>Dữ liệu mẫu:</h4>";
    $stmt = $db->query("SELECT mavattu, tenthietbi, somay, chusohuu FROM thietbihckd_iso LIMIT 5");
    echo "<table border='1'><tr><th>Mã VT</th><th>Tên TB</th><th>Số máy</th><th>Chủ sở hữu</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr><td>{$row['mavattu']}</td><td>{$row['tenthietbi']}</td><td>{$row['somay']}</td><td>{$row['chusohuu']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>Không tìm thấy cột liên quan đến chủ sở hữu</p>";
}

// Show all columns from both tables
echo "<h3>3. Tất cả cột trong kehoach_iso</h3>";
$stmt = $db->query("DESCRIBE kehoach_iso");
echo "<table border='1'><tr><th>Field</th><th>Type</th></tr>";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
}
echo "</table>";
