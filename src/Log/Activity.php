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
use Laika\Core\Service\Request;
use Laika\Core\Service\Visitor;
use Laika\Core\Exceptions\LogException;
use Laika\Core\Migration\ActivityMigration;

final class Activity
{
    /** @var array Author */
    protected array $author;

    /** @var string Log */
    protected string $log;

    /** @var array Activities */
    protected array $activities;

    /** @var array Change Log */
    protected array $changelog;

    public function __construct(?string $connection = null)
    {
        DB::run($connection);
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
     * @param array{old?:array,new?:array} $changes
     * @return void
     */
    public function event(string $event, array $changes = []): void
    {
        $this->activities[$event][] = [
            'author_type'   =>  $this->author['type'],
            'author_id'     =>  $this->author['id'],
            'event'         =>  strtolower(trim($event)),
            'log'           =>  $this->log,
            'changes'       =>  serialize($this->changelog),
            'from_ip'       =>  Visitor::ip()
        ];

        // Reset
        $this->author = [
            'type'  =>  'system',
            'id'    =>  null,
        ];
        $this->log = '';
        $this->changelog = [];
    }

    /**
     * Get All Activities
     * @param ?string $event Default is null
     * @return array
     * @throws InvalidArgumentException
     */
    public function events(?string $event = null): array
    {
        $event = $event ? strtolower($event) : null;
        if ($event === null) {
            return $this->activities;
        } elseif (isset($this->activities[$event])) {
            return $this->activities[$event];
        }
        throw new InvalidArgumentException("Invalid Activity Event Key: [{$event}]");
    }

    /**
     * Check Change Logs
     * @param array $existing Existing Value
     * @return array
     */
    public function changelog(array $existing): array
    {
        // Return if Empty
        if (empty($existing)) return $changes;

        // Check Changes
        foreach (Request::inputs() as $key => $input) {
            $old = $existing[$key] ?? '';
            if ($old !== $input) {
                $this->changelog[$key] = ['old' => $old, 'new' => $input];
            }
        }
        return $this->changelog;
    }

    /**
     * Insert Activities
     * @param ?string $connection Connection Name
     * @return int
     */
    public function insert(?string $connection = null): int
    {
        // Start Count
        $effected = 0;

        // Return if No Activities Exists
        if (empty($this->activities)) return 0;

        $model = new Model($connection);
        try {
            foreach($this->activities as $event => $logs) {
                $model->transaction(function (Model $m) use ($logs) {
                    $m->table('activities')->insert($logs);
                });
                $effected += count($logs);
            }
        } catch (\Throwable $th) {
            if (DEBUG) throw new LogException("Log Failed: {$th->getMessage()}");
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
        $this->activities = [];

        // Change Log
        $this->changelog = [];
    }
}
