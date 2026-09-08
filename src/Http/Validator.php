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

class Validator
{
    /**
     * Validate data according to given rules.
     *
     * Supported marker rules:
     *  - nullable : skip other rules when value is null/''
     *  - bail     : stop checking a field after its first error
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
            $ruleList = array_filter(explode('|', $ruleString), 'strlen');

            // Check if field is nullable / bail — affects how other rules run
            $isNullable = in_array('nullable', $ruleList, true);
            $bail       = in_array('bail', $ruleList, true);

            foreach ($ruleList as $rule) {
                $params = [];

                if (str_contains($rule, ':')) {
                    [$rule, $paramString] = explode(':', $rule, 2);
                    $params = strtolower($rule) === 'regex'
                        ? [$paramString]                       // regex pattern must stay verbatim
                        : array_map('trim', explode(',', $paramString)); // all other params trimmed
                }

                $ruleName      = strtolower(trim($rule));
                $messageKey    = "{$field}.{$ruleName}";
                $customMessage = $customMessages[$messageKey] ?? null;

                // Marker rules are always evaluated; everything else is skipped on null/''
                if (!in_array($ruleName, ['required', 'nullable', 'bail'], true)
                    && ($value === null || $value === '')
                ) {
                    continue;
                }

                switch ($ruleName) {

                    case 'nullable':
                    case 'bail':
                        // Marker rules only — no validation logic needed
                        break;

                    case 'required':
                        if ($value === null || $value === '') {
                            $errors[$field][] = $customMessage ?? "The [{$field}] field is required.";
                        }
                        break;

                    case 'email':
                        if (!self::isStringable($value) || !filter_var((string) $value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be a valid email address.";
                        }
                        break;

                    case 'url':
                        if (!self::isStringable($value) || !filter_var((string) $value, FILTER_VALIDATE_URL)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be a valid URL.";
                        }
                        break;

                    case 'numeric':
                        if (!is_numeric($value)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be numeric.";
                        }
                        break;

                    case 'integer':
                        // Accepts "42", "-7", 42 — rejects "3.5", "1e2", "abc"
                        if (!is_scalar($value) || filter_var($value, FILTER_VALIDATE_INT) === false) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be an integer.";
                        }
                        break;

                    case 'float':
                        // Accepts "42.", "-7.5", 42, "3" — rejects "1e2", "abc"
                        if (!is_scalar($value) || filter_var($value, FILTER_VALIDATE_FLOAT) === false) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be a float number.";
                        }
                        break;

                    case 'boolean':
                        // Accepts: true, false, 1, 0, "1", "0", "true", "false", "yes", "no"
                        if (!is_scalar($value)
                            || filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === null
                        ) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be a boolean value.";
                        }
                        break;

                    case 'array':
                        if (!is_array($value)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be an array.";
                        }
                        break;

                    case 'string':
                        if (!is_string($value)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be a string.";
                        }
                        break;

                    case 'date':
                        // date        → any format strtotime() understands ("2024-01-15", "15 Jan 2024")
                        // date:Y-m-d  → strict format check only
                        $format  = $params[0] ?? null;
                        if ($format !== null && $format !== '') {
                            $dt    = \DateTime::createFromFormat($format, (string) $value);
                            $valid = $dt !== false && $dt->format($format) === (string) $value;
                            $hint  = " (format: {$format})";
                        } else {
                            $valid = strtotime((string) $value) !== false;
                            $hint  = '';
                        }
                        if (!$valid) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be a valid date{$hint}.";
                        }
                        break;

                    case 'time':
                        // time        → any format strtotime() understands ("15:45", "3:45 pm")
                        // time:H:i:s  → strict format check only
                        $format  = $params[0] ?? null;
                        if ($format !== null && $format !== '') {
                            $dt    = \DateTime::createFromFormat($format, (string) $value);
                            $valid = $dt !== false && $dt->format($format) === (string) $value;
                            $hint  = " (format: {$format})";
                        } else {
                            $valid = strtotime((string) $value) !== false;
                            $hint  = '';
                        }
                        if (!$valid) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be a valid time{$hint}.";
                        }
                        break;

                    case 'before':
                    case 'after':
                        // before:2030-01-01 / after:2000-01-01
                        $limit = $params[0] ?? null;
                        $limitTime = $limit !== null ? strtotime($limit) : false;
                        if ($limitTime === false) {
                            throw new \InvalidArgumentException(
                                "Invalid date limit [{$limit}] for rule [{$ruleName}] on field [{$field}]."
                            );
                        }
                        $valueTime = strtotime((string) $value);
                        $isBefore  = $ruleName === 'before';
                        if ($valueTime === false || ($isBefore ? $valueTime >= $limitTime : $valueTime <= $limitTime)) {
                            $errors[$field][] = $customMessage
                                ?? "The [{$field}] must be a date " . ($isBefore ? 'before' : 'after') . " {$limit}.";
                        }
                        break;

                    case 'min':
                        $min = (int) ($params[0] ?? 0);
                        if (is_numeric($value) && (float) $value < $min) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be at least {$min}.";
                        } elseif (is_string($value) && mb_strlen($value) < $min) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be at least {$min} characters.";
                        } elseif (is_array($value) && count($value) < $min) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must have at least {$min} items.";
                        }
                        break;

                    case 'max':
                        $max = (int) ($params[0] ?? 0);
                        if (is_numeric($value) && (float) $value > $max) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] may not be greater than {$max}.";
                        } elseif (is_string($value) && mb_strlen($value) > $max) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] may not be greater than {$max} characters.";
                        } elseif (is_array($value) && count($value) > $max) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] may not have more than {$max} items.";
                        }
                        break;

                    case 'between':
                        // between:2,10 — value/length/count must fall inside the inclusive range
                        $lo = (int) ($params[0] ?? 0);
                        $hi = (int) ($params[1] ?? 0);
                        if (is_numeric($value) && ((float) $value < $lo || (float) $value > $hi)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be between {$lo} and {$hi}.";
                        } elseif (is_string($value) && (mb_strlen($value) < $lo || mb_strlen($value) > $hi)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be between {$lo} and {$hi} characters.";
                        } elseif (is_array($value) && (count($value) < $lo || count($value) > $hi)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must have between {$lo} and {$hi} items.";
                        }
                        break;

                    case 'size':
                        // Exact size: size:10 — characters for strings, count for arrays, value for numbers
                        $size = (int) ($params[0] ?? 0);
                        if (is_numeric($value) && (float) $value !== (float) $size) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must equal {$size}.";
                        } elseif (is_string($value) && mb_strlen($value) !== $size) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be exactly {$size} characters.";
                        } elseif (is_array($value) && count($value) !== $size) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must contain exactly {$size} items.";
                        }
                        break;

                    case 'match':
                        $other = $params[0] ?? '';
                        if (!array_key_exists($other, $data) || $value !== $data[$other]) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must match [{$other}].";
                        }
                        break;

                    case 'in':
                        if (!self::isStringable($value) || !in_array((string) $value, $params, true)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be one of: " . implode(', ', $params) . ".";
                        }
                        break;

                    case 'not_in':
                        if (self::isStringable($value) && in_array((string) $value, $params, true)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must not be one of: " . implode(', ', $params) . ".";
                        }
                        break;

                    case 'alpha':
                        // Unicode letters only (ASCII via ctype_* would reject e.g. "Ångström")
                        if (!preg_match('/^\p{L}+$/u', (string) $value)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must contain only alphabetic characters.";
                        }
                        break;

                    case 'upper':
                        if (!preg_match('/^\p{Lu}+$/u', (string) $value)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must contain upper alphabetic characters.";
                        }
                        break;

                    case 'lower':
                        if (!preg_match('/^\p{Ll}+$/u', (string) $value)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must contain lower alphabetic characters.";
                        }
                        break;

                    case 'alpha_num':
                        if (!preg_match('/^[\p{L}\p{N}]+$/u', (string) $value)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must contain only alphanumeric characters.";
                        }
                        break;

                    case 'alpha_dash':
                        // Letters, numbers, hyphens, underscores (unicode-aware)
                        if (!preg_match('/^[\p{L}\p{N}_-]+$/u', (string) $value)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must contain only letters, numbers, hyphens, and underscores.";
                        }
                        break;

                    case 'regex':
                        $pattern = $params[0] ?? '';
                        if ($pattern && @preg_match($pattern, '') !== false) {
                            if (!preg_match($pattern, (string) $value)) {
                                $errors[$field][] = $customMessage ?? "The [{$field}] format is invalid.";
                            }
                        } else {
                            $errors[$field][] = "Invalid regex pattern for [{$field}].";
                        }
                        break;

                    case 'ip':
                        if (!self::isStringable($value) || !filter_var((string) $value, FILTER_VALIDATE_IP)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be a valid IP address.";
                        }
                        break;

                    case 'ipv4':
                        if (!self::isStringable($value) || !filter_var((string) $value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be a valid IPv4 address.";
                        }
                        break;

                    case 'ipv6':
                        if (!self::isStringable($value) || !filter_var((string) $value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be a valid IPv6 address.";
                        }
                        break;

                    case 'uid':
                        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', (string) $value)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be a valid UID.";
                        }
                        break;

                    case 'json':
                        if (!self::isStringable($value)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be valid JSON.";
                            break;
                        }
                        json_decode((string) $value);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be valid JSON.";
                        }
                        break;

                    case 'callback':
                        $callbackName = $params[0] ?? null;
                        if ($callbackName !== null && is_callable($callbackName)) {
                            $result = call_user_func($callbackName, $value, $data, array_slice($params, 1));
                            if ($result !== true) {
                                $errors[$field][] = $customMessage ?? (is_string($result) ? $result : "The [{$field}] failed custom validation.");
                            }
                        } else {
                            $errors[$field][] = "Invalid callback validation for [{$field}].";
                        }
                        break;

                    default:
                        throw new \InvalidArgumentException("Unknown validation rule [{$ruleName}] for field [{$field}].");
                }

                // bail: stop checking this field after its first error
                if ($bail && isset($errors[$field])) {
                    break;
                }
            }
        }

        return $errors;
    }

    /**
     * True for scalars and stringable objects — safe to cast to string.
     */
    private static function isStringable(mixed $value): bool
    {
        return is_scalar($value) || (is_object($value) && method_exists($value, '__toString'));
    }
}
