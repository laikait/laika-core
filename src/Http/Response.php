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
    /** @var int Status Code */
    protected int $statusCode = 200;

    /** @var string Content Type */
    protected string $contentType = 'text/html; charset=UTF-8';

    /** @var array Headers */
    protected array $headers = [];

    /** @var mixed Body */
    protected mixed $body = null;

    ##############################################################################
    ################################ EXTERNAL API ################################
    ##############################################################################
    /**
     * Set Status Code
     * @param int $code
     * @return static
     */
    public function setStatus(int $code): static
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Get Status Code
     * @return int
     */
    public function getStatus(): int
    {
        return $this->statusCode;
    }

    /**
     * Set Content Type
     * @param string $type
     * @return static
     */
    public function setContentType(string $type): static
    {
        $this->contentType = $type;
        return $this;
    }

    /**
     * Get Content Type
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * Set Header
     * @param string $name
     * @param string $value
     * @return static
     */
    public function setHeader(string $name, string $value): static
    {
        $this->headers[static::normalizeHeaderName($name)] = $value;
        return $this;
    }

    /**
     * Set Headers
     * @param array<non-empty-string,string> $headers
     * @return static
     */
    public function setHeaders(array $headers): static
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, (string) $value);
        }
        return $this;
    }

    /**
     * Get Header
     * @param string $name
     * @return ?string
     */
    public function getHeader(string $name): ?string
    {
        return $this->headers[static::normalizeHeaderName($name)] ?? null;
    }

    /**
     * Get Headers
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Remove Header
     * @param string $name
     * @return static
     */
    public function removeHeader(string $name): static
    {
        unset($this->headers[static::normalizeHeaderName($name)]);
        return $this;
    }

    /**
     * Set Body
     * @param mixed $body
     * @return static
     */
    public function body(mixed $body): static
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Get Body
     * @return mixed
     */
    public function getBody(): mixed
    {
        return $this->body;
    }

    /**
     * Set JSON Content Type
     * @param mixed $data
     * @param int $status
     * @return static
     */
    public function json(mixed $data, int $status = 200): static
    {
        $this->setStatus($status)
             ->setContentType('application/json; charset=UTF-8')
             ->body(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $this;
    }

    /**
     * Set HTML Content Type
     * @param mixed $data
     * @param int $status
     * @return static
     */
    public function html(string $html, int $status = 200): static
    {
        $this->setStatus($status)
             ->setContentType('text/html; charset=UTF-8')
             ->body($html);
        return $this;
    }

    /**
     * Set TEXT Content Type
     * @param mixed $data
     * @param int $status
     * @return static
     */
    public function text(string $text, int $status = 200): static
    {
        $this->setStatus($status)
             ->setContentType('text/plain; charset=UTF-8')
             ->body($text);
        return $this;
    }

    /**
     * Set No Content Header
     * @return static
     */
    public function noContent(): static
    {
        $this->setStatus(204)->body(null);
        return $this;
    }

    /**
     * Send Response
     */
    public function send(): void
    {
        // Check If Alredy Sent
        if (headers_sent()) return;

        http_response_code($this->statusCode);
        header("Content-Type: {$this->contentType}");

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        if ($this->body !== null) {
            echo $this->body;
        }
    }

    /**
     * Response Status Codes With Message & Reference
     * @return array
     */
    public function statusCodes(): array
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

    ##############################################################################
    ################################ INTERNAL API ################################
    ##############################################################################
    /**
     * Normalize Header Name
     * @param string $name
     * @return string
     */
    protected static function normalizeHeaderName(string $name): string
    {
        return ucwords(strtolower(trim($name)), '-');
    }
}
