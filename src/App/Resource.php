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

use Laika\Queue\Abstracts\Job;
use Laika\Relay\RelayProvider;
use Laika\Core\Helper\Directory;
use Laika\Model\Contract\SchemaAbstract;
use Laika\Cli\Contracts\CommandInterface;
use Laika\Route\Contracts\FilterInterface;
use Laika\Core\Exceptions\ResourceException;
use Laika\Route\Contracts\PipelineInterface;

/**
 * Resource Registry.
 *
 * Maps a resource name (models, routes, filters...) to the directories that
 * provide it, and turns those directories into class names or file paths.
 *
 * Registration only records a definition — nothing touches the filesystem
 * until a resource is actually read, and every result is memoised. The class
 * is static on purpose: package bootstrappers register through it during
 * composer's `files` autoload, long before the Relay container is wired.
 *
 * Public method names must not collide with the static methods on Laika\Relay\Relay
 * (classes, bindings, swap, relayRoot, setRegistry, getRegistry, swapRegistry,
 * clearResolvedInstance). Relay resolves those itself instead of forwarding, so a
 * colliding name would silently do the wrong thing through Laika\Service\Resource.
 */
final class Resource
{
    /** @var string Resource name pattern */
    private const NAME_PATTERN = '/^[a-z][a-z0-9_]*$/i';

    /** @var string PSR-4 base namespace pattern */
    private const NAMESPACE_PATTERN = '/^\\\\?[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*\\\\?$/';

    /** @var array<string,string> Legacy resource names kept working */
    private const ALIASES = ['controller' => 'controllers'];

    /** @var array<string,ResourceDefinition> Registered definitions, keyed by identity */
    private static array $definitions = [];

    /** @var array<string,string[]> Resolved resources, keyed by name */
    private static array $resolved = [];

    /** @var array<string,string> Package composer.json files awaiting seeding */
    private static array $packages = [];

    /** @var bool Whether the declared definitions have been seeded */
    private static bool $booted = false;

    ############################################################################
    /*============================= EXTERNAL API =============================*/
    ############################################################################

    /**
     * Register Resource
     * @param string $name Resource Name. Example: models, filters...
     * @param string $path Resource Path
     * @param ?string $base_namespace Resource Class Base Namespace. Null registers file paths
     * @param ?string $contract Interface or Base Class Every Resource Class Must Satisfy
     * @return void
     * @throws ResourceException
     */
    public static function register(string $name, string $path, ?string $base_namespace = null, ?string $contract = null): void
    {
        self::define(new ResourceDefinition(
            self::normalizeName($name),
            self::normalizePath($path),
            self::normalizeNamespace($base_namespace),
            $contract,
            'runtime'
        ));
    }

    /**
     * Register a Prepared Definition
     * @param ResourceDefinition $definition
     * @return void
     */
    public static function define(ResourceDefinition $definition): void
    {
        $key = $definition->key();

        // Registering the same location twice is a no-op, never a duplicate
        if (isset(self::$definitions[$key])) {
            return;
        }

        self::$definitions[$key] = $definition;

        // A new location invalidates whatever was memoised for that name
        unset(self::$resolved[$definition->name]);
    }

    /**
     * Declare a Package's Resources From Its composer.json
     *
     * Reads `extra.laika.resources`, with paths relative to the package root.
     * Nothing is read from disk until a resource is actually used, so calling
     * this from a `files` autoload entry costs nothing.
     * @param string $composer_file Path to the package composer.json
     * @return void
     */
    public static function package(string $composer_file): void
    {
        $file = realpath($composer_file);

        if (!$file || isset(self::$packages[$file])) {
            return;
        }

        self::$packages[$file] = $file;

        // Late registration still takes effect
        if (self::$booted) {
            self::seedPackageFile($file);
        }
    }

    /**
     * Get Resources
     * @param ?string $name Resource Name. Default is null for every resource
     * @return array
     * @throws ResourceException
     */
    public static function getResources(?string $name = null): array
    {
        if ($name !== null) {
            return self::resolve($name);
        }

        $all = [];
        foreach (self::names() as $resource) {
            $all[$resource] = self::resolve($resource);
        }
        return $all;
    }

    /**
     * Get Resource Class Names, Validated
     * @param string $name Resource Name
     * @param ?string $contract Override the contract declared by the definition
     * @return string[]
     * @throws ResourceException
     */
    public static function getClasses(string $name, ?string $contract = null): array
    {
        $name = self::normalizeName($name);

        if (!self::isClassMap($name)) {
            throw ResourceException::notClassMap($name);
        }

        $classes = self::resolve($name);
        $contract = $contract ?? self::contract($name);

        foreach ($classes as $class) {
            if (!class_exists($class)) {
                throw ResourceException::classNotFound($class, $name);
            }
            if ($contract && !is_subclass_of($class, $contract)) {
                throw ResourceException::notInstanceOf($class, $contract);
            }
        }

        return $classes;
    }

    /**
     * Get Resource File Paths
     * @param string $name Resource Name
     * @return string[]
     * @throws ResourceException
     */
    public static function getFiles(string $name): array
    {
        return self::resolve($name);
    }

    /**
     * Get Every Registered Resource Name
     * @return string[]
     */
    public static function names(): array
    {
        self::boot();

        $names = [];
        foreach (self::$definitions as $definition) {
            $names[$definition->name] = true;
        }

        $names = array_keys($names);
        sort($names);
        return $names;
    }

    /**
     * Check a Resource Is Registered
     * @param string $name Resource Name
     * @return bool
     */
    public static function has(string $name): bool
    {
        return in_array(self::normalizeName($name), self::names(), true);
    }

    /**
     * Check a Resource Maps Files To Class Names
     *
     * False means it is a file resource — read it with getFiles().
     * @param string $name Resource Name
     * @return bool
     */
    public static function isClassMap(string $name): bool
    {
        foreach (self::definitions($name) as $definition) {
            if ($definition->isClassMap()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get Registered Definitions
     * @param ?string $name Resource Name. Default is null for every definition
     * @return ResourceDefinition[]
     */
    public static function definitions(?string $name = null): array
    {
        self::boot();

        if ($name === null) {
            return array_values(self::$definitions);
        }

        $name = self::normalizeName($name);
        return array_values(array_filter(
            self::$definitions,
            static fn(ResourceDefinition $d): bool => $d->name === $name
        ));
    }

    /**
     * Resolve a Single Definition
     *
     * Unlike getResources(), this reports what one location contributes rather
     * than the union for the resource name.
     * @param ResourceDefinition $definition
     * @return string[]
     */
    public static function entries(ResourceDefinition $definition): array
    {
        // Resolve the base the same way the scan does. A directory registered
        // before it existed keeps its raw path, which may not match what
        // realpath() returns once it is created.
        $base = realpath($definition->path) ?: $definition->path;

        $items = [];
        foreach (self::scan($definition->path) as $file) {
            $items[] = $definition->isClassMap() ? self::className($definition, $base, $file) : $file;
        }
        return $items;
    }

    /**
     * Default Compiled Manifest Location
     * @return string
     */
    public static function manifestPath(): string
    {
        // lf-storage, not lf-cache: lf-cache belongs to the template engine and
        // is wiped wholesale by `php laika app:sync`.
        return APP_PATH . DS . 'lf-storage' . DS . 'cache' . DS . 'resources.php';
    }

    /**
     * Resolve Everything Into a Plain Array
     * @return array
     * @throws ResourceException
     */
    public static function compile(): array
    {
        self::boot();

        $definitions = [];
        foreach (self::$definitions as $definition) {
            $definitions[] = $definition->toArray();
        }

        return [
            'definitions'   =>  $definitions,
            'resources'     =>  self::getResources()
        ];
    }

    /**
     * Write The Compiled Manifest
     * @param ?string $file Manifest path. Default is lf-storage/cache/resources.php
     * @return string Written file path
     * @throws ResourceException
     */
    public static function cache(?string $file = null): string
    {
        $file = $file ?? self::manifestPath();
        $data = self::compile();
        $directory = dirname($file);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $export = var_export($data, true);
        file_put_contents(
            $file,
            "<?php\n\n// Generated by `php laika app:cache` — do not edit.\n\nreturn {$export};\n"
        );

        return $file;
    }

    /**
     * Load The Compiled Manifest
     * @param ?string $file Manifest path. Default is lf-storage/cache/resources.php
     * @return bool Whether a usable manifest was loaded
     */
    public static function loadManifest(?string $file = null): bool
    {
        $file = $file ?? self::manifestPath();

        if (!is_file($file)) {
            return false;
        }

        $data = require $file;

        if (!is_array($data) || !isset($data['definitions'], $data['resources'])) {
            return false;
        }

        self::$booted = true;

        foreach ($data['definitions'] as $definition) {
            self::define(ResourceDefinition::fromArray($definition));
        }

        // Set after define(), which invalidates the memo for each name it adds
        self::$resolved = $data['resources'];

        return true;
    }

    /**
     * Empty The Registry And Suppress Declared Resources
     *
     * Testing seam. Unlike flush(), the registry is left marked as booted, so
     * defaults and every composer.json declaration stay out of the
     * way and a test sees only what it registers itself.
     * @return void
     */
    public static function isolate(): void
    {
        self::$definitions = [];
        self::$resolved = [];
        self::$packages = [];
        self::$booted = true;
    }

    /**
     * Forget Registered Resources
     * @param ?string $name Resource Name. Default is null to reset the registry
     * @return void
     */
    public static function flush(?string $name = null): void
    {
        if ($name === null) {
            self::$definitions = [];
            self::$resolved = [];
            self::$packages = [];
            self::$booted = false;
            return;
        }

        $name = self::normalizeName($name);
        foreach (self::$definitions as $key => $definition) {
            if ($definition->name === $name) {
                unset(self::$definitions[$key]);
            }
        }
        unset(self::$resolved[$name]);
    }

    ############################################################################
    /*============================= INTERNAL API =============================*/
    ############################################################################

    /**
     * Resolve a Resource, Memoised
     * @param string $name Resource Name
     * @return string[]
     * @throws ResourceException
     */
    private static function resolve(string $name): array
    {
        $name = self::normalizeName($name);

        if (isset(self::$resolved[$name])) {
            return self::$resolved[$name];
        }

        self::boot();

        $items = [];
        foreach (self::$definitions as $definition) {
            if ($definition->name === $name) {
                $items = array_merge($items, self::entries($definition));
            }
        }

        return self::$resolved[$name] = array_values(array_unique($items));
    }

    /**
     * Scan a Directory for PHP Files
     * @param string $dir
     * @return string[]
     */
    private static function scan(string $dir): array
    {
        // A declared-but-absent directory is normal: apps delete what they don't use
        if (!is_dir($dir)) {
            return [];
        }

        $files = (new Directory())->scan($dir, false, 'php');

        // Filesystem iteration order is not guaranteed — keep output stable
        sort($files);
        return $files;
    }

    /**
     * Build a Fully Qualified Class Name From a File Path
     * @param ResourceDefinition $definition
     * @param string $base Directory the file was found under
     * @param string $file
     * @return string
     */
    private static function className(ResourceDefinition $definition, string $base, string $file): string
    {
        // substr, not str_replace: the base path may appear again inside the file name
        $relative = trim(substr($file, strlen($base)), '/\\');
        $relative = preg_replace('/\.php$/i', '', $relative);

        // Both separators normalise to the namespace separator
        $parts = preg_split('#[/\\\\]+#', $relative) ?: [];

        return $definition->namespace . '\\' . implode('\\', $parts);
    }

    /**
     * Get The Contract Declared For a Resource
     * @param string $name Resource Name
     * @return ?string
     */
    private static function contract(string $name): ?string
    {
        foreach (self::definitions($name) as $definition) {
            if ($definition->contract) {
                return $definition->contract;
            }
        }
        return null;
    }

    /**
     * Validate and Normalize a Resource Name
     * @param string $name
     * @return string
     * @throws ResourceException
     */
    private static function normalizeName(string $name): string
    {
        if (!preg_match(self::NAME_PATTERN, $name)) {
            throw ResourceException::invalidName($name);
        }

        $name = strtolower($name);
        return self::ALIASES[$name] ?? $name;
    }

    /**
     * Normalize a Resource Path
     * @param string $path
     * @return string
     */
    private static function normalizePath(string $path): string
    {
        // A missing directory is recorded, not fatal: register() runs during
        // autoload, before the error handler exists, so throwing here produces
        // an uncatchable fatal. `php laika resource:list` reports it instead.
        return realpath($path) ?: rtrim($path, '/\\');
    }

    /**
     * Validate and Normalize a Base Namespace
     * @param ?string $namespace
     * @return ?string
     * @throws ResourceException
     */
    private static function normalizeNamespace(?string $namespace): ?string
    {
        if ($namespace === null || $namespace === '') {
            return null;
        }

        if (!preg_match(self::NAMESPACE_PATTERN, $namespace)) {
            throw ResourceException::invalidNamespace($namespace);
        }

        return trim($namespace, '\\');
    }

    /**
     * Seed Declared Definitions Once
     *
     * Precedence, lowest first: framework defaults, package composer `extra`,
     * the root composer.json. Anything registered at runtime is additive.
     * @return void
     */
    private static function boot(): void
    {
        if (self::$booted) {
            return;
        }

        // Set first: seeding reads config, which must not recurse back in here
        self::$booted = true;

        // A compiled manifest short-circuits discovery entirely — no installed.json,
        // no config read, no directory walking
        if (!DEBUG && self::loadManifest()) {
            return;
        }

        // The application declares resources exactly like a package does, in the
        // root composer.json. Read from disk rather than installed.json, which
        // never carries the root package and would lag behind edits anyway.
        $app = self::manifest(APP_PATH . DS . 'composer.json');

        // A name the application declares replaces its default outright, rather
        // than adding a second location for the same resource
        self::seed(array_diff_key(self::defaults(), $app), 'default');

        foreach (self::$packages as $file) {
            self::seedPackageFile($file);
        }

        self::seedInstalledPackages();
        self::seed($app, 'app');
    }

    /**
     * Framework Defaults For an Application That Declares Nothing
     * @return array
     */
    private static function defaults(): array
    {
        return [
            'models'        =>  [
                'path' => 'lf-app/Model',
                'namespace' => 'App\\Model'
            ],
            'schemas'       =>  [
                'path' => 'lf-app/Schema',
                'namespace' => 'App\\Schema',
                'contract' => SchemaAbstract::class
            ],
            'controllers'   =>  [
                'path' => 'lf-app/Controller',
                'namespace' => 'App\\Controller'
            ],
            'jobs'          =>  [
                'path' => 'lf-app/Job',
                'namespace' => 'App\\Job',
                'contract' => Job::class
            ],
            'pipelines'     =>  [
                'path' => 'lf-app/Pipeline',
                'namespace' => 'App\\Pipeline',
                'contract' => PipelineInterface::class
            ],
            'filters'       =>  [
                'path' => 'lf-app/Filter',
                'namespace' => 'App\\Filter',
                'contract' => FilterInterface::class
            ],
            'commands'      =>  [
                'path' => 'lf-app/Command',
                'namespace' => 'App\\Command',
                'contract' => CommandInterface::class
            ],
            // Relay providers, not the bound accessors Infra::getRelayClasses() reports
            'relays'        =>  [
                'path' => 'lf-app/Relay',
                'namespace' => 'App\\Relay',
                'contract' => RelayProvider::class
            ],
            'routes'        =>  ['path' => 'lf-routes'],
            'hooks'         =>  ['path' => 'lf-hooks']
        ];
    }

    /**
     * Read extra.laika.resources From a composer.json
     * @param string $file
     * @return array
     */
    private static function manifest(string $file): array
    {
        if (!is_file($file)) {
            return [];
        }

        $json = json_decode((string) file_get_contents($file), true);
        return (array) ($json['extra']['laika']['resources'] ?? []);
    }

    /**
     * Register The Resources Declared By a Package composer.json
     * @param string $file
     * @return void
     */
    private static function seedPackageFile(string $file): void
    {
        $resources = self::manifest($file);

        if (!$resources) {
            return;
        }

        $json = json_decode((string) file_get_contents($file), true);
        self::seed($resources, (string) ($json['name'] ?? 'package'), dirname($file));
    }

    /**
     * Read extra.laika.resources From Installed Packages
     *
     * Covers third-party packages that declare resources without shipping a
     * bootstrapper of their own.
     * @return void
     */
    private static function seedInstalledPackages(): void
    {
        $file = APP_PATH . DS . 'vendor' . DS . 'composer' . DS . 'installed.json';

        if (!is_file($file)) {
            return;
        }

        $installed = json_decode((string) file_get_contents($file), true);
        if (!is_array($installed)) {
            return;
        }

        $vendor = APP_PATH . DS . 'vendor' . DS . 'composer';

        foreach ($installed['packages'] ?? $installed as $package) {
            $resources = (array) ($package['extra']['laika']['resources'] ?? []);
            if (!$resources) {
                continue;
            }

            // install-path is relative to vendor/composer
            $base = $vendor . DS . ($package['install-path'] ?? '');
            self::seed($resources, (string) ($package['name'] ?? 'package'), $base);
        }
    }

    /**
     * Register a Map of Declared Resources
     * @param array $resources name => ['path' => ..., 'namespace' => ..., 'contract' => ...]
     * @param string $source
     * @param ?string $base Directory relative paths resolve against. Default is APP_PATH
     * @return void
     * @throws ResourceException
     */
    private static function seed(array $resources, string $source, ?string $base = null): void
    {
        $base = $base ?? APP_PATH;

        foreach ($resources as $name => $declaration) {
            // A bare string is shorthand for ['path' => '...']
            $declaration = is_array($declaration) ? $declaration : ['path' => $declaration];
            $path = (string) ($declaration['path'] ?? '');

            if ($path === '') {
                continue;
            }

            if (!self::isAbsolute($path)) {
                $path = $base . DS . $path;
            }

            self::define(new ResourceDefinition(
                self::normalizeName((string) $name),
                self::normalizePath($path),
                self::normalizeNamespace($declaration['namespace'] ?? null),
                $declaration['contract'] ?? null,
                $source
            ));
        }
    }

    /**
     * Check a Path Is Absolute
     * @param string $path
     * @return bool
     */
    private static function isAbsolute(string $path): bool
    {
        return (bool) preg_match('#^(?:[a-zA-Z]:[\\\\/]|[\\\\/])#', $path);
    }
}
