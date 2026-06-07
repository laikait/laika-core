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

use Laika\Model\Model;
use Laika\Core\Service\DB;
use InvalidArgumentException;
use Laika\Core\Service\Visitor;
use Laika\Core\Exceptions\LogException;

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
     * @return static
     */
    public function author(?string $type = null, ?int $id = null): static
    {
        $this->author = [
            'type'  =>  strtolower($type ?? 'system'),
            'id'    =>  $id,
        ];
        return $this;
    }

    /**
     * Log Details
     * @param string $log
     * @return static
     */
    public function log(string $log): static
    {
        $this->log = trim($log);
        return $this;
    }

    /**
     * Create Custom Activity
     * @param string $event
     * @param array $changes
     * @return void
     */
    public function event(string $event, array $changes = []): void
    {
        $this->activities[$event][] = [
            'author_type'   =>  $this->author['type'],
            'author_id'     =>  $this->author['id'],
            'event'         =>  strtolower(trim($event)),
            'log'           =>  $this->log,
            'changes'       =>  serialize($changes),
            'from_ip'       =>  Visitor::ip()
        ];
    }

    /**
     * Get All Activities
     * @param ?string $event Default is null
     * @return array
     * @throws InvalidArgumentException
     */
    public function events(?string $event = null): array
    {
        $event = strtolower($event);
        if ($event === null) {
            return $this->activities;
        } elseif (isset($this->activities[$event])) {
            return $this->activities[$event];
        }
        throw new InvalidArgumentException("Invalid Activity Event Key: [{$event}]");
    }

    /**
     * Insert Activities
     * @param ?string $connection Connection Name
     * @return int
     */
    public function insert(?string $connection = null): int
    {
        // Initiate Database
        DB::run();

        // Start Count
        $effected = 0;

        $model = new Model($connection);
        try {
            foreach($this->activities as $event => $logs) {
                $model->transaction(function (Model $m) use ($effected) {
                    $effected += $m->table('activities')->insert($logs);
                });
            }
        } catch (\Throwable $th) {
            if (DEBUG) throw new LogException($th->getMessage());
        }

        // Reset
        $this->reset();

        return $effected;
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
