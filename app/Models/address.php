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
    public static function get($userId, $id = null)
    {
        if(is_null($id)) {
            return app('db')->table(self::$model)
                            ->where([
                                ['state', '=', '0'],
                                ['user_id', '=', $userId],
                            ])
                            ->first();
        } else {
            return app('db')->table(self::$model)
                            ->where([
                                ['id', '=', $id],
                                ['user_id', '=', $userId],
                            ])
                            ->first();
        }
    }
    /**
     * [获取完整的地址]
     * @param  [Integer] $addrID [地址ID]
     * @return [Array]         [完整地址信息的数组]
     */
    public function getFullAddr($userId, $addrID) {
        $rsp = config('wx.msg');
        if(empty($addrID)) {
            $addrDetail = $this->get($userId);
        } else {
            $addrDetail = $this->get($userId, $addrID);
        }
        if(empty($addrDetail)) {
            $rsp['state'] = 1;
            $rsp['msg'] =  ['请填写你的收获地址'];
        } else {
            // 根据获取的地址的详细信息来获取省,市,县区的名称
            $provinceName = Province::get($addrDetail->province_id)->name;
            $cityName = City::get($addrDetail->city_id)->name;
            $areaName = Area::get($addrDetail->area_id)->area_name;
            $fullAddr = $provinceName.$cityName.$areaName.$addrDetail->location;
            $rsp['state'] = 0;
            $rsp['msg'] =  [
                        'id' => $addrDetail->id,
                        'name' => $addrDetail->name,
                        'phone' => $addrDetail->tel,
                        'state' => $addrDetail->state ? true : false,
                        'fullAddr' => $fullAddr,
            ];
        }

        return $rsp;
    }
    /**
     * [新增地址信息]
     * @param [Array] $addrArr [地址信息数组]
     */
    public static function add($addrArr)
    {
        if(empty(static::get($addrArr['user_id']))) {
            $addrArr['state'] = 0;
        }
        return app('db')->table(self::$model)
                        ->insert($addrArr);
    }
    /**
     * [获取地址信息条目]
     * @param  [Integer] $limit [条目数]
     * @param  [Integer] $page  [页数]
     * @return [Object]        [地址信息对象集合]
     */
    public static function mget($userId, $limit, $page)
    {
        return app('db')->table(self::$model)
                        ->limit($limit)
                        ->offset(($page-1)*$limit)
                        ->where('user_id', $userId)
                        ->orderBy('created_at', 'desc')
                        ->get();
    }
    /**
     * [删除某个地址]
     * @param  [Integer] $id [地址ID]
     * @return [Integer]     [影响的行数]
     */
    public static function remove($userId, $id)
    {
        return app('db')->table(self::$model)
                        ->where([
                            ['id', '=', $id],
                            ['user_id', '=', $userId],
                        ])
                        ->delete();
    }
    /**
     * [更新地址信息]
     * @param  [Integer] $id      [地址ID]
     * @param  [Array] $addrArr [要更新的地址信息]
     * @return [Integer]          [影响的行数]
     */
    public static  function modify($addrArr)
    {
        return app('db')->table(self::$model)
                        ->where([
                            ['id', '=', $addrArr['id']],
                            ['user_id', '=', $addrArr['user_id']],
                        ])
                        ->update($addrArr);
    }
    public static function setDefault($userId, $id)
    {
        app('db')->table(self::$model)
                 ->where('user_id', $userId)
                 ->update(['state' => 1]);
        return  app('db')->table(self::$model)
                         ->where([
                            ['id', '=', $id],
                            ['user_id', '=', $userId],
                        ])
                         ->update(['state' => 0]);
    }
    public static function getAddrId($userId,$addrID) {
        if(is_null($addrID)) {
            $addrDetail = static::get($userId);
        } else {
            $addrDetail = static::get($userId, $addrID);
        }
        if(empty($addrDetail)) {
            return null;
        } else {
            return $addrDetail->id;
        }
    }
}

?>