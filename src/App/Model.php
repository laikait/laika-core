<?php

/**
 * Laika Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Laika\Core\App;

use Laika\Model\Model as BaseModel;

abstract class Model extends BaseModel
{
    // List
    private array $list = [];

    /**
     * Get Limit
     * @param int|string $page Page Number
     * @param array $where Example: ['id'=>1, 'status'=>'active']
     * @return array
     */
    public function getLimit(int|string $page = 1, array $where = []): array
    {
        $limit = (int) option('data.limit', 20);
        return $this
            ->where($where)
            ->limit($limit)
            ->offset($page)
            ->get();
    }

    /**
     * Get Selected Column
     * @param string $columns Example 'id,title'
     * @param array $where Example ['id'=>1].
     */
    public function getColumns(string $columns, array $where = []): array
    {
        return $this->select($columns)->where($where)->get();
    }

    /**
     * Get List
     * @param string $column1 Optional Parameter
     * @param string $column2 Required Parameter
     * @param array $where Optiona Argument. Example ['id'=>1].
     */
    public function list(string $column1, string $column2, array $where = []): array
    {
        $data = call_user_func([$this, 'getColumns'], "{$column1}, {$column2}", $where);
        foreach ($data as $val) {
            $this->list[$val[$column1]] = $val[$column2];
        }
        return $this->list;
    }
}
