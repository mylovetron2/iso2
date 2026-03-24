<?php
// Test escapeWordText function

function escapeWordText($text) {
    if ($text === null || $text === '') {
        return '';
    }
    
    // First strip any HTML tags
    $text = strip_tags($text);
    
    // Decode HTML entities (e.g., &ocirc; -> ô)
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // If data is stored as UTF-8 in latin1 column, it needs to be re-encoded
    // First check if it's valid UTF-8
    if (!mb_check_encoding($text, 'UTF-8')) {
        // Try to convert from latin1 to UTF-8
        $text = mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
    }
    
    // Escape XML special characters AFTER ensuring proper encoding
    $text = str_replace('&', '&amp;', $text);
    $text = str_replace('<', '&lt;', $text);
    $text = str_replace('>', '&gt;', $text);
    $text = str_replace('"', '&quot;', $text);
    $text = str_replace("'", '&#39;', $text);
    
    return $text;
}

// Test cases
$testCases = [
    'Số đếm nhiễu kh&ocirc;ng kết nối chuỗi' => 'HTML entity test',
    '<p>Số đếm nhiễu không kết nối chuỗi</p>' => 'HTML tag test',
    '<div id="gtx-trans" style="position: absolute;">Test</div>' => 'Complex HTML test',
    'Normal text without issues' => 'Plain text test',
    'Text with & special < characters >' => 'XML characters test',
    'Kiểm tra thiết bị' => 'Vietnamese text test',
    null => 'Null test',
    '' => 'Empty string test'
];

echo "=== Testing escapeWordText() Function ===\n\n";

foreach ($testCases as $input => $description) {
    $output = escapeWordText($input);
    echo "Test: $description\n";
    echo "Input:  " . ($input ?? 'NULL') . "\n";
    echo "Output: $output\n";
    echo str_repeat('-', 60) . "\n";
}

echo "\n=== Test Complete ===\n";
