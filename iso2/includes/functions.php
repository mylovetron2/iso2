<?php
declare(strict_types=1);

// General helper functions

/**
 * Ensure string is in UTF-8 encoding
 */
function ensureUtf8(string $str): string {
    if (mb_check_encoding($str, 'UTF-8')) {
        return $str;
    }
    return mb_convert_encoding($str, 'UTF-8', 'auto');
}

/**
 * Sanitize and normalize input string to UTF-8
 */
function sanitizeInput(mixed $input): mixed {
    if (is_string($input)) {
        return ensureUtf8(trim($input));
    }
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return $input;
}

/**
 * Display text content safely - decodes HTML entities and strips tags
 */
function displayText(?string $text): string {
    if (empty($text)) {
        return '';
    }
    // Decode HTML entities first
    $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // Strip HTML tags but keep line breaks
    $stripped = strip_tags($decoded);
    // Escape for safe display
    return htmlspecialchars($stripped, ENT_QUOTES, 'UTF-8');
}
