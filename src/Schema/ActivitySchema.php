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
namespace Laika\Core\Schema;

// Deny Direct Access
defined('APP_PATH') || http_response_code(403) . die('403 Direct Access Denied!');

use Laika\Model\Schema\Schema;
use Laika\Model\Schema\Blueprint;
use Laika\Core\Abstracts\SchemaAbstract;

class ActivitySchema extends SchemaAbstract
{
    protected string $table = 'activities';

    public function up(): void
    {
        Schema::on($this->connection)->createIfNotExists($this->table, function (Blueprint $t) {
            $t->bigId('log_id');
            $t->string('author_type', 30);
            $t->unsignedBigInteger('author_id')->nullable();
            $t->string('event', 100)->comment('Event Name');
            $t->text('log')->comment('Log Details');
            $t->serialize('changes')->comment('Serialized Changed Data');
            $t->string('from_ip', 40);
            $t->timestamp('created_at');
            
            $t->index(['author_type', 'author_id'], 'author');
            $t->index('event');
            $t->index('created_at');
        });
    }
}
