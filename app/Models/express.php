<?php

namespace App\Models;


class Express
{
    private static $model = 'express';

    public static function get($id)
    {
        return app('db')->table(self::$model)
                        ->where('id', $id)
                        ->first();
    }
}