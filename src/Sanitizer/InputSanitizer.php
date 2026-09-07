<?php
/**
 * Laika PHP MVC Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 */

declare(strict_types=1);

namespace Laika\Core\Sanitizer;

use Laika\Core\Contracts\SanitizerInterface;

/**
 * Default input sanitizer for the Laika framework.
 *
 * Features:
 *   • Recursive array traversal
 *   • HTML entity encoding for strings (prevents XSS in mixed output contexts)
 *   • Trim whitespace
 *   • Null-byte removal
 *   • Configurable encoding and flags
 *   • Preserves integers, floats, booleans, null
 *   • Rejects objects / resources (returns null)
 */
class InputSanitizer implements SanitizerInterface
{
    /**
     * HTML encoding flags.
     * Default: ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE
     */
    protected int $htmlFlags;

    /**
     * Character set for htmlspecialchars().
     */
    protected string $encoding;

    /**
     * Whether to double-encode existing entities.
     */
    protected bool $doubleEncode;

    /**
     * Maximum recursion depth for nested arrays.
     */
    protected int $maxDepth;

    /**
     * Maximum string length allowed (0 = unlimited).
     */
    protected int $maxStringLength;

    /**
     * Constructor.
     *
     * @param int    $htmlFlags       Flags for htmlspecialchars()
     * @param string $encoding        Character encoding
     * @param bool   $doubleEncode    Whether to double-encode existing HTML entities
     * @param int    $maxDepth        Max nesting depth for arrays
     * @param int    $maxStringLength Max characters per string (0 = unlimited)
     */
    public function __construct(
        int $htmlFlags = ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE,
        string $encoding = 'UTF-8',
        bool $doubleEncode = true,
        int $maxDepth = 50,
        int $maxStringLength = 0
    ) {
        $this->htmlFlags = $htmlFlags;
        $this->encoding = $encoding;
        $this->doubleEncode = $doubleEncode;
        $this->maxDepth = $maxDepth;
        $this->maxStringLength = $maxStringLength;
    }

    /**
     * Sanitize an entire array of input data recursively.
     *
     * @param array<string, mixed> $data
     * @param int                  $depth Current recursion depth (internal)
     * @return array<string, mixed>
     */
    public function sanitize(array $data, int $depth = 0): array
    {
        if ($depth > $this->maxDepth) {
            // Prevent deep recursion attacks / stack overflow
            return [];
        }

        $clean = [];
        foreach ($data as $key => $value) {
            $safeKey = $this->sanitizeKey((string) $key);
            $clean[$safeKey] = $this->sanitizeValue($value, $depth + 1);
        }

        return $clean;
    }

    /**
     * Sanitize a single scalar value (public API for one-off cleaning).
     *
     * @param mixed $value
     * @return mixed
     */
    public function clean(mixed $value): mixed
    {
        return $this->sanitizeValue($value, 0);
    }

    /**
     * Recursively sanitize a single value.
     *
     * @param mixed $value
     * @param int   $depth
     * @return mixed
     */
    protected function sanitizeValue(mixed $value, int $depth): mixed
    {
        if ($depth > $this->maxDepth) {
            return null;
        }

        // Null
        if ($value === null) {
            return null;
        }

        // Boolean
        if (is_bool($value)) {
            return $value;
        }

        // Integer
        if (is_int($value)) {
            return $value;
        }

        // Float
        if (is_float($value)) {
            // Reject NaN and Infinity to prevent JSON serialization issues
            if (is_nan($value) || is_infinite($value)) {
                return null;
            }
            return $value;
        }

        // String
        if (is_string($value)) {
            return $this->sanitizeString($value);
        }

        // Array (recursive)
        if (is_array($value)) {
            return $this->sanitize($value, $depth);
        }

        // Reject objects, resources, closures, etc.
        return null;
    }

    /**
     * Sanitize an array key.
     * Removes null bytes and trims whitespace.
     *
     * @param string $key
     * @return string
     */
    protected function sanitizeKey(string $key): string
    {
        // Remove null bytes (path traversal / injection vector)
        $key = str_replace("\0", '', $key);
        return trim($key);
    }

    /**
     * Sanitize a string value.
     *
     * Steps:
     *   1. Trim whitespace
     *   2. Remove null bytes
     *   3. Optionally truncate to max length
     *   4. HTML-encode special characters
     *
     * @param string $value
     * @return string
     */
    protected function sanitizeString(string $value): string
    {
        // Trim
        $value = trim($value);

        // Remove null bytes
        $value = str_replace("\0", '', $value);

        // Length cap
        if ($this->maxStringLength > 0 && mb_strlen($value, $this->encoding) > $this->maxStringLength) {
            $value = mb_substr($value, 0, $this->maxStringLength, $this->encoding);
        }

        // HTML entity encode (prevents XSS when output unescaped)
        $value = htmlspecialchars($value, $this->htmlFlags, $this->encoding, $this->doubleEncode);

        return $value;
    }
}
