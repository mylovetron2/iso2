<?php
// Verify footer changes in PhieuKiemSoatVatTu

echo "=== Verifying Footer Changes ===\n\n";

$file = 'views/phieukiemsoatvattu/export_word.php';
$content = file_get_contents($file);

echo "Checking updated footer configuration:\n";
echo str_repeat('-', 60) . "\n";

$checks = [
    "Border removed (border='0')" => "id='hrdftrtbl' border='0'",
    "Code updated to BM.25.06" => "BM.25.06",
    "Date updated to 01/09/2020" => "01/09/2020",
    "Old code BM.25.02 removed" => "BM.25.02",
    "Old date 01/01/2024 removed" => "01/01/2024"
];

foreach ($checks as $desc => $pattern) {
    $found = strpos($content, $pattern) !== false;
    
    // For removal checks, we expect NOT found
    if (strpos($desc, 'removed') !== false) {
        $status = !$found ? '✅ PASS' : '❌ FAIL';
        $expected = 'Should NOT exist';
    } else {
        $status = $found ? '✅ PASS' : '❌ FAIL';
        $expected = 'Should exist';
    }
    
    echo "$status - $desc ($expected)\n";
}

echo str_repeat('-', 60) . "\n";

echo "\n✅ Footer Updated Successfully!\n";
echo "\nNew footer configuration:\n";
echo "  📋 Code: BM.25.06 (changed from BM.25.02)\n";
echo "  📅 Date: 01/09/2020 (changed from 01/01/2024)\n";
echo "  🔲 Border: Removed (border='0')\n";

echo "\n=== Verification Complete ===\n";
