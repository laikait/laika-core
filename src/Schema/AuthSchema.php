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
namespace Laika\Core\Migration;

// Deny Direct Access
defined('APP_PATH') || http_response_code(403) . die('403 Direct Access Denied!');

use Laika\Model\Schema\Blueprint;
use Laika\Model\Schema\Schema;

class AuthSchema
{
    /**
     * Migrate Table
     */
    public function migrate()
    {
        Schema::on()->createIfNotExists('authorizations', function (Blueprint $table) {
            $table->id('id');
            $table->string('token', 128);
            $table->string('session_id', 128);
            $table->string('user_type', 50);
            $table->json('user_data')->nullable()->comment('JSON Data');
            $table->unsignedInteger('user_id');
            $table->string('user_agent')->nullable();
            $table->string('device', 40)->nullable();
            $table->string('os', 40)->nullable();
            $table->unsignedInteger('expires_at');
            $table->unsignedInteger('created_at');

            // Indexes
            $table->unique('token');
            $table->index('session_id');
            $table->index('user_type');
            $table->index('user_id');
            $table->index('expires_at');
        });
    }
}
