<?php

/**
 * Laika PHP MMC Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika PHP MVC Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Laika\Core\App;

use Laika\Core\Helper\Directory;

// Application Infrastructure Info
class Infra
{
    /*============================ Model Info ============================*/
    public function getModels(): array
    {
        $models_path = str_replace('/', '\\', APP_PATH . '/lf-app/Model');
        // Get Model Paths
        $paths = Directory::scanRecursive($models_path, true, 'php');
        $models = [];
        foreach ($paths as $path) {
            if (is_file($path)) {
                $models[] = 'Laika\\App\\Model\\' . str_replace(["{$models_path}\\", '.php', '/'], ['', '', '\\'], $path);
            }
        }
        return $models;
    }
}