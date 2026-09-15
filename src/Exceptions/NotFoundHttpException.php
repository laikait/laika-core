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

class NotFoundHttpException extends HttpException
{
    public function __construct(string $message = 'Page Not Found')
    {
        parent::__construct(404, $message);
    }
}
