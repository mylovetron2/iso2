<?php
// Check footer configuration in PhieuKiemSoatVatTu

echo "=== Analyzing PhieuKiemSoatVatTu Footer Configuration ===\n\n";

$file = 'views/phieukiemsoatvattu/export_word.php';
$content = file_get_contents($file);

echo "Checking critical footer elements:\n";
echo str_repeat('-', 60) . "\n";

$checks = [
    '@page Section1 definition' => '@page Section1',
    'mso-footer-margin' => 'mso-footer-margin:',
    'mso-footer: f1' => 'mso-footer: f1',
    'Footer div id="f1"' => 'id="f1"',
    'mso-element:footer' => "mso-element:footer",
    'MsoFooter class' => 'class="MsoFooter"',
    'Footer table #hrdftrtbl' => "id='hrdftrtbl'",
    'xmlns:o namespace' => 'xmlns:o="urn:schemas-microsoft-com:office:office"',
    'xmlns:w namespace' => 'xmlns:w="urn:schemas-microsoft-com:office:word"',
    '<o:p> tag usage' => '<o:p></o:p>'
];

$allPass = true;
foreach ($checks as $desc => $pattern) {
    $found = strpos($content, $pattern) !== false;
    $status = $found ? '✅' : '❌';
    echo "$status $desc\n";
    if (!$found) {
        $allPass = false;
        echo "   Pattern: $pattern\n";
    }
}

echo str_repeat('-', 60) . "\n";

if ($allPass) {
    echo "\n✅ All footer configuration looks correct!\n";
    echo "\nFooter should display in MS Word with:\n";
    echo "  - BM.25.02\n";
    echo "  - 01/01/2024\n";
    echo "  - At bottom of each page\n";
} else {
    echo "\n❌ Some configuration missing!\n";
}

echo "\n=== Analysis Complete ===\n";
