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

    /** @var array $vars */
    protected array $vars = [];

    /** @var string $templateDirectory Template Directory */
    protected string $templateDirectory;

    /** @var string $cacheDirectory Template Cache Directory */
    protected string $cacheDirectory;

    /**
     * File Extension
     * @var string
     */
    protected string $extension = 'twig';

    public function __construct(?string $templateSubDirectory = null, ?string $cacheSubDirectory = null)
    {
        // Ensure Template & Cache Paths
        $this->ensureTemplatePath($templateSubDirectory);
        $this->ensureCachePath($cacheSubDirectory);

        // Run Template Engine
        $engine = new Engine($this->templateDirectory);
        $this->twig = new Environment($engine, [
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
     * @param string $name View Name. Always Slash Separated: a Twig name is not a file path
     * @return string Rendered View
     */
    public function view(string $name): string
    {
        return $this->twig->render("{$name}.{$this->extension}", $this->vars());
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
     * Ensure Template Path
     * @param ?string $subdir Sub Directory Path
     * @return void
     * @throws PathException
     */
    protected function ensureTemplatePath(?string $subdir = null): void
    {
        $this->templateDirectory = $this->resolvePath($subdir, TEMPLATE_PATH);
        Directory::make($this->templateDirectory);
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
     * Resolve a Directory Argument
     *
     * An absolute path is taken as given. Anything else is a sub directory of
     * $base. It is deliberately not tested with is_dir(): that resolves a bare
     * name against the process CWD, which is the document root under the front
     * controller but the invocation directory under the CLI and the queue
     * worker, so a name colliding with a directory there would silently escape.
     * @param ?string $path Absolute Path or Sub Directory Name
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

        if ($this->isAbsolute($path)) {
            return $path;
        }

        $path = ltrim($path, DS);

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
     * Root Template Directory
     * @return string
     */
    protected function templateBase(): string
    {
        return APP_PATH . DS . 'template';
    }

    /**
     * Load Template Function Files
     *
     * The root loader is scaffolded on first run and always loads, so global
     * enqueues stay in effect for a sub directory instance too. A sub directory
     * may add its own loader, but one is never generated for it.
     * @return void
     */
    protected function loadFunctions(): void
    {
        $loader = $this->templateDirectory . DS . 'loader.php';
        if (!File::exists($loader)) {
            File::touch($loader);
            File::write("<?php\n//Auto Generated by Framework\n", $loader);
        }

        require_once $loader;
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
