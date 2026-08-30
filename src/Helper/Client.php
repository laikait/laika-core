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

namespace Laika\Core\Helper;

use Laika\Core\Http\ProxyTrust;
use Laika\Shield\Support\IpHelper;

class Client
{
    /**
     * Known crawlers, plus two generic shapes: a "SomeBot/1.0" product token and
     * the "+http://..." contact URL crawlers conventionally carry.
     *
     * Deliberately not a bare "bot" substring - that matched Cubot handsets and
     * made every named entry above it redundant.
     */
    private const BOT_PATTERN = '/(?:googlebot|bingbot|slurp|duckduckbot|baiduspider'
        . '|yandexbot|sogou|exabot|facebot|ia_archiver|mj12bot|semrushbot|ahrefsbot'
        . '|dotbot|uptimebot|twitterbot|petalbot|applebot|crawler|spider'
        . '|bot\/|\+https?:\/\/)/i';

    /** @var ?string $userAgent Resolved on first use, not at construction. */
    protected ?string $userAgent = null;

    /** @var ?string $ip */
    protected ?string $ip = null;

    /** @var bool $ipResolved Distinguishes "not looked up yet" from "looked up, found nothing". */
    protected bool $ipResolved = false;

    /**
     * @return string User Agent Name
     */
    public function userAgent(): string
    {
        return $this->userAgent ??= (is_string($_SERVER['HTTP_USER_AGENT'] ?? null)
            ? $_SERVER['HTTP_USER_AGENT']
            : 'Unknown');
    }

    /**
     * @return ?string Client IP
     */
    public function ip(): ?string
    {
        if (!$this->ipResolved) {
            $this->ip         = $this->detectIp();
            $this->ipResolved = true;
        }

        return $this->ip;
    }

    /**
     * Drop the Cached Request Snapshot
     *
     * This class is a container singleton, so a long running worker would
     * otherwise report the values the process started with for every job.
     * @return static
     */
    public function refresh(): static
    {
        $this->userAgent  = null;
        $this->ip         = null;
        $this->ipResolved = false;

        return $this;
    }

    /**
     * @return string Client Language
     */
    public function language(): string
    {
        $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en-US';
        return explode(',', $lang)[0];
    }

    /**
     * @return string Client Operating System Name
     */
    public function os(): string
    {
        $ua = $this->userAgent();

        $osPatterns = [
            '/Android\s+([0-9\.]+)/i'       => 'Android %s',
            '/iPhone OS ([\d_]+)/i'         => 'iOS %s',
            '/iPad; CPU OS ([\d_]+)/i'      => 'iPadOS %s',
            '/Windows NT ([0-9\.]+)/i'      => [
                '10.0' => 'Windows 10',
                '6.3'  => 'Windows 8.1',
                '6.2'  => 'Windows 8',
                '6.1'  => 'Windows 7',
                '6.0'  => 'Windows Vista',
                '5.1'  => 'Windows XP',
            ],
            '/Mac OS X ([\d_]+)/i'          => 'Mac OS X %s',
            '/Linux/i'                      => 'Linux'
        ];

        foreach ($osPatterns as $pattern => $result) {
            if (preg_match($pattern, $ua, $m)) {
                if (is_array($result)) {
                    return $result[$m[1]] ?? "Windows NT {$m[1]}";
                }
                return sprintf($result, str_replace('_', '.', $m[1] ?? ''));
            }
        }

        return 'Unknown';
    }

    /**
     * @return string Client Browser Name
     */
    public function browser(): string
    {
        $ua = $this->userAgent();

        $browsers = [
            ['name' => 'Edge',              'pattern' => '/Edg\/([0-9\.]+)/'],
            ['name' => 'Internet Explorer', 'pattern' => '/MSIE\s([0-9\.]+)/'],
            ['name' => 'Internet Explorer', 'pattern' => '/Trident.*rv:([0-9\.]+)/'],
            ['name' => 'Chrome',            'pattern' => '/Chrome\/([0-9\.]+)/'],
            ['name' => 'Firefox',           'pattern' => '/Firefox\/([0-9\.]+)/'],
            ['name' => 'Safari',            'pattern' => '/Version\/([0-9\.]+).*Safari/'],
            ['name' => 'Opera',             'pattern' => '/OPR\/([0-9\.]+)/'],
            ['name' => 'Opera',             'pattern' => '/Opera\/([0-9\.]+)/'],
            ['name' => 'Brave',             'pattern' => '/Brave\/([0-9\.]+)/'],
            ['name' => 'Vivaldi',           'pattern' => '/Vivaldi\/([0-9\.]+)/'],
            ['name' => 'UC Browser',        'pattern' => '/UCBrowser\/([0-9\.]+)/'],
            ['name' => 'Samsung Internet',  'pattern' => '/SamsungBrowser\/([0-9\.]+)/'],
            ['name' => 'QQ Browser',        'pattern' => '/QQBrowser\/([0-9\.]+)/'],
            ['name' => 'Baidu',             'pattern' => '/BIDUBrowser\/([0-9\.]+)/'],
            ['name' => 'DuckDuckGo',        'pattern' => '/DuckDuckGo\/([0-9\.]+)/'],
        ];

        foreach ($browsers as $browser) {
            if (preg_match($browser['pattern'], $ua, $match)) {
                return $browser['name'] . ' ' . $match[1];
            }
        }

        return 'Unknown';
    }

    /**
     * @return string Client Device Type
     */
    public function deviceType(): string
    {
        $ua = strtolower($this->userAgent());

        if ($this->isBot()) {
            return 'Bot';
        }

        if (preg_match('/ipad|tablet/i', $ua)) {
            return 'Tablet';
        }

        if (strpos($ua, 'mobile') !== false || preg_match('/iphone|ipod|android/i', $ua)) {
            return 'Mobile';
        }

        return 'Desktop';
    }

    /**
     * @return bool Check Client is Bot
     */
    public function isBot(): bool
    {
        return (bool) preg_match(self::BOT_PATTERN, $this->userAgent());
    }

    /**
     * @return array<string,bool|string> Client All Info
     */
    public function info(): array
    {
        return [
            'ip'        => $this->ip(),
            'os'        => $this->os(),
            'browser'   => $this->browser(),
            'device'    => $this->deviceType(),
            'language'  => $this->language(),
            'agent'     => $this->userAgent(),
            'isBot'     => $this->isBot()
        ];
    }

    /*==========================================================================*/
    /*============================== INTERNAL API ==============================*/
    /*==========================================================================*/
    /**
     * Detect Client IP
     *
     * Forwarded headers are set by whoever sent the request, so they are only
     * consulted when the immediate peer is a proxy named in
     * lf-config/app.php -> trusted_proxies. Resolution itself is delegated to
     * laika-shield's IpHelper, which walks X-Forwarded-For right to left -
     * discarding hops we added and stopping at the first we did not - and
     * strips ports and IPv6 brackets on the way.
     * @return ?string IPv4/IPv6 on Success and null on Failure
     */
    protected function detectIp(): ?string
    {
        $ip = IpHelper::resolve(ProxyTrust::enabled(), ProxyTrust::ranges());

        // Flags are a bitmask. The old code passed a list array, which carries
        // no 'flags' key, so it silently validated with no flags at all.
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) ?: null;
    }
}
