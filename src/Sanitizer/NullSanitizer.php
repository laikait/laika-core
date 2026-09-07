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
 * Passthrough / No-op sanitizer for testing or when sanitization
 * is handled downstream (e.g. by an ORM or template engine).
 */
class NullSanitizer implements SanitizerInterface
{
    public function sanitize(array $data): array
    {
        return $data;
    }

    public function clean(mixed $value): mixed
    {
        return $value;
    }
}
