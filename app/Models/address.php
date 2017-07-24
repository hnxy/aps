<?php

namespace App\Models;

use App\Models\Province;
use App\Models\City;
use App\Models\Area;
use App\Models\Db\Address as DbAddress;

class Address extends Model
{
    public static $model = 'Address';
    public function get($userId, $addrId = null)
    {
        return DbAddress::get($userId, $addrId);
    }
    /**
     * [获取完整的地址]
     * @param  [Integer] $addrID [地址ID]
     * @return [Array]         [完整地址信息的数组]
     */
    public function getFullAddr($addrDetail) {
        $rsp = config('response.success');
        // 根据获取的地址的详细信息来获取省,市,县区的名称
        $provinceName = (new Province())->get($addrDetail->province_id)->name;
        $cityName = (new City())->get($addrDetail->city_id)->name;
        $areaName = (new Area())->get($addrDetail->area_id)->area_name;
        $fullAddr = $provinceName.$cityName.$areaName.$addrDetail->location;
        $addrDetail->provinceName = $provinceName;
        $addrDetail->cityName = $cityName;
        $addrDetail->areaName = $areaName;
        $addrDetail->status = $addrDetail->is_default ? true : false;
        $addrDetail->fullAddr = $fullAddr;
        $rsp['code'] = 0;
        $rsp['msg'] = $addrDetail;
        return $rsp;
    }
    public function isExist($userId, $addrId)
    {
        if(empty($addrId)) {
            $addrDetail = $this->get($userId);
        } else {
            $addrDetail = $this->get($userId, $addrId);
        }
        if(empty($addrDetail)) {
            return false;
        }
        return $addrDetail;
    }
    /**
     * [新增地址信息]
     * @param [Array] $addrArr [地址信息数组]
     */
    public function add($addrArr)
    {
        return DbAddress::add($addrArr);
    }
    /**
     * [获取地址信息条目]
     * @param  [Integer] $limit [条目数]
     * @param  [Integer] $page  [页数]
     * @return [Object]        [地址信息对象集合]
     */
    public function mget($userId, $limit, $page)
    {
        return DbAddress::mget($userId, $limit, $page);
    }
    public function remove($userId, $id)
    {
        return DbAddress::remove($userId, $id);
    }
    public function setDefault($userId, $id)
    {
        return DbAddress::setDefault($userId, $id);
    }
    public function modify($userId, $addrId, $arr)
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