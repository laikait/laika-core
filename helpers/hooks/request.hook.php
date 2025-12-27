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

use Laika\Core\Http\Request;

######################################################################
/*------------------------ REQUEST FILTERS -------------------------*/
######################################################################
// Get Request Header
add_hook('request.header', function(string $key): ?string {
    return call_user_func([new Request, 'header'], $key);
});

// Get Request Input Value
add_hook('request.input', function(string $key, mixed $default = ''): mixed {
    return call_user_func([new Request, 'input'], $key, $default);
});

// Get Request Values
add_hook('request.all', function(): array {
    return call_user_func([new Request, 'all']);
});

// Check Method Request is Post/Get/Ajax
add_hook('request.is', function(string $method): bool {
    $method = strtolower($method);
    switch ($method) {
        case 'post':
            return call_user_func([new Request, 'isPost']);
            break;
        case 'get':
            return call_user_func([new Request, 'isGet']);
            break;
        case 'put':
            return call_user_func([new Request, 'isPut']);
            break;
        case 'patch':
            return call_user_func([new Request, 'isPatch']);
            break;
        case 'delete':
            return call_user_func([new Request, 'isDelete']);
            break;
        case 'ajax':
            return call_user_func([new Request, 'isAjax']);
            break;
        default:
            return false;
            break;
    }
});

/**
 * Get Request Error
 * @return string
 */
add_hook('request.error', function(string $key): string{
    $errors = call_user_func([new Request, 'errors']);
    return $errors[$key][0] ?? '';
});