<?php

namespace App\Models;

class Province
{
    private static $model = 'province';

    public static function get($id)
    {
        return app('db')->table(self::$model)
                        ->
    }
}

?>