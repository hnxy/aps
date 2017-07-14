<?php

namespace App\Models;

use App\Models\Province;
use App\Models\City;
use App\Models\Area;
use App\Models\Db\Address as DbAddress;

class Address extends Model
{
    public static $model = 'Address';
    public static function get($userId, $addrId = null)
    {
        return DbAddress::get($userId, $addrId);
    }
    /**
     * [获取完整的地址]
     * @param  [Integer] $addrID [地址ID]
     * @return [Array]         [完整地址信息的数组]
     */
    public function getFullAddr($userId, $addrId) {
        $rsp = config('wx.msg');
        if(empty($addrId)) {
            $addrDetail = $this->get($userId);
        } else {
            $addrDetail = $this->get($userId, $addrId);
        }
        if(empty($addrDetail) || $addrDetail->state == 2) {
            $rsp['state'] = 1;
            $rsp['msg'] =  ['请填写你的收获地址'];
        } else {
            // 根据获取的地址的详细信息来获取省,市,县区的名称
            $provinceName = Province::get($addrDetail->province_id)->name;
            $cityName = City::get($addrDetail->city_id)->name;
            $areaName = Area::get($addrDetail->area_id)->area_name;
            $fullAddr = $provinceName.$cityName.$areaName.$addrDetail->location;
            $addrDetail->provinceName = $provinceName;
            $addrDetail->cityName = $cityName;
            $addrDetail->areaName = $areaName;
            $addrDetail->state = !$addrDetail->state ? true : false;
            $addrDetail->fullAddr = $fullAddr;
            $rsp['state'] = 0;
            $rsp['msg'] = $addrDetail;
        }
        return $rsp;
    }
    /**
     * [新增地址信息]
     * @param [Array] $addrArr [地址信息数组]
     */
    public static function add($addrArr)
    {
        return DbAddress::add($addrArr);
    }
    /**
     * [获取地址信息条目]
     * @param  [Integer] $limit [条目数]
     * @param  [Integer] $page  [页数]
     * @return [Object]        [地址信息对象集合]
     */
    public static function mget($userId, $limit, $page)
    {
        return DbAddress::mget($userId, $limit, $page);
    }
    public static function remove($userId, $id)
    {
        return DbAddress::remove($userId, $id);
    }
    public static function setDefault($userId, $id)
    {
        return DbAddress::setDefault($userId, $id);
    }
    public static function getAddrId($userId,$addrID) {
        if(is_null($addrID)) {
            $addrDetail = DbAddress::get($userId);
        } else {
            $addrDetail = DbAddress::get($userId, $addrID);
        }
        if(empty($addrDetail) || $addrDetail->state == 2) {
            return null;
        } else {
            return $addrDetail->id;
        }
    }
    public static function modify($userId, $addrId, $arr)
    {
        $uarr['where'] = [
            ['user_id', '=', $userId],
            ['id', '=', $addrId],
        ];
        $uarr['update'] = $arr;
        return DbAddress::modify($uarr);
    }
}

?>