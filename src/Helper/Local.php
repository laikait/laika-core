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

namespace Laika\Core\Helper;

use Exception;

class Local
{
    // Language Path
    private static string $path = APP_PATH . '/lf-lang';

    // Language Name
    private static string $lang = 'en';

    // Disable Clone
    private function __clone()
    {
        throw new Exception('Cloning is Disabled!');
    }

    /**
     * Set Language
     * @param string $lang Optional Argument. Default is null.
     * @return void
     */
    public static function set(?string $lang = null): void
    {
        self::$lang = \trim($lang ?: self::$lang);
    }

    /**
     * Get Language
     * @return string
     */
    public static function get(): string
    {
        return self::$lang;
    }

    /**
     * Set or Load Path
     * @param ?string Optional Argument. Default is null
     * @return void
     */
    public static function load(?string $path = null): void
    {
        // Set New Path if Argument is Not Blank or Null
        if ($path) {
            $path = \str_replace('\\', '/', $path);
            self::$path .= '/' . \trim($path, '/');
        }
        // Make Directory If Doesn't Exists
        Directory::make(self::$path);

        // Get File Name
        $lang_path = self::$path . '/' . self::get() . '.local.php';

        // Make Language File Object
        $file = new File($lang_path);

        if (!$file->exists()) {
            $content = <<<HTML
            <?php
            /**
             * Laika PHP MVC Framework
             * Author: Showket Ahmed
             * Email: riyadhtayf@gmail.com
             * License: MIT
             * This file is part of the Laika PHP MVC Framework.
             * For the full copyright and license information, please view the LICENSE file that was distributed with this source code
             */

            declare(strict_types=1);

            // Deny Direct Access
            defined('APP_PATH') || http_response_code(403).die('403 Direct Access Denied!');

            // English Language Class
            class LANG
            {
                // Declaer Static Language Variables.
            }
            HTML;

            // Create Language File
            $file->write($content);
        }
        // Return Language Path
        require_once $lang_path;
        return;
    }
}
