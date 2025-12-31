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

use Laika\Core\Helper\CSRF;

#####################################################################
/*------------------------- CSRF FILTERS --------------------------*/
#####################################################################
/**
 * CSRF Token HTL Field
 * @param ?string $for Default is null. Example: 'app' or 'admin'
 * @return string
 */
add_hook('csrf.field', function (?string $for = null): string{
    return call_user_func([new CSRF(for:$for), 'field']);
});