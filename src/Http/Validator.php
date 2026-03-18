<?php
/**
 * Laika PHP Micro Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 */

declare(strict_types=1);

namespace Laika\Core\Http;

class Validator
{
    /**
     * Validate data according to given rules.
     *
     * @param array $data Input data
     * @param array $rules Validation rules e.g. ['email' => 'required|email|max:100']
     * @param array $customMessages Custom error messages e.g. ['email.required' => 'Email is required.']
     * @return array Validation errors
     */
    public static function make(array $data, array $rules, array $customMessages = []): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $value    = $data[$field] ?? null;
            $ruleList = \array_filter(\explode('|', $ruleString), 'strlen');

            // Check if field is nullable — affects whether other rules run on null/empty
            $isNullable = \in_array('nullable', $ruleList, true);

            foreach ($ruleList as $rule) {
                $params = [];

                if (\str_contains($rule, ':')) {
                    [$rule, $paramString] = \explode(':', $rule, 2);
                    $params = \strtolower($rule) === 'regex'
                        ? [$paramString]
                        : \explode(',', $paramString);
                }

                $ruleName     = \strtolower(\trim($rule));
                $messageKey   = "{$field}.{$ruleName}";
                $customMessage = $customMessages[$messageKey] ?? null;

                // nullable and required are always evaluated
                if (!\in_array($ruleName, ['required', 'nullable'], true)) {
                    // Skip all other rules if field is nullable and value is absent
                    if ($isNullable && ($value === null || $value === '')) {
                        continue;
                    }
                    // Skip non-required empty fields
                    if (!$isNullable && ($value === null || $value === '')) {
                        continue;
                    }
                }

                switch ($ruleName) {

                    case 'nullable':
                        // Marker rule only — no validation logic needed
                        break;

                    case 'required':
                        if ($value === null || $value === '') {
                            $errors[$field][] = $customMessage ?? "The [{$field}] field is required.";
                        }
                        break;

                    case 'email':
                        if (!\filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be a valid email address.";
                        }
                        break;

                    case 'url':
                        if (!\filter_var($value, FILTER_VALIDATE_URL)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be a valid URL.";
                        }
                        break;

                    case 'numeric':
                        if (!\is_numeric($value)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be numeric.";
                        }
                        break;

                    case 'integer':
                        // Accepts "42", "-7", 42 — rejects "3.5", "1e2", "abc"
                        if (\filter_var($value, FILTER_VALIDATE_INT) === false) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be an integer.";
                        }
                        break;
                    
                    case 'float':
                        // Accepts "42.", "-7.5", 42.1 — rejects "3", "1e2", "abc"
                        if (\filter_var($value, FILTER_VALIDATE_FLOAT) === false) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be a float number.";
                        }
                        break;

                    case 'boolean':
                        // Accepts: true, false, 1, 0, "1", "0", "true", "false", "yes", "no"
                        if (\filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === null) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be a boolean value.";
                        }
                        break;

                    case 'array':
                        if (!\is_array($value)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be an array.";
                        }
                        break;

                    case 'string':
                        if (!\is_string($value)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be a string.";
                        }
                        break;

                    case 'date':
                        // Accepts any format strtotime() understands e.g. "2024-01-15", "15 Jan 2024"
                        // Optional param for strict format: date:Y-m-d
                        $format = $params[0] ?? 'Y-m-d';
                        if ($format) {
                            $dt = \DateTime::createFromFormat($format, (string) $value);
                            $valid = $dt && $dt->format($format) === (string) $value;
                        } else {
                            $valid = \strtotime((string) $value) !== false;
                        }
                        if (!$valid) {
                            $hint = $format ? " (format: {$format})" : '';
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be a valid date{$hint}.";
                        }
                        break;

                    case 'time':
                        // Accepts any format strtotime() understands e.g. "15:45:39"
                        // Optional param for strict format: date:H:i:s
                        $format = $params[0] ?? 'H:i:s';
                        if ($format) {
                            $dt = \DateTime::createFromFormat($format, (string) $value);
                            $valid = $dt && $dt->format($format) === (string) $value;
                        } else {
                            $valid = \strtotime((string) $value) !== false;
                        }
                        if (!$valid) {
                            $hint = $format ? " (format: {$format})" : '';
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be a valid time{$hint}.";
                        }
                        break;

                    case 'before':
                        // before:2030-01-01
                        $limit = $params[0] ?? null;
                        if ($limit && \strtotime((string) $value) !== false) {
                            if (\strtotime((string) $value) >= \strtotime($limit)) {
                                $errors[$field][] = $customMessage ?? "The [{$field}] must be a date before {$limit}.";
                            }
                        }
                        break;

                    case 'after':
                        // after:2000-01-01
                        $limit = $params[0] ?? null;
                        if ($limit && \strtotime((string) $value) !== false) {
                            if (\strtotime((string) $value) <= \strtotime($limit)) {
                                $errors[$field][] = $customMessage ?? "The [{$field}] must be a date after {$limit}.";
                            }
                        }
                        break;

                    case 'min':
                        $min = (int) ($params[0] ?? 0);
                        if (\is_numeric($value) && (float) $value < $min) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be at least {$min}.";
                        } elseif (\is_string($value) && \mb_strlen($value) < $min) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be at least {$min} characters.";
                        } elseif (\is_array($value) && \count($value) < $min) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must have at least {$min} items.";
                        }
                        break;

                    case 'max':
                        $max = (int) ($params[0] ?? 0);
                        if (\is_numeric($value) && (float) $value > $max) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] may not be greater than {$max}.";
                        } elseif (\is_string($value) && \mb_strlen($value) > $max) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] may not be greater than {$max} characters.";
                        } elseif (\is_array($value) && \count($value) > $max) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] may not have more than {$max} items.";
                        }
                        break;

                    case 'size':
                        // Exact size: size:10 — characters for strings, count for arrays, value for numbers
                        $size = (int) ($params[0] ?? 0);
                        if (\is_numeric($value) && (float) $value !== (float) $size) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must equal {$size}.";
                        } elseif (\is_string($value) && \mb_strlen($value) !== $size) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be exactly {$size} characters.";
                        } elseif (\is_array($value) && \count($value) !== $size) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must contain exactly {$size} items.";
                        }
                        break;

                    case 'match':
                        $other = $params[0] ?? '';
                        if (!isset($data[$other]) || $value !== $data[$other]) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must match [{$other}].";
                        }
                        break;

                    case 'in':
                        if (!\in_array((string) $value, $params, true)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be one of: " . \implode(', ', $params) . ".";
                        }
                        break;

                    case 'not_in':
                        if (\in_array((string) $value, $params, true)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must not be one of: " . \implode(', ', $params) . ".";
                        }
                        break;

                    case 'alpha':
                        if (!\ctype_alpha((string) $value)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must contain only alphabetic characters.";
                        }
                        break;

                    case 'alpha_num':
                        if (!\ctype_alnum((string) $value)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must contain only alphanumeric characters.";
                        }
                        break;

                    case 'alpha_dash':
                        // Letters, numbers, hyphens, underscores
                        if (!\preg_match('/^[\pL\pN_-]+$/u', (string) $value)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must contain only letters, numbers, hyphens, and underscores.";
                        }
                        break;

                    case 'regex':
                        $pattern = $params[0] ?? '';
                        if ($pattern && @\preg_match($pattern, '') !== false) {
                            if (!\preg_match($pattern, (string) $value)) {
                                $errors[$field][] = $customMessage ?? "The [{$field}] format is invalid.";
                            }
                        } else {
                            $errors[$field][] = "Invalid regex pattern for [{$field}].";
                        }
                        break;

                    case 'ip':
                        if (!\filter_var($value, FILTER_VALIDATE_IP)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be a valid IP address.";
                        }
                        break;

                    case 'ipv4':
                        if (!\filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be a valid IPv4 address.";
                        }
                        break;

                    case 'ipv6':
                        if (!\filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be a valid IPv6 address.";
                        }
                        break;

                    case 'uid':
                        if (!\preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', (string) $value)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be a valid UID.";
                        }
                        break;

                    case 'json':
                        \json_decode((string) $value);
                        if (\json_last_error() !== JSON_ERROR_NONE) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be valid JSON.";
                        }
                        break;

                    case 'callback':
                        $callbackName = $params[0] ?? null;
                        if ($callbackName) {
                            if (\is_callable($callbackName) || \function_exists($callbackName) || \strpos($callbackName, '::') !== false) {
                                $result = \call_user_func($callbackName, $value, $data, \array_slice($params, 1));
                            } else {
                                $result = "Invalid callback validation for [{$field}].";
                            }
                            if ($result !== true) {
                                $errors[$field][] = $customMessage ?? (\is_string($result) ? $result : "The [{$field}] failed custom validation.");
                            }
                        } else {
                            $errors[$field][] = "Invalid callback validation for [{$field}].";
                        }
                        break;

                    default:
                        throw new \InvalidArgumentException("Unknown validation rule [{$ruleName}] for field [{$field}].");
                }
            }
        }

        return $errors;
    }
}
