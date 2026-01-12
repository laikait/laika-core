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

use Laika\Core\Http\Response;
use Laika\Core\Http\Request;
use Laika\Core\Helper\Token;

class Api
{
    /**
     * @var array $accepted Application Types
     */
    protected array $accepted;

    /**
     * @var string $contentType Content Type
     */
    protected string $contentType;

    /**
     * @var string $method Request Method
     */
    protected string $method;

    /**
     * @var ?string $message Message to Send
     */
    protected ?string $message;

    /**
     * @var array $acceptableMethods Acceptable Request Methods
     */
    protected array $acceptableMethods;

    /**
     * @var string $allowedOrigin Request Method
     */
    protected string $allowedOrigin;

    /**
     * @var Response $response Response Object
     */
    protected Response $response;

    // Initiate API Object
    public function __construct()
    {
        $this->accepted             =   ['application/json', 'application/x-www-form-urlencoded'];
        $this->contentType          =   strtolower(strtok($_SERVER['CONTENT_TYPE'] ?? 'application/json', ';'));
        $this->method               =   call_user_func([new Request, 'method']);
        $this->message              =   null;
        $this->acceptableMethods    =   ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
        $this->allowedOrigin        =   '*';
        $this->response             =   new Response();

        // Handle CORS preflight
        if ($this->method === 'OPTIONS') {
            $this->handlePreflight();
        }
    }

    /**
     * Set Allowed Origin
     * @param string $origin
     * @return void
     */
    public function setAllowedOrigin(string $origin): void
    {
        $this->allowedOrigin = $origin;
    }

    /**
     * Set Message
     * @param string $message
     * @return void
     */
    public function message(string $message): void
    {
        $this->message = htmlspecialchars(trim($message));
    }

    /**
     * Content-Type
     * @return string
     */
    public function type(): string
    {
        return $this->contentType;
    }

    /**
     * Request Method
     * @return string
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * @return array Request Body
     */
    public function body(): array
    {
        return call_user_func([new Request], 'all');
    }

    /**
     * Get Bearer Token from Authorization Header
     * @return string Bearer Token
     */
    public function bearer(): string
    {
        // Try to fetch the Authorization header
        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? null;

        // Handle missing header
        if (!$header) {
            $this->message('Missing Authorization Header');
            $this->send([], 203);
        }

        // Validate Bearer pattern
        if (!preg_match('/^Bearer\s+(\S+)$/i', trim($header), $matches)) {
            $this->message('Invalid Authorization Header Format');
            $this->send([], 400);
        }

        $token = $matches[1] ?? '';

        // Handle empty token
        if (empty($token)) {
            $this->message('Empty Bearer Token');
            $this->send([], 203);
        }

        $obj = new Token();
        if (!$obj->validateToken($token)) {
            $this->message('Token Expired');
            $this->send([], 401);
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
                "context"   =>  $this->response->codes()[$status]['message'] ?? 'Unassigned',
                "timestamp" =>  date('c')
            ], $additional);
        }

        // Build body
        $body = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_FORCE_OBJECT);

        $charset  = $this->detectCharset();

        // Set Headers
        $this->response->code($status);
        $this->response->setHeader([
            "Content-Type"  =>  "application/json; charset={$charset}",
            "Vary"          =>  "Accept, Accept-Charset"
        ]);

        $this->applyCors();

        echo $body;
        exit;
    }

    ###############################################################
    /*----------------------- PRIVATE API -----------------------*/
    ###############################################################

    /**
     * Handle CORS preflight requests
     */
    private function handlePreflight(): never
    {
        $this->applyCors();
        header('Access-Control-Max-Age: 86400');
        http_response_code(204);
        exit;
    }

    /**
     * Apply CORS headers
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
        call_user_func([$this->response, 'setHeader'], $headers);
    }

    /**
     * Detect preferred charset from Accept-Charset
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
}
