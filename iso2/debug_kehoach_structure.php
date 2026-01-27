<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

$db = getDBConnection();

echo "<h2>Cấu trúc bảng kehoach_iso</h2>";
$stmt = $db->query("DESCRIBE kehoach_iso");
echo "<table border='1'><tr><th>Field</th><th>Type</th></tr>";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
}
echo "</table>";

echo "<h2>Sample data (5 records)</h2>";
$stmt = $db->query("SELECT * FROM kehoach_iso LIMIT 5");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!empty($data)) {
    echo "<table border='1'><tr>";
    foreach (array_keys($data[0]) as $col) {
        echo "<th>{$col}</th>";
    }
    echo "</tr>";
    foreach ($data as $row) {
        echo "<tr>";
        foreach ($row as $val) {
            echo "<td>" . htmlspecialchars($val) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}
