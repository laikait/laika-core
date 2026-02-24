<?php

/**
 * Laika PHP Micro Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika PHP Micro Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

// Namespace
namespace Laika\Core\Http;

// Deny Direct Access
defined('APP_PATH') || http_response_code(403) . die('403 Direct Access Denied!');

class ChangeLog
{
    /**
     * Check Change Logs
     * @param array $existing Existing Value
     * @param array $input New Input Value
     * @return array
     */
    public function check(array $existing, array $input): array
    {
        $changes = [];
        // Check Changes
        foreach ($input as $key => $new) {
            $old = $existing[$key] ?? '';
            if ($old !== $new) {
                $changes[$key] = ['old' => $old, 'new' => $new];
            }
        }
        return $changes;
    }
}