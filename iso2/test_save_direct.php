<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/models/HoSoHCKD.php';
require_once __DIR__ . '/models/ThietBiHCKD.php';

echo "<h2>Test Save Hồ Sơ HC/KD</h2>";

try {
    $model = new HoSoHCKD();
    $thietBiModel = new ThietBiHCKD();
    
    // Get first equipment for testing
    $thietBiList = $thietBiModel->getAll('', [], 1);
    if (empty($thietBiList)) {
        die("<p style='color:red;'>No equipment found in database</p>");
    }
    
    $mavattu = $thietBiList[0]['mavattu'];
    echo "<p><strong>Testing with equipment:</strong> " . htmlspecialchars($mavattu) . "</p>";
    
    // Test data
    $testData = [
        'sohs' => 'TEST-001',
        'tenmay' => $mavattu,
        'congviec' => 'HC',
        'ngayhc' => date('Y-m-d'),
        'ngayhctt' => date('Y-m-d', strtotime('+1 year')),
        'nhanvien' => 'Test User',
        'noithuchien' => 'Xưởng',
        'ttkt' => 'Tốt',
        'danchuan' => 'on',
        'mauchuan' => '',  // NOT NULL - must be empty string, not null
        'dinhky' => 'on',
        'dotxuat' => '',   // NOT NULL - must be empty string, not null
        'thietbidc1' => 'TB001',
        'thietbidc2' => '',
        'thietbidc3' => '',
        'thietbidc4' => '',
        'thietbidc5' => '',
        'namkh' => (int)date('Y')
    ];
    
    echo "<h3>Test Data:</h3>";
    echo "<pre>" . print_r($testData, true) . "</pre>";
    
    echo "<h3>Executing saveHoSo()...</h3>";
    
    $result = $model->saveHoSo($testData);
    
    if ($result) {
        echo "<p style='color:green;font-size:18px;'><strong>✓ SUCCESS!</strong> Hồ sơ đã được lưu</p>";
        
        // Get the saved record
        $saved = $model->getByDeviceAndDate($mavattu, $testData['ngayhc']);
        if ($saved) {
            echo "<h3>Saved Record:</h3>";
            echo "<pre>" . print_r($saved, true) . "</pre>";
        }
    } else {
        echo "<p style='color:red;font-size:18px;'><strong>✗ FAILED!</strong> Không thể lưu hồ sơ</p>";
    }
    
    // Check table structure
    echo "<hr><h3>Table Structure (hosohckd_iso):</h3>";
    $db = getDBConnection();
    $stmt = $db->query("DESCRIBE hosohckd_iso");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'><strong>Exception:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr><p>Check server error log for detailed SQL errors</p>";
