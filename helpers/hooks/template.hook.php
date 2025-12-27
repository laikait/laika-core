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

#####################################################################
/*----------------------- TEMPLATE FILTERS ------------------------*/
#####################################################################
// Load Template Asset
add_hook('template.asset', function(string $file): string {
    if(parse_url($file, PHP_URL_HOST)){
        return $file;
    }
    $file = trim($file, '/');
    return named('template.src', ['name' => $file], true);
});

// Set Template Default JS Vars
add_hook('template.scripts', function(): string{
    $authorizarion = call_user_func([new Response, 'get'], 'authorization');
    $appuri = trim(do_hook('app.host'), '/');
    $timeformat = do_hook('option', 'time.format', 'Y-M-d H:i:s');
    return <<<HTML
        <script>
                let token = '{$authorizarion}';
                let appuri = '{$appuri}';
                let timeformat = '{$timeformat}';
            </script>\n
    HTML;
});