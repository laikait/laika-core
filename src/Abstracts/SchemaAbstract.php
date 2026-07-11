<?php
/**
 * Laika Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika PHP MVC Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Laika\Core\Abstracts;

use Laika\Model\Schema\Schema;

abstract class SchemaAbstract
{
    protected string $table;

    abstract public function up(): void;
    
    public function seed(): void {}
    
    public function down(): void { Schema::on()->dropIfExists($this->table); }

    /**
     * Check if Property is Set
     * @param string $prop Property Name
     * @return bool
     */
    public function __isset($prop): bool
    {
        return isset($this->$prop);
    }

    /**
     * Get Property Value
     * @param string $prop Property Name
     * @return mixed
     */
    public function __get($prop): mixed
    {
        return $this->$prop;
    }
}
