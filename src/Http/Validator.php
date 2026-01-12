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
     * @param array $data           Input data (e.g., $_REQUEST)
     * @param array $rules          Validation rules (e.g., ['email' => 'required|email'])
     * @param array $customMessages Custom error messages
     * @return array                Validation errors
     */
    public static function make(array $data, array $rules, array $customMessages = []): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            $ruleList = \array_filter(\explode('|', $ruleString), 'strlen');

            foreach ($ruleList as $rule) {
                $params = [];

                if (\str_contains($rule, ':')) {
                    [$rule, $paramString] = \explode(':', $rule, 2);
                    $params = \strtolower($rule) === 'regex'
                        ? [$paramString]
                        : \explode(',', $paramString);
                }

                $ruleName = \strtolower(trim($rule));
                $messageKey = "{$field}.{$ruleName}";
                $customMessage = $customMessages[$messageKey] ?? null;

                // Skip non-required empty fields
                if ($ruleName !== 'required' && ($value === null || $value === '')) {
                    continue;
                }

                switch ($ruleName) {
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

                    case 'numeric':
                        if (!\is_numeric($value)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be numeric.";
                        }
                        break;

                    case 'min':
                        $min = (int)($params[0] ?? 0);
                        if (\is_numeric($value) && $value < $min) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be at least {$min}.";
                        } elseif (\is_string($value) && \mb_strlen($value) < $min) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be at least {$min} characters.";
                        }
                        break;

                    case 'max':
                        $max = (int)($params[0] ?? 0);
                        if (\is_numeric($value) && $value > $max) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] may not be greater than {$max}.";
                        } elseif (\is_string($value) && \mb_strlen($value) > $max) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] may not be greater than {$max} characters.";
                        }
                        break;

                    case 'match':
                        $other = $params[0] ?? '';
                        if (!isset($data[$other]) || $value !== $data[$other]) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must match [{$other}].";
                        }
                        break;

                    case 'in':
                        if (!\in_array($value, $params, true)) {
                            $errors[$field][] = $customMessage ?? "The [{$field}] must be one of: " . \implode(', ', $params);
                        }
                        break;

                    case 'regex':
                        $pattern = $params[0] ?? '';
                        if ($pattern && @\preg_match($pattern, '') !== false) {
                            if (!\preg_match($pattern, (string)$value)) {
                                $errors[$field][] = $customMessage ?? "The [{$field}] format is invalid.";
                            }
                        } else {
                            $errors[$field][] = "Invalid regex pattern for [{$field}].";
                        }
                        break;

                    case 'callback':
                        $callbackName = $params[0] ?? null;
                        if ($callbackName) {
                            if (\is_callable($callbackName)) {
                                $result = \call_user_func($callbackName, $value, $data, array_slice($params, 1));
                            } elseif (\strpos($callbackName, '::') !== false) {
                                // Static method callback
                                $result = \call_user_func($callbackName, $value, $data, array_slice($params, 1));
                            } elseif (\function_exists($callbackName)) {
                                // Global function
                                $result = \call_user_func($callbackName, $value, $data, array_slice($params, 1));
                            } else {
                                $result = "Invalid callback validation for [{$field}].";
                            }

                            if ($result !== true) {
                                $errors[$field][] = $customMessage ?? (\is_string($result) ? $result : "The {$field} failed custom validation.");
                            }
                        } else {
                            $errors[$field][] = "Invalid callback validation for [{$field}].";
                        }
                        break;

                    default:
                        $errors[$field][] = "Unknown validation rule [{$ruleName}] for field [{$field}].";
                        break;
                }
            }
        }

        return $errors;
    }
}
