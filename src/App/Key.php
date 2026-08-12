<?php
/**
 * Laika Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Laika\Core\App;

use Laika\Service\File;
use Laika\Service\Directory;
use Laika\Core\Exceptions\AppKeyException;

// Application Key
class Key
{
    /** @var string File Path */
    protected string $file;

    /** @var ?string Key */
    private static ?string $key = null;

    public function __construct()
    {
        $this->file = APP_PATH . DS . 'lf-storage' . DS . 'keys' . DS . 'app.key';
        Directory::make(dirname($this->file));
    }

    ##########################################################################################
    /*==================================== EXTERNAL API ====================================*/
    ##########################################################################################
    /**
     * Create Application Key
     * @param int $byte Default is 32
     * @return void
     */
    public function generate(int $byte = 32): void
    {
        $base64 = $this->generateBase64($byte);
        try {
            if (!File::exists($this->file)) File::touch($this->file);
            File::write($base64, $this->file);
            setPermission($this->file, 0600);
        } catch (\Throwable $th) {
            throw new AppKeyException("Key generate failed! {$th->getMessage()}", previous:$th);
        }
    }

    /**
     * Get Application Key
     * @return string
     * @throws AppKeyException
     */
    public function get(): string
    {
        if (self::$key !== null) return self::$key;

        if (!File::exists($this->file)) {
            throw new AppKeyException("App key not found! Please run `php laika secret:generate`");
        }
        if (!File::readable($this->file)) {
            throw new AppKeyException("Unable to read key. Please run `php laika secret:fix`");
        }
        $encoded = File::read($this->file);
        if (!is_string($encoded)) {
            throw new AppKeyException("Invalid app key. Please run `php laika secret:fix`");
        }

        // Validate Key
        $decoded = base64_decode($encoded, true);
        if ($decoded == false) {
            throw new AppKeyException("Invalid app key. Please run `php laika secret:fix`");
        }

        try {
            [$init, self::$key] = explode('.', $decoded, 2);
        } catch (\Throwable $th) {
            throw new AppKeyException("Invalid app key. Please run `php laika secret:fix`");
        }

        return self::$key;
    }

    /**
     * Validdate Key
     * @param int $byte Default is 32
     * @return bool
     * @throws AppKeyException
     */
    public function validate(int $byte = 32): bool
    {
        try {
            $key = $this->get();
            if (strlen($key) != $byte * 2) {
                throw new AppKeyException("Invalid app key byte. Please run `php laika secret:fix --byte=32`");
            }
        } catch (\Throwable $th) {
            throw new AppKeyException($th->getMessage(), previous:$th);
        }
        return true;
    }

    /**
     * Fix Key
     * @param int $byte Default is 32
     * @return void
     * @throws AppKeyException
     */
    public function fix(int $byte = 32): void
    {
        try {
            $isValid = $this->validate($byte);
        } catch (\Throwable $th) {
            $isValid = false;
        }

        if ($isValid) return;

        try {
            $base64 = $this->generateBase64($byte);
            setPermission($this->file, 0640);
            File::write($base64, $this->file);
            setPermission($this->file, 0600);
        } catch (\Throwable $th) {
            throw new AppKeyException("Unable to fix app key! {$th->getMessage()}", previous:$th);
        }
    }

    ##########################################################################################
    /*==================================== INTERNAL API ====================================*/
    ##########################################################################################
    /**
     * Make Base 64 Key
     * @param int $byte Default is 32
     * @return string
     */
    private function generateBase64(int $byte = 32): string
    {
        return base64_encode(bin2hex(random_bytes(16)) . '.' . bin2hex(random_bytes($byte)));
    }
}
