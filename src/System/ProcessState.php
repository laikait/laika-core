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

namespace Laika\Core\System;

use Laika\Core\Helper\Config;
use Laika\Core\Http\ProxyTrust;
use Laika\Core\Model\OptionModel;
use Laika\Model\Log;
use Laika\Route\Asset;
use Laika\Route\Dispatcher;
use Laika\Service\Cache;
use Laika\Service\Request;
use Laika\Service\Visitor;
use Laika\Shield\Support\RequestHelper;
use Throwable;

/**
 * The One Place Per-Process State is Reset
 *
 * The framework memoises a lot for the life of a process: every config file,
 * every option, the asset rules, trusted proxies, the request's headers, the
 * visitor's IP. Under FPM a process is one request, so none of it is ever
 * stale. A process that serves many -- the queue worker, which without pcntl
 * runs every job in one long-lived process -- sees the first job's state
 * forever unless something clears it.
 *
 * Several of these already had a reset method. None was ever called. They are
 * gathered here so a new memo has exactly one place to be registered, and the
 * host has exactly one thing to call.
 */
final class ProcessState
{
    /**
     * Reset Everything Held For The Life of The Process
     *
     * Each reset is isolated: a failure in one -- a relay that is not bound in
     * this context, say -- does not stop the rest from running.
     *
     * @return string[] Failures, as "name: message". Empty when every reset ran.
     */
    public static function reset(): array
    {
        $failures = [];

        foreach (self::resets() as $name => $reset) {
            try {
                $reset();
            } catch (Throwable $e) {
                $failures[] = "{$name}: {$e->getMessage()}";
            }
        }

        return $failures;
    }

    /**
     * @return array<string,callable>
     */
    private static function resets(): array
    {
        return [
            'cache'          => static fn() => Cache::resetProcess(),
            'config'         => static fn() => Config::flush(),
            'options'        => static fn() => OptionModel::flush(),
            'asset rules'    => static fn() => Asset::flushRules(),
            'cors headers'   => static fn() => Dispatcher::flushHeaders(),
            'trusted proxies' => static fn() => ProxyTrust::flush(),
            'request headers' => static fn() => Request::flushHeaders(),
            'visitor'        => static fn() => Visitor::refresh(),
            'shield input'   => static fn() => RequestHelper::flush(),
            // Last, and not optional: add() appends forever and would otherwise
            // grow until the worker's memory guard stops it
            'query log'      => static fn() => Log::flush(),
        ];
    }
}
