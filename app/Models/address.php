<?php

namespace App\Models;

class Address
{
    private static $model = 'address';

    public function get($id = null)
    {
        if(is_null($id)) {
            return app('db')->table(self::$model)
                            ->where(['state', '=', '0'])
                            ->first();
        } else {
            return app('db')->table(self::$model)
                            ->where(['id', '=', $id])
                            ->first();
        }
    }
}

?>