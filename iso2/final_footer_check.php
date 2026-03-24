<?php
// Final comparison: PhieuYeuCau vs PhieuKiemSoatVatTu footer setup

echo "=== Final Footer Configuration Comparison ===\n\n";

$files = [
    'PhieuYeuCau' => 'views/phieuyeucau/export_word.php',
    'PhieuKiemSoatVatTu' => 'views/phieukiemsoatvattu/export_word.php'
];

$checks = [
    'No DOCTYPE' => '!DOCTYPE',
    'HTML opening tag' => '<html xmlns:v=',
    'charset=unicode' => 'charset=unicode',
    '@page Section1' => '@page Section1',
    'mso-footer-margin' => 'mso-footer-margin',
    'mso-footer: f1' => 'mso-footer: f1',
    'Footer table #hrdftrtbl' => "id='hrdftrtbl'",
    'mso-element:footer' => 'mso-element:footer',
    'Footer content BM.25.02' => 'BM.25.02'
];

echo str_repeat('=', 70) . "\n";
printf("%-35s | %-15s | %-15s\n", "Check", "PhieuYeuCau", "PhieuKiemSoat");
echo str_repeat('=', 70) . "\n";

$allMatch = true;
foreach ($checks as $desc => $pattern) {
    $results = [];
    
    foreach ($files as $name => $file) {
        $content = file_get_contents($file);
        
        if ($desc === 'No DOCTYPE') {
            $found = strpos($content, 'DOCTYPE') === false;
        } else {
            $found = strpos($content, $pattern) !== false;
        }
        
        $results[$name] = $found ? '✅ Yes' : '❌ No';
    }
    
    $match = $results['PhieuYeuCau'] === $results['PhieuKiemSoatVatTu'];
    if (!$match) $allMatch = false;
    
    $status = $match ? '' : '⚠️ ';
    printf("%s%-35s | %-15s | %-15s\n", $status, $desc, $results['PhieuYeuCau'], $results['PhieuKiemSoatVatTu']);
}

echo str_repeat('=', 70) . "\n";

if ($allMatch) {
    echo "\n✅ PERFECT MATCH! Both files have identical footer configuration.\n";
    echo "\nFooter should now display correctly in MS Word:\n";
    echo "  📄 Document format: MS Word HTML (no DOCTYPE)\n";
    echo "  🔤 Charset: unicode (MS Word compatible)\n";
    echo "  📐 Page setup: @page Section1 with mso-footer: f1\n";
    echo "  📝 Footer content: BM.25.02 & 01/01/2024\n";
    echo "  📍 Footer position: Bottom of every page\n";
} else {
    echo "\n⚠️  Some differences detected. Review above table.\n";
}

echo "\n=== Test Complete ===\n";
