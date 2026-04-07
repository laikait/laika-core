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

use Laika\Core\Relay\RelayRegistry;
use Laika\Core\Relay\Relay;

// Get Relay Registry Object
$registry = new RelayRegistry();

/*==================================================================================*/
/*================================= DEFAULT RELAYS =================================*/
/*==================================================================================*/
// Singletons
$registry->singleton('config', Laika\Core\Helper\Config::class);
$registry->singleton('session', Laika\Session\Session::class);
$registry->singleton('cookie', Laika\Core\Helper\Cookie::class);
$registry->singleton('header', Laika\Core\Http\Header::class);
$registry->singleton('request', Laika\Core\Http\Request::class);
$registry->singleton('redirect', Laika\Core\Http\Redirect::class);
$registry->singleton('changelog', Laika\Core\Http\ChangeLog::class);
$registry->singleton('visitor', Laika\Core\Helper\Client::class);
$registry->singleton('directory', Laika\Core\Helper\Directory::class);
$registry->singleton('file', Laika\Core\Helper\file::class);
$registry->singleton('csrf', Laika\Core\Helper\Csrf::class);
$registry->singleton('url', Laika\Core\Helper\Url::class);
$registry->singleton('unique', Laika\Core\Generator\Unique::class);
$registry->singleton('auth', Laika\Core\Auth\Auth::class);
$registry->singleton('date', Laika\Core\Helper\Date::class);
$registry->singleton('image', Laika\Core\Helper\Image::class);
$registry->singleton('local', Laika\Core\Helper\Local::class);
$registry->singleton('meta', Laika\Core\Helper\Meta::class);
$registry->singleton('page', Laika\Core\Helper\Page::class);
$registry->singleton('nav', Laika\Core\Nav\Builder::class);
$registry->singleton('email', Laika\Core\Helper\Sendmail::class);
$registry->singleton('upload', Laika\Core\Helper\Upload::class);
$registry->singleton('vault', Laika\Core\Helper\Vault::class);
$registry->singleton('ip', Laika\Core\IP\IP::class);
$registry->singleton('regex', Laika\Core\Regex\Regex::class);
$registry->singleton('api', Laika\Core\Api\Api::class);
$registry->singleton('infra', Laika\Core\App\Infra::class);

// Binds
$registry->bind('token', Laika\Core\Generator\Token::class);



/**
 * Require Custom Relays
 */
try { require_once APP_PATH . '/lf-inc/relays.php'; } catch (\Throwable $th) {}

/**
 * Register All Relays
 */
Relay::setRegistry($registry);

/**
 * Delete $registry Object
 */
unset($registry);