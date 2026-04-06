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

class MaximumRule extends Rule
{
    public function __construct(private int $max = 100) {}

    /**
     * Get The Regex Pattern for Maximum Characters
     *
     * @return string
     */
    public function pattern(): string
    {
        return "/^.{1,{$this->max}}$/";
    }
}
