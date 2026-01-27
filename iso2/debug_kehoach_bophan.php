<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

$db = getDBConnection();

echo "<h2>Cấu trúc bảng kehoach_iso</h2>";
$stmt = $db->query("DESCRIBE kehoach_iso");
echo "<table border='1' cellpadding='5'><tr><th>Field</th><th>Type</th></tr>";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr><td><strong>{$row['Field']}</strong></td><td>{$row['Type']}</td></tr>";
}
echo "</table>";

echo "<h2>Sample data (5 records)</h2>";
$stmt = $db->query("SELECT * FROM kehoach_iso LIMIT 5");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!empty($data)) {
    echo "<table border='1' cellpadding='5'><tr>";
    foreach (array_keys($data[0]) as $col) {
        echo "<th>{$col}</th>";
    }
    echo "</tr>";
    foreach ($data as $row) {
        echo "<tr>";
        foreach ($row as $val) {
            echo "<td>" . htmlspecialchars($val ?? '') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h2>Các cột có thể dùng để filter bộ phận</h2>";
$stmt = $db->query("SHOW COLUMNS FROM kehoach_iso WHERE Field LIKE '%bp%' OR Field LIKE '%bo%' OR Field LIKE '%donvi%' OR Field LIKE '%dv%'");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!empty($cols)) {
    echo "<ul>";
    foreach ($cols as $col) {
        echo "<li><strong>{$col['Field']}</strong> - {$col['Type']}</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Không tìm thấy cột liên quan đến bộ phận</p>";
}
