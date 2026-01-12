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

class Client
{
    /**
     * @var string $userAgent
     */
    protected string $userAgent;

    /**
     * @var ?string $ip
     */
    protected ?string $ip;

    public function __construct()
    {
        $this->userAgent =  $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $this->ip        =  $this->detectIp();
    }

    /**
     * @return string User Agent Name
     */
    public function userAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * @return ?string Client IP
     */
    public function ip(): ?string
    {
        return $this->ip;
    }

    /**
     * @return string Client Language
     */
    public function language(): string
    {
        $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en-US';
        return \explode(',', $lang)[0];
    }

    /**
     * @return string Client Operating System Name
     */
    public function os(): string
    {
        $ua = $this->userAgent;

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
            if (\preg_match($pattern, $ua, $m)) {
                if (\is_array($result)) {
                    return $result[$m[1]] ?? "Windows NT {$m[1]}";
                }
                return \sprintf($result, \str_replace('_', '.', $m[1] ?? ''));
            }
        }

        return 'Unknown OS';
    }

    /**
     * @return string Client Browser Name
     */
    public function browser(): string
    {
        $ua = $this->userAgent;

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
            if (\preg_match($browser['pattern'], $ua, $match)) {
                return $browser['name'] . ' ' . $match[1];
            }
        }

        return 'Unknown Browser';
    }

    /**
     * @return string Client Device Type
     */
    public function deviceType(): string
    {
        $ua = \strtolower($this->userAgent);

        if ($this->isBot()) {
            return 'Bot';
        }

        if (\preg_match('/ipad|tablet/i', $ua)) {
            return 'Tablet';
        }

        if (\strpos($ua, 'mobile') !== false || \preg_match('/iphone|ipod|android/i', $ua)) {
            return 'Mobile';
        }

        return 'Desktop';
    }

    /**
     * @return bool Check Client is Bot
     */
    public function isBot(): bool
    {
        $ua = \strtolower($this->userAgent);

        $bots = [
            'googlebot',
            'bingbot',
            'slurp',
            'duckduckbot',
            'baiduspider',
            'yandexbot',
            'sogou',
            'exabot',
            'facebot',
            'ia_archiver',
            'mj12bot',
            'semrushbot',
            'ahrefsbot',
            'dotbot',
            'uptimebot',
            'twitterbot',
            'petalbot',
            'crawler',
            'spider',
            'bot'
        ];

        foreach ($bots as $bot) {
            if (\strpos($ua, $bot) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string,bool|string> Client All Info
     */
    public function all(): array
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

    /**
     * @return string Detect Client IP
     * @return ?string IPv4/IPv6 on Success and null of Failure
     */
    protected function detectIp(): ?string
    {
        foreach (
            [
                'HTTP_CLIENT_IP',
                'HTTP_X_FORWARDED_FOR',
                'HTTP_X_FORWARDED',
                'HTTP_FORWARDED_FOR',
                'HTTP_FORWARDED',
                'REMOTE_ADDR'
            ] as $key
        ) {
            if (!empty($_SERVER[$key])) {
                $ips = \explode(',', $_SERVER[$key]);
                foreach ($ips as $ip) {
                    $ip = \trim($ip);
                    if (\filter_var($ip, FILTER_VALIDATE_IP, [FILTER_FLAG_IPV4, FILTER_FLAG_IPV6])) {
                        return $ip;
                    }
                }
            }
        }
        return null;
    }
}
