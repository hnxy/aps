<?php

namespace App\Models;

use App\Models\Db\Goods as DbGoods;
use App\Exceptions\ApiException;


class Goods extends Model
{
    public static $model = 'Goods';
    /**
     * [获取商品信息]
     * @param  [Integer] $id [商品ID]
     * @return [Object]     [商品信息对象]
     */
    public function get($id)
    {
        $time = time();
        $goodsInfo = DbGoods::get(['where' =>
            ['id' => $id],
          ]);
        if(empty($goodsInfo)) {
            return null;
        }
        if($time >= $goodsInfo->start_time && $time < $goodsInfo->end_time) {
            $goodsInfo->status = 0;
            $goodsInfo->status_text = null;
        } else {
            $goodsInfo->status = 1;
            $goodsInfo->status_text = '该商品未开售或者已过期';
        }
        return $goodsInfo;
    }
    /**
     * [获取商品的条目]
     * @param  integer $limit [每次获取的条目]
     * @param  integer $page  [分页参数]
     * @return [Object]           [返回一个包含商品条目对象]
     */
    public function mget($limit, $page)
    {
        return DbGoods::mget($limit, $page);
    }

    /**
     * [通过商品ID获取商品的详细信息]
     * @param  [Integer] $id [商品的ID]
     * @return [Object]           [返回一个包含商品详细信息对象]
     */
    public function getDetail($id)
    {
        return DbGoods::get(['where' =>
            ['id' => $id],
          ]);
    }
    public function add($goodsArr)
    {
        return DbGoods::add($goodsArr);
    }
    public function isAbnormal($goodsCars)
    {
        $goods = [];
        foreach ($goodsCars as $goodsCar) {
            $goods[$goodsCar->goods_id] = $goodsCar->goods_num;
        }
        $time = time();
        $goodsInfos = DbGoods::mgetByIds(array_keys($goods));
        if (count(obj2arr($goodsInfos)) != count(array_keys($goods))) {
                throw new ApiException(config('error.goods_info_exception.msg'), config('error.goods_info_exception.code'));
        }
        foreach ($goodsInfos as $goodsInfo) {
            if($time < $goodsInfo->start_time || $time >= $goodsInfo->end_time) {
                return [
                    'msg' => "商品{$goodsInfo->title}未开售或者已下架",
                    'code' => config('error.add_goods_exception.code')
                ];
            }
            if($goodsInfo->stock < $goods[$goodsInfo->id]) {
                return [
                    'msg' => "商品{$goodsInfo->title}库存不够",
                    'code' => config('error.goods_not_enough_exception.code')
                ];
            }
        }
        return false;
    }

    public function modifyStock($goods, $type = 'increment') {
        DbGoods::modifyStock($goods, $type);
    }
    public function mgetByIds($goodsIds)
    {
        return DbGoods::mgetByIds($goodsIds);
    }
}
?>
