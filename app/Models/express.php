<?php

namespace App\Models;

use App\Models\Db\Express as DbExpress;

class Express extends Model
{
    public static $model = 'Express';

    public static function get($id)
    {
        return DbExpress::get(['where' => ['id' => $id] ]);
    }
}