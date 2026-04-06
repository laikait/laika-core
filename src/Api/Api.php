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

namespace Laika\Core\Api;

use Laika\Core\Relay\Relays\Header;
use Laika\Core\Relay\Relays\Request;
use Laika\Core\Relay\Relays\Token;

class Api
{
    /** @var array $accepted Application Types */
    protected array $accepted;

    /** @var string $contentType Content Type */
    protected string $contentType;

    /** @var string $method Request Method */
    protected string $method;

    /** @var ?string $message Message to Send */
    protected ?string $message;

    /** @var array $acceptableMethods Acceptable Request Methods */
    protected array $acceptableMethods;

    /** @var string $allowedOrigin Request Method */
    protected string $allowedOrigin;

    ################################################################################
    /*=============================== INTERNAL API ===============================*/
    ################################################################################
    // Initiate API Object
    public function __construct()
    {
        $this->reset();
    }

    /**
     * Set Allowed Origin
     * @param string $origin
     * @return static
     */
    public function setAllowedOrigin(string $origin = '*'): static
    {
        $this->allowedOrigin = $origin;
        return $this;
    }

    /**
     * Set Message
     * @param string $message
     * @return static
     */
    public function setMessage(string $message): static
    {
        $this->message = htmlspecialchars(trim($message));
        return $this;
    }

    /**
     * Content-Type
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * Request Method
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Request Body
     * @return array
     */
    public function body(): array
    {
        return Request::inputs();
    }

    /**
     * Get Bearer Token from Authorization Header
     * @return string
     */
    public function bearerToken(): string
    {
        // Try to fetch the Authorization header
        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? null;

        // Handle missing header
        if ($header === null || $header === '') {
            $this->setMessage('Missing Authorization Header');
            return $this->send([], 401);
        }

        // Validate Bearer pattern
        if (!preg_match('/^Bearer\s+(\S+)$/i', trim($header), $matches)) {
            $this->setMessage('Invalid Authorization Header Format');
            return $this->send([], 400);
        }

        $token = $matches[1];

        if (!Token::validateToken($token)) {
            $this->setMessage('Token Expired');
            return $this->send([], 401);
        }

        return $token;
    }

    /**
     * @param array $payload Payload Data
     * @param int $status Response Status
     * @param array $additional Additionl Response to Send
     * @return never Send Response
     */
    public function send(array $payload, int $status = 200, array $additional = []): never
    {
        // Set Data
        if (!in_array($this->method, $this->acceptableMethods)) {
            $status = 415;
            $payload = [];
            $data = [
                "status"    =>  $status,
                "data"      =>  $payload,
                "message"   =>  "Unsupported Method: '{$this->method}'",
                "context"   =>  "Accepted Methods Are: " . implode(', ', $this->acceptableMethods),
                "timestamp" =>  date('c')
            ];
        } else {
            $data = array_merge([
                "status"    =>  $status,
                "data"      =>  $payload,
                "message"   =>  $this->message ?: "Success",
                "context"   =>  Header::codes()[$status]['message'] ?? 'Unassigned',
                "timestamp" =>  date('c')
            ], $additional);
        }

        // Build body
        $body = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_FORCE_OBJECT);

        $charset  = $this->detectCharset();

        // Set Headers
        Header::code($status);
        Header::set([
            "Content-Type"  =>  "application/json; charset={$charset}",
            "Vary"          =>  "Accept, Accept-Charset"
        ]);

        $this->applyCors();

        echo $body;
        $this->reset();
        exit;
    }

    ################################################################################
    /*=============================== INTERNAL API ===============================*/
    ################################################################################

    /**
     * Handle CORS preflight requests
     * @return never
     */
    private function handlePreflight(): never
    {
        $this->applyCors();
        header('Access-Control-Max-Age: 86400');
        Header::code(204);
        exit;
    }

    /**
     * Apply CORS headers
     * @return void
     */
    private function applyCors(): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        $headers = [
            "Access-Control-Allow-Origin"   =>  $this->allowedOrigin,
            "Access-Control-Allow-Methods"  =>  implode(', ', $this->acceptableMethods),
            "Access-Control-Allow-Headers"  =>  "Content-Type, Authorization, X-Requested-With, Accept, Accept-Encoding, Accept-Charset",
            "Access-Control-Expose-Headers" =>  "Content-Encoding, Content-Type, Content-Length"
        ];
        Header::set($headers);
    }

    /**
     * Detect preferred charset from Accept-Charset
     * @return string
     */
    private function detectCharset(): string
    {
        $acceptCharset = strtolower($_SERVER['HTTP_ACCEPT_CHARSET'] ?? '');
        if (empty($acceptCharset)) {
            return 'utf-8';
        }

        $parsed = [];
        foreach (explode(',', $acceptCharset) as $part) {
            [$charset, $q] = array_map('trim', explode(';q=', $part) + [1 => '1']);
            $parsed[$charset] = (float)$q;
        }

        arsort($parsed, SORT_NUMERIC);
        return array_key_first($parsed) ?: 'utf-8';
    }

    /**
     * Reset
     * @return void
     */
    protected function reset()
    {
        $this->accepted             =   ['application/json', 'application/x-www-form-urlencoded'];
        $this->contentType          =   strtolower(strtok($_SERVER['CONTENT_TYPE'] ?? 'application/json', ';'));
        $this->method               =   Request::method();
        $this->message              =   null;
        $this->acceptableMethods    =   ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
        $this->allowedOrigin        =   '*';

        // Handle CORS preflight
        if ($this->method === 'OPTIONS') {
            $this->handlePreflight();
        }
    }
}
