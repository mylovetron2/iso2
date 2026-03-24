<?php
/**
 * TEST CREATE VIEW RENDER
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/constants.php';

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Mock data like controller
    $stmt = $db->query("
        SELECT 
            stt as id, 
            tenvt as ten_thiet_bi, 
            somay as ky_ma_hieu
        FROM thietbi_iso 
        ORDER BY tenvt ASC
        LIMIT 50
    ");
    $thietbiList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmtDonVi = $db->query("SELECT madv, tendv FROM donvi_iso ORDER BY tendv");
    $donviList = $stmtDonVi->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<!-- DEBUG: About to render create view -->\n";
    echo "<!-- Thiet bi count: " . count($thietbiList) . " -->\n";
    echo "<!-- Don vi count: " . count($donviList) . " -->\n";
    
    // Render view
    require __DIR__ . '/views/giaonhanthietbi/create.php';
    
    echo "\n<!-- DEBUG: View rendered successfully! -->\n";
    
} catch (Throwable $e) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body>";
    echo "<div style='background: red; color: white; padding: 20px; margin: 20px;'>";
    echo "<h2>ERROR RENDERING CREATE VIEW</h2>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
    echo "</body></html>";
}
