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

namespace Laika\Core\IP\Version;

use Laika\Core\Exceptions\IPException;

// ─────────────────────────────────────────────────────────────────────────────
// IPv6 CIDR
// ─────────────────────────────────────────────────────────────────────────────
class IPv6
{
    private string $networkBin;   // 128-bit binary string (network address)
    private string $broadcastBin; // 128-bit binary string (last address)
    private int    $prefix;       // /0 – /128

    /*##################################################################*/
    /*=========================== PUBLIC API ===========================*/
    /*##################################################################*/

    public function __construct(string $cidr)
    {
        if (!str_contains($cidr, '/')) {
            throw new IPException("Invalid CIDR (missing prefix length): $cidr");
        }

        [$ip, $prefixStr] = explode('/', $cidr, 2);

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            throw new IPException("Invalid IPv6 address: $ip");
        }

        $prefix = (int) $prefixStr;
        if ($prefix < 0 || $prefix > 128) {
            throw new IPException("IPv6 prefix must be 0–128, got: $prefix");
        }

        $this->prefix = $prefix;
        $ipBin        = self::ip2bin($ip);
        $mask         = self::prefixToMaskBin($prefix);

        $this->networkBin  = $ipBin & $mask;
        $invMask           = ~$mask & str_repeat("\xFF", 16);
        $this->broadcastBin = $this->networkBin | $invMask;
    }

    /**
     * Create from a start IP and end IP.
     */
    public static function fromRange(string $startIp, string $endIp): self
    {
        if (!filter_var($startIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            throw new IPException("Invalid start IPv6: $startIp");
        }
        if (!filter_var($endIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            throw new IPException("Invalid end IPv6: $endIp");
        }

        $startBin = self::ip2bin($startIp);
        $endBin   = self::ip2bin($endIp);

        if ($startBin > $endBin) {
            [$startBin, $endBin] = [$endBin, $startBin];
        }

        // Find first differing bit
        $xor    = $startBin ^ $endBin;
        $prefix = 128;
        for ($byte = 0; $byte < 16; $byte++) {
            $b = ord($xor[$byte]);
            if ($b !== 0) {
                $prefix = $byte * 8 + (7 - (int) floor(log($b, 2)));
                break;
            }
        }

        $network = self::bin2ip($startBin & self::prefixToMaskBin($prefix));
        return new self("$network/$prefix");
    }

    // ── Core Information ──────────────────────────────────────────────────────

    public function getCidr(): string
    {
        return $this->getNetworkAddress() . '/' . $this->prefix;
    }

    public function getPrefix(): int
    {
        return $this->prefix;
    }

    public function getNetworkAddress(): string
    {
        return self::bin2ip($this->networkBin);
    }

    /** Last address in the block (analogous to broadcast in IPv4) */
    public function getLastAddress(): string
    {
        return self::bin2ip($this->broadcastBin);
    }

    /** First usable host (same as network for IPv6 — no concept of broadcast) */
    public function getFirstHost(): string
    {
        if ($this->prefix === 128) {
            return $this->getNetworkAddress();
        }
        return self::bin2ip(self::addOneToBin($this->networkBin));
    }

    public function getLastHost(): string
    {
        if ($this->prefix === 128) {
            return $this->getLastAddress();
        }
        return self::bin2ip(self::subOneFromBin($this->broadcastBin));
    }

    /** Total addresses as a BCMath string (can be astronomically large). */
    public function getTotalAddresses(): string
    {
        return bcpow('2', (string) (128 - $this->prefix));
    }

    /** Human-readable total. */
    public function getTotalAddressesString(): string
    {
        return $this->getTotalAddresses();
    }

    public function getAddressType(): string
    {
        $net = $this->getNetworkAddress();
        return match (true) {
            str_starts_with($net, '::1')         => 'Loopback',
            str_starts_with($net, 'fe80:')       => 'Link-Local',
            str_starts_with($net, 'fc') ||
            str_starts_with($net, 'fd')          => 'Unique Local (ULA)',
            str_starts_with($net, 'ff')          => 'Multicast',
            str_starts_with($net, '2002:')       => '6to4',
            str_starts_with($net, '2001:db8:')   => 'Documentation',
            str_starts_with($net, '2001:')       => 'Global Unicast (Teredo/etc)',
            str_starts_with($net, '2') ||
            str_starts_with($net, '3')           => 'Global Unicast',
            $net === '::'                        => 'Unspecified',
            default                              => 'Other',
        };
    }

    public function isPrivate(): bool
    {
        $net = strtolower($this->getNetworkAddress());
        return str_starts_with($net, 'fc') || str_starts_with($net, 'fd');
    }

    public function isLinkLocal(): bool
    {
        return str_starts_with(strtolower($this->getNetworkAddress()), 'fe80:');
    }

    public function isLoopback(): bool
    {
        return $this->getNetworkAddress() === '::1';
    }

    public function isMulticast(): bool
    {
        return str_starts_with(strtolower($this->getNetworkAddress()), 'ff');
    }

    /** Does this CIDR contain another CIDR or IP? */
    public function contains(string $cidrOrIp): bool
    {
        if (str_contains($cidrOrIp, '/')) {
            $other = new self($cidrOrIp);
            return $other->networkBin  >= $this->networkBin
                && $other->broadcastBin <= $this->broadcastBin;
        }

        if (!filter_var($cidrOrIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            throw new IPException("Invalid IPv6: $cidrOrIp");
        }

        $ipBin = self::ip2bin($cidrOrIp);
        return $ipBin >= $this->networkBin && $ipBin <= $this->broadcastBin;
    }

    public function overlaps(string $cidr): bool
    {
        $other = new self($cidr);
        return $this->networkBin  <= $other->broadcastBin
            && $this->broadcastBin >= $other->networkBin;
    }

    // ── IP Generation ─────────────────────────────────────────────────────────

    /**
     * Generate IPs from the block.
     * IPv6 blocks are enormous — always use $limit!
     *
     * @param int $limit  Max IPs to yield (0 = unlimited — dangerous for large prefixes!)
     * @return \Generator<string>
     */
    public function generateIPs(int $limit = 256): \Generator
    {
        $current = $this->networkBin;
        $count   = 0;
        while ($current <= $this->broadcastBin) {
            yield self::bin2ip($current);
            $count++;
            if ($limit > 0 && $count >= $limit) {
                return;
            }
            $next = self::addOneToBin($current);
            if ($next <= $current) {
                break; // overflow (reached end of IPv6 space)
            }
            $current = $next;
        }
    }

    /**
     * Return IPs as an array.
     * Only safe for very small blocks like /120 – /128.
     */
    public function toArray(int $limit = 256): array
    {
        return iterator_to_array($this->generateIPs($limit), false);
    }

    // ── Splitting ─────────────────────────────────────────────────────────────

    /**
     * Split into $count equal sub-networks (must be power of 2).
     *
     * @return self[]
     */
    public function split(int $count): array
    {
        if ($count < 2 || ($count & ($count - 1)) !== 0) {
            throw new IPException("Split count must be a power of 2, got: $count");
        }

        $newPrefix = $this->prefix + (int) log($count, 2);
        if ($newPrefix > 128) {
            throw new IPException("Cannot split /{$this->prefix} into $count subnets");
        }

        $subnets   = [];
        $blockBits = 128 - $newPrefix;
        $blockSize = bcpow('2', (string) $blockBits);
        $baseInt   = self::binToBc($this->networkBin);

        for ($i = 0; $i < $count; $i++) {
            $offset    = bcmul((string) $i, $blockSize);
            $netInt    = bcadd($baseInt, $offset);
            $netBin    = self::bcToBin($netInt);
            $subnets[] = new self(self::bin2ip($netBin) . "/$newPrefix");
        }
        return $subnets;
    }

    /**
     * Get the parent (supernet).
     */
    public function supernet(): self
    {
        if ($this->prefix === 0) {
            throw new IPException("Already at /0");
        }
        return new self($this->getNetworkAddress() . '/' . ($this->prefix - 1));
    }

    // ── Reverse DNS ──────────────────────────────────────────────────────────

    /**
     * Returns the reverse DNS zone (ip6.arpa nibble notation).
     * e.g. 2001:db8::/32 → "8.b.d.0.1.0.0.2.ip6.arpa"
     */
    public function getReverseDnsZone(): string
    {
        $hex     = bin2hex($this->networkBin);               // 32 hex chars
        $nibbles = array_reverse(str_split($hex));
        $numNibbles = (int) ceil($this->prefix / 4);
        $zone    = implode('.', array_slice($nibbles, 32 - $numNibbles));
        return $zone . '.ip6.arpa';
    }

    // ── Summary ───────────────────────────────────────────────────────────────

    public function info(): array
    {
        return [
            'cidr'                  => $this->getCidr(),
            'prefix'                => $this->prefix,
            'network_address'       => $this->getNetworkAddress(),
            'last_address'          => $this->getLastAddress(),
            'first_host'            => $this->getFirstHost(),
            'last_host'             => $this->getLastHost(),
            'total_addresses'       => $this->getTotalAddresses(),
            'address_type'          => $this->getAddressType(),
            'is_private'            => $this->isPrivate(),
            'is_link_local'         => $this->isLinkLocal(),
            'is_loopback'           => $this->isLoopback(),
            'is_multicast'          => $this->isMulticast(),
            'reverse_dns_zone'      => $this->getReverseDnsZone(),
        ];
    }

    public function __toString(): string
    {
        return $this->getCidr();
    }

    /*##################################################################*/
    /*========================== INTERNAL API ==========================*/
    /*##################################################################*/

    /** Convert IPv6 string to raw 16-byte binary string */
    private static function ip2bin(string $ip): string
    {
        return inet_pton($ip);
    }

    /** Convert raw 16-byte binary string to compressed IPv6 string */
    private static function bin2ip(string $bin): string
    {
        return inet_ntop($bin);
    }

    /** Build a 16-byte subnet mask from prefix length */
    private static function prefixToMaskBin(int $prefix): string
    {
        $mask = '';
        for ($byte = 0; $byte < 16; $byte++) {
            $bits = max(0, min(8, $prefix - $byte * 8));
            $mask .= chr($bits === 0 ? 0 : (0xFF & (0xFF << (8 - $bits))));
        }
        return $mask;
    }

    /** Add 1 to a 16-byte binary integer */
    private static function addOneToBin(string $bin): string
    {
        $bytes = array_values(unpack('C*', $bin));
        for ($i = 15; $i >= 0; $i--) {
            if ($bytes[$i] < 255) {
                $bytes[$i]++;
                break;
            }
            $bytes[$i] = 0;
        }
        return pack('C*', ...$bytes);
    }

    /** Subtract 1 from a 16-byte binary integer */
    private static function subOneFromBin(string $bin): string
    {
        $bytes = array_values(unpack('C*', $bin));
        for ($i = 15; $i >= 0; $i--) {
            if ($bytes[$i] > 0) {
                $bytes[$i]--;
                break;
            }
            $bytes[$i] = 255;
        }
        return pack('C*', ...$bytes);
    }

    private static function binToBc(string $bin): string
    {
        $result = '0';
        for ($i = 0; $i < 16; $i++) {
            $result = bcadd(bcmul($result, '256'), (string) ord($bin[$i]));
        }
        return $result;
    }

    private static function bcToBin(string $dec): string
    {
        $hex = '';
        $n   = $dec;
        while (bccomp($n, '0') > 0) {
            $rem = (int) bcmod($n, '16');
            $hex = dechex($rem) . $hex;
            $n   = bcdiv($n, '16', 0);
        }
        $hex = str_pad($hex, 32, '0', STR_PAD_LEFT);
        return hex2bin($hex);
    }
}
