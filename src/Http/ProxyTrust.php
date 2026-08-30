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

namespace Laika\Core\Http;

use Laika\Service\Config;
use Laika\Shield\Support\IpHelper;
use Throwable;

/**
 * Answers one question: did this request actually arrive through a proxy we run?
 *
 * X-Forwarded-* and friends are set by whoever sent the request. They only mean
 * anything when the immediate peer is a proxy of ours, so nothing may read them
 * until this says so. Configure the proxies in lf-config/app.php:
 *
 *   'trusted_proxies' => ['10.0.0.0/8', '192.168.1.5'],
 *
 * The default is an empty list, which trusts nothing - the correct behaviour for
 * a server reached directly.
 *
 * CIDR work is delegated to Laika\Shield\Support\IpHelper, which laika-core
 * already requires and which guards against the out-of-range prefix that would
 * otherwise be a fatal negative bit shift.
 */
final class ProxyTrust
{
    /** @var string[]|null Resolved once per process. */
    private static ?array $ranges = null;

    /**
     * Configured proxy IPs / CIDR ranges.
     * @return string[]
     */
    public static function ranges(): array
    {
        if (self::$ranges !== null) {
            return self::$ranges;
        }

        try {
            $configured = (array) (Config::get('app', 'trusted_proxies') ?? []);
        } catch (Throwable) {
            // No container yet (a bare `new Url()` in a unit test, a CLI script
            // that never booted). Fail closed rather than fatal.
            $configured = [];
        }

        return self::$ranges = array_values(array_filter(
            array_map(static fn ($range): string => trim((string) $range), $configured),
            static fn (string $range): bool => $range !== ''
        ));
    }

    /**
     * Whether the given IP - REMOTE_ADDR by default - is one of our proxies.
     * @param ?string $ip Defaults to the immediate peer.
     * @return bool
     */
    public static function trusts(?string $ip = null): bool
    {
        $ranges = self::ranges();

        if ($ranges === []) {
            return false;
        }

        $ip ??= is_string($_SERVER['REMOTE_ADDR'] ?? null) ? trim($_SERVER['REMOTE_ADDR']) : '';

        if ($ip === '') {
            return false;
        }

        // Escape hatch for a platform that never exposes a stable proxy IP.
        // Documented as a last resort: it trusts every forwarded header.
        if (in_array('*', $ranges, true)) {
            return true;
        }

        return IpHelper::inAnyCidr($ip, $ranges);
    }

    /**
     * Whether any proxy is configured at all.
     * @return bool
     */
    public static function enabled(): bool
    {
        return self::ranges() !== [];
    }

    /**
     * Drop the cached ranges. Testing, and long-running workers that reload config.
     * @return void
     */
    public static function flush(): void
    {
        self::$ranges = null;
    }
}
