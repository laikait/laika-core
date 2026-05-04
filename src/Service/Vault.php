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
 * @method static string    encrypt(string $text)
 * @method static string    encryptArray(array $data)
 * @method static string    decrypt(string $encryptedBase64)
 * @method static array     decryptArray(string $encrypted)
 * @method static static    setCipher(string $cipher)
 * @method static string    hash(string $text, string $algo = 'sha256')
 * @method static bool      hashVerify(string $text, string $hash, string $algo = 'sha256')
 * @method static string    hashPassword(string $password)
 * @method static bool      verifyPassword(string $password, string $hash)
 * @method static bool      needsRehash(string $hash)
 * @method static string    sign(string $text)
 * @method static string    token(int $length = 32)
 * @method static string    numericOtp(int $digits = 6)
 */
class Vault extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'vault';
    }
}