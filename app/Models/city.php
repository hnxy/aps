<?php

namespace App\Models;

class City
{
    private static $model = 'city';

    public static function get($id)
    {
        return app('db')->table(self::$model)
                        ->where([
                            ['city_id', '=', $id],
                        ])
                        ->first();
    }
    public static function mget($provinceId)
    {
        return app('db')->table(self::$model)
                        ->where([
                            ['province_id', '=', $provinceId],
                        ])
                        ->get();
    }
}

?>