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
use Laika\Service\Directory;
use Laika\Core\Exceptions\SchemaException;
use Laika\Core\Abstracts\SchemaAbstract;
use Loader;

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
     * Get Response Classes
     * @return array
     */
    public function getResponseClasses(): array
    {
        Resource::register('responses', APP_PATH . '/lf-app/Response', 'App\\Response');
        $classes = Resource::getResources('responses');
        $list = [];
        foreach ($classes as $class) $list[] = $class;
        ksort($list);
        return $list;
    }

    /**
     * Get Pipeline Classes
     * @return array
     */
    public function getPipelineClasses(): array
    {
        Resource::register('pipelines', APP_PATH . '/lf-app/Pipeline', 'App\\Pipeline');
        $classes = Resource::getResources('pipelines');
        $list = [];
        foreach ($classes as $class) $list[] = $class;
        ksort($list);
        return $list;
    }

    /**
     * Get Filre Classes
     * @return array
     */
    public function getFilterClasses(): array
    {
        Resource::register('filters', APP_PATH . '/lf-app/Filter', 'App\\Filter');
        $classes = Resource::getResources('filters');
        $list = [];
        foreach ($classes as $class) $list[] = $class;
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
}
