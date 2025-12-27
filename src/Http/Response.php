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

namespace Laika\Core\Http;

class Response
{
    /**
     * @property Response $instance
     */
    private static Response $instance;

    /**
     * Default Headers
     */
    protected array $headers;

    /**
     * Initiate Instance
     */
    public function __construct()
    {
        $this->headers = [
            "Access-Control-Allow-Origin"       =>  "*",
            "Access-Control-Allow-Methods"      =>  "GET, POST",
            "Access-Control-Allow-Headers"      =>  "Authorization, Origin, X-Requested-With, Content-Type, Accept, X-Laika-Token",
            "Access-Control-Allow-Credentials"  =>  "true",
            "X-Powered-By"                      =>  "Laika Framework",
            "X-Frame-Options"                   =>  "sameorigin",
            "Content-Security-Policy"           =>  "frame-ancestors 'self'",
            "Referrer-Policy"                   =>  "origin-when-cross-origin",
            "Cache-Control"                     =>  "no-store, no-cache, must-revalidate",
            "Pragma"                            =>  "no-cache",
            "Expires"                           =>  "0",
        ];
    }

    // /**
    //  * Get Instance
    //  * @return Response
    //  */
    // public static function instance(): Response
    // {
    //     self::$instance ??= new Response();
    //     return self::$instance;
    // }

    /**
     * Set HTTP response code
     * @return int
     */
    public function code(int $code = 200): int
    {
        http_response_code($code);
        return $code;
    }

    /**
     * Set custom "X-Powered-By" header
     * return void
     */
    public function poweredBy(string $str): void
    {
        header("X-Powered-By: {$str}", true);
    }

    /**
     * Set or overwrite headers
     * @return void
     */
    public function setHeader(array $headers = []): void
    {
        foreach ($headers as $key => $value) {
            header(trim($key) . ": " . trim((string) $value), true);
        }
    }

    /**
     * Send default headers + framework-specific ones
     * @return void
     */
    public function register(): void
    {
        foreach ($this->headers as $key => $value) {
            header(trim($key) . ": " . trim((string) $value), true);
        }
    }

    /**
     * Get sent response headers
     * @param string|null $key  Header key to fetch (case-insensitive)
     * @return array|string
     */
    public function get(?string $key = null): array|string
    {
        $val = [];
        foreach (headers_list() as $header) {
            $parts = explode(':', $header, 2);
            $val[strtolower(trim($parts[0]))] = trim($parts[1] ?? '');
        }

        if ($key !== null) {
            return $val[strtolower($key)] ?? '';
        }

        return $val;
    }

    /**
     * Response Status Codes With Message & Reference
     * @return array
     */
    public function codes()
    {
        return [
            // 1xx Informational Responses
            100 => ['message' => 'Continue', 'reference' => 'RFC9110, Section 15.2.1'],
            101 => ['message' => 'Switching Protocols', 'reference' => 'RFC9110, Section 15.2.2'],
            102 => ['message' => 'Processing', 'reference' => 'RFC2518'],
            103 => ['message' => 'Early Hints', 'reference' => 'RFC8297'],
            104 => ['message' => 'Upload Resumption Supported', 'reference' => 'draft-ietf-httpbis-resumable-upload-05 (TEMPORARY - registered 2024-11-13, extension registered 2025-09-15, expires 2026-11-13)'],
            // 105–199 Unassigned
            105 => ['message' => 'Unassigned', 'reference' => 'Unassigned'],

            // 2xx Successful Responses
            200 => ['message' => 'OK', 'reference' => 'RFC9110, Section 15.3.1'],
            201 => ['message' => 'Created', 'reference' => 'RFC9110, Section 15.3.2'],
            202 => ['message' => 'Accepted', 'reference' => 'RFC9110, Section 15.3.3'],
            203 => ['message' => 'Non-Authoritative Information', 'reference' => 'RFC9110, Section 15.3.4'],
            204 => ['message' => 'No Content', 'reference' => 'RFC9110, Section 15.3.5'],
            205 => ['message' => 'Reset Content', 'reference' => 'RFC9110, Section 15.3.6'],
            206 => ['message' => 'Partial Content', 'reference' => 'RFC9110, Section 15.3.7'],
            207 => ['message' => 'Multi-Status', 'reference' => 'RFC4918'],
            208 => ['message' => 'Already Reported', 'reference' => 'RFC5842'],
            // 209–225 Unassigned
            209 => ['message' => 'Unassigned', 'reference' => 'Unassigned'],
            226 => ['message' => 'IM Used', 'reference' => 'RFC3229'],
            // 227–299 Unassigned
            227 => ['message' => 'Unassigned', 'reference' => 'Unassigned'],

            // 3xx Redirection
            300 => ['message' => 'Multiple Choices', 'reference' => 'RFC9110, Section 15.4.1'],
            301 => ['message' => 'Moved Permanently', 'reference' => 'RFC9110, Section 15.4.2'],
            302 => ['message' => 'Found', 'reference' => 'RFC9110, Section 15.4.3'],
            303 => ['message' => 'See Other', 'reference' => 'RFC9110, Section 15.4.4'],
            304 => ['message' => 'Not Modified', 'reference' => 'RFC9110, Section 15.4.5'],
            305 => ['message' => 'Use Proxy', 'reference' => 'RFC9110, Section 15.4.6'],
            306 => ['message' => '(Unused)', 'reference' => 'RFC9110, Section 15.4.7'],
            307 => ['message' => 'Temporary Redirect', 'reference' => 'RFC9110, Section 15.4.8'],
            308 => ['message' => 'Permanent Redirect', 'reference' => 'RFC9110, Section 15.4.9'],
            // 309–399 Unassigned
            309 => ['message' => 'Unassigned', 'reference' => 'Unassigned'],

            // 4xx Client Error
            400 => ['message' => 'Bad Request', 'reference' => 'RFC9110, Section 15.5.1'],
            401 => ['message' => 'Unauthorized', 'reference' => 'RFC9110, Section 15.5.2'],
            402 => ['message' => 'Payment Required', 'reference' => 'RFC9110, Section 15.5.3'],
            403 => ['message' => 'Forbidden', 'reference' => 'RFC9110, Section 15.5.4'],
            404 => ['message' => 'Not Found', 'reference' => 'RFC9110, Section 15.5.5'],
            405 => ['message' => 'Method Not Allowed', 'reference' => 'RFC9110, Section 15.5.6'],
            406 => ['message' => 'Not Acceptable', 'reference' => 'RFC9110, Section 15.5.7'],
            407 => ['message' => 'Proxy Authentication Required', 'reference' => 'RFC9110, Section 15.5.8'],
            408 => ['message' => 'Request Timeout', 'reference' => 'RFC9110, Section 15.5.9'],
            409 => ['message' => 'Conflict', 'reference' => 'RFC9110, Section 15.5.10'],
            410 => ['message' => 'Gone', 'reference' => 'RFC9110, Section 15.5.11'],
            411 => ['message' => 'Length Required', 'reference' => 'RFC9110, Section 15.5.12'],
            412 => ['message' => 'Precondition Failed', 'reference' => 'RFC9110, Section 15.5.13'],
            413 => ['message' => 'Content Too Large', 'reference' => 'RFC9110, Section 15.5.14'],
            414 => ['message' => 'URI Too Long', 'reference' => 'RFC9110, Section 15.5.15'],
            415 => ['message' => 'Unsupported Media Type', 'reference' => 'RFC9110, Section 15.5.16'],
            416 => ['message' => 'Range Not Satisfiable', 'reference' => 'RFC9110, Section 15.5.17'],
            417 => ['message' => 'Expectation Failed', 'reference' => 'RFC9110, Section 15.5.18'],
            418 => ['message' => '(Unused)', 'reference' => 'RFC9110, Section 15.5.19'],
            // 419–420 Unassigned
            419 => ['message' => 'Unassigned', 'reference' => 'Unassigned'],
            421 => ['message' => 'Misdirected Request', 'reference' => 'RFC9110, Section 15.5.20'],
            422 => ['message' => 'Unprocessable Content', 'reference' => 'RFC9110, Section 15.5.21'],
            423 => ['message' => 'Locked', 'reference' => 'RFC4918'],
            424 => ['message' => 'Failed Dependency', 'reference' => 'RFC4918'],
            425 => ['message' => 'Too Early', 'reference' => 'RFC8470'],
            426 => ['message' => 'Upgrade Required', 'reference' => 'RFC9110, Section 15.5.22'],
            // 427 Unassigned
            427 => ['message' => 'Unassigned', 'reference' => 'Unassigned'],
            428 => ['message' => 'Precondition Required', 'reference' => 'RFC6585'],
            429 => ['message' => 'Too Many Requests', 'reference' => 'RFC6585'],
            // 430 Unassigned
            430 => ['message' => 'Unassigned', 'reference' => 'Unassigned'],
            431 => ['message' => 'Request Header Fields Too Large', 'reference' => 'RFC6585'],
            // 432–450 Unassigned
            432 => ['message' => 'Unassigned', 'reference' => 'Unassigned'],
            451 => ['message' => 'Unavailable For Legal Reasons', 'reference' => 'RFC7725'],
            // 452–499 Unassigned
            452 => ['message' => 'Unassigned', 'reference' => 'Unassigned'],

            // 5xx Server Error
            500 => ['message' => 'Internal Server Error', 'reference' => 'RFC9110, Section 15.6.1'],
            501 => ['message' => 'Not Implemented', 'reference' => 'RFC9110, Section 15.6.2'],
            502 => ['message' => 'Bad Gateway', 'reference' => 'RFC9110, Section 15.6.3'],
            503 => ['message' => 'Service Unavailable', 'reference' => 'RFC9110, Section 15.6.4'],
            504 => ['message' => 'Gateway Timeout', 'reference' => 'RFC9110, Section 15.6.5'],
            505 => ['message' => 'HTTP Version Not Supported', 'reference' => 'RFC9110, Section 15.6.6'],
            506 => ['message' => 'Variant Also Negotiates', 'reference' => 'RFC2295'],
            507 => ['message' => 'Insufficient Storage', 'reference' => 'RFC4918'],
            508 => ['message' => 'Loop Detected', 'reference' => 'RFC5842'],
            // 509 Unassigned
            509 => ['message' => 'Unassigned', 'reference' => 'Unassigned'],
            510 => ['message' => 'Not Extended (OBSOLETED)', 'reference' => 'RFC2774 / Status change of HTTP experiments to Historic'],
            511 => ['message' => 'Network Authentication Required', 'reference' => 'RFC6585'],
            // 512–599 Unassigned
            512 => ['message' => 'Unassigned', 'reference' => 'Unassigned'],
        ];
    }
}
