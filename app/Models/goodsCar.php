<?php

namespace App\Models;

use App\Models\Db\GoodsCar as DbGoodsCar;

class GoodsCar extends Model
{
    public static $model = 'GoodsCar';
    /**
     * [更新购物车状态]
     * @param  [Array]  $goodsCarIDs [购物车ID集合]
     * @param  integer $state       [状态码]
     * @return [type]               [description]
     */
    public function updateState($userId, $goodsCarIDs, $state = 0)
    {
        $arr['where'] = ['user_id' => $userId];
        $arr['whereIn']['key'] = 'id';
        $arr['whereIn']['values'] = $goodsCarIDs;
        $arr['update'] = ['state' => $state];
        return  DbGoodsCar::mModify($arr);
    }
    /**
     * [更新购物车商品数量]
     * @param  [Integer] $goodsCarId [购物车ID]
     * @param  [Integer] $goodsNum   [商品数量]
     * @return [Integer]             [返回影响的行数]
     */
    public function updateGoodsNum($userId, $goodsCarId, $goodsNum)
    {
        $arr['where'] = [
            ['user_id', '=', $userId],
            ['id', '=', $goodsCarId],
        ];
        $arr['update'] = ['goods_num' => $goodsNum];
        return DbGoodsCar::modify($arr);
    }
    /**
     * [通过购物车ID获取购物车信息集合]
     * @param  [Array] $goodsCarIDs [购物车ID集合]
     * @param  [Integer] $goodsCarIDs [购物车状态码]
     * @return [Array]              [购物车信息集合]
     */
    public function mgetByGoodsCarIds($userId, $goodsCarIDs, $state = 0)
    {
        return  DbGoodsCar::mgetByGoodsCarIds($userId, $goodsCarIDs, $state);
    }
    /**
     * [添加购物车信息]
     * @param [Array] $msg [购物车信息数组]
     * @return [Integer] [返回影响的行数]
     */
    public function add($msg)
    {
        return DbGoodsCar::add($msg);
    }
    /**
     * [获取购物车条目]
     * @param  [Integer] $limit [条目数]
     * @param  [Integer] $page  [页数]
     * @return [Object]        [购物车信息的对象]
     */
    public function getItems($userId, $limit, $page)
    {

        $arr['limit'] = $limit;
        $arr['page'] = $page;
        $arr['where'] = [
            ['user_id', '=', $userId],
            ['state', '=', 0],
        ];
        $goodsCars = DbGoodsCar::mget($arr);
        $goodsModel = new Goods();
        $goodsClassesModel = new GoodsClasses();
        $rsp = [];
        foreach ($goodsCars as $GoodsCar) {
            //购物车包含过期商品
            $goodsInfo = $goodsModel->getDetail($GoodsCar->goods_id);
            if($goodsInfo->end_time <= time() ) {
                $temp['state'] = 1;
                $temp['goods_car_id'] = $GoodsCar->id;
                $temp['goods_num'] = $GoodsCar->goods_num;
                $goodsInfo->status_text = '该商品已下架';
                $goodsInfo->send_time = formatM($goodsInfo->send_time);
                $temp['goods_info'] = $goodsInfo;
            } else {
                $temp['state'] = 0;
                $temp['goods_car_id'] = $GoodsCar->id;
                $temp['goods_num'] = $GoodsCar->goods_num;
                $goodsClasses = $goodsClassesModel->get($goodsInfo->classes_id);
                if(empty($goodsClasses)) {
                    $goodsInfo->status_text = null;
                } else {
                    $goodsInfo->status_text = $goodsClasses->name;
                }
                $goodsInfo->send_time = formatM($goodsInfo->send_time);
                $temp['goods_info'] = $goodsInfo;
            }
            $rsp[] = $temp;
        }
        return $rsp;
    }
    /**
     * [删除购物车]
     * @param  [Integer] $goodsCarId [购物车ID]
     * @return [Integer]             [影响的行数]
     */
    public function remove($userId, $goodsCarId)
    {
        return DbGoodsCar::remove($userId, $goodsCarId);
    }
    /**
     * [hasGoods description]
     * @param  [type]  $userId [description]
     * @param  [type]  $id     [description]
     * @return boolean         [description]
     */
    public function hasGoods($userId, $id)
    {
        return DbGoodsCar::get(['where' => [
                ['goods_id', '=', $id],
                ['user_id', '=', $userId],
                ['state', '=', 0],
            ]]);
    }
    public function get($userId, $id)
    {
        return DbGoodsCar::get(['where' => [
                ['user_id', '=', $userId],
                ['id', '=', $id],
            ]]);
    }
    public function getAllNum($userId)
    {
        return DbGoodsCar::getAllNum($userId);
    }
}
?>