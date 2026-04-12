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

use Laika\Core\Relay\Relays\Directory;
use Laika\Core\Relay\Relays\File;
use RuntimeException;

class Local
{
    /** @var string Local Path */
    private string $path = APP_PATH . '/lf-lang';

    /** @var string Local Name */
    private string $lang = 'en';

    /**
     * Set Language
     * @param string $lang Optional Argument. Default is 'en'.
     * @return void
     */
    public function set(string $lang = 'en'): void
    {
        $this->lang = strtolower(trim($lang ?: $this->lang));
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
     * @throws RuntimeException
     */
    public function setPath(string $path): void
    {
        if (is_dir($path)) {
            $this->path = realpath($path);
        } else {
            $this->path .= '/' . trim($path, '/.\\');
        }

        if (!is_dir($this->path)) {
            throw new RuntimeException("Invalid Local Path [{$this->path}]");
        }
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
        $file = $this->path . '/' . $this->get() . '.local.php';

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
            }
            HTML;

            // Create Language File
            File::write($content, $file);
        }
        // Return Language Path
        require_once $file;
    }
}
