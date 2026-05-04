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
 * @method static static init(?array $config)
 * @method static static isHTML(bool $isHtml = true)
 * @method static static setCharset(string $charset)
 * @method static static addFrom(string $email, string $name = '')
 * @method static static addTo(string $email, string $name = '')
 * @method static static addReplyTo(string $email, ?string $name = null)
 * @method static static addCC(string $email, string $name = '')
 * @method static static addBCC(string $email, string $name = '')
 * @method static static maxAttachmentSize(int $size)
 * @method static static attach(string $filePath, string $name = '')
 * @method static static attachMultiple(array $files)
 * @method static static attachFromString(string $content, string $name, ?string $mime = null)
 * @method static static addSubject(string $subject)
 * @method static static body(string $body)
 * @method static static altBody(string $text)
 * @method static bool send()
 * @method static static clearAddresses()
 * @method static static clearAttachments()
 * @method static static clearCCs()
 * @method static static clearBCCs()
 * @method static static clearReplyTos()
 * @method static static clear()
 * @method static \PHPMailer\PHPMailer\PHPMailer getMailer()
 * @method static void close()
 */
class Email extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'sendmail';
    }
}