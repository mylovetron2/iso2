<?php
declare(strict_types=1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/ThietBi.php';

requireAuth();

$model = new ThietBi();

echo "<h2>Test Search ThietBi</h2>";

// Test 1: Get all
echo "<h3>Test 1: Get All (no filter)</h3>";
$items = $model->getAll('ORDER BY stt DESC', [], 5, 0);
echo "Count: " . count($items) . "<br>";
echo "<pre>";
print_r($items);
echo "</pre>";

// Test 2: Search with LIKE
echo "<h3>Test 2: Search with LIKE</h3>";
$search = 'test';
$where = "WHERE (mavt LIKE :search OR tenvt LIKE :search)";
$params = ['search' => "%$search%"];
echo "WHERE: $where<br>";
echo "Params: ";
print_r($params);
echo "<br>";

try {
    $items = $model->getAll($where . ' ORDER BY stt DESC', $params, 5, 0);
    echo "Count: " . count($items) . "<br>";
    echo "<pre>";
    print_r($items);
    echo "</pre>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
