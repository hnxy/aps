<?php
namespace App\Models;

class GoodsClasses
{
    private static $model = 'goods_classes';

    public function mget()
    {
        return app('db')->table(self::$model)
                        ->where(['state' => 1])
                        ->select(['name','id'])
                        ->get();
    }
}
?>