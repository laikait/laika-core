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

use Laika\Session\Session;

####################################################################
/*----------------------- MESSAGE FILTERS ------------------------*/
####################################################################
/**
 * Set Notification Message
 * @param string $message Message to Set
 * @param bool $status Warning or Success. true for Success & false for Warning
 */
add_hook('message.set', function(string $message, bool $status): void {
    Session::set('message', ['info'=>$message,'status'=>$status]);
    return;
});

// Get Notification Message
add_hook('message.show', function(): array {
    $message = Session::get('message');
    Session::pop('message');
    if(!$message) return [];

    return $message;
});