<?php

namespace App\Models;

class City
{
    private static $model = 'city';

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