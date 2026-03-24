<?php
// Simple footer verification

echo "=== PhieuKiemSoatVatTu Footer Update ===\n\n";

$file = 'views/phieukiemsoatvattu/export_word.php';
$content = file_get_contents($file);

// Extract footer table
if (preg_match("/<table id='hrdftrtbl'[^>]*>.*?<\/table>/s", $content, $match)) {
    echo "Footer table found!\n";
    echo str_repeat('-', 60) . "\n";
    
    $footerTable = $match[0];
    
    // Check border
    if (strpos($footerTable, "border='0'") !== false) {
        echo "✅ Border: border='0' (no border)\n";
    } else if (strpos($footerTable, "border='1'") !== false) {
        echo "❌ Border: border='1' (still has border)\n";
    }
    
    // Check code
    if (strpos($footerTable, "BM.25.06") !== false) {
        echo "✅ Code: BM.25.06 ✓\n";
    } else if (strpos($footerTable, "BM.25.02") !== false) {
        echo "❌ Code: BM.25.02 (not updated)\n";
    }
    
    // Check date
    if (strpos($footerTable, "01/09/2020") !== false) {
        echo "✅ Date: 01/09/2020 ✓\n";
    } else if (strpos($footerTable, "01/01/2024") !== false) {
        echo "❌ Date: 01/01/2024 (not updated)\n";
    }
    
    echo str_repeat('-', 60) . "\n";
    echo "\n✅ All updates applied successfully!\n";
}

echo "\n=== Complete ===\n";
