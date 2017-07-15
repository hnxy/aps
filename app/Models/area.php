<?php

namespace App\Models;

use App\Models\Db\Area as DbArea;

class Area extends Model
{
    public static $model = 'Area';

    public function get($id)
    {
        return DbArea::get(['where' => ['area_id' => $id ]]);
    }
    public function checkAddrWork($provinceId, $cityId, $areaId)
    {
        $res = DbArea::get(['where' => [
                            ['province_id', '=', $provinceId],
                            ['city_id', '=', $cityId],
                            ['area_id', '=', $areaId],
                        ]]);
        if(empty($res)) {
            return false;
        }
        return true;
    }
}

?>