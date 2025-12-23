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

use Laika\Model\Blueprint;
use Laika\Core\App\Model;
use Laika\Model\Schema;

class Options Extends Model
{
    // Table
    public string $table = 'options';

    // ID
    public string $id = 'id';

    // Option Key Column Name
    public string $key = 'lkey';

    // Option Value Column Name
    public string $value = 'lvalue';

    // Default Option Column Name
    public string $default = 'ldefault';

    // UUID Column Name
    public string $uuid = 'luuid';

    /**
     * Make Table if Doesn't Exists
     * @return void
     */
    public function migrate()
    {
        // Maigrate Table
        Schema::table($this->table)
                ->create($this->table, function (Blueprint $e) {
                    $e->column($this->id)->int()->auto();
                    $e->column($this->key)->varchar();
                    $e->column($this->value)->text();
                    $e->column($this->default)->enum(['yes', 'no'], 'no');
                });
        return;
    }
}
