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

class Response
{
    /**
     * Send Success Response
     * @param array $data
     * @param string $message
     * @param int $status
     * @param array $meta
     * @return void
     */
    public static function success(
        array $data = [],
        string $message = 'Success',
        int $status = 200,
        array $meta = []
    ) {
        http_response_code($status);

        echo json_encode([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
            'meta'    => $meta,
        ], JSON_PRETTY_PRINT|JSON_FORCE_OBJECT);

        exit;
    }

    /**
     * Send Error Response
     * @param string $message
     * @param int $status
     * @param array $errors
     * @param array $meta
     * @return void
     */
    public static function error(
        string $message = 'Error',
        int $status = 400,
        array $errors = [],
        array $meta = []
    ) {
        http_response_code($status);

        echo json_encode([
            'status'  => 'error',
            'message' => $message,
            'errors'  => $errors,
            'meta'    => $meta,
        ], JSON_PRETTY_PRINT|JSON_FORCE_OBJECT);

        exit;
    }
}
