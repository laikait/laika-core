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

use Laika\Service\{Directory, File};
use Laika\Core\Exceptions\LocalException;

class Local
{
    /** @var string Local Path */
    private string $path = LANG_PATH;

    /** @var string Local Name */
    private string $lang = 'en';

    /**
     * Set Language
     * @param string $lang Optional Argument. Default is 'en'.
     * @return void
     */
    public function set(string $lang = 'en'): void
    {
        $lang = strtolower(trim($lang));

        if ($lang === '') {
            return;
        }

        // This value becomes a filename in load(), which require_once's it.
        // Anything looser than a language tag is a path traversal.
        if (!preg_match('/^[a-z]{2}(-[a-z]{2})?$/', $lang)) {
            throw new LocalException("Invalid Language Code [{$lang}]", 400);
        }

        $this->lang = $lang;
    }

    /**
     * Get Language
     * @return string
     */
    public function get(): string
    {
        return $this->lang;
    }

    /**
     * Set Path
     * @param string $path Sub Directory or Absolute Path
     * @return void
     * @throws LocalException
     */
    public function setPath(string $path): void
    {
        // Always resolved from the base, so a second call replaces the first.
        // Appending to $this->path meant repeated calls concatenated fragments
        // onto each other - and this is a container singleton, so that stuck.
        $resolved = is_dir($path)
            ? realpath($path)
            : realpath(LANG_PATH . DS . trim(str_replace(['/', '\\'], DS, $path), DS));

        if ($resolved === false || !is_dir($resolved)) {
            throw new LocalException("Invalid Local Path [{$path}]", 500);
        }

        $this->path = $resolved;
    }

    /**
     * Set or Load Path
     * @return void
     */
    public function load(): void
    {
        // Make Directory If Doesn't Exists
        Directory::make($this->path);

        // Get File Name
        $file = $this->path . DS . $this->get() . '.local.php';

        if (!File::exists($file)) {
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
                public static string \$sample = 'Hello World!';
            }
            HTML;

            // Create Language File
            File::write($content, $file);
        }
        // Return Language Path
        require_once $file;
    }
}
