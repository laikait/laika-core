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

use Laika\Core\Contracts\SanitizerInterface;
use Laika\Core\Sanitizer\InputSanitizer;


class Request
{
    /** @var array $get */
    protected array $get;

    /** @var array $post */
    protected array $post;

    /** @var array $files */
    protected array $files;

    /** @var array $json */
    protected array $json;

    /** @var string $rawBody */
    protected string $rawBody;

    /** @var ?array Cached Headers */
    protected ?array $cachedHeaders = null;

    /** @var string $method */
    protected string $method;

    /** @var array $errors Request Validation Errors */
    protected array $errors = [];

    /** @var SanitizerInterface Input Sanitizer Interface */
    protected SanitizerInterface $sanitizer;

    ####################################################################
    /*------------------------- EXTERNAL API -------------------------*/
    ####################################################################

    public function __construct(?SanitizerInterface $sanitizer = null)
    {
        $this->sanitizer = $sanitizer ?? new InputSanitizer();
        $this->get = $this->sanitizer->sanitize($_GET ?? []);
        $this->post = $this->sanitizer->sanitize($_POST ?? []);
        $this->files = $_FILES ?? [];
        $this->rawBody = file_get_contents('php://input') ?: '';
        $this->json = $this->sanitizer->sanitize($this->decode($this->rawBody));
        $spoofable = ['PUT', 'PATCH', 'DELETE'];
        $spoofed = strtoupper($this->post['_method'] ?? $this->json['_method'] ?? '');
        $this->method = in_array($spoofed, $spoofable, true) ? $spoofed : strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Get Method
     * @return string
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * Get Request Headers
     * @return array
     */
    public function headers(): array
    {
        if ($this->cachedHeaders !== null) return $this->cachedHeaders;

        $this->cachedHeaders = [];

        if (function_exists('getallheaders')) {
            $all = getallheaders();
            if (is_array($all)) {
                $this->cachedHeaders = $all;
            }
        } else {
            foreach ($_SERVER as $key => $value) {
                if (str_starts_with($key, 'HTTP_')) {
                    $name = ucwords(strtolower(str_replace('_', '-', substr($key, 5))), '-');
                    $this->cachedHeaders[$name] = $value;
                } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
                    $name = ucwords(strtolower(str_replace('_', '-', $key)), '-');
                    $this->cachedHeaders[$name] = $value;
                }
            }
        }

        return $this->cachedHeaders;
    }

    /**
     * Get Header Key Value
     * @param string $name Key name of headers. Example: 'content-type'
     * @return ?string
     */
    public function header(string $name): ?string
    {
        $normalized = $this->normalizeHeaderName($name);
    
        // Fast path: direct $_SERVER lookup
        $key = strtoupper(str_replace('-', '_', $normalized));
        if (str_starts_with($key, 'CONTENT_')) {
            if (isset($_SERVER[$key])) return $_SERVER[$key];
        } else {
            $httpKey = 'HTTP_' . $key;
            if (isset($_SERVER[$httpKey])) return $_SERVER[$httpKey];
        }
        
        // Fallback: case-insensitive search
        foreach ($this->headers() as $k => $v) {
            if ($this->normalizeHeaderName($k) === $normalized) {
                return $v;
            }
        }
        
        return null;
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
        if (array_key_exists($key, $this->json)) return $this->json[$key];
        if (array_key_exists($key, $this->post)) return $this->post[$key];
        if (array_key_exists($key, $this->get)) return $this->get[$key];
        return $default;
    }

    /**
     * Get All Request Key & Values
     * @return array
     */
    public function inputs(): array
    {
        return array_merge($this->get, $this->post, $this->json);
    }

    /**
     * Get Selected Key Values
     * @param string[] $keys Array Keys to Get Values
     * @return array
     */
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
    public function body(): array
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
        return $key !== null ? ($this->files[$key] ?? null) : $this->files;
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
     * Validate request inputs against rules.
     * Preserves manually added errors; only validation errors are replaced.
     * @param array $rules Required. Example: ['email'=>'required','age'=>'required|min:18|max:65']
     * @param array $customMessages Optional. Example: ['email.required'=>'Email is Required!']
     * @return bool True if validation passes (no new errors), false otherwise
     */
    public function validate(array $rules, array $customMessages = []): bool
    {
        $validationErrors = Validator::make($this->inputs(), $rules, $customMessages);

        // Guard: ensure Validator returns an array
        if (!is_array($validationErrors)) {
            $validationErrors = [];
        }

        // Merge validation errors without wiping manually added ones
        foreach ($validationErrors as $key => $messages) {
            $messages = is_array($messages) ? $messages : [$messages];
            $this->errors[$key] = array_merge($this->errors[$key] ?? [], $messages);
        }

        return empty($validationErrors);
    }

    /**
     * Add Bulk Errors
     * @param array<string,array> $errors
     * @return void
     */
    public function addBulkError(array $errors): void
    {
        foreach ($errors as $key => $messages) {
            $messages = is_array($messages) ? $messages : [$messages];
            $this->errors[$key] = array_merge($this->errors[$key] ?? [], $messages);
        }
    }

    /**
     * Add Error
     * @param string $key Form Error Key
     * @param string $error Error Message
     * @return void
     */
    public function addError(string $key, string $error): void
    {
        $this->errors[$key][] = $error;
    }

    /**
     * Request Errors
     * @return array
     */
    public function errors(): array
    {
        return $this->errors;
    }

    ###################################################################
    /*------------------------- INTERNAL API -------------------------*/
    ###################################################################
    /**
     * Decode Raw Body
     * @return array
     */
    protected function decode(string $rawBody): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_starts_with(strtolower($contentType), 'application/json')) {
            $decoded = json_decode($rawBody, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->addError('body', 'Invalid JSON: ' . json_last_error_msg());
                return [];
            }
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    /**
     * Normalize Header Name
     * @param string $name
     * @return string
     */
    protected function normalizeHeaderName(string $name): string
    {
        return ucwords(strtolower(trim($name)), '-');
    }
}
