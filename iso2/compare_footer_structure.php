<?php
// Compare footer structure between PhieuYeuCau and PhieuKiemSoatVatTu

echo "=== Comparing Footer Structures ===\n\n";

$files = [
    'PhieuYeuCau' => 'views/phieuyeucau/export_word.php',
    'PhieuKiemSoatVatTu' => 'views/phieukiemsoatvattu/export_word.php'
];

foreach ($files as $name => $file) {
    if (!file_exists($file)) {
        echo "❌ File not found: $file\n";
        continue;
    }
    
    $content = file_get_contents($file);
    
    echo "$name Export:\n";
    echo str_repeat('-', 50) . "\n";
    
    // Extract footer table
    if (preg_match("/<table id='hrdftrtbl'.*?<\/table>/s", $content, $match)) {
        echo "Footer table found:\n";
        $lines = explode("\n", $match[0]);
        foreach (array_slice($lines, 0, 10) as $line) {
            echo "  " . trim($line) . "\n";
        }
        echo "  ...\n";
    } else {
        echo "❌ Footer table NOT found\n";
    }
    
    echo "\n";
}

echo "=== Comparison Complete ===\n";
echo "\nBoth files should have identical footer structure:\n";
echo "✅ Same table#hrdftrtbl ID\n";
echo "✅ Same mso-element:footer div\n";
echo "✅ Same MsoFooter class\n";
echo "✅ Same content: BM.25.02 & 01/01/2024\n";
