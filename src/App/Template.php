<?php

/**
 * Laika PHP Micro Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika PHP Micro Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Laika\Core\App;

use Twig\TwigFilter;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader as Engine;
use Laika\Core\Exceptions\PathException;
use Laika\Service\{Directory, Visitor, Request, Local, File, Page, Url, Context};

class Template
{
    /** @var string[] Template File Extensions Twig May Load */
    protected const EXTENSIONS = ['twig', 'html'];

    /** @var Environment Twig Environment */
    protected Environment $twig;

    /** @var Engine Twig Filesystem Loader */
    protected Engine $loader;

    /** @var array $vars */
    protected array $vars = [];

    /** @var string $templateDirectory Template Directory */
    protected string $templateDirectory;

    /** @var string $cacheDirectory Template Cache Directory */
    protected string $cacheDirectory;

    /** @var string[] $extraPaths Additional Loader Paths Registered By The Caller */
    protected array $extraPaths = [];

    /**
     * File Extension
     * @var string
     */
    protected string $extension = 'twig';

    public function __construct()
    {
        // Ensure Template & Cache Paths
        $this->ensureTemplatePath();
        $this->ensureCachePath();

        // Run Template Engine
        $this->loader = new Engine($this->templateDirectory);
        $this->twig = new Environment($this->loader, [
            'debug' =>  DEBUG,
            'cache' =>  $this->cacheDirectory
        ]);

        // Twig's debug option on its own does not define dump(). The extension does.
        if (DEBUG) {
            $this->twig->addExtension(new DebugExtension());
        }

        // Assign Template Default Filters
        $this->defaultFilters();

        // Load Template Function Files
        $this->loadFunctions();
    }

    /**
     * Set File Extension
     * @param string $extension File Extension
     * @return void
     * @throws PathException
     * @deprecated Use Template::html() or Template::twig() Instead
     */
    public function extension(string $extension): void
    {
        trigger_error(
            'Template::extension() is deprecated. Use Template::html() or Template::twig() instead.',
            E_USER_DEPRECATED
        );

        $extension = strtolower(ltrim($extension, '.'));

        // An unlisted extension would let the loader pull any file in as a template
        if (!in_array($extension, self::EXTENSIONS, true)) {
            $allowed = implode(', ', self::EXTENSIONS);
            throw new PathException("Invalid template extension: [{$extension}]. Allowed: {$allowed}.");
        }

        $this->extension = $extension;
        return;
    }

    /**
     * Set HTML Extension
     * @return static
     */
    public function html(): static
    {
        $this->extension = 'html';
        return $this;
    }

    /**
     * Set Twig Extension. Reverts Template::html()
     * @return static
     */
    public function twig(): static
    {
        $this->extension = 'twig';
        return $this;
    }

    /**
     * Render View
     *
     * The name carries the directory: 'admin/bootstrap/home' renders
     * template/admin/bootstrap/home.twig, cached under
     * cache/template/admin/bootstrap. The loader is re-pointed per render, so
     * one instance may render views from several sub directories in turn.
     * @param string $name View Name. Always Slash Separated: a Twig name is not a file path
     * @return string Rendered View
     * @throws PathException
     */
    public function view(string $name): string
    {
        [$subdir, $file] = $this->splitViewName($name);

        // Ensure Template & Cache Paths
        $this->ensureTemplatePath($subdir);
        $this->ensureCachePath($subdir);

        // Re-point The Engine At The Resolved Directories
        $this->loader->setPaths($this->paths());
        $this->twig->setCache($this->cacheDirectory);

        // Load The Sub Directory's Own Function File, If It Has One
        $this->loadSubFunctions();

        return $this->twig->render("{$file}.{$this->extension}", $this->vars());
    }

    /**
     * Register An Additional Loader Path
     *
     * A view resolves against its own directory and nothing else. This adds a
     * fallback searched after it, for templates shared across sub directories.
     * A path set here survives the per-render re-point that view() performs.
     * @param string $path Absolute Directory Path
     * @return static
     */
    public function addPath(string $path): static
    {
        $this->extraPaths[] = rtrim($path, '/\\');
        return $this;
    }

    /**
     * Assign Variable
     * @param string|array $key Key Name or Array of Key, Value Pair
     * @param mixed $value Key Name or Array of Key, Value Pair
     */
    public function assign(string|array $key, mixed $value = null): void
    {
        if (is_string($key)) {
            $key = [$key => $value];
        }
        $this->vars = array_replace($this->vars, $key);
    }

    /**
     * @param string $name Filter Name
     * @param string|callable $callable Callable Filter
     * @return void
     */
    public function addFilter(string $name, string|callable $callable): void
    {
        $this->twig->addFilter(new TwigFilter($name, $callable));
    }

    /**
     * Get Twig Environment
     *
     * The escape hatch for anything this wrapper does not expose:
     * addFunction(), addGlobal(), addTest(), addExtension(), getLoader().
     * @return Environment
     */
    public function engine(): Environment
    {
        return $this->twig;
    }

    /**
     * Get Assigned Vars, Merged Over The Defaults
     * @return array
     */
    public function vars(): array
    {
        return array_replace($this->defaultVars(), $this->vars);
    }

    ###########################################################################
    /*---------------------------- INTERNAL API ----------------------------*/
    ###########################################################################

    /**
     * Split a View Name Into Its Directory And File Parts
     *
     * A Twig name is always slash separated, so this splits the name, never a
     * filesystem path. 'admin/bootstrap/home' gives ['admin/bootstrap', 'home'],
     * 'home' gives [null, 'home'].
     * @param string $name View Name
     * @return array{0: ?string, 1: string}
     * @throws PathException
     */
    protected function splitViewName(string $name): array
    {
        $name = trim(str_replace('\\', '/', $name), '/');

        if ($name === '') {
            throw new PathException('Invalid view name: a view name must not be empty.');
        }

        $pos = strrpos($name, '/');

        if ($pos === false) {
            return [null, $name];
        }

        $file = substr($name, $pos + 1);

        if ($file === '') {
            throw new PathException("Invalid view name: [{$name}]. A view name must end in a file name.");
        }

        return [substr($name, 0, $pos), $file];
    }

    /**
     * Loader Paths For The Current Render
     *
     * The view's own directory first, then anything addPath() registered.
     * @return string[]
     */
    protected function paths(): array
    {
        return array_values(array_unique(array_merge([$this->templateDirectory], $this->extraPaths)));
    }

    /**
     * Ensure Template Path
     * @param ?string $subdir Sub Directory Path
     * @return void
     * @throws PathException
     */
    protected function ensureTemplatePath(?string $subdir = null): void
    {
        $this->templateDirectory = $this->resolvePath($subdir, TEMPLATE_PATH);
    }

    /**
     * Ensure Cache Path
     * @param ?string $subdir Sub Directory Path
     * @return void
     * @throws PathException
     */
    protected function ensureCachePath(?string $subdir = null): void
    {
        $this->cacheDirectory = $this->resolvePath($subdir, TEMPLATE_CACHE_PATH);
        Directory::make($this->cacheDirectory);
    }

    /**
     * Resolve a Sub Directory Taken From a View Name
     *
     * Always a sub directory of $base. It is deliberately not tested with
     * is_dir(): that resolves a bare name against the process CWD, which is the
     * document root under the front controller but the invocation directory
     * under the CLI and the queue worker, so a name colliding with a directory
     * there would silently escape.
     * @param ?string $path Sub Directory Name
     * @param string $base Base Directory a Sub Directory Hangs Off
     * @return string
     * @throws PathException
     */
    protected function resolvePath(?string $path, string $base): string
    {
        // Fold mixed separators to DS and drop any trailing one
        $path = trim(str_replace(['/', '\\'], DS, (string) $path), DS);

        if ($path === '') {
            return $base;
        }

        // A view name is caller supplied. Trimming leading separators does not
        // strip a drive prefix, so 'C:/windows/x' would otherwise escape.
        if ($this->isAbsolute($path)) {
            throw new PathException("Invalid view name: [{$path}]. A view name must be relative to the template directory.");
        }

        // A sub directory may not climb out of its base
        if (in_array('..', explode(DS, $path), true)) {
            throw new PathException("Invalid template directory: [{$path}]. A sub directory must not contain '..'.");
        }

        return $base . DS . $path;
    }

    /**
     * Check a Path Is Absolute
     *
     * Mirrors Laika\Core\App\Resource::isAbsolute().
     * @param string $path
     * @return bool
     */
    protected function isAbsolute(string $path): bool
    {
        return (bool) preg_match('#^(?:[a-zA-Z]:[\\\\/]|[\\\\/])#', $path);
    }

    /**
     * Load The Root Template Function File
     *
     * Scaffolded on first run and always loaded, so global enqueues stay in
     * effect whichever sub directory a view comes from.
     * @return void
     */
    protected function loadFunctions(): void
    {
        $loader = TEMPLATE_PATH . DS . 'loader.php';
        if (!File::exists($loader)) {
            File::touch($loader);
            File::write("<?php\n//Auto Generated by Framework\n", $loader);
        }

        require_once $loader;
    }

    /**
     * Load a Sub Directory's Own Function File
     *
     * Loads after the root one, and only when it is already there: unlike the
     * root loader, one is never generated for a sub directory.
     * @return void
     */
    protected function loadSubFunctions(): void
    {
        if ($this->templateDirectory === TEMPLATE_PATH) {
            return;
        }

        $loader = $this->templateDirectory . DS . 'loader.php';
        if (File::exists($loader)) {
            require_once $loader;
        }
    }

    /**
     * Default Vars
     *
     * Resolved at render time, not at construction: errors, context and locale
     * are all commonly set after the instance already exists.
     * @return array
     */
    protected function defaultVars(): array
    {
        return [
            // Local Name. Default is en
            'local'     =>  Local::get(),
            // Pagination
            'page'      =>  ['number' => Page::number(), 'next' => Page::next(), 'previous' => Page::previous()],
            // Request Inputs
            'input'     =>  new InputHandler(),
            // Form Errors
            'errors'    =>  Request::errors(),
            // Visitor Info
            'visitor'   =>  Visitor::info(),
            // Context
            'context'   =>  Context::get(),
        ];
    }

    /**
     * Assign Default Filters
     * @return void
     */
    protected function defaultFilters(): void
    {
        // Register Hooks
        $this->addFilter('hook', 'apply_hook');
        // Decode Html Special Characters
        $this->addFilter('decode', 'htmlspecialchars_decode');
        // Register Slugs
        $this->addFilter('slug', function (int $index){ return Url::segment($index); });
        // Register Queries
        $this->addFilter('query', function (string $key) { return Url::query($key); });
        // Register Named
        $this->addFilter('named', function(string $name, array $params = []){
            return named($name, $params);
        });
        // Register Asset
        $this->addFilter('asset', 'asset');
        // Register Context
        $this->addFilter('context', 'context_get');
    }
}
