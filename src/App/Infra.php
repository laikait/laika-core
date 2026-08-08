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

use RuntimeException;
use Laika\Relay\Relay;
use Laika\Service\Directory;
use Laika\Queue\Abstracts\Job;
use Laika\Core\Abstracts\SchemaAbstract;
use Laika\Core\Exceptions\SchemaException;
use Laika\Route\Interfaces\FilterInterface;
use Laika\Route\Interfaces\PipelineInterface;

// Application Infrastructure Info
class Infra
{
    /**
     * Get All Model Classes
     * @return array
     */
    public function getModelClasses(): array
    {
        Resource::register('models', APP_PATH . '/lf-app/Model', 'App\\Model');
        $classes = Resource::getResources('models');
        $list = [];
        foreach ($classes as $class) {
            $reflection = new \ReflectionClass($class);
            $obj = $reflection->newInstanceWithoutConstructor();
            $list[$obj->table] = $class;
        }
        ksort($list);
        return $list;
    }

    /**
     * Get All Schema Classes
     * @return array
     */
    public function getSchemaClasses(): array
    {
        Resource::register('schemas', APP_PATH . '/lf-app/Schema', 'App\\Schema');
        $classes = Resource::getResources('schemas');
        $list = [];

        foreach ($classes as $t => $c) {
            if (!class_exists($c)) {
                throw new RuntimeException("Invalid schema class [{$c}]");
            }
            if (!is_subclass_of($c, SchemaAbstract::class)) {
                throw new SchemaException("{$c} is not a child class of " . SchemaAbstract::class);
            }
            $reflection = new \ReflectionClass($c);
            $obj = $reflection->newInstanceWithoutConstructor();
            $list[$obj->table] = $c;
        }
        ksort($list);
        return $list;
    }

    /**
     * Get All Queue Jobs Classes
     * @return array
     */
    public function getQueueJobsClasses(): array
    {
        Resource::register('jobs', APP_PATH . '/lf-app/Job', 'App\\Job');
        $classes = Resource::getResources('jobs');
        $list = [];

        foreach ($classes as $class) {
            if (!class_exists($class)) {
                throw new RuntimeException("Invalid job class [{$class}]");
            }
            if (!is_subclass_of($class, Job::class)) {
                throw new RuntimeException("{$class} is not a child class of " . Job::class);
            }
            $reflection = new \ReflectionClass($class);
            $obj = $reflection->newInstanceWithoutConstructor();
            $list[] = $class;
        }
        ksort($list);
        return $list;
    }

    /**
     * Get Controller Classes
     * @return array
     */
    public function getControllerClasses(): array
    {
        Resource::register('controller', APP_PATH . '/lf-app/Controller', 'App\\Controller');
        $classes = Resource::getResources('controller');
        $list = [];
        foreach ($classes as $class) $list[] = $class;
        ksort($list);
        return $list;
    }

    /**
     * Get Pipeline Classes
     * @return array
     * @throws RuntimeException
     */
    public function getPipelineClasses(): array
    {
        Resource::register('pipelines', APP_PATH . '/lf-app/Pipeline', 'App\\Pipeline');
        $classes = Resource::getResources('pipelines');
        $list = [];
        foreach ($classes as $class) {
            if (!class_exists($class)) {
                throw new RuntimeException("Invalid pipeline class [{$class}]");
            }
            if (!is_subclass_of($class, PipelineInterface::class)) {
                throw new RuntimeException("{$class} is not a child class of " . PipelineInterface::class);
            }
            $list[] = $class;
        }
        ksort($list);
        return $list;
    }

    /**
     * Get Filre Classes
     * @return array
     * @throws RuntimeException
     */
    public function getFilterClasses(): array
    {
        Resource::register('filters', APP_PATH . '/lf-app/Filter', 'App\\Filter');
        $classes = Resource::getResources('filters');
        $list = [];
        foreach ($classes as $class) {
            if (!class_exists($class)) {
                throw new RuntimeException("Invalid filter class [{$class}]");
            }
            if (!is_subclass_of($class, FilterInterface::class)) {
                throw new RuntimeException("{$class} is not a child class of " . FilterInterface::class);
            }
            $list[] = $class;
        }
        ksort($list);
        return $list;
    }

    /**
     * Get Template Names
     * @return array
     */
    public function getTemplateNames(): array
    {
        $base = realpath(APP_PATH . '/template');
        $paths = Directory::scan($base, false, ['html','twig']);
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
        return Resource::getResources('functions');
    }

    /**
     * Get Hook Files
     * @return string[]
     */
    public function getHookFiles(): array
    {
        Resource::register('hooks', APP_PATH . '/lf-hooks');
        return Resource::getResources('hooks');
    }

    /**
     * Get Route Files
     * @return string[]
     */
    public function getRouteFiles(): array
    {
        Resource::register('routes', APP_PATH . '/lf-routes');
        return Resource::getResources('routes');
    }
}
