<?php
// Web-accessible debug page
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to capture all output
ob_start();

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/ThietBiHCKDController.php';

// Disable authentication for debugging
// checkAuth();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Filter Query</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        table { border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        .error { color: red; background: #ffe6e6; padding: 10px; margin: 10px 0; }
        .success { color: green; background: #e6ffe6; padding: 10px; margin: 10px 0; }
        pre { background: #f4f4f4; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Debug Filter Query - ThietBiHCKD</h1>
    
    <?php
    try {
        $controller = new ThietBiHCKDController();
        $model = new ThietBiHCKD();
        
        echo "<h2>Test 1: Query without filter (first 5 records)</h2>";
        $items = $model->getAllWithLatestHC('', [], 5, 0, '');
        echo "<div class='success'>Found " . count($items) . " records</div>";
        
        if (count($items) > 0) {
            echo "<table>";
            echo "<tr><th>Mã vật tư</th><th>Tên TB</th><th>Thời hạn (tháng)</th><th>Ngày HC</th><th>Days to expire</th></tr>";
            foreach ($items as $item) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($item['mavattu']) . "</td>";
                echo "<td>" . htmlspecialchars($item['tenthietbi'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($item['thoihankd'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($item['ngayhc_latest'] ?? 'N/A') . "</td>";
                echo "<td><strong>" . htmlspecialchars($item['days_to_expire'] ?? 'NULL') . "</strong></td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        echo "<hr>";
        echo "<h2>Test 2: Query with filter 'saphethan' (0-30 days)</h2>";
        $itemsFiltered = $model->getAllWithLatestHC('', [], 10, 0, 'saphethan');
        echo "<div class='success'>Found " . count($itemsFiltered) . " records with filter</div>";
        
        if (count($itemsFiltered) > 0) {
            echo "<table>";
            echo "<tr><th>Mã vật tư</th><th>Tên TB</th><th>Thời hạn (tháng)</th><th>Ngày HC</th><th>Days to expire</th></tr>";
            foreach ($itemsFiltered as $item) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($item['mavattu']) . "</td>";
                echo "<td>" . htmlspecialchars($item['tenthietbi'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($item['thoihankd'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($item['ngayhc_latest'] ?? 'N/A') . "</td>";
                echo "<td><strong>" . htmlspecialchars($item['days_to_expire'] ?? 'NULL') . "</strong></td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='error'>No records found matching filter criteria (0-30 days to expire)</div>";
        }
        
        echo "<hr>";
        echo "<h2>Test 3: Count queries</h2>";
        $totalAll = $model->count('', []);
        echo "<p><strong>Total equipment (count):</strong> $totalAll</p>";
        
        $totalFiltered = $model->countWithFilter('', [], 'saphethan');
        echo "<p><strong>Total with filter 'saphethan':</strong> $totalFiltered</p>";
        
        $totalExpired = $model->countWithFilter('', [], 'dahethan');
        echo "<p><strong>Total with filter 'dahethan':</strong> $totalExpired</p>";
        
    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage());
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        echo "</div>";
    }
    
    // Get buffered output
    $output = ob_get_clean();
    echo $output;
    ?>
    
    <hr>
    <p><a href="thietbihckd.php">← Back to Equipment List</a></p>
    <p><a href="thietbihckd.php?filter=saphethan">Test with filter=saphethan</a></p>
    
</body>
</html>
