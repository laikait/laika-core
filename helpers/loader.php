<?php
/**
 * Laika PHP MVC Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika PHP MVC Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

##############################################################################
/*--------------------------------- LOADER ---------------------------------*/
##############################################################################

declare(strict_types=1);

// Require All Functions File
array_map(function($file) { require $file; }, glob(__DIR__ . '/functions/*.func.php'));

// Require All Hooks File
array_map(function($file) { require $file; }, glob(__DIR__ . '/functions/*.hook.php'));