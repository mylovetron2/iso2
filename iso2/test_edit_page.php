<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Testing Edit Page Load</h1>";

try {
    require_once __DIR__ . '/config/database.php';
    echo "<p>✓ Database config loaded</p>";
    
    require_once __DIR__ . '/models/HoSoScBd.php';
    echo "<p>✓ HoSoScBd model loaded</p>";
    
    require_once __DIR__ . '/models/DonVi.php';
    echo "<p>✓ DonVi model loaded</p>";
    
    require_once __DIR__ . '/models/ThietBiHoTro.php';
    echo "<p>✓ ThietBiHoTro model loaded</p>";
    
    $hoSoModel = new HoSoScBd();
    echo "<p>✓ HoSoScBd instantiated</p>";
    
    $item = $hoSoModel->findById(7678);
    if ($item) {
        echo "<p>✓ Record found: " . htmlspecialchars($item['maql'] ?? 'N/A') . "</p>";
        echo "<pre>" . print_r($item, true) . "</pre>";
    } else {
        echo "<p>✗ Record not found</p>";
    }
    
    $donViModel = new DonVi();
    $donViList = $donViModel->getAllSimple();
    echo "<p>✓ DonVi list loaded: " . count($donViList) . " items</p>";
    
    $thietBiHoTroModel = new ThietBiHoTro();
    $thietBiHoTroList = $thietBiHoTroModel->getAllSimple();
    echo "<p>✓ ThietBiHoTro list loaded: " . count($thietBiHoTroList) . " items</p>";
    
    echo "<h2>All checks passed!</h2>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
