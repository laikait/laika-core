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

use Laika\Service\Option;
use Laika\Model\Schema\Schema;
use Laika\Model\Schema\Blueprint;
use Laika\Core\Exceptions\OptionException;

class OptionSchema
{
    /**
     * Migrate Table
     */
    public function migrate()
    {
        Schema::on()->createIfNotExists('options', function (Blueprint $table) {
            $table->string('op_key');
            $table->text('op_value');
            $table->enum('is_default', ['yes', 'no'])->default('no');

            // Indexes
            $table->primary('op_key');
        });
    }

    /**
     * Insert Defaults
     * @return void
     */
    public function default(): void
    {
        try {
            $opts = [
                'app_icon'          =>  'icon.png',
                'app_logo'          =>  'logo.png',
                'app_name'          =>  'Laika Framework',
                'app_path'          =>  APP_PATH,
                'data_limit'        =>  20,
                'datetime_format'   =>  'Y-M-d H:i:s',
                'date_format'       =>  'Y-M-d',
                'time_format'       =>  'H:i:s',
                'time_zone'         =>  date_default_timezone_get(),
            ];

            foreach ($opts as $k => $v) {
                Option::insert($k, $v);
            }
        } catch (\Throwable $e) {
            if (DEBUG) throw new OptionException("Option Insert Failed. {$e->getMessage()}", (int) $e->getCode(), $e);
        }
    }
}
