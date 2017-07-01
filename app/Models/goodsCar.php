<?php

namespace App\Models;

class GoodsCar
{
    private static $model = 'goods_car';
    /**
     * [更新购物车状态]
     * @param  [Array]  $goodsCarIDs [购物车ID集合]
     * @param  integer $state       [状态码]
     * @return [type]               [description]
     */
    public static function updateState($userId, $goodsCarIDs, $state = 0)
    {
        return  app('db')->table(self::$model)
                     ->where([
                        ['user_id', '=', $userId],
                    ])
                     ->whereIn('id', $goodsCarIDs)
                     ->update(['state' => $state]);
    }
    /**
     * [更新购物车商品数量]
     * @param  [Integer] $goodsCarId [购物车ID]
     * @param  [Integer] $goodsNum   [商品数量]
     * @return [Integer]             [返回影响的行数]
     */
    public static function updateGoodsNum($userId, $goodsCarId, $goodsNum)
    {
        return app('db')->table(self::$model)
                        ->where([
                            ['id', '=', $goodsCarId],
                            ['user_id', '=', $userId],
                        ])
                        ->update(['goods_num' => $goodsNum]);
    }
    /**
     * [通过购物车ID获取购物车信息集合]
     * @param  [Array] $goodsCarIDs [购物车ID集合]
     * @param  [Integer] $goodsCarIDs [购物车状态码]
     * @return [Array]              [购物车信息集合]
     */
    public static function mget($userId, $goodsCarIDs, $state = 0)
    {
        return  app('db')->table(self::$model)
                         ->where([
                            ['user_id', '=', $userId],
                            ['state', '=', $state],
                         ])
                         ->whereIn('id', $goodsCarIDs)
                         ->get();
    }
    /**
     * [添加购物车信息]
     * @param [Array] $msg [购物车信息数组]
     * @return [Integer] [返回影响的行数]
     */
    public static function add($msg)
    {
        return app('db')->table(self::$model)
                        ->insertGetId($msg);
    }
    /**
     * [获取购物车条目]
     * @param  [Integer] $limit [条目数]
     * @param  [Integer] $page  [页数]
     * @return [Object]        [购物车信息的对象]
     */
    public static function getItems($userId, $limit, $page)
    {
        return app('db')->table(self::$model)
                        ->limit($limit)
                        ->offset(($page-1)*$limit)
                        ->where([
                            ['user_id', '=', $userId],
                            ['state', '=', 0]
                        ])
                        ->orderBy('created_at', 'desc')
                        ->get();
    }
    /**
     * [删除购物车]
     * @param  [Integer] $goodsCarId [购物车ID]
     * @return [Integer]             [影响的行数]
     */
    public static function remove($userId, $goodsCarId)
    {
        return app('db')->table(self::$model)
                        ->where([
                            ['user_id', '=', $userId],
                            ['id', '=', $goodsCarId]
                        ])
                        ->delete();
    }
    /**
     * [hasGoods description]
     * @param  [type]  $userId [description]
     * @param  [type]  $id     [description]
     * @return boolean         [description]
     */
    public static function hasGoods($userId, $id)
    {
        return app('db')->table(self::$model)
                        ->where([
                            ['goods_id', '=', $id],
                            ['user_id', '=', $userId],
                            ['state', '=', 0],
                        ])
                        ->first();

    }
    public static function addLogistics($goodsCarIds, $goodsCarArr)
    {
        return app('db')->table(self::$model)
                        ->whereIn('id', $goodsCarIds)
                        ->update($goodsCarArr);
    }
    public static function get($userId, $id)
    {
        return app('db')->table(self::$model)
                        ->where([
                            ['id', '=', $id],
                            ['user_id', '=', $userId],
                        ])
                        ->first();
    }
}
?>