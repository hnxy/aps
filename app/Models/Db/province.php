<?php

namespace App\Models\Db;

class Province extends Model
{
    public static $model = 'province';

    public static function get($arr)
    {
        return app('db')->table(self::$model)
                        ->where($arr['where'])
                        ->first();
    }
}

?>