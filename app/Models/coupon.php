<?php

namespace App\Models;

use App\Models\Db\Coupon as DbCoupon;

class Coupon extends Model
{
    public static $model = 'Coupon';
    /**
     * [get description]
     * @param  [type] $goodsId [description]
     * @param  [type] $agentId [description]
     * @return [type]          [description]
     */
    public static function get($goodsId, $agentId)
    {
        return DbCoupon::get(['where' => [
                            ['goods_id', '=', $goodsId],
                            ['agent_id', '=', $agentId],
                        ]]);
    }
    public static function mget($agentId, $limit, $page)
    {
        $arr['where'] = ['agent_id' => $agentId];
        $arr['limit'] = $limit;
        $arr['page'] = $page;
        return DbCoupon::mget($arr);
    }
    public static function add($arr)
    {
        return DbCoupon::add($arr);
    }
    /**
     * [验证优惠码是否有效]
     * @param  [String] $couponCode [优惠码]
     * @return [Object]             [包含该优惠码信息的对象]
     */
    private function validate($value, $key)
    {
        return DbCoupon::get([
                            'where' => [
                                    [$key, '=', $value],
                                    ['expired', '>', time()],
                            ],
                            'whereColumn' => [
                                ['times', '<', 'all_times']
                            ],
                        ]);
    }
    /**
     * [检查字段是否有效]
     * @param  [String] $value [字段值]
     * @param  [String] $key   [字段名]
     * @return [Object]        [包含该优惠券ID信息的对象]
     */
    public  function checkWork($value, $key, $goods_ids) {
        if(is_null($value)) {
            return [];
        }
        $result = $this->validate($value, $key);
        if(!empty($result) && in_array($result->goods_id, $goods_ids)) {
            DbCoupon::modifyById($result->id);
            return [
                'id' => $result->id,
                'code' => $result->code,
                'price' => $result->price,
            ];
        }
        return [];
    }

    public static function modifyById($id)
    {
        return DbCoupon::modifyById($id);
    }
    public static function has($goodsId)
    {
        $coupon = DbCoupon::get(['where' => ['goods_id' => $goodsId] ]);
        if(!empty($coupon)) {
            return true;
        }
        return false;
    }
    public static function modifyByGoodsId($goodsId, $arr)
    {
        $uarr['where'] = ['goods_id' => $goodsId];
        $uarr['update'] = $arr;
        return DbCoupon::modify($uarr);
    }
    public static function remove($id)
    {
        return DbCoupon::remove($id);
    }
    public static function getById($id)
    {
        return DbCoupon::get(['where' => ['id' => $id] ]);
    }
}

?>