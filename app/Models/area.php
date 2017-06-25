<?php

namespace App\Models;

class Area
{
    private static $model = 'area';

    public static function get($id)
    {
        return app('db')->table(self::$model)
                        ->where([
                            ['area_id', '=', $id],
                        ])
                        ->first();
    }
    public static function mget($provinceId, $cityId)
    {
        return app('db')->table(self::$model)
                        ->where([
                            ['province_id', '=', $provinceId],
                            ['city_id', '=', $cityId],
                        ])
                        ->get();
    }
    public static function checkAddrWork($provinceId, $cityId, $areaId)
    {
        $res = app('db')->table(self::$model)
                        ->where([
                            ['province_id', '=', $provinceId],
                            ['city_id', '=', $cityId],
                            ['area_id', '=', $areaId],
                        ]);
        if(empty($res)) {
            return false;
        }
        return true;
    }
}

?>