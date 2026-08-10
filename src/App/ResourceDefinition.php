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

/**
 * A single registered resource location.
 *
 * Immutable description of *where* a resource type lives — never the files
 * themselves. Scanning is deferred to Resource::getResources().
 */
final class ResourceDefinition
{
    /**
     * @param string $name Canonical resource name. Example: models, routes
     * @param string $path Absolute directory path
     * @param ?string $namespace Base namespace, or null for file-path resources
     * @param ?string $contract Interface/base class every class must satisfy
     * @param string $source Where the definition came from. Example: app, runtime, laikait/laika-auth
     */
    public function __construct(
        public readonly string $name,
        public readonly string $path,
        public readonly ?string $namespace = null,
        public readonly ?string $contract = null,
        public readonly string $source = 'runtime'
    ) {
    }

    /**
     * Identity Used To De-duplicate Definitions
     * @return string
     */
    public function key(): string
    {
        return "{$this->name}|{$this->path}|{$this->namespace}";
    }

    /**
     * Check The Directory Is Present On Disk
     * @return bool
     */
    public function exists(): bool
    {
        return is_dir($this->path);
    }

    /**
     * Check Resource Maps Files To Class Names
     * @return bool
     */
    public function isClassMap(): bool
    {
        return $this->namespace !== null;
    }

    /**
     * Export For The Compiled Manifest
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name'      =>  $this->name,
            'path'      =>  $this->path,
            'namespace' =>  $this->namespace,
            'contract'  =>  $this->contract,
            'source'    =>  $this->source
        ];
    }

    /**
     * Rebuild From The Compiled Manifest
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) $data['name'],
            (string) $data['path'],
            $data['namespace'] ?? null,
            $data['contract'] ?? null,
            (string) ($data['source'] ?? 'runtime')
        );
    }
}
