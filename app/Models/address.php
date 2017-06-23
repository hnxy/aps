<?php

namespace App\Models;

class Address
{
    private static $model = 'address';

    public function get($id = null)
    {
        if(is_null($id)) {
            return app('db')->table(self::$model)
                            ->where([
                                ['state', '=', '0']
                            ])
                            ->first();
        } else {
            return app('db')->table(self::$model)
                            ->where([
                                ['id', '=', $id]
                            ])
                            ->first();
        }
    }
    public function getFullAddr($province, $city, $area, $addrID) {
        if(!empty($addrID)) {
            $addrDetail = $this->get();
        } else {
            $addrDetail = $this->get($addrID);
        }
        if(empty($addrDetail)) {
            return [
                'state' => 1,
                'msg' => '请填写你的收获地址',
            ];
        }
        // 根据获取的地址的详细信息来获取省,市,县区的名称
        $provinceName = $province->get($addrDetail->province_id)->name;
        $cityName = $city->get($addrDetail->city_id)->name;
        $areaName = $area->get($addrDetail->area_id)->name;
        $fullAddr = $provinceName.$cityName.$areaName.$addrDetail->location;
        return [
                'state' => 0,
                'msg' => [
                    'id' => $addrDetail->id,
                    'name' => $addrDetail->name,
                    'phone' => $addrDetail->tel,
                    'fullAddr' => $fullAddr,
                ],
            ];
    }
    public function add($addrArr)
    {
        return app('db')->table(self::$model)
                        ->insertGetId($addrArr);
    }
    public function mget($limit, $page)
    {
        return app('db')->table(self::$model)
                        ->limit($limit)
                        ->offset($page-1)
                        ->orderBy('created_at', 'desc')
                        ->get();
    }
    public function remove($id)
    {
        return app('db')->table(self::$model)
                        ->where('id', $id)
                        ->delete();
    }
}

?>