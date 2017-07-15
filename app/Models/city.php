<?php

namespace App\Models;

use App\Models\Db\City as DbCity;

class City extends Model
{
    public static $model = 'City';

    public function get($id)
    {
        return DbCity::get(['where' => ['city_id' => $id] ]);
    }
}

?>