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
use Throwable;

class Url
{
    /** @var string Scheme */
    protected string $scheme;

    /** @var string Host */
    protected string $host;

    /** @var string Path */
    protected string $path;

    /** @var int Port */
    protected int $port;


    /** @var string Query String */
    protected string $queryString;

    /** @var string Base Url */
    protected string $baseUrl;

    /** @var string Script Name */
    protected string $scriptName;

    /** @var string Directory */
    protected string $directory;

    public function __construct()
    {
        // Get Schema
        $this->scheme = $this->detectScheme();

        // Get Host Name & Port
        [$this->host, $port] = $this->detectHost();
        $this->port = $port ?? ($this->scheme === 'https' ? 443 : 80);

        // Get Url Path
        $this->path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        // Get Query String
        $this->queryString = $_SERVER['QUERY_STRING'] ?? '';

        // Make Script Path
        $this->scriptName = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '/index.php');

        // Get Additional Directory if Exists
        $this->directory = rtrim(str_replace('\\', '/', dirname($this->scriptName)), '/');
        if ($this->directory === '.') {
            $this->directory = '';
        }

        // Make Base Url
        $this->baseUrl = "{$this->scheme}://{$this->host}{$this->portSuffix()}{$this->directory}/";
    }

    ##########################################################################
    # ============================ EXTERNAL API ============================ #
    ##########################################################################

    /**
     * Get Current URL
     * * @return string
     */
    public function current(): string
    {
        return rtrim($this->scheme . '://' . $this->host . $this->portSuffix() . ($_SERVER['REQUEST_URI'] ?? '/'), '/');
    }

    /**
     * Get Base URL
     * * @return string
     */
    public function base(): string
    {
        return $this->baseUrl;
    }

    /**
     * Get Sub Directory
     * @return string
     */
    public function directory(): string
    {
        return trim($this->directory, '/');
    }

    /**
     * Path/Sub Folder
     * @return string Path/Sub Folder
     */
    public function path(): string
    {
        $path = $this->path;

        // The prefix must end at a segment boundary. A bare strpos() match let
        // directory "/app" eat the front of "/application/list" and return
        // "lication/list".
        if ($this->directory !== '') {
            $prefix = rtrim($this->directory, '/');

            if ($path === $prefix) {
                $path = '/';
            } elseif (str_starts_with($path, $prefix . '/')) {
                $path = substr($path, strlen($prefix));
            }
        }

        return trim($path, '/');
    }

    /**
     * Get Port
     * @return int
     */
    public function port(): int
    {
        return $this->port;
    }

    /**
     * Get Query Strings
     * * @return array{string:string}
     */
    public function queries(): array
    {
        parse_str($this->queryString, $queries);
        return purify($queries);
    }

    /**
     * Get Query String by Key
     * @param string $key - Required Argument as String
     * @param string|null $default - Optional Argument as String
     * @return ?string
     */
    public function query(string $key, ?string $default = null): ?string
    {
        return $this->queries()[$key] ?? $default;
    }

    /**
     * Build URL From Args
     * @param string $path Required Argument as String.
     * @param array{string:int|string} $params Optional Argument as Array. Example ['key' => 'value']
     * @return string Absolute URL
     */
    public function build(string $path, array $params = []): string
    {
        $path = trim($path, '/');
        $url = $this->base() . $path;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        return $url;
    }

    /**
     * Get Segment by Index
     * @param int $index - Required Argument as Integer, Start from 1
     * @return string Get Segment by Index
     */
    public function segment(int $index): ?string
    {
        $segments = explode('/', trim($this->path(), '/'));
        return $segments[$index - 1] ?? null;
    }

    /**
     * Get All Segments
     * @return array<int,string>
     */
    public function segments(): array
    {
        $segments = explode('/', trim($this->path(), '/'));
        return $segments[0] !== '' ? $segments : [];
    }

    /**
     * Get URL With Query String(s)
     * @param array $params - Required Argument as Array. Example ['key' => 'value']
     * @return string Get URL With Query String(s)
     */
    public function withQuery(array $params): string
    {
        $queries = array_merge($this->queries(), $params);
        return $this->base() . $this->path() . '?' . http_build_query($queries);
    }

    /**
     * Get URL By Removing Selected Queries
     * @param array $keys - Required Argument as Array. Example ['key1', 'key2']
     * @return string Get URL Without Query String(s)
     */
    public function withoutQuery(array $keys): string
    {
        $queries = $this->queries();
        foreach ($keys as $key) {
            unset($queries[$key]);
        }
        return $this->base() . $this->path() . (empty($queries) ? '' : '?' . http_build_query($queries));
    }

    /**
     * Get URL With Incremented Query String
     * @param ?string $key Optional Argument. Default is null
     * @return string Get URL With Incremented Query String
     */
    public function incrementQuery(?string $key = null): string
    {
        $key = strtolower($key ?: 'page');
        $queries = $this->queries();
        $queries[$key] = max(1, (int) ($queries[$key] ?? 1)) + 1;

        return $this->base() . $this->path() . '?' . http_build_query($queries);
    }

    /**
     * Get URL With Decremented Query String
     * @param ?string $key Optional Argument. Default is null
     * @return string Get URL With Decremented Query String
     */
    public function decrementQuery(?string $key = null): string
    {
        $key = strtolower($key ?: 'page');
        $queries = $this->queries();
        $queries[$key] = max(1, ((int) ($queries[$key] ?? 1)) -1);

        return $this->base() . $this->path() . '?' . http_build_query($queries);
    }

    /**
     * Get Host Name
     * @return string
     */
    public function host(): string
    {
        return $this->host;
    }

    /**
     * Check Scheme is HTTPS
     * @return bool
     */
    public function isHttps(): bool
    {
        return $this->scheme === 'https';
    }

    /**
     * Scheme
     * @return string
     */
    public function scheme(): string
    {
        return $this->scheme;
    }

    ############################################################################
    /*============================= INTERNAL API =============================*/
    ############################################################################
    /**
     * Port Suffix
     * @return string
     */
    protected function portSuffix(): string
    {
        $isStandard = ($this->scheme === 'https' && $this->port === 443) || ($this->scheme === 'http' && $this->port === 80);
        return $isStandard ? '' : ":{$this->port}";
    }

    /**
     * Read a Server Variable as Trimmed String
     * @param string $key
     * @return string
     */
    protected function server(string $key): string
    {
        return is_string($_SERVER[$key] ?? null) ? trim($_SERVER[$key]) : '';
    }

    /**
     * First Value of a Comma Separated Proxy Header
     *
     * Empty unless the request reached us through a configured proxy - the
     * header is client controlled on every other request.
     * @param string $key
     * @return string
     */
    protected function forwarded(string $key): string
    {
        if (!ProxyTrust::trusts()) {
            return '';
        }

        $value = $this->server($key);
        return $value === '' ? '' : trim(explode(',', $value)[0]);
    }

    /**
     * A Proxy Header Read Whole Rather Than as a Comma Separated List
     * @param string $key
     * @return string
     */
    protected function proxyHeader(string $key): string
    {
        return ProxyTrust::trusts() ? $this->server($key) : '';
    }

    /**
     * Detect Request Scheme
     * Behind a Reverse Proxy or CDN The TLS Leg Ends at The Proxy, So $_SERVER['HTTPS']
     * is Unset And The Real Client Facing Scheme Only Arrives in a Forwarded Header
     * @return string
     */
    protected function detectScheme(): string
    {
        // Standard Proxy Header. May Hold a List Like "https, http"
        $proto = strtolower($this->forwarded('HTTP_X_FORWARDED_PROTO'));
        if ($proto === 'https' || $proto === 'http') {
            return $proto;
        }

        // Nginx & Microsoft Proxy Variants
        foreach (['HTTP_X_FORWARDED_SSL', 'HTTP_FRONT_END_HTTPS'] as $key) {
            $value = strtolower($this->proxyHeader($key));
            if ($value !== '' && $value !== 'off') {
                return 'https';
            }
        }

        $scheme = strtolower($this->proxyHeader('HTTP_X_URL_SCHEME'));
        if ($scheme === 'https' || $scheme === 'http') {
            return $scheme;
        }

        // Cloudflare Sends {"scheme":"https"}
        if (str_contains(str_replace(' ', '', $this->proxyHeader('HTTP_CF_VISITOR')), '"scheme":"https"')) {
            return 'https';
        }

        // TLS Terminated by PHP Itself. IIS Sends 'off' And Some FastCGI Setups Send ''
        $https = strtolower($this->server('HTTPS'));
        if ($https !== '' && $https !== 'off') {
            return 'https';
        }

        $scheme = strtolower($this->server('REQUEST_SCHEME'));
        if ($scheme === 'https' || $scheme === 'http') {
            return $scheme;
        }

        return ((int) $this->server('SERVER_PORT') === 443) ? 'https' : 'http';
    }

    /**
     * Detect Host Name & Port
     * @return array{0:string,1:?int}
     */
    protected function detectHost(): array
    {
        // The Proxy Keeps The Original Host Here When It Rewrites The Host Header
        $candidates = [
            $this->forwarded('HTTP_X_FORWARDED_HOST'),
            $this->server('HTTP_HOST'),
            $this->server('SERVER_NAME')
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === '') {
                continue;
            }

            [$host, $port] = $this->splitHost($candidate);
            if (!$this->isValidHost($host)) {
                continue;
            }

            // SERVER_PORT is The Internal Port Behind a Proxy, So It is Never Used Here
            if ($port === null) {
                $forwardedPort = (int) $this->forwarded('HTTP_X_FORWARDED_PORT');
                if ($forwardedPort > 0 && $forwardedPort <= 65535) {
                    $port = $forwardedPort;
                }
            }
            return [$host, $port];
        }
        return ['localhost', null];
    }

    /**
     * Split "host:port" Keeping IPv6 Literals Intact
     * @param string $host
     * @return array{0:string,1:?int}
     */
    protected function splitHost(string $host): array
    {
        if (preg_match('/^(\[[^\]]+\])(?::(\d+))?$/', $host, $matches)) {
            return [strtolower($matches[1]), isset($matches[2]) ? (int) $matches[2] : null];
        }
        if (preg_match('/^([^:]+):(\d+)$/', $host, $matches)) {
            return [strtolower($matches[1]), (int) $matches[2]];
        }
        return [strtolower($host), null];
    }

    /**
     * Validate a Host Name. The Host Header is Client Controlled
     * @param string $host
     * @return bool
     */
    protected function isValidHost(string $host): bool
    {
        if ($host === '' || strlen($host) > 253) {
            return false;
        }
        return (bool) preg_match('/^\[[0-9a-f:.]+\]$|^[a-z0-9][a-z0-9._\-]*$/', $host);
    }
}
