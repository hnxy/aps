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
    public function get($goodsId, $agentId)
    {
        return DbCoupon::get(['where' => [
                            ['goods_id', '=', $goodsId],
                            ['agent_id', '=', $agentId],
                        ]]);
    }
    public function getItems($agentId, $limit, $page)
    {
        $arr['where'] = ['agent_id' => $agentId];
        $arr['limit'] = $limit;
        $arr['page'] = $page;
        $coupons = DbCoupon::mget($arr);
        foreach ($coupons as &$coupon) {
            if($coupon->expired < time()) {
                $coupon->status = 1;
                $coupon->status_text = '该优惠券已过期';
            } else {
                $coupon->status = 0;
                $coupon->status_text = null;
            }
            $coupon->created_at = formatTime($coupon->created_at);
            $coupon->start_time = formatTime($coupon->start_time);
            $coupon->expired = formatTime($coupon->expired);
        }
        unset($coupon);
        return obj2arr($coupons);
    }
    public function add($arr)
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
    public  function checkWork($value, $key, $goodsIds) {
        if(is_null($value)) {
            return [];
        }
        $result = $this->validate($value, $key);
        if(!empty($result) && in_array($result->goods_id, $goodsIds)) {
            return [
                'id' => $result->id,
                'code' => $result->code,
                'price' => $result->price,
            ];
        }
        return [];
    }

    public function modifyById($id)
    {
        return DbCoupon::modifyById($id);
    }
    public function has($goodsId)
    {
        $coupon = DbCoupon::get(['where' => ['goods_id' => $goodsId] ]);
        if(!empty($coupon)) {
            return true;
        }
        return false;
    }
    public function modifyByGoodsId($goodsId, $arr)
    {
        $uarr['where'] = ['goods_id' => $goodsId];
        $uarr['update'] = $arr;
        return DbCoupon::modify($uarr);
    }
    public function remove($id)
    {
        return DbCoupon::remove($id);
    }
    public function getById($id)
    {
        return DbCoupon::get(['where' => ['id' => $id] ]);
    }
    public function couponValidate($goodsCars, $code)
    {
        $allPrice = 0;
        $goodsModel = new Goods();
        $goods = getMap($goodsCars, 'goods_id');
        if($coupon = $this->checkWork($code, 'code', array_keys($goods))) {
            $allPrice = (-1)*$coupon['price'];
            foreach ($goodsModel->mgetByIds(array_keys($goods)) as $goodsInfo) {
                $allPrice += $goodsInfo->price*$goods[$goodsInfo->id]->goods_num;
            }
            $coupon['all_price'] = sprintf('%0.2f', $allPrice);
        }
        return $coupon;
    }
}

?>