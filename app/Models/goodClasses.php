<?php

namespace App\Models;

use App\Models\Db\GoodsClasses as DbClasses;

class GoodsClasses extends Model
{
    public static $model = 'GoodsClasses';

    public static function mget()
    {
        return DbClasses::mget(['where' => ['state' => 1]]);
    }
    public static function get($id)
    {
        DbClasses::get(['where' => ['id' => $id] ]);
    }
}
?>