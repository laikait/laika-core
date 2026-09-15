# Utilities

## Date

**Relay:** `Laika\Service\Date` (`date`). **Class:** `Laika\Core\Helper\Date`.

An immutable wrapper around `DateTime`. Every method that changes the date returns a **new** instance, so the shared relay instance is never modified.

```php
use Laika\Service\Date;

Date::now()->format('d M Y');                    // "14 Sep 2026"
Date::parse('next Monday')->format('l');         // "Monday"
Date::fromFormat('d/m/Y', '21/04/2025', 'Y-m-d')->format(); // "2025-04-21"
Date::parse('-3 days')->humanDiff();             // "3 days ago"
Date::now()->modify('+2 hours')->setTimezone('Asia/Dhaka')->toIso8601();
```

| Method | Returns |
|---|---|
| `now(): static` | The current time |
| `parse(string $time, ?string $timezone = null): static` | Anything `DateTime` understands: `"2025-04-21"`, `"next Friday"`, `"-1 week"` |
| `fromFormat(string $format, string $time, ?string $outputFormat = null, ?string $timezone = null): static` | Parses an exact format, falling back to free-form parsing. Throws `InvalidArgumentException`. |
| `format(?string $format = null): string` | Formatted with `$format` or the default `Y-m-d H:i:s` |
| `setFormat(string $format): static` | A copy with another default format |
| `setTimestamp(int $timestamp): static` / `getTimestamp(): int` | Unix time |
| `setTimezone(string $timezone): static` | A copy in another timezone |
| `toLocal(): static` | Back in the instance's own timezone |
| `modify(string $modifier): static` | `"+1 day"`, `"-2 hours"`, `"last day of this month"` |
| `getOffset(): string` | `"+06:00"` |
| `toIso8601(bool $extended = true): string` | `2025-04-21T10:30:00+06:00`, or the compact form |
| `toArray(): array` | `year`, `month`, `day`, `hour`, `minute`, `second`, `timezone` |
| `diff(Date $other): \DateInterval` | The interval |
| `humanDiff(?Date $other = null): string` | `"3 days ago"`, `"in 2 hours"`, `"just now"` |
| `humanDiffShort(?Date $other = null): string` | `"3d"`, `"2h"`, `"+5m"`, `"now"` |
| `getDateTime(): DateTime` | The underlying object |
| `setAppTimezone(string $timezone): void` | Sets PHP's default timezone, and this instance's |
| `getAppTimezone(): string` | PHP's default timezone |
| `__toString(): string` | `format()` |

The relay instance is created in UTC. At boot, `CoreProviders` calls `Date::setAppTimezone('UTC')`, so **PHP's default timezone is UTC** until you change it:

```php
// lf-hooks/timezone.php
use Laika\Service\Date;

Date::setAppTimezone(config('app', 'timezone', 'UTC'));
```

## Math

**Relay:** `Laika\Service\Math` (`math`). **Class:** `Laika\Core\Helper\Math`. Requires `ext-bcmath`.

Arbitrary-precision arithmetic on strings, for money and anything else floats get wrong.

```php
use Laika\Service\Math;

Math::add('0.1', '0.2');                  // "0.3000" (default scale 4)
Math::scale(2)->mul('19.99', 3);          // "59.97"
Math::round('2.345', 2);                  // "2.35"
Math::trim(Math::div(10, 4));             // "2.5"
Math::percent(45, 60);                    // "75.0000"
```

Every argument accepts `int`, `float` or a numeric `string`, and results are strings. Most methods take an optional `$scale` (decimal places); otherwise the instance scale applies, which is **4** by default. `scale(int $scale): static` returns a **copy** with another scale, leaving the shared instance alone.

| Method | Returns |
|---|---|
| `add`, `sub`, `mul`, `div`, `mod($a, $b, ?int $scale = null): string` | Arithmetic. `div` and `mod` throw `DivisionByZeroError` for a zero divisor. |
| `pow($number, $exponent, ?int $scale = null)` / `sqrt($number, ?int $scale = null)` / `powmod($number, $exponent, $modulus, ?int $scale = null): string` | Powers and roots. `sqrt` of a negative number throws; `powmod` floors the base. |
| `compare($a, $b, ?int $scale = null): int` | -1, 0 or 1 |
| `abs($a, ?int $scale = null)` / `negate($a): string` | Sign |
| `floor($a)` / `ceil($a): string` | Whole numbers |
| `round($a, int\|string $precision = 0): string` | Rounds half away from zero |
| `percent($value, $total, ?int $scale = null): string` | `$value` as a percentage of `$total` |
| `percentOf($percent, $value, ?int $scale = null): string` | `$percent`% of `$value` |
| `max($a, $b)` / `min($a, $b): string` | The larger or smaller input, unchanged |
| `sum(array $values, ?int $scale = null)` / `avg(array $values, ?int $scale = null): string` | Aggregates. `avg([])` throws. |
| `isZero`, `isPositive`, `isNegative($a): bool` | Sign tests, precise to 50 decimal places |
| `isEqual`, `isGt`, `isLt`, `isGte`, `isLte($a, $b): bool` | Comparisons at the instance scale |
| `trim($a): string` | Drops trailing zeros: `"2.5000"` → `"2.5"` |

> **Note:** results are **truncated** to the scale, not rounded, which is how bcmath works. `Math::div(2, 3)` is `"0.6666"`. Use `round()` when you need rounding.

## Cron

**Class:** `Laika\Core\Helper\Cron`. Linux and macOS only.

Manages a block of jobs in a user's crontab, between `# [LAIKA-CRON-START]` and `# [LAIKA-CRON-END]` markers. Lines outside the block are never touched.

```php
use Laika\Core\Helper\Cron;

$cron = new Cron();                       // current user; new Cron('www-data') needs rights to that crontab
$cron->everyMinute('php /var/www/app/laika queue:work --once', 'queue')
     ->daily('php /var/www/app/laika app:clear', '03:30', 'nightly-clear')
     ->install();                         // writes or replaces the Laika block
```

| Method | Does |
|---|---|
| `add(string $expression, string $command, ?string $label = null): static` | Any cron expression. A line break in any field throws. |
| `everyMinute` / `every5Minutes` / `every10Minutes` / `every15Minutes` / `every30Minutes` / `hourly(string $command, ?string $label = null): static` | Fixed schedules |
| `daily(string $command, string $time = '00:00', ?string $label = null): static` | At `HH:MM` |
| `weekly(string $command, int $dayOfWeek = 0, string $time = '00:00', ?string $label = null): static` | 0 = Sunday |
| `monthly(string $command, int $dayOfMonth = 1, string $time = '00:00', ?string $label = null): static` | |
| `yearly(string $command, int $month = 1, int $day = 1, string $time = '00:00', ?string $label = null): static` | |
| `jobs(): array` / `pop(string $label): static` / `flush(): static` | The pending list |
| `render(): string` | The block that `install()` would write |
| `install(): bool` | Writes the pending jobs, replacing the existing Laika block |
| `uninstall(): bool` | Removes the Laika block |
| `installed(): array` | Jobs currently in the Laika block: `expression`, `command`, `label` |
| `isSupported(): bool` | Linux or macOS |

`install()`, `uninstall()` and `installed()` throw `RuntimeException` on other systems, and need `shell_exec()` and `system()` to be enabled.

## Shell Commands

**Classes:** `Laika\Core\System\Command\Runner`, `Result`, `AsyncJob`, `ProcessPool`.

```php
use Laika\Core\System\Command\Runner;

$result = Runner::make()->timeout(30)->cwd(APP_PATH)->run(['git', 'rev-parse', 'HEAD']);

if ($result->success()) {
    echo $result->output;
}

Runner::make()->onOutput(fn (string $chunk, string $stream) => print $chunk)
              ->run(['composer', 'install', '--no-dev']);
```

| `Runner` method | Does |
|---|---|
| `make(): self` | A new runner |
| `timeout(int $seconds): self` | Terminates the process after this long. Default 60. |
| `cwd(string $path): self` / `env(array $env): self` | Working directory and environment |
| `onOutput(callable $callback): self` | Streams output as it arrives: `fn(string $chunk, string $stream)`, where the stream is `out` or `err` |
| `async(bool $async = true): self` | Runs in the background and returns an `AsyncJob` |
| `run(string\|array $command): Result\|AsyncJob` | Runs the command |

- **Arguments:** an array is escaped argument by argument (`escapeshellarg`). A string goes through `escapeshellcmd()`, which escapes shell operators, so pipes, redirects and `&&` **don't work** in string commands.
- **`Result`** has read-only properties `command`, `output`, `error`, `exitCode`, `timedOut` and `pid`, and `success()`, which means exit code 0 and no timeout.
- **`AsyncJob`** has `pid()`, `isRunning()`, `stop(int $signal = SIGTERM)` and `status()`. Its output is discarded.
- **`ProcessPool`:** `(new ProcessPool())->add($cmd)->add($cmd2)->run(concurrency: 4)` runs commands in the background, at most `$concurrency` at a time, and returns each job's `status()`.

> **Note:** asynchronous runs don't work on Windows in 5.1.x. The runner has no PID there and passes `null` to `AsyncJob`, whose constructor requires an `int`, so it throws a `TypeError`. On Linux and macOS, `isRunning()` and `stop()` need `ext-posix`.

These classes use `proc_open()` and `exec()`, which hardened PHP-FPM pools often disable. Run them from the CLI.

## PhpMetadataParser

**Relay:** `Laika\Service\PhpMetadataParser` (`php.metadata.parser`). **Class:** `Laika\Core\Helper\PhpMetadataParser` (static).

`parse(string $file): array` reads `Key: Value` lines from the first docblock of a PHP file. It's used to describe modules and themes:

```php
/**
 * Name: Blog Module
 * Version: 1.0.0
 * Author: Laika IT
 */
```

That docblock yields `['name' => 'Blog Module', 'version' => '1.0.0', 'author' => 'Laika IT']`. Keys are lowercased, with spaces turned into `-`. An unreadable file throws `InvalidArgumentException`.

## Queue

**Class:** `Laika\Core\Worker\Queue` (static).

Resolves laika-queue's driver and failed-job store from `lf-config/queue.php`. It's used by the `queue:*` CLI commands, and is useful when you push jobs from application code.

| Method | Returns |
|---|---|
| `driver(string $default = 'json'): QueueDriverInterface` | `redis`, `json` or `database`, per `queue.driver`. Database drivers use `queue.connection`. |
| `failedProvider(string $default = 'database'): FailedJobProviderInterface` | Per `queue.failed_driver`: `database` when the driver is `database`, otherwise `json` |

The Redis driver reads its connection from `lf-config/redis.php`, with the key prefix `{redis.prefix}:queue`. See the [laika-queue README](https://github.com/laikait/laika-queue) for jobs and workers.
