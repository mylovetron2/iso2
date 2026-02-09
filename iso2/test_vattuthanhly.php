<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

try {
    echo "Testing VatTuThanhLy...<br>";
    
    // Test Database connection
    $db = getDBConnection();
    echo "Database connected: OK<br>";
    
    // Test query donvi_iso
    $stmtDonVi = $db->query("SELECT madv, tendv FROM donvi_iso ORDER BY tendv ASC LIMIT 5");
    $donViList = $stmtDonVi->fetchAll(PDO::FETCH_ASSOC);
    echo "DonVi count: " . count($donViList) . "<br>";
    echo "<pre>" . print_r($donViList, true) . "</pre>";
    
    // Test VatTuThanhLy model
    require_once __DIR__ . '/models/VatTuThanhLy.php';
    $model = new VatTuThanhLy();
    echo "VatTuThanhLy model: OK<br>";
    
    echo "<br>All tests passed!";
    
} catch (Exception $e) {
    echo "<div style='color:red; border:2px solid red; padding:10px; margin:10px;'>";
    echo "<h3>ERROR:</h3>";
    echo "<strong>Message:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
    echo "<strong>Trace:</strong><pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}
