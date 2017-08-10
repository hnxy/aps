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
    public function get($goodsId)
    {
        return DbCoupon::get(['where' => [
                            ['goods_id', '=', $goodsId],
                            ['is_del', '=', 0],
                        ]]);
    }
    public function getItems($limit, $page)
    {
        $goodsIds = [];
        $goodsModel = new Goods();
        $arr['where'] = [
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
    private function validate($value, $key)
    {
        return DbCoupon::get([
                            'where' => [
                                    [$key, '=', $value],
                                    ['expired', '>', time()],
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
    public  function checkWork($value, $key, $goodsIds, $goodsCarMap) {
        if(is_null($value)) {
            return [];
        }
        $result = $this->validate($value, $key);
        if(!empty($result) && in_array($result->goods_id, $goodsIds) && ($result->times + $goodsCarMap[$result->goods_id]->goods_num < $result->all_times)) {
            return [
                'id' => $result->id,
                'goods_id' => $result->goods_id,
                'code' => $result->code,
                'price' => $result->price,
            ];
        }
        return [];
    }

    public function modifyTimesById($id, $times)
    {
        return DbCoupon::modifyTimesById($id, $times);
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
    public function remove($couponId)
    {
        return DbCoupon::remove(['where' => [
                ['id', '=', $couponId],
            ]]);
    }
    public function getById($id)
    {
        return DbCoupon::get(['where' => ['id' => $id] ]);
    }
    public function couponValidate($goodsCars, $code)
    {
        $allPrice = 0;
        $goodsModel = new Goods();
        $goodsCarMap = getMap($goodsCars, 'goods_id');
        if($coupon = $this->checkWork($code, 'code', array_keys($goodsCarMap), $goodsCarMap)) {
            $goodses = $goodsModel->mgetByIds(array_keys($goodsCarMap));
            $couponPrice = $coupon['price'] * $goodsCarMap[$coupon['goods_id']]->goods_num;
            if (count(obj2arr($goodses)) != count(array_keys($goodsCarMap))) {
                throw new ApiException(config('error.goods_info_exception.msg'), config('error.goods_info_exception.code'));
            }
            foreach ($goodses as $goodsInfo) {
                $allPrice += $goodsInfo->price * $goodsCarMap[$goodsInfo->id]->goods_num;
            }
            $allPrice -= $couponPrice;
            $coupon['coupon_all_price'] = '￥' .sprintf('%.2f', $couponPrice);
            $coupon['coupon_text'] = '您已优惠' . $couponPrice . '元';
            $coupon['all_price'] = sprintf('%0.2f', $allPrice);
        }
        return $coupon;
    }
    public function getAll()
    {
        return DbCoupon::all();
    }
}

?>