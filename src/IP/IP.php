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

namespace Laika\Core\IP;

use Laika\Core\IP\Version\IPv4;
use Laika\Core\IP\Version\IPv6;
use Laika\Core\Exceptions\IPException;

/**
 * Comprehensive IPv4 & IPv6 CIDR class.
 *
 * Quick Examples:
 *   $net = Cidr::parse("192.168.1.0/24");
 *   foreach ($net->generateIPs() as $ip) { echo $ip . "\n"; }
 *
 *   $net = Cidr::fromRange("10.0.0.1", "10.0.0.200");
 *   echo $net->getCidr();          // 10.0.0.0/24
 *
 *   $net6 = Cidr::parse("2001:db8::/32");
 *   echo $net6->getNetworkAddress();
 */

// ─────────────────────────────────────────────────────────────────────────────
// Unified Façade
// ─────────────────────────────────────────────────────────────────────────────

class Cidr
{
    /**
     * Auto-detect address family and return the appropriate CIDR object.
     *
     * @return IPv4|IPv6
     */
    public static function parse(string $cidr): IPv4|IPv6
    {
        [$ip] = explode('/', $cidr . '/');

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return new IPv4($cidr);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return new IPv6($cidr);
        }

        throw new IPException("Cannot detect address family for: $cidr");
    }

    /**
     * Build the tightest containing CIDR from a start and end IP.
     * Works for both IPv4 and IPv6 (both must be same family).
     *
     * @return IPv4|IPv6
     */
    public static function fromRange(string $startIp, string $endIp): IPv4|IPv6
    {
        if (filter_var($startIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return IPv4::fromRange($startIp, $endIp);
        }
        if (filter_var($startIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return IPv6::fromRange($startIp, $endIp);
        }
        throw new IPException("Unrecognised IP address: $startIp");
    }

    /**
     * IPv4 only: build from network IP + dotted-decimal subnet mask.
     */
    public static function fromMask(string $networkIp, string $subnetMask): IPv4
    {
        return IPv4::fromMask($networkIp, $subnetMask);
    }

    /**
     * Check if an IP (v4 or v6) falls within a CIDR block.
     */
    public static function ipInCidr(string $ip, string $cidr): bool
    {
        return self::parse($cidr)->contains($ip);
    }

    /**
     * Summarise a list of same-family CIDR strings.
     *
     * @param  string[] $cidrs
     * @return IPv4[]
     */
    public static function summarise(array $cidrs): array
    {
        if (empty($cidrs)) {
            return [];
        }
        // Detect family from first element
        [$firstIp] = explode('/', $cidrs[0] . '/');
        if (filter_var($firstIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return IPv4::summarise($cidrs);
        }
        throw new IPException("IPv6 CIDR summarisation not yet implemented in this release");
    }
}
