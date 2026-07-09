<?php
/**
 * Laika PHP Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika PHP MVC Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Laika\Core\Http;

defined('APP_PATH') || http_response_code(403) . die('Direct access not allowed.');

class CORS
{
    protected static array $allowedOrigins = ['*'];
    protected static array $allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
    protected static array $allowedHeaders = ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept'];
    protected static array $exposedHeaders = [];
    protected static bool $allowCredentials = false;
    protected static int $maxAge = 86400;
    protected static array $securityHeaders = [
        "X-Content-Type-Options"    => "nosniff",
        "Referrer-Policy"           => "strict-origin-when-cross-origin",
        "X-Frame-Options"           => "sameorigin",
        "Content-Security-Policy"   => "frame-ancestors 'self'",
        "X-Powered-By"              =>  "Laika Framework"
    ];

    ####################################################################################
    /*================================= EXTERNAL API =================================*/
    ####################################################################################
    /**
     * CORS Origins
     * @param string[] $origins
     * @return void
     */
    public static function origins(array $origins): void
    {
        static::$allowedOrigins = $origins;
    }

    /**
     * CORS Methods
     * @param string[] $methods
     * @return void
     */
    public static function methods(array $methods): void
    {
        static::$allowedMethods = $methods;
    }

    /**
     * CORS Headers
     * @param string[] $headers
     * @return void
     */
    public static function headers(array $headers): void
    {
        static::$allowedHeaders = $headers;
    }

    /**
     * CORS Expose
     * @param string[] $headers
     * @return void
     */
    public static function expose(array $headers): void
    {
        static::$exposedHeaders = $headers;
    }

    /**
     * CORS Accept Credentials
     * @param bool $allow
     * @return void
     */
    public static function credentials(bool $allow = true): void
    {
        static::$allowCredentials = $allow;
    }

    /**
     * CORS Max Age
     * @param int $seconds
     * @return void
     */
    public static function maxAge(int $seconds): void
    {
        static::$maxAge = $seconds;
    }

    /**
     * CORS Security Headers
     * @param int $seconds
     * @return void
     */
    public static function securityHeaders(array $headers): void
    {
        static::$securityHeaders = $headers;
    }

    /**
     * CORS Handle
     * @return void
     */
    public static function handle(): void
    {
        static::applySecurityHeaders();

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if ($origin !== '' && static::isAllowedOrigin($origin)) {
            if (static::$allowCredentials) {
                header("Access-Control-Allow-Origin: {$origin}");
                header('Access-Control-Allow-Credentials: true');
                header('Vary: Origin');
            } else {
                header('Access-Control-Allow-Origin: ' . (in_array('*', static::$allowedOrigins, true) ? '*' : $origin));
                header('Vary: Origin');
            }
        }

        if (!empty(static::$exposedHeaders)) {
            header('Access-Control-Expose-Headers: ' . implode(', ', static::$exposedHeaders));
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            header('Access-Control-Allow-Methods: ' . implode(', ', static::$allowedMethods));
            header('Access-Control-Allow-Headers: ' . implode(', ', static::$allowedHeaders));
            header('Access-Control-Max-Age: ' . static::$maxAge);
            header('Content-Length: 0');
            header('Content-Type: text/plain');
            http_response_code(204);
            exit;
        }
    }

    ####################################################################################
    /*================================= INTERNAL API =================================*/
    ####################################################################################
    /**
     * Check Origin is Allowed
     * @param string $origin
     * @return void
     */
    protected static function isAllowedOrigin(string $origin): bool
    {
        if (in_array('*', static::$allowedOrigins, true)) {
            return true;
        }

        foreach (static::$allowedOrigins as $allowed) {
            if (strcasecmp($allowed, trim($origin)) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apply Security Header
     * @return void
     */
    protected static function applySecurityHeaders(): void
    {
        foreach (static::$securityHeaders as $key => $value) {
            header("{$key}: {$value}");
        }
    }
}
