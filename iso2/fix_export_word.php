<?php
// Fix export_word.php by removing invalid u1:p tags

$file = __DIR__ . '/views/phieuyeucau/export_word.php';

$content = file_get_contents($file);

$originalSize = strlen($content);

// Remove all <u1:p></u1:p> tags
$content = str_replace('<u1:p></u1:p>', '', $content);

$newSize = strlen($content);

file_put_contents($file, $content);

echo "Fixed export_word.php\n";
echo "Original size: $originalSize bytes\n";
echo "New size: $newSize bytes\n";
echo "Removed: " . ($originalSize - $newSize) . " bytes\n";
echo "Changes: Removed all <u1:p></u1:p> tags\n";
?>
