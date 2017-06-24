<?php

namespace App\Models;

use App\Models\Province;
use App\Models\City;
use App\Models\Area;

class Address
{
    private static $model = 'address';
    /**
     * [获取地址信息]
     * @param  [Integer] $id [地址的ID]
     * @return [Object]     [地址信息对象]
     */
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
    /**
     * [获取完整的地址]
     * @param  [Integer] $addrID [地址ID]
     * @return [Array]         [完整地址信息的数组]
     */
    public function getFullAddr($addrID) {
        $rsp = config('wx.fullAddr');
        if(!empty($addrID)) {
            $addrDetail = $this->get();
        } else {
            $addrDetail = $this->get($addrID);
        }
        if(empty($addrDetail)) {
            $rsp['state'] = 1;
            $rsp['msg'] =  ['请填写你的收获地址'];
        } else {
            // 根据获取的地址的详细信息来获取省,市,县区的名称
            $provinceName = Province::get($addrDetail->province_id)->name;
            $cityName = City::get($addrDetail->city_id)->name;
            $areaName = Area::get($addrDetail->area_id)->name;
            $fullAddr = $provinceName.$cityName.$areaName.$addrDetail->location;
            $rsp['state'] = 0;
            $rsp['msg'] =  [
                        'id' => $addrDetail->id,
                        'name' => $addrDetail->name,
                        'phone' => $addrDetail->tel,
                        'fullAddr' => $fullAddr,
            ];
        }

        return $rsp;
    }
    /**
     * [新增地址信息]
     * @param [Array] $addrArr [地址信息数组]
     */
    public function add($addrArr)
    {
        return app('db')->table(self::$model)
                        ->insertGetId($addrArr);
    }
    /**
     * [获取地址信息条目]
     * @param  [Integer] $limit [条目数]
     * @param  [Integer] $page  [页数]
     * @return [Object]        [地址信息对象集合]
     */
    public function mget($limit, $page)
    {
        return app('db')->table(self::$model)
                        ->limit($limit)
                        ->offset($page-1)
                        ->orderBy('created_at', 'desc')
                        ->get();
    }
    /**
     * [删除某个地址]
     * @param  [Integer] $id [地址ID]
     * @return [Integer]     [影响的行数]
     */
    public function remove($id)
    {
        return app('db')->table(self::$model)
                        ->where('id', $id)
                        ->delete();
    }
    /**
     * [更新地址信息]
     * @param  [Integer] $id      [地址ID]
     * @param  [Array] $addrArr [要更新的地址信息]
     * @return [Integer]          [影响的行数]
     */
    public function modify($id, $addrArr)
    {
        return app('db')->table(self::$model)
                        ->where('id', $id)
                        ->update($addrArr);
    }
}

?>