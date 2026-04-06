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

class HasNumericRule extends Rule
{
    /**
     * Get The Regex Pattern for Has Numeric Character
     *
     * @return string
     */
    public function pattern(): string
    {
        return '/^(?=.*\d).+$/';
    }
}
