<?php
/**
 * MIGRATION SCRIPT - Run this file ONCE via browser
 * URL: https://diavatly.cloud/iso2/execute_vattu_migration.php
 * 
 * This script will:
 * 1. Backup existing data
 * 2. Add new multilingual columns
 * 3. Copy old data to new columns
 */

require_once 'config/database.php';

// Security check - remove after running
$ALLOW_MIGRATION = true; // Set to false after migration completed

if (!$ALLOW_MIGRATION) {
    die('Migration already completed or not allowed. Set $ALLOW_MIGRATION = true to run.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Vật Tư Migration</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0; }
        .info { color: #004085; padding: 10px; background: #cce5ff; border: 1px solid #b8daff; border-radius: 4px; margin: 10px 0; }
        .step { margin: 15px 0; padding: 10px; background: #f8f9fa; border-left: 4px solid #007bff; }
        pre { background: #272822; color: #f8f8f2; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Vật Tư Database Migration</h1>
        <p>Updating database structure to support multilingual fields...</p>

<?php
try {
    $pdo = getDBConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo '<div class="step"><strong>Step 1:</strong> Checking current structure...</div>';
    
    // Check if columns already exist
    $stmt = $pdo->query("SHOW COLUMNS FROM vattu_thanh_ly_iso LIKE 'ten_tienganh'");
    if ($stmt->rowCount() > 0) {
        echo '<div class="info">⚠️ Migration already completed! New columns already exist.</div>';
        echo '<div class="success">✅ Database is up to date.</div>';
    } else {
        echo '<div class="success">✓ Table exists and needs update</div>';
        
        // Step 2: Create backup
        echo '<div class="step"><strong>Step 2:</strong> Creating backup...</div>';
        $pdo->exec("CREATE TABLE IF NOT EXISTS `vattu_thanh_ly_iso_backup` AS SELECT * FROM `vattu_thanh_ly_iso`");
        echo '<div class="success">✓ Backup created: vattu_thanh_ly_iso_backup</div>';
        
        // Step 3: Add new columns
        echo '<div class="step"><strong>Step 3:</strong> Adding new multilingual columns...</div>';
        
        $alterSQL = "
        ALTER TABLE `vattu_thanh_ly_iso` 
        ADD COLUMN `ten_tienganh` TEXT DEFAULT NULL COMMENT 'Tên tiếng Anh' AFTER `mavattu`,
        ADD COLUMN `ten_tiengnga` TEXT DEFAULT NULL COMMENT 'Tên tiếng Nga' AFTER `ten_tienganh`,
        ADD COLUMN `ten_tiengviet` TEXT DEFAULT NULL COMMENT 'Tên tiếng Việt' AFTER `ten_tiengnga`,
        ADD COLUMN `dactinhkt_tiengnga` TEXT DEFAULT NULL COMMENT 'Đặc tính kỹ thuật tiếng Nga' AFTER `ten_tiengviet`,
        ADD COLUMN `dactinhkt_tiengviet` TEXT DEFAULT NULL COMMENT 'Đặc tính kỹ thuật tiếng Việt' AFTER `dactinhkt_tiengnga`,
        ADD COLUMN `dvt_tiengnga` VARCHAR(50) DEFAULT NULL COMMENT 'Đơn vị tính tiếng Nga' AFTER `dactinhkt_tiengviet`,
        ADD COLUMN `dvt_tiengviet` VARCHAR(50) DEFAULT NULL COMMENT 'Đơn vị tính tiếng Việt' AFTER `dvt_tiengnga`
        ";
        
        $pdo->exec($alterSQL);
        echo '<div class="success">✓ New columns added successfully</div>';
        
        // Step 4: Copy old data
        echo '<div class="step"><strong>Step 4:</strong> Migrating existing data...</div>';
        
        $updateSQL = "
        UPDATE `vattu_thanh_ly_iso` 
        SET `ten_tiengviet` = `tenkyhieuvt`,
            `dvt_tiengviet` = `dvt`
        WHERE `tenkyhieuvt` IS NOT NULL
        ";
        
        $stmt = $pdo->exec($updateSQL);
        echo '<div class="success">✓ Migrated ' . $stmt . ' records</div>';
        
        // Step 5: Verify
        echo '<div class="step"><strong>Step 5:</strong> Verifying new structure...</div>';
        
        $stmt = $pdo->query("DESCRIBE vattu_thanh_ly_iso");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo '<pre>';
        echo "New columns in vattu_thanh_ly_iso:\n";
        foreach ($columns as $col) {
            if (in_array($col['Field'], ['ten_tienganh', 'ten_tiengnga', 'ten_tiengviet', 'dactinhkt_tiengnga', 'dactinhkt_tiengviet', 'dvt_tiengnga', 'dvt_tiengviet'])) {
                echo "  ✓ {$col['Field']} ({$col['Type']})\n";
            }
        }
        echo '</pre>';
        
        echo '<div class="success">
            <h3>✅ Migration Completed Successfully!</h3>
            <p>Your database now supports multilingual fields:</p>
            <ul>
                <li>English, Russian, Vietnamese names (ten_tienganh, ten_tiengnga, ten_tiengviet)</li>
                <li>Technical specifications in Russian & Vietnamese (dactinhkt_tiengnga, dactinhkt_tiengviet)</li>
                <li>Units in Russian & Vietnamese (dvt_tiengnga, dvt_tiengviet)</li>
            </ul>
            <p><strong>Next step:</strong> You can now use the application normally.</p>
            <p><strong>Important:</strong> Set <code>$ALLOW_MIGRATION = false</code> in this file to prevent re-running.</p>
        </div>';
    }
    
} catch (PDOException $e) {
    echo '<div class="error">';
    echo '<h3>❌ Migration Failed</h3>';
    echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><strong>Code:</strong> ' . $e->getCode() . '</p>';
    echo '<p><strong>File:</strong> ' . $e->getFile() . ' (Line ' . $e->getLine() . ')</p>';
    echo '</div>';
    
    // Try to show current columns for debugging
    try {
        echo '<div class="info">';
        echo '<h4>Current table structure:</h4>';
        $stmt = $pdo->query("DESCRIBE vattu_thanh_ly_iso");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo '<pre>' . implode("\n", $columns) . '</pre>';
        echo '</div>';
    } catch (Exception $e2) {
        // Ignore
    }
}
?>

        <hr>
        <p><small>Migration script for Vật Tư Thanh Lý module - Version 1.0</small></p>
    </div>
</body>
</html>
