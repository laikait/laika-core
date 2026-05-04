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
namespace Laika\Core\Http;

// Deny Direct Access
defined('APP_PATH') || http_response_code(403) . die('403 Direct Access Denied!');

class ChangeLog
{
    /** @var array new */
    protected array $new;

    /** @var array old */
    protected array $old;

    ################################################################################
    ################################# EXTERNAL API #################################
    ################################################################################

    public function __construct()
    {
        $this->reset();
    }

    /**
     * Add Existing Value
     * @param array $array Example: ['name' => 'John Doe', 'email' => '
     * @return static
     */
    public function addExisting(array $array): static
    {
        $this->old = $array;
        return $this;
    }
    /**
     * Add New Value
     * @param array $array Example: ['name' => 'John Doe', 'email' => '
     * @return static
     */
    public function addNew(array $array): static
    {
        $this->new = $array;
        return $this;
    }

    /**
     * Get Change Logs
     * @return array
     */
    public function getLogs(): array
    {
        return [
            'new' => $this->new,
            'old' => $this->old,
            'changes' => $this->check($this->old, $this->new)
        ];
    }

    ################################################################################
    ################################# INTERNAL API #################################
    ################################################################################
    /**
     * Reset Change Logs
     * @return void
     */
    protected function reset(): void
    {
        $this->new = [];
        $this->old = [];
    }

    /**
     * Check Change Logs
     * @param array $existing Existing Value
     * @param array $input New Input Value
     * @return array
     */
    protected function check(array $existing, array $input): array
    {
        $changes = [];
        // Check Changes
        foreach ($input as $key => $new) {
            $old = $existing[$key] ?? '';
            if ($old !== $new) {
                $changes[$key] = ['old' => $old, 'new' => $new];
            }
        }
        $this->reset();
        return $changes;
    }
}
