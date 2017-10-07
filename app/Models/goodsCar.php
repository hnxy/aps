<?php

namespace App\Models;

use App\Models\Db\GoodsCar as DbGoodsCar;
use App\Exceptions\ApiException;

class GoodsCar extends Model
{
    public static $model = 'GoodsCar';
    /**
     * [更新购物车状态]
     * @param  [Array]  $goodsCarIDs [购物车ID集合]
     * @param  integer $status       [状态码]
     * @return [type]               [description]
     */
    public function updateStatus($userId, $goodsCarIDs, $status = 0)
    {
        $arr['where'] = ['user_id' => $userId];
        $arr['whereIn']['key'] = 'id';
        $arr['whereIn']['values'] = $goodsCarIDs;
        $arr['update'] = ['status' => $status];
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
    public function canDelete($goodsCar)
    {
        if(!$this->isExist($goodsCar)) {
            return false;
        }
        if ($goodsCar->status == 0) {
            return true;
        }
        return false;
    }
    public function canUpdate($goodsCar)
    {
        if(!$this->isExist($goodsCar)) {
            return false;
        }
        if ($goodsCar->status == 0) {
            return true;
        }
        return false;
    }
    /**
     * [通过购物车ID获取购物车信息集合]
     * @param  [Array] $goodsCarIDs [购物车ID集合]
     * @param  [Integer] $goodsCarIDs [购物车状态码]
     * @return [Array]              [购物车信息集合]
     */
    public function mgetByGoodsCarIdsWithStatus($userId, $goodsCarIDs, $status = 0)
    {
        return  DbGoodsCar::mgetByGoodsCarIdsWithStatus($userId, $goodsCarIDs, $status);
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
            ['status', '=', 0],
            ['is_del', '=', 0],
        ];
        $goodsCars = DbGoodsCar::mget($arr);
        $goodsModel = new Goods();
        $goodsClassesModel = new GoodsClasses();
        $goodsIds = [];
        foreach ($goodsCars as $goodsCar) {
            $goodsIds[] = $goodsCar->goods_id;
        }
        $goodses = $goodsModel->mgetByIds($goodsIds);
        if (count(obj2arr($goodses)) != count($goodsIds)) {
            throw new ApiException(config('error.goods_info_exception.msg'), config('error.goods_info_exception.code'));
        }
        $goodsMap = getMap($goodses, 'id');
        $rsp = [];
        foreach ($goodsCars as $goodsCar) {
            $goods = $goodsMap[$goodsCar->goods_id];
            $rsp[] = $this->formatGoodsCar($goodsCar, $goods);
        }
        return $rsp;
    }
    public function isExist($goodsCar)
    {
        return (empty($goodsCar) || $goodsCar->is_del ==1 ) ? false : true;
    }
    private function formatGoodsCar($goodsCar, $goods)
    {
        $goodsClassesModel = new GoodsClasses();
        if ($goods->end_time <= time()) {
            $temp['status'] = 1;
            $goods->status_text = '该商品已下架';
            $goods->send_time = '预计' . formatM($goods->send_time) . '发货';
        } else {
            $temp['status'] = 0;
            $goodsClasses = $goodsClassesModel->get($goods->classes_id);
            if (empty($goodsClasses)) {
                $goods->status_text = null;
            } else {
                $goods->status_text = $goodsClasses->name;
            }
            $goods->send_time = '预计' . formatM($goods->send_time) . '发货';
        }
        $temp['goods_car_id'] = $goodsCar->id;
        $temp['goods_num'] = $goodsCar->goods_num;
        $goods->price = $goods->price / 100;
        $goods->origin_price = $goods->origin_price / 100;
        $temp['goods_info'] = $goods;
        return $temp;
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
    public function hasGoods($userId, $goodsId)
    {
        $goodsCar = DbGoodsCar::get(['where' => [
                ['user_id', '=', $userId],
                ['goods_id', '=', $goodsId],
                ['status', '=', 0],
                ['is_del', '=', 0]
            ]]);
        if (empty($goodsCar)) {
            return false;
        }
        return $goodsCar;
    }
    public function get($userId, $id)
    {
        return DbGoodsCar::get(['where' => [
                ['user_id', '=', $userId],
                ['id', '=', $id],
            ]]);
    }
    public function mgetByUserId($userId)
    {
        $arr['where'] = [
            ['user_id', '=', $userId],
            ['status', '=', 0],
            ['is_del', '=', 0],
        ];
        return DbGoodsCar::mgetByUserId($arr);
    }
    public function mgetByGoodsCarIds($userId, $goodsCarIDs)
    {
        return  DbGoodsCar::mgetByGoodsCarIds($userId, $goodsCarIDs);
    }
}
?>