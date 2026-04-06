# IP Utilities — Laika Core

Part of the **[Laika PHP MVC Framework](https://github.com/laikait/laika-core)** · `laikait/laika-core`

> Full-featured IPv4 & IPv6 CIDR toolkit — zero external dependencies, BCMath only.

**Author:** Showket Ahmed &lt;riyadhtayf@gmail.com&gt;  
**License:** MIT  
**Requirements:** PHP 8.1+ · `bcmath` extension (on by default)

---

## Table of Contents

1. [Installation](#installation)
2. [Namespace Overview](#namespace-overview)
3. [Classes at a Glance](#classes-at-a-glance)
4. [IPv4 — Parsing & Construction](#ipv4--parsing--construction)
5. [IPv4 — Full Info Dump](#ipv4--full-info-dump)
6. [IPv4 — Generate IPs](#ipv4--generate-ips)
7. [IPv4 — Build CIDR from IP Range](#ipv4--build-cidr-from-ip-range)
8. [IPv4 — Build CIDR from Subnet Mask](#ipv4--build-cidr-from-subnet-mask)
9. [IPv4 — Contains & IP-in-CIDR](#ipv4--contains--ip-in-cidr)
10. [IPv4 — Overlaps](#ipv4--overlaps)
11. [IPv4 — Splitting into Subnets](#ipv4--splitting-into-subnets)
12. [IPv4 — Supernet & Sibling](#ipv4--supernet--sibling)
13. [IPv4 — Summarise / Merge Ranges](#ipv4--summarise--merge-ranges)
14. [IPv4 — Reverse DNS Zone](#ipv4--reverse-dns-zone)
15. [IPv4 — Special Address Flags](#ipv4--special-address-flags)
16. [IPv6 — Parsing & Construction](#ipv6--parsing--construction)
17. [IPv6 — Full Info Dump](#ipv6--full-info-dump)
18. [IPv6 — Generate IPs](#ipv6--generate-ips)
19. [IPv6 — Build CIDR from IP Range](#ipv6--build-cidr-from-ip-range)
20. [IPv6 — Contains & Overlaps](#ipv6--contains--overlaps)
21. [IPv6 — Splitting into Subnets](#ipv6--splitting-into-subnets)
22. [IPv6 — Supernet](#ipv6--supernet)
23. [IPv6 — Reverse DNS Zone](#ipv6--reverse-dns-zone)
24. [IPv6 — Address Type & Flags](#ipv6--address-type--flags)
25. [Unified Façade — IP::](#unified-façade--cidr)
26. [Error Handling](#error-handling)
27. [API Reference](#api-reference)

---

## Installation

```bash
composer require laikait/laika-core
```

---

## Namespace Overview

```
Laika\Core\IP\IP                  ← Unified façade (start here)
Laika\Core\IP\Version\IPv4          ← IPv4-specific operations
Laika\Core\IP\Version\IPv6          ← IPv6-specific operations
Laika\Core\Exceptions\IPException   ← Thrown on any invalid input
```

---

## Classes at a Glance

|     Class     |        Namespace        |                   Purpose                    |
|---------------|-------------------------|----------------------------------------------|
| `IP`          | `Laika\Core\IP`         | Auto-detecting façade — the main entry point |
| `IPv4`        | `Laika\Core\IP\Version` | Full IPv4 CIDR operations                    |
| `IPv6`        | `Laika\Core\IP\Version` | Full IPv6 CIDR operations                    |
| `IPException` | `Laika\Core\Exceptions` | Thrown on invalid input                      |

---

## IPv4 — Parsing & Construction

```php
use Laika\Core\IP\IP;
use Laika\Core\IP\Version\IPv4;

// Via the unified façade (recommended)
$net = IP::parse('192.168.1.0/24');       // returns IPv4 instance

// Direct construction
$net = new IPv4('192.168.1.0/24');

// IP is normalised to the true network address automatically
$net = new IPv4('192.168.1.100/24');        // normalised → 192.168.1.0/24

// Every valid prefix /0–/32 is supported
$all  = new IPv4('0.0.0.0/0');              // entire internet
$host = new IPv4('10.0.0.1/32');            // single host route
$ptp  = new IPv4('10.0.0.0/31');            // point-to-point link (RFC 3021)
```

---

## IPv4 — Full Info Dump

`info()` returns every property in one call — useful for APIs or debugging.

```php
use Laika\Core\IP\IP;

$net = IP::parse('192.168.1.0/24');

print_r($net->info());
```

```
Array
(
    [cidr]              => 192.168.1.0/24
    [prefix]            => 24
    [network_address]   => 192.168.1.0
    [broadcast_address] => 192.168.1.255
    [subnet_mask]       => 255.255.255.0
    [wildcard_mask]     => 0.0.0.255
    [first_host]        => 192.168.1.1
    [last_host]         => 192.168.1.254
    [total_addresses]   => 256
    [usable_hosts]      => 254
    [ip_class]          => C
    [is_private]        => true
    [is_loopback]       => false
    [is_link_local]     => false
    [reverse_dns_zone]  => 1.168.192.in-addr.arpa
)
```

Individual getters:

```php
use Laika\Core\IP\IP;

$net = IP::parse('10.0.0.0/8');

echo $net->getCidr();               // "10.0.0.0/8"
echo $net->getPrefix();             // 8
echo $net->getNetworkAddress();     // "10.0.0.0"
echo $net->getBroadcastAddress();   // "10.255.255.255"
echo $net->getSubnetMask();         // "255.0.0.0"
echo $net->getWildcardMask();       // "0.255.255.255"
echo $net->getFirstUsableHost();    // "10.0.0.1"
echo $net->getLastUsableHost();     // "10.255.255.254"
echo $net->getTotalAddresses();     // 16777216
echo $net->getUsableHosts();        // 16777214
echo $net->getIpClass();            // "A"
echo (string) $net;                 // "10.0.0.0/8"  via __toString()
```

---

## IPv4 — Generate IPs

All generators are memory-safe — they yield one IP at a time regardless of block size.

### Usable hosts only (excludes network & broadcast)

```php
use Laika\Core\IP\IP;

$net = IP::parse('192.168.1.0/29');   // 6 usable hosts

foreach ($net->generateIPs() as $ip) {
    echo $ip . "\n";
}
// 192.168.1.1
// 192.168.1.2
// 192.168.1.3
// 192.168.1.4
// 192.168.1.5
// 192.168.1.6
```

### All IPs including network address and broadcast

```php
use Laika\Core\IP\IP;

$net = IP::parse('10.0.0.0/30');   // 4 total addresses

foreach ($net->generateAllIPs() as $ip) {
    echo $ip . "\n";
}
// 10.0.0.0   ← network
// 10.0.0.1
// 10.0.0.2
// 10.0.0.3   ← broadcast
```

### Limit output for large subnets

```php
use Laika\Core\IP\IP;

$net = IP::parse('10.0.0.0/8');   // 16 million IPs — always use a limit!

foreach ($net->generateIPs(limit: 5) as $ip) {
    echo $ip . "\n";
}
// 10.0.0.1
// 10.0.0.2
// 10.0.0.3
// 10.0.0.4
// 10.0.0.5
```

### Collect into an array

Only safe for small subnets — `/28` or smaller is recommended:

```php
use Laika\Core\IP\IP;

// Usable hosts only (default)
$arr = IP::parse('10.0.0.0/28')->toArray();
// ['10.0.0.1', '10.0.0.2', ..., '10.0.0.14']

// All IPs including network & broadcast
$arr = IP::parse('10.0.0.0/28')->toArray(usableOnly: false);
// ['10.0.0.0', '10.0.0.1', ..., '10.0.0.15']
```

### /31 and /32 edge cases

```php
use Laika\Core\IP\IP;

// /31 — point-to-point (RFC 3021), both IPs are usable
foreach (IP::parse('10.0.0.0/31')->generateAllIPs() as $ip) {
    echo $ip . "\n";
}
// 10.0.0.0
// 10.0.0.1

// /32 — single host route
foreach (IP::parse('203.0.113.5/32')->generateAllIPs() as $ip) {
    echo $ip . "\n";
}
// 203.0.113.5
```

---

## IPv4 — Build CIDR from IP Range

Returns the **tightest single CIDR block** that fully contains the given start→end range.

```php
use Laika\Core\IP\IP;

// Exact block
$net = IP::fromRange('192.168.1.0', '192.168.1.255');
echo $net->getCidr();   // 192.168.1.0/24

// Offset hosts — still returns the covering /24
$net = IP::fromRange('192.168.1.1', '192.168.1.254');
echo $net->getCidr();   // 192.168.1.0/24

// Quarter-block
$net = IP::fromRange('172.16.5.0', '172.16.5.63');
echo $net->getCidr();   // 172.16.5.0/26

// Class B range
$net = IP::fromRange('192.168.0.0', '192.168.255.255');
echo $net->getCidr();   // 192.168.0.0/16

// Class A range
$net = IP::fromRange('10.0.0.0', '10.255.255.255');
echo $net->getCidr();   // 10.0.0.0/8

// Reversed order is handled automatically
$net = IP::fromRange('192.168.1.255', '192.168.1.0');
echo $net->getCidr();   // 192.168.1.0/24

// Single IP → /32
$net = IP::fromRange('8.8.8.8', '8.8.8.8');
echo $net->getCidr();   // 8.8.8.8/32

// Entire IPv4 internet
$net = IP::fromRange('0.0.0.0', '255.255.255.255');
echo $net->getCidr();   // 0.0.0.0/0
```

---

## IPv4 — Build CIDR from Subnet Mask

Traditional dotted-decimal subnet masks are fully supported.

```php
use Laika\Core\IP\IP;

$net = IP::fromMask('192.168.1.0',   '255.255.255.0');
echo $net->getCidr();   // 192.168.1.0/24

$net = IP::fromMask('10.0.0.0',      '255.0.0.0');
echo $net->getCidr();   // 10.0.0.0/8

$net = IP::fromMask('172.16.0.0',    '255.255.0.0');
echo $net->getCidr();   // 172.16.0.0/16

$net = IP::fromMask('192.168.1.128', '255.255.255.128');
echo $net->getCidr();   // 192.168.1.128/25

$net = IP::fromMask('10.20.30.0',    '255.255.255.252');
echo $net->getCidr();   // 10.20.30.0/30

// Non-contiguous mask → IPException
try {
    IP::fromMask('10.0.0.0', '255.0.255.0');
} catch (\Laika\Core\Exceptions\IPException $e) {
    echo $e->getMessage();  // "Subnet mask is not contiguous: 255.0.255.0"
}
```

---

## IPv4 — Contains & IP-in-CIDR

### Check if an IP is inside a block

```php
use Laika\Core\IP\IP;

$net = IP::parse('192.168.1.0/24');

var_dump($net->contains('192.168.1.1'));    // true  — first host
var_dump($net->contains('192.168.1.254')); // true  — last host
var_dump($net->contains('192.168.1.0'));   // true  — network address
var_dump($net->contains('192.168.1.255')); // true  — broadcast
var_dump($net->contains('192.168.2.0'));   // false — next block
var_dump($net->contains('10.0.0.1'));      // false — different range
```

### Check if a CIDR block is fully inside another

```php
use Laika\Core\IP\IP;

$outer = IP::parse('10.0.0.0/8');

var_dump($outer->contains('10.0.0.0/24'));    // true  — sub-block
var_dump($outer->contains('10.255.0.0/16'));  // true  — sub-block
var_dump($outer->contains('10.0.0.0/8'));     // true  — identical block
var_dump($outer->contains('10.0.0.0/4'));     // false — supernet is larger
var_dump($outer->contains('172.16.0.0/12'));  // false — different range
```

### Convenience: `IP::ipInCidr()`

```php
use Laika\Core\IP\IP;

var_dump(IP::ipInCidr('8.8.8.8',       '8.8.8.0/24'));     // true
var_dump(IP::ipInCidr('8.8.4.4',       '8.8.8.0/24'));     // false
var_dump(IP::ipInCidr('192.168.1.100', '192.168.0.0/16')); // true
var_dump(IP::ipInCidr('1.1.1.1',       '0.0.0.0/0'));      // true — global
```

---

## IPv4 — Overlaps

Two blocks overlap if they share **any** addresses, even partially.

```php
use Laika\Core\IP\IP;

$net = IP::parse('192.168.1.0/24');

var_dump($net->overlaps('192.168.1.128/25')); // true  — sub-block fully inside
var_dump($net->overlaps('192.168.0.0/23'));   // true  — supernet contains it
var_dump($net->overlaps('192.168.1.0/24'));   // true  — identical block
var_dump($net->overlaps('192.168.0.0/24'));   // false — adjacent, no shared IPs
var_dump($net->overlaps('192.168.2.0/24'));   // false — adjacent, no shared IPs
var_dump($net->overlaps('10.0.0.0/8'));       // false — completely separate
```

---

## IPv4 — Splitting into Subnets

Split any block into equal sub-networks. Count must be a power of 2.

```php
use Laika\Core\IP\IP;

// Split a /24 into 4 × /26
$net     = IP::parse('192.168.1.0/24');
$subnets = $net->split(4);

foreach ($subnets as $i => $subnet) {
    printf(
        "Subnet %d: %-20s first=%-16s last=%-16s hosts=%d\n",
        $i + 1,
        $subnet->getCidr(),
        $subnet->getFirstUsableHost(),
        $subnet->getLastUsableHost(),
        $subnet->getUsableHosts()
    );
}
// Subnet 1: 192.168.1.0/26      first=192.168.1.1    last=192.168.1.62    hosts=62
// Subnet 2: 192.168.1.64/26     first=192.168.1.65   last=192.168.1.126   hosts=62
// Subnet 3: 192.168.1.128/26    first=192.168.1.129  last=192.168.1.190   hosts=62
// Subnet 4: 192.168.1.192/26    first=192.168.1.193  last=192.168.1.254   hosts=62

// Split a /22 into 8 × /25
$subnets = IP::parse('10.0.0.0/22')->split(8);
foreach ($subnets as $s) { echo $s->getCidr() . "\n"; }
// 10.0.0.0/25, 10.0.0.128/25, 10.0.1.0/25, 10.0.1.128/25 ...

// Split into 2 halves
$halves = IP::parse('172.16.0.0/16')->split(2);
echo $halves[0]->getCidr();   // 172.16.0.0/17
echo $halves[1]->getCidr();   // 172.16.128.0/17

// Non-power-of-2 → IPException
try {
    IP::parse('10.0.0.0/24')->split(3);
} catch (\Laika\Core\Exceptions\IPException $e) {
    echo $e->getMessage();    // "Split count must be a power of 2, got: 3"
}
```

---

## IPv4 — Supernet & Sibling

Navigate up and sideways in the subnet tree.

```php
use Laika\Core\IP\IP;

$net = IP::parse('192.168.1.0/25');

// One level up — the parent block
echo $net->supernet()->getCidr();                         // 192.168.1.0/24
echo $net->supernet()->supernet()->getCidr();             // 192.168.0.0/23
echo $net->supernet()->supernet()->supernet()->getCidr(); // 192.168.0.0/22

// The sibling — the adjacent same-size block sharing the same parent
echo $net->sibling()->getCidr();    // 192.168.1.128/25

// Together they form the parent /24
// 192.168.1.0/25  +  192.168.1.128/25  =  192.168.1.0/24

// Edge case: already at /0
try {
    IP::parse('0.0.0.0/0')->supernet();
} catch (\Laika\Core\Exceptions\IPException $e) {
    echo $e->getMessage();  // "Already at /0, no supernet available"
}
```

---

## IPv4 — Summarise / Merge Ranges

Collapse a list of CIDR blocks into the minimal set of covering blocks.

```php
use Laika\Core\IP\IP;

// Two adjacent /25s merge into a /24
$merged = IP::summarise(['192.168.1.0/25', '192.168.1.128/25']);
echo $merged[0]->getCidr();   // 192.168.1.0/24

// Three blocks collapse into one /23
$merged = IP::summarise(['10.0.0.0/25', '10.0.0.128/25', '10.0.1.0/24']);
echo $merged[0]->getCidr();   // 10.0.0.0/23

// Non-contiguous blocks stay separate
$input  = ['10.0.0.0/24', '10.0.2.0/24', '10.0.4.0/24'];
$merged = IP::summarise($input);
foreach ($merged as $m) { echo $m->getCidr() . "\n"; }
// 10.0.0.0/24
// 10.0.2.0/24
// 10.0.4.0/24

// Input order does not matter — sorted internally
$input  = ['192.168.3.0/24', '192.168.1.0/24', '192.168.2.0/24', '192.168.0.0/24'];
$merged = IP::summarise($input);
echo $merged[0]->getCidr();   // 192.168.0.0/22
```

---

## IPv4 — Reverse DNS Zone

```php
use Laika\Core\IP\IP;

echo IP::parse('192.168.1.0/24')->getReverseDnsZone();  // 1.168.192.in-addr.arpa
echo IP::parse('10.0.0.0/8')->getReverseDnsZone();      // 10.in-addr.arpa
echo IP::parse('172.16.0.0/12')->getReverseDnsZone();   // 16.172.in-addr.arpa
echo IP::parse('8.8.8.0/24')->getReverseDnsZone();      // 8.8.8.in-addr.arpa
echo IP::parse('10.20.30.0/30')->getReverseDnsZone();   // 30.20.10.in-addr.arpa
```

---

## IPv4 — Special Address Flags

```php
use Laika\Core\IP\IP;

// Private ranges (RFC 1918)
var_dump(IP::parse('10.0.0.0/8')->isPrivate());         // true
var_dump(IP::parse('172.16.0.0/12')->isPrivate());      // true
var_dump(IP::parse('192.168.0.0/16')->isPrivate());     // true
var_dump(IP::parse('8.8.8.0/24')->isPrivate());         // false

// Loopback (127.0.0.0/8)
var_dump(IP::parse('127.0.0.0/8')->isLoopback());       // true
var_dump(IP::parse('127.0.0.1/32')->isLoopback());      // true
var_dump(IP::parse('192.168.1.0/24')->isLoopback());    // false

// Link-local (169.254.0.0/16)
var_dump(IP::parse('169.254.0.0/16')->isLinkLocal());   // true
var_dump(IP::parse('169.254.1.0/24')->isLinkLocal());   // true
var_dump(IP::parse('192.168.1.0/24')->isLinkLocal());   // false

// IP Class
echo IP::parse('10.0.0.0/8')->getIpClass();             // A
echo IP::parse('172.16.0.0/12')->getIpClass();          // B
echo IP::parse('192.168.0.0/16')->getIpClass();         // C
echo IP::parse('224.0.0.0/4')->getIpClass();            // D (Multicast)
echo IP::parse('240.0.0.0/4')->getIpClass();            // E (Reserved)
```

---

## IPv6 — Parsing & Construction

```php
use Laika\Core\IP\IP;
use Laika\Core\IP\Version\IPv6;

// Via the unified façade (recommended)
$net = IP::parse('2001:db8::/32');          // returns IPv6 instance

// Direct construction
$net = new IPv6('2001:db8::/32');

// Various prefix lengths
$net = IP::parse('fe80::/10');              // link-local range
$net = IP::parse('fd00::/8');               // ULA range
$net = IP::parse('2001:db8:abcd::/48');     // typical site prefix
$net = IP::parse('2001:db8:abcd:1::/64');   // typical LAN segment
$net = IP::parse('::1/128');                // loopback host route
$net = IP::parse('::/0');                   // default route
```

---

## IPv6 — Full Info Dump

```php
use Laika\Core\IP\IP;

$net = IP::parse('2001:db8::/32');

print_r($net->info());
```

```
Array
(
    [cidr]             => 2001:db8::/32
    [prefix]           => 32
    [network_address]  => 2001:db8::
    [last_address]     => 2001:db8:ffff:ffff:ffff:ffff:ffff:ffff
    [first_host]       => 2001:db8::1
    [last_host]        => 2001:db8:ffff:ffff:ffff:ffff:ffff:fffe
    [total_addresses]  => 79228162514264337593543950336
    [address_type]     => Documentation
    [is_private]       => false
    [is_link_local]    => false
    [is_loopback]      => false
    [is_multicast]     => false
    [reverse_dns_zone] => 8.b.d.0.1.0.0.2.ip6.arpa
)
```

Individual getters:

```php
use Laika\Core\IP\IP;

$net = IP::parse('2001:db8::/32');

echo $net->getCidr();                 // "2001:db8::/32"
echo $net->getPrefix();               // 32
echo $net->getNetworkAddress();       // "2001:db8::"
echo $net->getLastAddress();          // "2001:db8:ffff:ffff:ffff:ffff:ffff:ffff"
echo $net->getFirstHost();            // "2001:db8::1"
echo $net->getLastHost();             // "2001:db8:ffff:ffff:ffff:ffff:ffff:fffe"
echo $net->getTotalAddresses();       // BCMath string e.g. "79228162514264337593543950336"
echo $net->getTotalAddressesString(); // alias of above
echo $net->getAddressType();          // "Documentation"
echo (string) $net;                   // "2001:db8::/32"  via __toString()
```

---

## IPv6 — Generate IPs

> ⚠️ IPv6 blocks are astronomically large. **Always pass a `$limit`** unless working with `/120`–`/128` blocks.

```php
use Laika\Core\IP\IP;

// Small /120 block — first 8 IPs
$net = IP::parse('fe80::/120');
foreach ($net->generateIPs(8) as $ip) {
    echo $ip . "\n";
}
// fe80::
// fe80::1
// fe80::2
// fe80::3  ...

// /128 — single address
foreach (IP::parse('::1/128')->generateIPs() as $ip) {
    echo $ip . "\n";
}
// ::1

// Collect a /126 block (4 addresses) into array
$arr = IP::parse('2001:db8::/126')->toArray();
// ['2001:db8::', '2001:db8::1', '2001:db8::2', '2001:db8::3']

// Large block — always cap with a limit
$net = IP::parse('2001:db8::/32');
foreach ($net->generateIPs(limit: 3) as $ip) {
    echo $ip . "\n";
}
// 2001:db8::
// 2001:db8::1
// 2001:db8::2
```

---

## IPv6 — Build CIDR from IP Range

```php
use Laika\Core\IP\IP;

// Exact /64
$net = IP::fromRange('2001:db8::', '2001:db8::ffff:ffff:ffff:ffff');
echo $net->getCidr();   // 2001:db8::/64

// Tight block from small range
$net = IP::fromRange('2001:db8::1', '2001:db8::ff');
echo $net->getCidr();   // 2001:db8::/56

// Link-local range
$net = IP::fromRange('fe80::1', 'fe80::ffff');
echo $net->getCidr();   // fe80::/112

// Single IP → /128
$net = IP::fromRange('::1', '::1');
echo $net->getCidr();   // ::1/128
```

---

## IPv6 — Contains & Overlaps

```php
use Laika\Core\IP\IP;

$net = IP::parse('2001:db8::/32');

// Contains — IP
var_dump($net->contains('2001:db8::1'));           // true
var_dump($net->contains('2001:db8:abcd::1'));      // true
var_dump($net->contains('2001:db9::1'));           // false

// Contains — sub-block
var_dump($net->contains('2001:db8::/48'));         // true
var_dump($net->contains('2001:db8:1234::/48'));    // true
var_dump($net->contains('2001:db8::/31'));         // false — supernet

// Overlaps
var_dump($net->overlaps('2001:db8:1::/48'));       // true  — inside
var_dump($net->overlaps('2001:db8::/16'));         // true  — supernet
var_dump($net->overlaps('2001:db8::/32'));         // true  — identical
var_dump($net->overlaps('2001:db9::/32'));         // false — different range
```

---

## IPv6 — Splitting into Subnets

```php
use Laika\Core\IP\IP;

// Split /32 into 4 × /34
$subnets = IP::parse('2001:db8::/32')->split(4);
foreach ($subnets as $i => $s) {
    echo "Subnet " . ($i + 1) . ": " . $s->getCidr() . "\n";
}
// Subnet 1: 2001:db8::/34
// Subnet 2: 2001:db8:4000::/34
// Subnet 3: 2001:db8:8000::/34
// Subnet 4: 2001:db8:c000::/34

// Split /48 into 8 × /51
$subnets = IP::parse('2001:db8:abcd::/48')->split(8);
foreach ($subnets as $s) { echo $s->getCidr() . "\n"; }

// Split /64 into 2 halves
$halves = IP::parse('fe80::/64')->split(2);
echo $halves[0]->getCidr();   // fe80::/65
echo $halves[1]->getCidr();   // fe80:0:0:0:8000::/65
```

---

## IPv6 — Supernet

```php
use Laika\Core\IP\IP;

$net = IP::parse('2001:db8::/48');

echo $net->supernet()->getCidr();                          // 2001:db8::/47
echo $net->supernet()->supernet()->getCidr();              // 2001:db8::/46
echo $net->supernet()->supernet()->supernet()->getCidr();  // 2001:db8::/45

// Walk up to /32
$current = IP::parse('2001:db8:abcd:1::/64');
while ($current->getPrefix() > 32) {
    $current = $current->supernet();
}
echo $current->getCidr();   // 2001:db8::/32
```

---

## IPv6 — Reverse DNS Zone

```php
use Laika\Core\IP\IP;

echo IP::parse('2001:db8::/32')->getReverseDnsZone();
// 8.b.d.0.1.0.0.2.ip6.arpa

echo IP::parse('fe80::/64')->getReverseDnsZone();
// 0.0.0.0.0.0.0.0.0.8.e.f.ip6.arpa

echo IP::parse('::1/128')->getReverseDnsZone();
// 1.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.ip6.arpa

echo IP::parse('fd00::/8')->getReverseDnsZone();
// d.f.ip6.arpa

echo IP::parse('2001:db8:abcd::/48')->getReverseDnsZone();
// d.c.b.a.8.b.d.0.1.0.0.2.ip6.arpa
```

---

## IPv6 — Address Type & Flags

```php
use Laika\Core\IP\IP;

// getAddressType()
echo IP::parse('::1/128')->getAddressType();           // Loopback
echo IP::parse('fe80::/10')->getAddressType();         // Link-Local
echo IP::parse('fc00::/7')->getAddressType();          // Unique Local (ULA)
echo IP::parse('fd12:3456::/32')->getAddressType();    // Unique Local (ULA)
echo IP::parse('ff02::1/128')->getAddressType();       // Multicast
echo IP::parse('2002::/16')->getAddressType();         // 6to4
echo IP::parse('2001:db8::/32')->getAddressType();     // Documentation
echo IP::parse('2607:f8b0::/32')->getAddressType();    // Global Unicast
echo IP::parse('::/0')->getAddressType();              // Unspecified

// Boolean flags
$net = IP::parse('fd12:3456::/32');
var_dump($net->isPrivate());      // true  — ULA (fd::/8)
var_dump($net->isLinkLocal());    // false
var_dump($net->isLoopback());     // false
var_dump($net->isMulticast());    // false

$net = IP::parse('fe80::1/64');
var_dump($net->isLinkLocal());    // true
var_dump($net->isPrivate());      // false

var_dump(IP::parse('ff02::1/128')->isMulticast());  // true
var_dump(IP::parse('::1/128')->isLoopback());       // true
```

---

## Unified Façade — `IP::`

`Cidr` auto-detects IPv4 vs IPv6 so you never need to import `IPv4` or `IPv6` directly in most cases.

```php
use Laika\Core\IP\IP;

// parse() — returns IPv4 or IPv6 instance automatically
$v4 = IP::parse('192.168.1.0/24');    // instanceof IPv4
$v6 = IP::parse('2001:db8::/32');     // instanceof IPv6

// fromRange() — works for both families
$v4 = IP::fromRange('192.168.1.1',  '192.168.1.254');  // IPv4
$v6 = IP::fromRange('2001:db8::1',  '2001:db8::ff');   // IPv6

// fromMask() — IPv4 only
$v4 = IP::fromMask('10.0.0.0', '255.0.0.0');

// ipInCidr() — works for both families
var_dump(IP::ipInCidr('192.168.1.50', '192.168.1.0/24'));  // true
var_dump(IP::ipInCidr('2001:db8::1',  '2001:db8::/32'));   // true
var_dump(IP::ipInCidr('10.0.0.1',     '192.168.1.0/24'));  // false

// summarise() — collapses a fragmented IPv4 CIDR list
$merged = IP::summarise(['10.0.0.0/25', '10.0.0.128/25']);
echo $merged[0]->getCidr();   // 10.0.0.0/24
```

---

## Error Handling

Every invalid input throws `Laika\Core\Exceptions\IPException` which extends `\RuntimeException`.

```php
use Laika\Core\IP\IP;
use Laika\Core\Exceptions\IPException;

// Missing prefix length
try {
    IP::parse('192.168.1.0');
} catch (IPException $e) {
    echo $e->getMessage();  // "Invalid CIDR (missing prefix length): 192.168.1.0"
}

// Invalid IP address
try {
    IP::parse('999.0.0.1/24');
} catch (IPException $e) {
    echo $e->getMessage();  // "Invalid IPv4 address: 999.0.0.1"
}

// Prefix out of range
try {
    IP::parse('10.0.0.0/33');
} catch (IPException $e) {
    echo $e->getMessage();  // "IPv4 prefix must be 0–32, got: 33"
}

// Non-contiguous subnet mask
try {
    IP::fromMask('10.0.0.0', '255.0.255.0');
} catch (IPException $e) {
    echo $e->getMessage();  // "Subnet mask is not contiguous: 255.0.255.0"
}

// Split count not a power of 2
try {
    IP::parse('10.0.0.0/24')->split(3);
} catch (IPException $e) {
    echo $e->getMessage();  // "Split count must be a power of 2, got: 3"
}

// Split would exceed maximum prefix
try {
    IP::parse('10.0.0.0/31')->split(4);
} catch (IPException $e) {
    echo $e->getMessage();  // "Cannot split /31 into 4 subnets (would exceed /32)"
}

// Supernet at /0
try {
    IP::parse('0.0.0.0/0')->supernet();
} catch (IPException $e) {
    echo $e->getMessage();  // "Already at /0, no supernet available"
}

// IPv6 prefix out of range
try {
    IP::parse('2001:db8::/129');
} catch (IPException $e) {
    echo $e->getMessage();  // "IPv6 prefix must be 0–128, got: 129"
}

// Unrecognised address family
try {
    IP::parse('not-an-ip/24');
} catch (IPException $e) {
    echo $e->getMessage();  // "Cannot detect address family for: not-an-ip/24"
}
```

---

## API Reference

### `Cidr` — `Laika\Core\IP\IP`

| Method | Returns | Description |
|---|---|---|
| `parse(string $cidr)` | `IPv4\|IPv6` | Auto-detect family and parse CIDR string |
| `fromRange(string $start, string $end)` | `IPv4\|IPv6` | Tightest CIDR covering an IP range |
| `fromMask(string $ip, string $mask)` | `IPv4` | Build from dotted-decimal subnet mask |
| `ipInCidr(string $ip, string $cidr)` | `bool` | Check if IP is within a CIDR block |
| `summarise(array $cidrs)` | `IPv4[]` | Merge and collapse a fragmented CIDR list |

### `IPv4` — `Laika\Core\IP\Version\IPv4`

| Method | Returns | Description |
|---|---|---|
| `__construct(string $cidr)` | | Parse from CIDR string |
| `fromRange(string $start, string $end)` | `self` | Tightest CIDR covering a range |
| `fromMask(string $ip, string $mask)` | `self` | Build from dotted-decimal subnet mask |
| `summarise(array $cidrs)` | `self[]` | Merge/collapse CIDR list |
| `getCidr()` | `string` | Normalised CIDR e.g. `"192.168.1.0/24"` |
| `getPrefix()` | `int` | Prefix length |
| `getNetworkAddress()` | `string` | Network address |
| `getBroadcastAddress()` | `string` | Broadcast address |
| `getSubnetMask()` | `string` | Dotted-decimal subnet mask |
| `getWildcardMask()` | `string` | Wildcard (inverse) mask |
| `getFirstUsableHost()` | `string` | First usable host IP |
| `getLastUsableHost()` | `string` | Last usable host IP |
| `getTotalAddresses()` | `int` | Total IPs including network & broadcast |
| `getUsableHosts()` | `int` | Usable host count |
| `getIpClass()` | `string` | `A`, `B`, `C`, `D (Multicast)`, `E (Reserved)` |
| `isPrivate()` | `bool` | RFC 1918 private range |
| `isLoopback()` | `bool` | `127.0.0.0/8` |
| `isLinkLocal()` | `bool` | `169.254.0.0/16` |
| `contains(string $cidrOrIp)` | `bool` | Full containment check for IP or sub-block |
| `overlaps(string $cidr)` | `bool` | Any-overlap check |
| `generateIPs(int $limit = 0)` | `Generator` | Yields usable host IPs |
| `generateAllIPs(int $limit = 0)` | `Generator` | Yields all IPs including network & broadcast |
| `toArray(bool $usableOnly = true)` | `array` | Collect IPs into array (small subnets only) |
| `split(int $count)` | `self[]` | Divide into equal sub-networks |
| `supernet()` | `self` | Parent block (one prefix shorter) |
| `sibling()` | `self` | Adjacent same-size block |
| `getReverseDnsZone()` | `string` | `*.in-addr.arpa` zone name |
| `info()` | `array` | All properties as associative array |

### `IPv6` — `Laika\Core\IP\Version\IPv6`

| Method | Returns | Description |
|---|---|---|
| `__construct(string $cidr)` | | Parse from CIDR string |
| `fromRange(string $start, string $end)` | `self` | Tightest CIDR covering a range |
| `getCidr()` | `string` | Normalised CIDR |
| `getPrefix()` | `int` | Prefix length |
| `getNetworkAddress()` | `string` | Network address (compressed notation) |
| `getLastAddress()` | `string` | Last address in block |
| `getFirstHost()` | `string` | First host |
| `getLastHost()` | `string` | Last host |
| `getTotalAddresses()` | `string` | Total addresses as BCMath decimal string |
| `getTotalAddressesString()` | `string` | Alias for `getTotalAddresses()` |
| `getAddressType()` | `string` | Human-readable type classification |
| `isPrivate()` | `bool` | ULA range (`fc00::/7`) |
| `isLinkLocal()` | `bool` | `fe80::/10` |
| `isLoopback()` | `bool` | `::1` |
| `isMulticast()` | `bool` | `ff00::/8` |
| `contains(string $cidrOrIp)` | `bool` | Containment check for IP or sub-block |
| `overlaps(string $cidr)` | `bool` | Any-overlap check |
| `generateIPs(int $limit = 256)` | `Generator` | Yields IPs — always set a limit for large blocks |
| `toArray(int $limit = 256)` | `array` | Collect into array (small blocks only) |
| `split(int $count)` | `self[]` | Divide into equal sub-networks |
| `supernet()` | `self` | Parent block |
| `getReverseDnsZone()` | `string` | `*.ip6.arpa` nibble notation |
| `info()` | `array` | All properties as associative array |

### `IPException` — `Laika\Core\Exceptions\IPException`

Extends `\RuntimeException`. Thrown by all three classes on any invalid input — invalid IPs, out-of-range prefixes, non-contiguous masks, invalid split counts, and navigation beyond `/0`.

---

*Part of the Laika PHP Micro Framework · MIT License · Showket Ahmed &lt;riyadhtayf@gmail.com&gt;*
