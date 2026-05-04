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

namespace Laika\Core\Service;

use Laika\Core\Relay\Relay;

/**
 * @method static bool exists(string $file)
 * @method static bool readable(string $file)
 * @method static bool writable(string $file)
 * @method static int|false size(string $file)
 * @method static array info(string $file)
 * @method static string|false mime(string $file)
 * @method static string extension(string $file)
 * @method static string name(string $file)
 * @method static string base(string $file)
 * @method static string path(string $file)
 * @method static string|false read(string $file)
 * @method static bool write(string $str, string $file)
 * @method static bool append(string $str, string $file)
 * @method static bool pop(string $file)
 * @method static bool move(string $from, string $to)
 * @method static bool copy(string $from, string $to)
 * @method static bool touch(string $file, ?int $mtime = null, ?int $atime = null)
 * @method static mixed require(string $file, bool $require_once = false)
 * @method static void download(string $file, ?string $as = null)
 */
class File extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'file';
    }
}