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
use Laika\Core\IP\Version\IPv4;
use Laika\Core\IP\Version\IPv6;

/**
 * @method static IPv4|IPv6 parse(string $cidr)
 * @method static IPv4|IPv6 fromRange(string $startIp, string $endIp)
 * @method static IPv4 fromMask(string $networkIp, string $subnetMask)
 * @method static bool ipInCidr(string $ip, string $cidr)
 * @method static array summarise(array $cidrs)
 * @method static string getCidr()
 * @method static int getPrefix()
 * @method static string getNetworkAddress()
 * @method static string getLastAddress()
 * @method static string getFirstHost()
 * @method static string getLastHost()
 * @method static string getBroadcastAddress()
 * @method static string getSubnetMask()
 * @method static string getWildcardMask()
 * @method static ?string getFirstUsableHost()
 * @method static ?string getLastUsableHost()
 * @method static int getTotalAddresses()
 * @method static int getTotalAddressesString()
 * @method static int getAddressType()
 * @method static int getUsableHosts()
 * @method static string getIpClass()
 * @method static bool isPrivate()
 * @method static bool isLoopback()
 * @method static bool isMulticast()
 * @method static bool isLinkLocal()
 * @method static bool contains(string $cidrOrIp)
 * @method static bool overlaps(string $cidr)
 * @method static \Generator generateAllIPs()
 * @method static \Generator generateIPs(int $limit = 0)
 * @method static array toArray(bool $usableOnly = true)
 * @method static array split(int $count)
 * @method static IPv4|IPv6 supernet()
 * @method static IPv4|IPv6 sibling()
 * @method static array summarise(array $cidrs)
 * @method static string getReverseDnsZone()
 * @method static array info()
 */
class IP extends Relay
{
    protected static function getRelayAccessor(): string
    {
        return 'ip';
    }
}