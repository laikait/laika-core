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

// Namespace
namespace Laika\Core\Log;

// Deny Direct Access
defined('APP_PATH') || http_response_code(403) . die('403 Direct Access Denied!');

use Laika\Core\Service\Visitor;
use InvalidArgumentException;

final class Activity
{
    /** @var bool Booted */
    protected static bool $booted = false;

    /** @var array Author */
    protected array $author;

    /** @var string Log */
    protected string $log;

    /** @var array Activities */
    protected array $activities;

    public function __construct()
    {
        $this->reset();
    }

    ################################################################################
    ################################# EXTERNAL API #################################
    ################################################################################
    /**
     * Set Author
     * @param ?string $type Example: client/admin/system
     * @param ?int $id Example: 1/2/3
     * @return self
     */
    public function author(?string $type = null, ?int $id = null): self
    {
        $this->author = [
            'type'  =>  strtolower($type ?? 'system'),
            'id'    =>  $id,
        ];
    }

    /**
     * Log Details
     * @param string $log
     * @return self
     */
    public function log(string $log): self
    {
        $this->log = trim($log);
    }

    /**
     * Create Create Activity
     * @return void
     */
    public function create()
    {
        $this->activities['create'][] = [
            'author_type'   =>  $this->author['type'],
            'author_id'     =>  $this->author['id'],
            'event'         =>  'create',
            'log'           =>  $this->log,
            'changes'       =>  serialize([]),
            'from_ip'       =>  Visitor::ip()
        ];
    }

    /**
     * Create Read Activity
     * @return void
     */
    public function read()
    {
        $this->activities['create'][] = [
            'author_type'   =>  $this->author['type'],
            'author_id'     =>  $this->author['id'],
            'event'         =>  'read',
            'log'           =>  $this->log,
            'changes'       =>  serialize([]),
            'from_ip'       =>  Visitor::ip()
        ];
    }

    /**
     * Create Update Activity
     * @param array $changes
     * @return void
     */
    public function update(array $changes = [])
    {
        $this->activities['create'][] = [
            'author_type'   =>  $this->author['type'],
            'author_id'     =>  $this->author['id'],
            'event'         =>  'update',
            'log'           =>  $this->log,
            'changes'       =>  serialize($changes),
            'from_ip'       =>  Visitor::ip()
        ];
    }

    /**
     * Create Update Activity
     * @return void
     */
    public function delete()
    {
        $this->activities['create'][] = [
            'author_type'   =>  $this->author['type'],
            'author_id'     =>  $this->author['id'],
            'event'         =>  'update',
            'log'           =>  $this->log,
            'changes'       =>  serialize([]),
            'from_ip'       =>  Visitor::ip()
        ];
    }

    /**
     * Get All Activities
     * @param ?string $event Default is null
     * @return array
     * @throws InvalidArgumentException
     */
    public function actions(?string $event = null): array
    {
        $event = strtolower($event);
        if ($event === null) {
            return $this->activities;
        } elseif (isset($this->activities[$event])) {
            return $this->activities[$event];
        }
        throw new InvalidArgumentException("Invalid Activity Event Key: [{$event}]");
    }

    ####################################################################################
    ################################### INTERNAL API ###################################
    ####################################################################################
    /**
     * Reset
     * @return void
     */
    private function reset(): void
    {
        // Set Log
        $this->log = '';

        // Set Author
        $this->author = [
            'type'  =>  'system',
            'id'    =>  null,
        ];

        // Set Activity Keys
        $this->activities = [
            'create'    =>  [],
            'read'      =>  [],
            'update'    =>  [],
            'delete'    =>  [],
        ];
    }
}
