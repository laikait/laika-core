<?php
/**
 * Laika Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 */

declare(strict_types=1);

namespace Laika\Core\Relay\Providers;

use Laika\Core\System\MemoryManager;
use Laika\Core\Relay\RelayProvider;
use Laika\Core\Exceptions\Handler;

/**
 * CoreServiceProvider — Registers all built-in Laika core services.
 *
 * This provider is registered automatically by the framework during bootstrap.
 * You do not need to add it to your config/app.php providers array.
 *
 * Services registered:
 *   - config   → Laika\Core\Helper\Config
 *   - session  → Laika\Core\Session\Session
 *   - auth     → Laika\Core\Auth\Auth
 *   - date     → Laika\Core\Helper\Date
 *   - csrf     → Laika\Core\Csrf\Csrf
 */
class CoreServiceProvider extends RelayProvider
{
    public function register(): void
    {
        // Register Each Core Service As A Singleton.
        $this->registry->singleton('config', \Laika\Core\Helper\Config::class);
        $this->registry->singleton('cookie', \Laika\Core\Helper\Cookie::class);
        $this->registry->singleton('request', \Laika\Core\Http\Request::class);
        $this->registry->singleton('redirect', \Laika\Core\Http\Redirect::class);
        $this->registry->singleton('changelog', \Laika\Core\Http\ChangeLog::class);
        $this->registry->singleton('header', \Laika\Core\Http\Header::class);
        $this->registry->singleton('visitor', \Laika\Core\Helper\Client::class);
        $this->registry->singleton('directory', \Laika\Core\Helper\Directory::class);
        $this->registry->singleton('file', \Laika\Core\Helper\File::class);
        $this->registry->singleton('csrf', \Laika\Core\Helper\CSRF::class);
        $this->registry->singleton('url', \Laika\Core\Helper\Url::class);
        $this->registry->singleton('unique', \Laika\Core\Generator\Unique::class);
        $this->registry->singleton('auth', \Laika\Core\Auth\Auth::class);
        $this->registry->singleton('date', \Laika\Core\Helper\Date::class);
        $this->registry->singleton('image', \Laika\Core\Helper\Image::class);
        $this->registry->singleton('local', \Laika\Core\Helper\Local::class);
        $this->registry->singleton('meta', \Laika\Core\Helper\Meta::class);
        $this->registry->singleton('page', \Laika\Core\Helper\Page::class);
        $this->registry->singleton('nav', \Laika\Core\Nav\Builder::class);
        $this->registry->singleton('email', \Laika\Core\Helper\Sendmail::class);
        $this->registry->singleton('upload', \Laika\Core\Helper\Upload::class);
        $this->registry->singleton('vault', \Laika\Core\Helper\Vault::class);
        $this->registry->singleton('ip', \Laika\Core\IP\IP::class);
        $this->registry->singleton('regex', \Laika\Core\Regex\Regex::class);
        $this->registry->singleton('api', \Laika\Core\Api\Api::class);
        $this->registry->singleton('infra', \Laika\Core\App\Infra::class);
        $this->registry->singleton('token', \Laika\Core\Generator\Token::class);
    }

    public function boot(): void
    {
        //
    }
}
