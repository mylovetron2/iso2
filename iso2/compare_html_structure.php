<?php
// Compare HTML structure between exports

$files = [
    'PhieuYeuCau' => 'views/phieuyeucau/export_word.php',
    'PhieuKiemSoatVatTu' => 'views/phieukiemsoatvattu/export_word.php'
];

echo "=== Comparing HTML Structure ===\n\n";

foreach ($files as $name => $file) {
    $content = file_get_contents($file);
    
    echo "$name:\n";
    echo str_repeat('-', 50) . "\n";
    
    // Extract first 10 lines after PHP close
    if (preg_match('/\?>\s*(.{0,500})/s', $content, $match)) {
        $start = trim($match[1]);
        $lines = explode("\n", $start);
        foreach (array_slice($lines, 0, 8) as $line) {
            echo "  " . trim($line) . "\n";
        }
    }
    
    echo "\n";
}

echo "Key differences to note:\n";
echo "- PhieuYeuCau: No DOCTYPE\n";
echo "- PhieuKiemSoatVatTu: Should also have no DOCTYPE\n";
echo "- Both should use xmlns declarations\n";
