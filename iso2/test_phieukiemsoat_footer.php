<?php
// Test footer in PhieuKiemSoatVatTu Word export

echo "=== Testing PhieuKiemSoatVatTu Footer ===\n\n";

$file = 'views/phieukiemsoatvattu/export_word.php';

if (!file_exists($file)) {
    die("File not found: $file\n");
}

$content = file_get_contents($file);

// Check for required footer elements
$checks = [
    'MsoFooter style definition' => 'p.MsoFooter, li.MsoFooter, div.MsoFooter',
    'hrdftrtbl table style' => 'table#hrdftrtbl',
    'Footer table structure' => "id='hrdftrtbl'",
    'Footer div element' => "mso-element:footer",
    'Footer content BM.25.02' => 'BM.25.02',
    'Footer date' => '01/01/2024'
];

echo "Checking footer components:\n";
echo str_repeat('-', 60) . "\n";

$allPassed = true;
foreach ($checks as $name => $pattern) {
    $found = strpos($content, $pattern) !== false;
    $status = $found ? '✅ PASS' : '❌ FAIL';
    echo "$status - $name\n";
    if (!$found) {
        $allPassed = false;
    }
}

echo str_repeat('-', 60) . "\n";

if ($allPassed) {
    echo "\n✅ All footer components present!\n";
    echo "\nFooter structure matches PhieuYeuCau export format:\n";
    echo "  - CSS styles for MsoFooter\n";
    echo "  - Table #hrdftrtbl positioning\n";
    echo "  - Footer content with BM.25.02\n";
    echo "  - Date: 01/01/2024\n";
} else {
    echo "\n❌ Some footer components missing!\n";
}

echo "\n=== Test Complete ===\n";
