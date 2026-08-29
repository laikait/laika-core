<?php

/**
 * Laika PHP Micro Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika PHP Micro Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Laika\Core\App;

use Laika\Service\Request;

/**
 * Template Input Handler
 *
 * Backs the `input` template variable, so {{ input.email }} reads a request
 * input by property and {{ input.tags(0) }} indexes an array one.
 */
final class InputHandler
{
    /**
     * Check Property Exists
     *
     * Always true: a missing input reads as an empty string rather than
     * raising an undefined variable in the view.
     * @param mixed $name
     * @return bool
     */
    public function __isset($name): bool
    {
        return true;
    }

    /**
     * Get Input Value From Key
     *
     * A JSON request body decodes to int, float, bool and null as well as
     * string, so every scalar is cast rather than discarded.
     * @param mixed $name
     * @return string
     */
    public function __get($name): string
    {
        $input = Request::input($name);
        return is_scalar($input) ? (string) $input : '';
    }

    /**
     * Get Input Value From Key, Optionally Indexed
     * @param mixed $name Input Key
     * @param array $arguments Index of an Array Input. Omit For The Whole Array
     * @return mixed
     */
    public function __call($name, $arguments): mixed
    {
        $input = Request::input($name);

        if (is_array($input)) {
            if ($arguments === []) {
                return $input;
            }
            return $input[(int) $arguments[0]] ?? '';
        }

        return is_scalar($input) ? $input : '';
    }
}
