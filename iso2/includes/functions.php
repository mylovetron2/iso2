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
