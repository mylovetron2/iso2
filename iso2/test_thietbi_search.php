<?php
require_once __DIR__ . '/config/database.php';

$db = getDBConnection();

// Kiểm tra cấu trúc bảng
echo "=== Cấu trúc bảng thietbi_iso ===\n";
$stmt = $db->query('DESCRIBE thietbi_iso');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\n=== Test search ===\n";
$search = 'test';
$params = ['search' => "%$search%"];
$sql = "SELECT * FROM thietbi_iso WHERE (mavt LIKE :search OR tenvt LIKE :search OR somay LIKE :search OR model LIKE :search) LIMIT 5";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($results) . " results\n";
    print_r($results);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test count ===\n";
$sql2 = "SELECT COUNT(*) FROM thietbi_iso";
$count = $db->query($sql2)->fetchColumn();
echo "Total records: $count\n";
