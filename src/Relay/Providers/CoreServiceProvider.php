<?php
/**
 * Laika Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 */

declare(strict_types=1);

namespace Laika\Core\Relay\Providers;

use Laika\Core\Relay\RelayProvider;
use Laika\Core\Exceptions\Handler;
use Laika\Core\Helper\Config;
use Laika\Core\Helper\Cookie;
use Laika\Core\Http\Request;
use Laika\Core\Http\Redirect;
use Laika\Core\Http\Header;
use Laika\Core\Http\ChangeLog;
use Laika\Core\Helper\Client;
use Laika\Core\Helper\Directory;
use Laika\Core\Helper\File;
use Laika\Core\Helper\CSRF;
use Laika\Core\Helper\Url;
use Laika\Core\Generator\Unique;
use Laika\Core\Auth\Auth;
use Laika\Core\Helper\Date;
use Laika\Core\Helper\Image;
use Laika\Core\Helper\Local;
use Laika\Core\Helper\Meta;
use Laika\Core\Helper\Page;
use Laika\Core\Nav\Builder;
use Laika\Core\Helper\Sendmail;
use Laika\Core\Helper\Upload;
use Laika\Core\Helper\Vault;
use Laika\Core\IP\IP;
use Laika\Core\Regex\Regex;
use Laika\Core\Api\Api;
use Laika\Core\App\Infra;
use Laika\Core\Generator\Token;

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
        $this->registry->singleton('config', Config::class);
        $this->registry->singleton('cookie', Cookie::class);
        $this->registry->singleton('request', Request::class);
        $this->registry->singleton('redirect', Redirect::class);
        $this->registry->singleton('changelog', ChangeLog::class);
        $this->registry->singleton('header', Header::class);
        $this->registry->singleton('visitor', Client::class);
        $this->registry->singleton('directory', Directory::class);
        $this->registry->singleton('file', File::class);
        $this->registry->singleton('csrf', CSRF::class);
        $this->registry->singleton('url', Url::class);
        $this->registry->singleton('unique', Unique::class);
        $this->registry->singleton('auth', Auth::class);
        $this->registry->singleton('date', Date::class);
        $this->registry->singleton('image', Image::class);
        $this->registry->singleton('local', Local::class);
        $this->registry->singleton('meta', Meta::class);
        $this->registry->singleton('page', Page::class);
        $this->registry->singleton('nav', Builder::class);
        $this->registry->singleton('email', Sendmail::class);
        $this->registry->singleton('upload', Upload::class);
        $this->registry->singleton('vault', Vault::class);
        $this->registry->singleton('ip', IP::class);
        $this->registry->singleton('regex', Regex::class);
        $this->registry->singleton('api', Api::class);
        $this->registry->singleton('infra', Infra::class);
        $this->registry->singleton('token', Token::class);
    }

    public function boot(): void
    {
        // Register Error Handler
        Handler::register();
    }
}
