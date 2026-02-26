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

namespace Laika\Core\Cidr\Version;

use Laika\Core\Exceptions\CidrException;

// ─────────────────────────────────────────────────────────────────────────────
// IPv4 CIDR
// ─────────────────────────────────────────────────────────────────────────────

class IPv4
{
    private int    $networkInt;   // network address as 32-bit unsigned int
    private int    $broadcastInt; // broadcast address as 32-bit unsigned int
    private int    $prefix;       // /0 – /32
    private int    $maskInt;      // subnet mask as 32-bit unsigned int

    // ── Construction ──────────────────────────────────────────────────────────

    public function __construct(string $cidr)
    {
        if (!str_contains($cidr, '/')) {
            throw new CidrException("Invalid CIDR (missing prefix length): $cidr");
        }

        [$ip, $prefixStr] = explode('/', $cidr, 2);

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            throw new CidrException("Invalid IPv4 address: $ip");
        }

        $prefix = (int) $prefixStr;
        if ($prefix < 0 || $prefix > 32) {
            throw new CidrException("IPv4 prefix must be 0–32, got: $prefix");
        }

        $this->prefix      = $prefix;
        $this->maskInt     = $prefix === 0 ? 0 : (~0 << (32 - $prefix)) & 0xFFFFFFFF;
        $ipInt             = self::ip2int($ip);
        $this->networkInt  = $ipInt & $this->maskInt;
        $this->broadcastInt = $this->networkInt | (~$this->maskInt & 0xFFFFFFFF);
    }

    /**
     * Create from a start IP and end IP.
     * Returns the tightest containing CIDR block.
     */
    public static function fromRange(string $startIp, string $endIp): self
    {
        if (!filter_var($startIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            throw new CidrException("Invalid start IPv4: $startIp");
        }
        if (!filter_var($endIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            throw new CidrException("Invalid end IPv4: $endIp");
        }

        $start = self::ip2int($startIp);
        $end   = self::ip2int($endIp);

        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        // Find the smallest prefix that covers both
        $diff   = $start ^ $end;
        $bits   = $diff === 0 ? 32 : (32 - (int) floor(log($diff, 2)) - 1);
        $prefix = $bits;

        // Walk up until the network covers both addresses
        while ($prefix >= 0) {
            $mask    = $prefix === 0 ? 0 : (~0 << (32 - $prefix)) & 0xFFFFFFFF;
            $network = $start & $mask;
            $bcast   = $network | (~$mask & 0xFFFFFFFF);
            if ($network <= $start && $bcast >= $end) {
                break;
            }
            $prefix--;
        }

        return new self(self::int2ip($start & $mask) . "/$prefix");
    }

    /**
     * Create from a network IP and a subnet mask (dotted-decimal).
     * e.g. ("192.168.1.0", "255.255.255.0")
     */
    public static function fromMask(string $networkIp, string $subnetMask): self
    {
        if (!filter_var($subnetMask, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            throw new CidrException("Invalid subnet mask: $subnetMask");
        }

        $maskInt = self::ip2int($subnetMask);
        $prefix  = substr_count(decbin($maskInt), '1');

        // Validate the mask is contiguous
        $expected = $prefix === 0 ? 0 : (~0 << (32 - $prefix)) & 0xFFFFFFFF;
        if ($maskInt !== $expected) {
            throw new CidrException("Subnet mask is not contiguous: $subnetMask");
        }

        return new self("$networkIp/$prefix");
    }

    // ── Core Information ──────────────────────────────────────────────────────

    /** Returns the CIDR string e.g. "192.168.1.0/24" */
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
        return self::int2ip($this->networkInt);
    }

    public function getBroadcastAddress(): string
    {
        return self::int2ip($this->broadcastInt);
    }

    public function getSubnetMask(): string
    {
        return self::int2ip($this->maskInt);
    }

    public function getWildcardMask(): string
    {
        return self::int2ip(~$this->maskInt & 0xFFFFFFFF);
    }

    public function getFirstUsableHost(): ?string
    {
        if ($this->prefix >= 31) {
            // /31 and /32 have no traditional "host" range
            return $this->prefix === 31 ? self::int2ip($this->networkInt) : self::int2ip($this->networkInt);
        }
        return self::int2ip($this->networkInt + 1);
    }

    public function getLastUsableHost(): ?string
    {
        if ($this->prefix >= 31) {
            return $this->prefix === 31 ? self::int2ip($this->broadcastInt) : self::int2ip($this->broadcastInt);
        }
        return self::int2ip($this->broadcastInt - 1);
    }

    /** Total addresses in the block (including network & broadcast) */
    public function getTotalAddresses(): int
    {
        return (int) pow(2, 32 - $this->prefix);
    }

    /** Usable host count (excludes network & broadcast for /0–/30) */
    public function getUsableHosts(): int
    {
        $total = $this->getTotalAddresses();
        if ($this->prefix >= 31) {
            return $total; // /31 point-to-point, /32 host route
        }
        return max(0, $total - 2);
    }

    public function getIpClass(): string
    {
        $first = ($this->networkInt >> 24) & 0xFF;
        return match (true) {
            $first < 128  => 'A',
            $first < 192  => 'B',
            $first < 224  => 'C',
            $first < 240  => 'D (Multicast)',
            default       => 'E (Reserved)',
        };
    }

    public function isPrivate(): bool
    {
        return $this->contains('10.0.0.0/8')
            || $this->contains('172.16.0.0/12')
            || $this->contains('192.168.0.0/16');
    }

    public function isLoopback(): bool
    {
        return $this->contains('127.0.0.0/8');
    }

    public function isLinkLocal(): bool
    {
        return $this->contains('169.254.0.0/16');
    }

    /** Does this CIDR fully contain another CIDR or IP? */
    public function contains(string $cidrOrIp): bool
    {
        if (str_contains($cidrOrIp, '/')) {
            $other = new self($cidrOrIp);
            return $other->networkInt  >= $this->networkInt
                && $other->broadcastInt <= $this->broadcastInt;
        }

        if (!filter_var($cidrOrIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            throw new CidrException("Invalid IP: $cidrOrIp");
        }

        $ipInt = self::ip2int($cidrOrIp);
        return $ipInt >= $this->networkInt && $ipInt <= $this->broadcastInt;
    }

    /** Do two CIDR blocks overlap? */
    public function overlaps(string $cidr): bool
    {
        $other = new self($cidr);
        return $this->networkInt  <= $other->broadcastInt
            && $this->broadcastInt >= $other->networkInt;
    }

    // ── IP Generation ─────────────────────────────────────────────────────────

    /**
     * Generate ALL IPs (network + broadcast included).
     * Uses a Generator to avoid loading millions of IPs into memory.
     *
     * @param int $limit  Max IPs to yield (0 = unlimited)
     * @return \Generator<string>
     */
    public function generateAllIPs(int $limit = 0): \Generator
    {
        $count = 0;
        for ($i = $this->networkInt; $i <= $this->broadcastInt; $i++) {
            yield self::int2ip($i);
            $count++;
            if ($limit > 0 && $count >= $limit) {
                return;
            }
        }
    }

    /**
     * Generate only usable host IPs (excludes network & broadcast for /0–/30).
     *
     * @param int $limit  Max IPs to yield (0 = unlimited)
     * @return \Generator<string>
     */
    public function generateIPs(int $limit = 0): \Generator
    {
        $start = $this->prefix >= 31 ? $this->networkInt  : $this->networkInt  + 1;
        $end   = $this->prefix >= 31 ? $this->broadcastInt : $this->broadcastInt - 1;

        $count = 0;
        for ($i = $start; $i <= $end; $i++) {
            yield self::int2ip($i);
            $count++;
            if ($limit > 0 && $count >= $limit) {
                return;
            }
        }
    }

    /**
     * Return all IPs as an array.
     * ⚠️  Use only for small subnets (e.g. /24 and smaller).
     */
    public function toArray(bool $usableOnly = true): array
    {
        $gen = $usableOnly ? $this->generateIPs() : $this->generateAllIPs();
        return iterator_to_array($gen, false);
    }

    // ── Splitting & Subnetting ────────────────────────────────────────────────

    /**
     * Split this CIDR into $count equal sub-networks.
     * $count must be a power of 2.
     *
     * @return self[]
     */
    public function split(int $count): array
    {
        if ($count < 2 || ($count & ($count - 1)) !== 0) {
            throw new CidrException("Split count must be a power of 2, got: $count");
        }

        $newPrefix = $this->prefix + (int) log($count, 2);
        if ($newPrefix > 32) {
            throw new CidrException("Cannot split /{$this->prefix} into $count subnets (would exceed /32)");
        }

        $subnets   = [];
        $blockSize = (int) pow(2, 32 - $newPrefix);
        for ($i = 0; $i < $count; $i++) {
            $networkInt = $this->networkInt + ($i * $blockSize);
            $subnets[]  = new self(self::int2ip($networkInt) . "/$newPrefix");
        }
        return $subnets;
    }

    /**
     * Get the parent (supernet) of this CIDR.
     */
    public function supernet(): self
    {
        if ($this->prefix === 0) {
            throw new CidrException("Already at /0, no supernet available");
        }
        return new self($this->getNetworkAddress() . '/' . ($this->prefix - 1));
    }

    /**
     * Get the sibling CIDR block (same size, adjacent).
     */
    public function sibling(): self
    {
        $blockSize  = $this->getTotalAddresses();
        $siblingInt = $this->networkInt ^ $blockSize; // XOR flips the bit
        return new self(self::int2ip($siblingInt) . "/{$this->prefix}");
    }

    // ── Summarisation ─────────────────────────────────────────────────────────

    /**
     * Summarise a list of IPv4 CIDR strings into the minimal covering set.
     *
     * @param  string[] $cidrs
     * @return self[]
     */
    public static function summarise(array $cidrs): array
    {
        // Collect all [network, broadcast] ranges
        $ranges = array_map(fn(string $c) => new self($c), $cidrs);

        // Sort by network address
        usort($ranges, fn(self $a, self $b) => $a->networkInt <=> $b->networkInt);

        $merged = [];
        foreach ($ranges as $range) {
            if (empty($merged)) {
                $merged[] = $range;
                continue;
            }
            $last = end($merged);
            if ($range->networkInt <= $last->broadcastInt + 1) {
                // Extend the last range if needed
                if ($range->broadcastInt > $last->broadcastInt) {
                    $merged[count($merged) - 1] = self::fromRange(
                        $last->getNetworkAddress(),
                        self::int2ip($range->broadcastInt)
                    );
                }
            } else {
                $merged[] = $range;
            }
        }

        return $merged;
    }

    // ── Reverse DNS ──────────────────────────────────────────────────────────

    /**
     * Returns the reverse DNS zone name for this network.
     * e.g. 192.168.1.0/24  → "1.168.192.in-addr.arpa"
     */
    public function getReverseDnsZone(): string
    {
        $octets  = explode('.', $this->getNetworkAddress());
        $numOctets = (int) ceil($this->prefix / 8);
        $zone    = implode('.', array_reverse(array_slice($octets, 0, $numOctets)));
        return $zone . '.in-addr.arpa';
    }

    // ── Summary ───────────────────────────────────────────────────────────────

    public function toArray_info(): array
    {
        return [
            'cidr'              => $this->getCidr(),
            'prefix'            => $this->prefix,
            'network_address'   => $this->getNetworkAddress(),
            'broadcast_address' => $this->getBroadcastAddress(),
            'subnet_mask'       => $this->getSubnetMask(),
            'wildcard_mask'     => $this->getWildcardMask(),
            'first_host'        => $this->getFirstUsableHost(),
            'last_host'         => $this->getLastUsableHost(),
            'total_addresses'   => $this->getTotalAddresses(),
            'usable_hosts'      => $this->getUsableHosts(),
            'ip_class'          => $this->getIpClass(),
            'is_private'        => $this->isPrivate(),
            'is_loopback'       => $this->isLoopback(),
            'is_link_local'     => $this->isLinkLocal(),
            'reverse_dns_zone'  => $this->getReverseDnsZone(),
        ];
    }

    public function __toString(): string
    {
        return $this->getCidr();
    }

    // ── Internal Helpers ──────────────────────────────────────────────────────

    private static function ip2int(string $ip): int
    {
        return (int) sprintf('%u', ip2long($ip));
    }

    private static function int2ip(int $int): string
    {
        return long2ip($int);
    }
}
