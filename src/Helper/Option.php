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

namespace Laika\Core\Helper;

use Laika\Core\App\Options;

class Option
{
    /**
     * Get Option Value
     * @param string $name - Required Argument as Option Key.
     * @param mixed $default - If No Valu Exists/Found, Default will Return.
     * @return mixed
     */
    public static function get(string $name, mixed $default = null): mixed
    {
        try {
            $model = new Options();
            $option = $model->first([$model->key => $name]);
            $default = $option[$model->value] ?? $default;
        } catch (\Throwable $th) {}
        return $default;
    }

    /**
     * Set Option
     * @param string $name Required Argument. Option Name
     * @param string $value Required Argument. Option Value
     * @param bool $default Optional Argument. Default is false
     */
    public static function set(string $name, string $value, bool $default = false): bool
    {
        $model = new Options();
        $default = $default ? 'yes' : 'no';

        // Check Option Name Doesn't Exists
        if (empty($model->first([$model->key => $name]))) {
            return (bool) $model->insert([
                $model->key => $name,
                $model->value => $value,
                $model->default => $default
            ]);
        }

        // Update Value
        return (bool) $model->update([$model->key => $name], [$model->value => $value]);
    }
}
