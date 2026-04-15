<?php
/**
 * Debug version to find the error
 */

declare(strict_types=1);

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "Step 1: Starting...<br>";

require_once __DIR__ . '/config/constants.php';
echo "Step 2: Constants loaded (includes database, auth, functions, permissions)<br>";

requireAuth();
echo "Step 3: Auth checked<br>";

try {
    $db = getDBConnection();
    echo "Step 4: DB connection established<br>";
    
    // Test simple query
    $test = $db->query("SELECT COUNT(*) as count FROM thietbihckd_iso")->fetch(PDO::FETCH_ASSOC);
    echo "Step 5: Simple query works - found " . $test['count'] . " records<br>";
    
    // Test the complex query
    $sql = "SELECT t.stt, t.tenthietbi
            FROM thietbihckd_iso t
            LEFT JOIN kehoach_kiemdinh_2026_iso k ON t.stt = k.stt AND k.nam_kehoach = 2026
            LEFT JOIN hosohckd_iso h ON (t.mavattu = h.tenmay OR t.somay = h.tenmay) 
                AND YEAR(h.ngayhc) = 2026
            WHERE 1=1
            GROUP BY t.stt
            LIMIT 5";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Step 6: Complex query works - found " . count($results) . " results<br>";
    
    echo "<br><strong>SUCCESS!</strong> All operations working. The issue must be in the view or later code.";
    
} catch (Exception $e) {
    echo "<br><strong>ERROR:</strong> " . $e->getMessage();
    echo "<br><strong>Trace:</strong><pre>" . $e->getTraceAsString() . "</pre>";
}
