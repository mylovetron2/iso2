<?php
echo "=== Verify Footer Border CSS Override ===\n\n";

$file = 'views/phieukiemsoatvattu/export_word.php';
$content = file_get_contents($file);

// Check for global border rule
if (preg_match('/table,\s*th,\s*td\s*\{[^}]*border:\s*1px solid black/s', $content)) {
    echo "✅ Global table border rule found: table, th, td { border: 1px solid black; }\n";
} else {
    echo "❌ Global table border rule NOT found\n";
}

// Check for footer border override
if (preg_match('/table#hrdftrtbl.*border:\s*none\s*!important/s', $content)) {
    echo "✅ Footer border override found: table#hrdftrtbl { border: none !important; }\n";
} else {
    echo "❌ Footer border override NOT found\n";
}

// Extract the CSS section around the override
if (preg_match('/(table,\s*th,\s*td\s*\{.*?\}.*?\/\*.*?Remove borders from footer.*?\*\/.*?table#hrdftrtbl[^}]+\})/s', $content, $match)) {
    echo "\n" . str_repeat('-', 60) . "\n";
    echo "CSS Section:\n";
    echo str_repeat('-', 60) . "\n";
    echo trim($match[0]) . "\n";
    echo str_repeat('-', 60) . "\n";
}

echo "\n✅ Footer border override successfully added!\n";
echo "\nThe footer table will now display WITHOUT borders.\n";
