<?php
// includes/security.php

require_once __DIR__ . '/Validator.php';

/**
 * Safely encodes input strings for HTML context to prevent XSS payloads.
 *
 * @param mixed $value String, array, or scalar value to encode.
 * @string $encoding Character set (default: UTF-8).
 * @return mixed Safe HTML output.
 */
function e($value, string $encoding = 'UTF-8') {
    if (is_null($value)) {
        return '';
    }

    // Recursively encode array elements
    if (is_array($value)) {
        return array_map(function ($item) use ($encoding) {
            return e($item, $encoding);
        }, $value);
    }

    // Numbers and booleans don't need escaping
    if (is_numeric($value) || is_bool($value)) {
        return $value;
    }

    // Standard UTF-8 HTML entity encoding
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, $encoding);
}

/**
 * Directly echo safely encoded output (shorthand for view templates).
 *
 * @param mixed $value
 */
function _e($value): void {
    echo e($value);
}

/**
 * Escapes values specifically for use inside HTML attributes (e.g. href, value, alt).
 *
 * @param string|null $value
 * @return string
 */
function e_attr($value): string {
    return e($value);
}

/**
 * Escapes values intended to be embedded safely inside JavaScript variables.
 *
 * @param mixed $value
 * @return string
 */
function e_js($value): string {
    return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

/**
 * Centralized helper function to initialize the POST Input Validator.
 * Automatically trims whitespace and strips non-printable control characters.
 *
 * @param array|null $data Custom input data (defaults to $_POST if null)
 * @return Validator
 */
function validateInput(?array $data = null): Validator {
    return new Validator($data);
}