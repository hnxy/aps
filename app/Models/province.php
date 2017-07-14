<?php

namespace App\Models;

use App\Models\Db\Province as DbProvince;
class Province extends Model
{
    public static $model = 'Province';

    public static function get($id)
    {
        return DbProvince::get(['where' => ['province_id' => $id] ]);
    }
}

?>