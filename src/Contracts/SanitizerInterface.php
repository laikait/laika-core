<?php
/**
 * Laika PHP MVC Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 */

declare(strict_types=1);

namespace Laika\Core\Contracts;

/**
 * Contract for input sanitization strategies.
 * Implementations must handle scalar values and nested arrays recursively.
 */
interface SanitizerInterface
{
    /**
     * Sanitize input data.
     *
     * @param array<string, mixed> $data Raw input (e.g. $_GET, $_POST, decoded JSON)
     * @return array<string, mixed> Sanitized data with the same structure
     */
    public function sanitize(array $data): array;

    /**
     * Sanitize a single scalar value.
     *
     * @param mixed $value
     * @return mixed
     */
    public function clean(mixed $value): mixed;
}
