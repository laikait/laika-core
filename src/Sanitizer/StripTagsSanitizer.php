<?php
/**
 * Laika PHP MVC Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 */

declare(strict_types=1);

namespace Laika\Core\Sanitizer;

/**
 * Strict sanitizer that strips ALL HTML tags instead of encoding them.
 * Useful for plain-text fields (usernames, search queries, etc.).
 */
class StripTagsSanitizer extends InputSanitizer
{
    /**
     * @var string Allowed tags (empty = none)
     */
    protected string $allowedTags;

    public function __construct(
        string $allowedTags = '',
        int $maxDepth = 50,
        int $maxStringLength = 0
    ) {
        parent::__construct(ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8', true, $maxDepth, $maxStringLength);
        $this->allowedTags = $allowedTags;
    }

    protected function sanitizeString(string $value): string
    {
        $value = trim($value);
        $value = str_replace("\0", '', $value);

        if ($this->maxStringLength > 0 && mb_strlen($value, $this->encoding) > $this->maxStringLength) {
            $value = mb_substr($value, 0, $this->maxStringLength, $this->encoding);
        }

        // Strip tags BEFORE encoding to avoid encoding stripped tag brackets
        $value = strip_tags($value, $this->allowedTags);

        // Still encode remaining special chars
        $value = htmlspecialchars($value, $this->htmlFlags, $this->encoding, $this->doubleEncode);

        return $value;
    }
}
