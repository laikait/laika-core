<?php

/**
 * Laika PHP MVC Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika PHP MVC Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Laika\Core\Exceptions;

class ValidationException extends HttpException
{
    protected array $errors;

    public function __construct(array $errors, string $message = 'Validation Failed')
    {
        parent::__construct(422, $message);
        $this->errors = $errors;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
