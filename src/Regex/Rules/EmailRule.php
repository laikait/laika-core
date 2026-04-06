<?php
/**
 * Laika PHP MVC Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika PHP MVC Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Laika\Core\Regex\Rules;

use Laika\Core\Regex\Abstracts\Rule;

class EmailRule extends Rule
{
    /**
     * Get the regex pattern for email validation
     *
     * @return string
     */
    public function pattern(): string
    {
        return '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
    }
}
