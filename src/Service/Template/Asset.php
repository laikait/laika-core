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

namespace Laika\Core\Service\Template;

use Laika\Core\Relay\Relay;

/**
 * @method static void addStyle(string $handle, string $src, string $version = '1.0.0', string $media = 'all')
 * @method static void addScript(string $handle, string $src, string $version = '1.0.0', bool $defer = false)
 * @method static void printStyles()
 * @method static void printScripts()
 * @method static void headerScripts()
 */
class Asset extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'template.asset';
    }
}