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

use Laika\Relay\Relay;
use Laika\Queue\Abstracts\Job;
use Laika\Core\Helper\Directory;
use Laika\Model\Contract\SchemaAbstract;
use Laika\Core\Exceptions\SchemaException;
use Laika\Core\Exceptions\ResourceException;
use Laika\Route\Contracts\FilterInterface;
use Laika\Route\Contracts\PipelineInterface;

// Application Infrastructure Info
class Infra
{
    /**
     * Get All Model Classes
     * @return array Table name => Model class
     */
    public function getModelClasses(): array
    {
        return $this->discover('models', null, 'table');
    }

    /**
     * Get All Schema Classes
     * @return array Table name => Schema class
     * @throws SchemaException
     */
    public function getSchemaClasses(): array
    {
        return $this->discover('schemas', SchemaAbstract::class, 'table', SchemaException::class);
    }

    /**
     * Get All Queue Jobs Classes
     * @return string[]
     * @throws ResourceException
     */
    public function getQueueJobsClasses(): array
    {
        return $this->discover('jobs', Job::class);
    }

    /**
     * Get Controller Classes
     * @return string[]
     * @throws ResourceException
     */
    public function getControllerClasses(): array
    {
        return $this->discover('controllers');
    }

    /**
     * Get Pipeline Classes
     * @return string[]
     * @throws ResourceException
     */
    public function getPipelineClasses(): array
    {
        return $this->discover('pipelines', PipelineInterface::class);
    }

    /**
     * Get Filter Classes
     * @return string[]
     * @throws ResourceException
     */
    public function getFilterClasses(): array
    {
        return $this->discover('filters', FilterInterface::class);
    }

    /**
     * Get Classes For Any Registered Resource
     *
     * The extension point for resource types declared in composer.json
     * or a package's composer `extra.laika.resources`.
     * @param string $name Resource Name
     * @param ?string $contract Override the contract declared by the resource
     * @return string[]
     * @throws ResourceException
     */
    public function get(string $name, ?string $contract = null): array
    {
        return $this->discover($name, $contract);
    }

    /**
     * Get Template Names
     * @return array
     */
    public function getTemplateNames(): array
    {
        $base = realpath(APP_PATH . '/template');
        $paths = (new Directory())->scan($base, false, ['html','twig']);
        $list = [];
        foreach ($paths as $path) {
            $name = trim(str_replace($base, '', $path), DS);
            $parts = explode(DS, $name);

            $template = $parts[0];
            $key = DS;

            if (count($parts) > 1) {
                $template = array_pop($parts);
                $key = implode(DS, $parts);
            }
            $ext = pathinfo($template, PATHINFO_EXTENSION);
            $file_name = pathinfo($template, PATHINFO_FILENAME);
            $list[$key][][strtolower($ext)] = $file_name;
        }
        ksort($list);
        return $list;
    }

    /**
     * Get Service Classes
     * @return array
     */
    public function getRelayClasses(): array
    {
        return Relay::classes();
    }

    /**
     * Get Function Files
     * @return string[]
     */
    public function getFunctionFiles(): array
    {
        return Resource::getFiles('functions');
    }

    /**
     * Get Hook Files
     * @return string[]
     */
    public function getHookFiles(): array
    {
        return Resource::getFiles('hooks');
    }

    /**
     * Get Route Files
     * @return string[]
     */
    public function getRouteFiles(): array
    {
        return Resource::getFiles('routes');
    }

    ############################################################################
    /*============================= INTERNAL API =============================*/
    ############################################################################

    /**
     * Resolve, Validate and Shape a Resource Class List
     * @param string $name Resource Name
     * @param ?string $contract Interface or Base Class Every Class Must Satisfy
     * @param ?string $keyBy Property to key the result by. Null returns a plain list
     * @param ?string $exception Exception class to rethrow resource errors as
     * @return array
     * @throws ResourceException
     */
    private function discover(string $name, ?string $contract = null, ?string $keyBy = null, ?string $exception = null): array
    {
        try {
            $classes = Resource::getClasses($name, $contract);
        } catch (ResourceException $e) {
            if ($exception === null) {
                throw $e;
            }
            throw new $exception($e->getMessage());
        }

        // Plain list
        if ($keyBy === null) {
            sort($classes);
            return $classes;
        }

        // Keyed by a declared property, e.g. the model/schema table name
        $list = [];
        foreach ($classes as $class) {
            $obj = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
            $list[$obj->{$keyBy}] = $class;
        }
        ksort($list);
        return $list;
    }
}
