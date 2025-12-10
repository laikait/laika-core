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

use Laika\Core\Http\Response;
use Laika\Core\App\Route\Asset;

#####################################################################
/*----------------------- TEMPLATE FILTERS ------------------------*/
#####################################################################
// Load Template Asset
add_hook('template.asset', function(string $file): string {
    if(parse_url($file, PHP_URL_HOST)){
        return $file;
    }
    $file = trim($file, '/');
    $slug = trim(Asset::$template, '/');
    return do_hook('app.host') . "{$slug}/{$file}";
});

// Set Template Default JS Vars
add_hook('template.scripts', function(): string{
    $authorizarion = Response::instance()->get('authorization');
    $appuri = trim(do_hook('app.host'), '/');
    $timeformat = option('time.format', 'Y-M-d H:i:s');
    return <<<HTML
        <script>
                let token = '{$authorizarion}';
                let appuri = '{$appuri}';
                let timeformat = '{$timeformat}';
            </script>\n
    HTML;
});