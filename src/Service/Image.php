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
 * @method static static path(string $path)
 * @method static static resize(int $width, int $height, bool $keepAspect = true)
 * @method static static crop(int $x, int $y, int $width, int $height)
 * @method static static thumbnail(int $width, int $height, string $mode = 'fit')
 * @method static static watermark(string $text, int $gdfont = 5, ?array $rgb = null, int $x = 10, int $y = 20)
 * @method static static watermarkImage(string $logoPath, int $x = 0, int $y = 0, int $opacity = 100)
 * @method static static rotate(float|int $angle)
 * @method static static flipHorizontal()
 * @method static static flipVertical()
 * @method static static grayscale()
 * @method static static convertTo(string $format)
 * @method static bool save(string $path, int $quality = 100)
 * @method static void show()
 * @method static string toBase64()
 * @method static array info()
 * @method static void destroy()
 * @method static bool unlink()
 */
class Image extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'image';
    }
}