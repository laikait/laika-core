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

class Url
{
    /**
     * Singleton Object
     * @var Url $instance
     */
    private static Url $instance;

    /**
     * Scheme
     * @var string
     */
    protected string $scheme;

    /**
     * Host
     * @var string
     */
    protected string $host;

    /**
     * Path
     * @var string
     */

    protected string $path;

    /**
     * Query string
     * @var string
     */
    protected string $queryString;

    /**
     * Base URL
     * @var string
     */
    protected string $baseUrl;

    /**
     * Script name
     * @var string
     */
    protected string $scriptName;

    /**
     * Script directory
     * @var string
     */
    protected string $directory;

    public function __construct()
    {
        $this->scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $this->host = strtolower(explode(':', $host)[0]);
        $this->path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $this->queryString = $_SERVER['QUERY_STRING'] ?? '';
        $this->scriptName = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '/index.php');
        $this->directory = rtrim(str_replace('\\', '/', dirname($this->scriptName)), '/');
        $this->baseUrl = $this->scheme . '://' . $this->host . $this->directory . '/';
    }

    ##############################################################################
    //------------------------------- PUBLIC API------------------------------- //
    ##############################################################################

    /**
     * Get Current URL
     * * @return string
     */
    public function current(): string
    {
        return rtrim($this->scheme . '://' . $this->host . ($_SERVER['REQUEST_URI'] ?? '/'), '/');
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
        return trim(str_replace($this->directory, '', $this->path), '/');
    }

    /**
     * Get Query Strings
     * * @return array<string,string>
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
     * @param array<string,int|string> $params Optional Argument as Array. Example ['key' => 'value']
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
        return $segments[0] ? $segments : [];
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
        $key = $key ?: 'page';
        $queries = $this->queries();
        $queries[$key] = isset($queries[$key]) && is_numeric($queries[$key]) && (int) $queries[$key] > 1
            ? (int) $queries[$key] + 1 : 2;

        return $this->base() . $this->path() . '?' . http_build_query($queries);
    }

    /**
     * Get URL With Decremented Query String
     * @param ?string $key Optional Argument. Default is null
     * @return string Get URL With Decremented Query String
     */
    public function decrementQuery(?string $key = null): string
    {
        $key = $key ?: 'page';
        $queries = $this->queries();
        $queries[$key] = isset($queries[$key]) && is_numeric($queries[$key]) && (int) $queries[$key] > 1
            ? (int) $queries[$key] - 1 : 1;

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
}
