<?php

namespace App\Models;

class Order
{
    private static $model = 'order';
    public static function create($orderMsg)
    {
        return app('db')->table(self::$model)
                        ->insertGetId($orderMsg);
    }
    public static function get($id)
    {
        return app('db')->table(self::$model)
                        ->where([
                            ['id', '=', $id],
                        ])
                        ->first();
    }
}
?>
