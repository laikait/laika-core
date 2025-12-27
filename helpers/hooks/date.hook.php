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

use Laika\Core\Helper\Date;

#####################################################################
/*------------------------- DATE FILTERS --------------------------*/
#####################################################################
/**
 * Display Date
 * @param int $time Unix Timestamps
 * @return string
 */
add_hook('date.display', function(int $time): string{
    $date = Date::now(\do_hook('option', 'time.format', 'Y-M-d H:i:s'));
    $date->setTimestamp($time);
    return $date->format();
});