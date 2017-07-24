<?php

namespace App\Models;

use App\Models\Db\GoodsClasses as DbClasses;

class GoodsClasses extends Model
{
    public static $model = 'GoodsClasses';

    public function mget()
    {
        return DbClasses::mget(['where' => ['status' => 1]]);
    }
    public function get($id)
    {
        return DbClasses::get(['where' => ['id' => $id] ]);
    }
}
?>