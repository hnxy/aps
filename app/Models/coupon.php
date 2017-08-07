<?php

namespace App\Models;

use App\Models\Db\Coupon as DbCoupon;
use App\Exceptions\ApiException;

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
                            ['is_del', '=', 0],
                        ]]);
    }
    public function getItems($agentId, $limit, $page)
    {
        $goodsIds = [];
        $goodsModel = new Goods();
        $arr['where'] = [
            ['agent_id', '=', $agentId],
            ['is_del', '=', 0],
        ];
        $arr['limit'] = $limit;
        $arr['page'] = $page;
        $coupons = DbCoupon::mget($arr);
        foreach ($coupons as $coupon) {
            $goodsIds[] = $coupon->goods_id;
        }
        array_unique($goodsIds);
        $goodses = $goodsModel->mgetByIds($goodsIds);
        if (count(obj2arr($goodses)) != count($goodsIds)) {
            throw new ApiException(config('error.goods_info_exception.msg'), config('error.goods_info_exception.code'));
        }
        $goodsMap = getMap($goodses, 'id');
        foreach ($coupons as &$coupon) {
            if($coupon->expired < time()) {
                $coupon->status = 1;
                $coupon->status_text = '已过期';
            } else {
                $coupon->status = 0;
                $coupon->status_text = '使用中';
            }
            $coupon->created_at = formatTime($coupon->created_at);
            $coupon->start_time = formatTime($coupon->start_time);
            $coupon->expired = formatTime($coupon->expired);
            $coupon->range = '特定商品(' . $goodsMap[$coupon->goods_id]->title . ')';
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
    private function validate($value, $key, $agentId)
    {
        return DbCoupon::get([
                            'where' => [
                                    [$key, '=', $value],
                                    ['expired', '>', time()],
                                    ['agent_id', '=', $agentId],
                                    ['is_del', '=', 0],
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
    public  function checkWork($value, $key, $goodsIds, $agentId, $userId) {
        if(is_null($value)) {
            return [];
        }
        $result = $this->validate($value, $key, $agentId);
        if(!empty($result) && in_array($result->goods_id, $goodsIds)) {
            $this->isUsed($result->id, $userId);
            return [
                'id' => $result->id,
                'goods_id' => $result->goods_id,
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
        $coupon = DbCoupon::get(['where' => [
                ['goods_id', '=', $goodsId],
                ['is_del', '=', 0],
            ]]);
        if(!empty($coupon)) {
            return true;
        }
        return false;
    }
    public function modifyByGoodsId($goodsId, $arr)
    {
        $uarr['where'] = [
            ['goods_id', '=', $goodsId],
            ['is_del', '=', 0],
        ];
        $uarr['update'] = $arr;
        return DbCoupon::modify($uarr);
    }
    public function remove($agentId, $couponId)
    {
        return DbCoupon::remove(['where' => [
                ['agent_id', '=', $agentId],
                ['id', '=', $couponId],
            ]]);
    }
    public function getById($id)
    {
        return DbCoupon::get(['where' => ['id' => $id] ]);
    }
    public function couponValidate($goodsCars, $code, $agentId, $userId)
    {
        $allPrice = 0;
        $goodsModel = new Goods();
        $goods = getMap($goodsCars, 'goods_id');
        if($coupon = $this->checkWork($code, 'code', array_keys($goods), $agentId, $userId)) {
            $allPrice = (-1)*$coupon['price'];
            $goodses = $goodsModel->mgetByIds(array_keys($goods));
            if (count(obj2arr($goodses)) != count(array_keys($goods))) {
                throw new ApiException(config('error.goods_info_exception.msg'), config('error.goods_info_exception.code'));
            }
            foreach ($goodses as $goodsInfo) {
                $allPrice += $goodsInfo->price*$goods[$goodsInfo->id]->goods_num;
            }
            $coupon['all_price'] = sprintf('%0.2f', $allPrice);
        }
        return $coupon;
    }
    protected function isUsed($couponId, $userId)
    {
        $orderModel = new Order();
        $order = $orderModel->getByCouponId($userId, $couponId);
        if (!empty($order)) {
            throw new ApiException(config('error.coupon_has_been_used_exception.msg'), config('error.coupon_has_been_used_exception.code'));
        }
    }
    public function getAll($agentId)
    {
        $arr['where'] = ['agent_id' => $agentId];
        return DbCoupon::all($arr);
    }
}

?>