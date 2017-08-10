<?php

namespace App\Models;

use App\Models\Db\Setting as DbSetting;

class Setting extends Model
{
    public static $model = 'Setting';
    public function add($arr)
    {
        DbSetting::add($arr);
    }
}