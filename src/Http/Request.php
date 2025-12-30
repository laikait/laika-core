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

class Request
{
    /**
     * @property Request $instance
     */
    protected static Request $instance;

    /**
     * @property array $get
     */
    protected array $get;

    /**
     * @property array $post
     */
    protected array $post;

    /**
     * @property array $files
     */
    protected array $files;

    /**
     * @property array $json
     */
    protected array $json;

    /**
     * @property string $rawBody
     */
    protected string $rawBody;

    /**
     * @property string $method
     */
    protected string $method;

    /**
     * @property array $errors Request Validation Errors
     */
    protected array $errors;


    ##################################################################
    /*------------------------- PUBLIC API -------------------------*/
    ##################################################################

    /**
     * Start Instance
     */
    public function __construct()
    {
        $this->get = $this->purify($_GET ?? []);
        $this->post = $this->purify($_POST ?? []);
        $this->files = $_FILES ?? [];
        $this->rawBody = file_get_contents('php://input');
        $this->json = $this->purify($this->decode($this->rawBody));
        $this->method = strtoupper($this->post['_method'] ?? $_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->errors = [];
    }

    /**
     * Get Method
     * @return string
     */
    public function method(): string
    {
        // Define $instance if Not Defined Yet
        return $this->method;
    }

    /**
     * Get Header Key Values
     * @return array
     */
    public function headers(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = $value;
            }
        }
        return $headers;
    }

    /**
     * Get Header Key Value
     * @param string $key Key name of headers. Example: 'content-type'
     * @return ?string
     */
    public function header(string $key): ?string
    {
        return $this->headers()[strtolower($key)] ?? null;
    }

    /**
     * Request is POST
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    /**
     * Request is GET
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    /**
     * Request is PUT
     * @return bool
     */
    public function isPut(): bool
    {
        return $this->method === 'PUT';
    }

    /**
     * Request is DELETE
     * @return bool
     */
    public function isDelete(): bool
    {
        return $this->method === 'DELETE';
    }

    /**
     * Request is PATCH
     * @return bool
     */
    public function isPatch(): bool
    {
        return $this->method === 'PATCH';
    }

    /**
     * Check Request is Ajax
     * @return bool
     */
    public function isAjax(): bool
    {
        return strtolower($this->header('X-Requested-With')) === 'xmlhttprequest';
    }

    /**
     * Get Value From Input Key
     * @param string $key Key Name of Request
     * @param mixed $default Default is null if not Key Exists
     * @return mixed
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->get[$key] ?? $this->json[$key] ?? $default;
    }

    /**
     * Get All Request Key & Values
     * @return array
     */
    public function all(): array
    {
        return array_merge($this->json, $this->post, $this->get);
    }

    // Get Selected Key Values
    public function only(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->input($key, null);
        }
        return $result;
    }

    /**
     * Check Request Key Exist
     * @param string $key Key Name of Request
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->post) || array_key_exists($key, $this->get) || array_key_exists($key, $this->json);
    }

    /**
     * Get JSON Body
     * @return array
     */
    public function json(): array
    {
        return $this->json;
    }

    /**
     * Get Selected Request File or All Request Files
     * @param ?string $key Key Name of Request File. Null Will Return All Request File Info
     * @return ?array
     */
    public function file(?string $key = null): ?array
    {
        return $key ? ($this->files[$key] ?? []) : $this->files;
    }

    /**
     * Get JSON String
     * @return string
     */
    public function raw(): string
    {
        return $this->rawBody;
    }

    /**
     * Validate Request Keys
     * @param array $keys Request Keys. Example: ['name', 'password']
     * @return bool
     */
    public function validRequestKeys(array $keys): bool
    {
        foreach ($keys as $key) {
            if (!$this->has($key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check If Required Inputs Has Blank Value
     * @param $keys Required Argument. Example: ['username','email','password']
     */
    public function hasBlankInput(array $keys): bool
    {
        foreach ($keys as $key) {
            $value = $this->input($key);
            if ($value === null || $value === '') {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array $rules Required Argument. Example ['email'=>'required','age'=>'required|min:18|max:65']
     * @param array $customMessages Optional Argument. Example: ['email.required'=>'Email is Required!']
     * @return array
     */
    public function validate(array $rules, array $customMessages = []): array
    {
        $this->errors = Validator::make($this->all(), $rules, $customMessages);
        return $this->errors;
    }

    /**
     * Request Errors
     * @return array
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Decode Raw Body
     * @return array
     */
    public function decode(string $rawBody): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_starts_with(strtolower($contentType), 'application/json')) {
            $decoded = json_decode($rawBody, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    // Purify Arry Values
    /**
     * @param array $data Array Data to Purify
     * @return array
     */
    public function purify(array $data): array
    {
        return array_map(function($val){
            return is_array($val)
                ? $this->purify($val)
                : htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
        }, $data);
    }
}
