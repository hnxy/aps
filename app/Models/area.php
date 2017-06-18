<?php

namespace App\Models;

class Area
{
    private static $model = 'area';

    public static function get($id)
    {
        return app('db')->table(self::$model)
                        ->where(['id', '=', $id])
                        ->first();
    }
}

?>