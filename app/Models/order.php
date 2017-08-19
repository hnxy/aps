<?php

namespace App\Models;

use App\Models\Goods;
use App\Models\Coupon;
use App\Models\Express;
use App\Exceptions\ApiException;
use App\Models\Db\Order as DbOrder;

class Order extends Model
{
    public static $model = 'Order';
    const orderStatus = [
        'WAIT_PAY' => 1,
        'WAIT_SEND' => 2,
        'WAIT_RECV' => 3,
        'IS_FINISH' => 4,
        'IS_CANCEL' => 5,
    ];
    /**
     * [创建新订单]
     * @param  [Array] $orderMsg [订单的信息]
     * @return [Integer]           [返回订单的ID]
     */
    public function create($orderMsg)
    {
        return DbOrder::create($orderMsg);
    }
    /**
     * [根据订单ID获取订单]
     * @param  [integer] $id [订单ID]
     * @return [Object]     [包含订单信息的对象]
     */
    public function get($userId, $id)
    {
        return DbOrder::get(['where' => [
                ['user_id', '=', $userId],
                ['id', '=', $id],
            ]]);
    }
    public function getByCouponId($userId, $couponId)
    {
        return DbOrder::get(['where' => [
                ['user_id', '=', $userId],
                ['coupon_id', '=', $couponId],
            ]]);
    }
    public function getById($id)
    {
        return DbOrder::get(['where' => ['id' => $id]]);
    }
    /**
     * [获取价格有关的信息]
     * @param  [Object] $goodsCars [购物车对象的集合]
     * @param  [String] $value     [$key字段的值]
     * @param  string $key       [字段名称]
     * @return [Array]            [价格有关的信息]
     */
    public function getPrice($goodsCars)
    {
        $priceInfos = $goodsIds = $goodsCarInfos = [];
        $allPrice = 0;
        $time = time();
        $sendTime = 99999999999;
        $timespace = 999;
        $couponModel = new Coupon();
        foreach ($goodsCars as $goodsCar) {
            $goodsIds[] = $goodsCar->goods_id;
        }
        $goodsMap = $this->getGoodsMap($goodsIds);
        if ($goodsMap === false) {
            throw new ApiException(config('error.goods_info_exception.msg'), config('error.goods_info_exception.code'));
        }
        foreach ($goodsCars as $goodsCar) {
            $goodsInfo = $goodsMap[$goodsCar->goods_id];
            $currentPrice = sprintf('%.2f', $goodsInfo->price * $goodsCar->goods_num);
            $allPrice += $currentPrice;
            $goodsCarInfo = $this->formatGoods($goodsCar, $goodsInfo);
            $goodsCarInfo['value'] = '￥' . $currentPrice;
            $goodsCarInfo['goods_car_id'] = $goodsCar->id;
            $goodsCarInfos[] = $goodsCarInfo;
            $sendTime = min($sendTime, $goodsInfo->send_time);
            $timespace = min($timespace, $goodsInfo->timespace);
        }
        $allPrice = sprintf('%.2f', $allPrice);
        $priceInfos[] = ['name' => '配送费', 'value' => '￥00.00'];
        $priceInfos[] =  ['name' => '订单总价格', 'value' => '￥' . $allPrice];
        $priceInfos[] = ['name' => '优惠金额', 'value' => '￥00.00'];
        $sendTime = formatY($sendTime);
        return [
            'send_time' => ["预计{$sendTime}发货", "预计{$timespace}天后到货"],
            'price_info' => $priceInfos,
            'goods_car_info' => $goodsCarInfos,
        ];
    }
    protected function getGoodsMap($goodsIds)
    {
        $goodsModel = new Goods();
        $goodses = $goodsModel->mgetByIds($goodsIds);
        if (count(obj2arr($goodses)) != count($goodsIds)) {
            return false;
        }
        return getMap($goodses, 'id');
    }
    public function isExist($order)
    {
       return (empty($order) || $order->is_del == 1 ) ? false : true;
    }
    /**
     * [删除订单]
     * @param  [integer] $id [订单ID]
     * @return [integer]     [返回影响的行数]
     */
    public function remove($userId, $id)
    {
        return DbOrder::remove($userId, $id);
    }
    public function mget($userId, $limit, $page, $status)
    {
        $arr['limit'] = $limit;
        $arr['page'] = $page;
        if($status == 0) {
            $arr['where'] = [
                ['user_id', '=', $userId],
                ['is_del', '=', 0],
            ];
        } elseif($status == 1) {
            $arr['where'] = [
                ['user_id', '=', $userId],
                ['is_del', '=', 0],
                ['order_status', '=', $status],
                ['created_at', '>', time() - config('wx.order_work_time')],
            ];
        } else {
            $arr['where'] = [
                ['user_id', '=', $userId],
                ['order_status', '=', $status],
                ['is_del', '=', 0],
            ];
        }
        return DbOrder::mget($arr);
    }
    public function getOrdersInfo($orders)
    {
        $orderInfos = $otherInfo = $goodsIds = [];
        $time = time();
        //获取商品id,同时获取商品于id之间的映射
        foreach ($orders as $order) {
            $goodsIds[] = $order->goods_id;
        }
        $goodsIds = array_unique($goodsIds);
        $goodsMap = $this->getGoodsMap($goodsIds);
        if ($goodsMap === false) {
            throw new ApiException(config('error.goods_info_exception.msg'), config('error.goods_info_exception.code'));
        }
        foreach ($orders as $order) {
            $allPrice = 0;
            $goods = $goodsMap[$order->goods_id];
            // $allPrice += sprintf('%.2f', $goods->price*$order->goods_num);
            $orderInfo = $this->formatOrder($order);
            $goodsInfo = $this->formatGoods($order, $goods);
            $express = $this->getExpress($order->express_id);
            $couponPrice = $this->getPriceByCouponId($order->coupon_id)['couponPrice'];
            $allPrice = sprintf('%.2f', ($goods->price - $couponPrice) * $order->goods_num);
            $goodsInfo['value'] = '￥'.$allPrice;
            $sendPrice = sprintf('%.2f', $order->send_price);
            $sendTime = formatTime($goods->send_time);
            $priceInfo[] = ['text' => '配送费', 'value' => '￥'.$sendPrice];
            $otherInfo['buttons'] = $this->getButtons($order);
            $otherInfo['texts'] = $this->getTexts($order, $sendTime);
            $orderInfos[] =[
                'order_info' => $orderInfo,
                'goods_info' => $goodsInfo,
                'express_info' => $express,
                'price_info' => $priceInfo,
                'other_info' => $otherInfo,
                'summary' =>  ['goods_count' => "共1件商品", 'price_count' => "合计:￥{$allPrice}", 'send_price_count' => "(含运费￥{$sendPrice})"],
            ];
            $goodsInfo = $priceInfo = [];
        }
        return $orderInfos;
    }
    protected function getPriceByCouponId($couponId)
    {
        $coupon = null;
        $couponPrice = 0;
        if(!is_null($couponId)) {
            $coupon = (new Coupon())->getById($couponId);
            if (!empty($coupon)) {
                $couponPrice = $coupon->price;
            }
        }
        return ['couponPrice' => $couponPrice, 'coupon' => $coupon];
    }
    protected function formatGoods($order, $goods)
    {
        $goodsInfo = [
                'goods_id' => $order->goods_id,
                'name' => $goods->title,
                'goods_desc' => $goods->description,
                'num' => $order->goods_num,
                'unit' => $goods->unit,
                'goods_img' => $goods->goods_order_img,
                'goods_price' => $goods->price,
            ];

        if($goods->end_time < time()) {
            $goodsInfo['status'] = 1;
            $goodsInfo['status_text'] = '该商品已下架';
        } else {
            $goodsInfo['status'] = 0;
            $goodsInfo['status_text'] = null;
        }
        return $goodsInfo;
    }
    protected function formatOrder($order)
    {
        return [
                'order_id' => $order->id,
                'order_num' => $order->order_num,
                'created_at' => formatTime($order->created_at),
            ];
    }
    private function getButtons($order)
    {
        $otherInfo[] = ['name' => '取消订单', 'click' => 1, 'img_url' => '../../../../static/willpay/cancel.png', 'state' => $this->cancelable($order)];
        $otherInfo[] = ['name' => '立即支付', 'click' => 2, 'img_url' => '../../../../static/willpay/pay.png', 'state' => $this->canPay($order)];
        $otherInfo[] = ['name' => '确认收货', 'click' => 3, 'img_url' => '../../../../static/willget/got.png', 'state' => $this->canFinish($order)];
        $otherInfo[] = ['name' => '查看物流', 'click' => 4, 'img_url' => '../../../../static/willget/wuliu.png', 'state' => $this->canSearch($order)];
        $otherInfo[] = ['name' => '联系客服', 'click' => 5, 'img_url' => '../../../../static/alldd/kefu.png', 'state' => $this->canCall($order)];
        $otherInfo[] = ['name' => '删除订单', 'click' => 6, 'img_url' => '../../../../static/alldd/del.png', 'state' => $this->canDelete($order)];
        return $otherInfo;
    }
    private function getTexts($order, $sendTime)
    {
        $otherInfo[] = ['name' => '发货时间', 'value' =>"{$sendTime}起" ,'state' => $this->canShow($order)];
        return $otherInfo;
    }
    public function canShow($order)
    {
        if (!$this->isExist($order)) {
            return false;
        }
        if ($order->order_status == self::orderStatus['WAIT_SEND']) {
            return true;
        }
        return false;
    }
    public function cancelable($order)
    {
        if (!$this->isExist($order)) {
            return false;
        }
        $expired = config('wx.order_work_time');
        if ($order->created_at + $expired < time() ) {
            return false;
        }
        if ($order->order_status == self::orderStatus['WAIT_PAY'] ) {
            return true;
        }
        return false;
    }
    public function canPay($order)
    {
        if (!$this->isExist($order)) {
            return false;
        }
        $expired = config('wx.order_work_time');
        if ($order->created_at + $expired < time()) {
            return false;
        }
        if ($order->order_status == self::orderStatus['WAIT_PAY']) {
            return true;
        }
        return false;
    }
    public function canDelete($order)
    {
        if (!$this->isExist($order)) {
            return false;
        }
        $expired = config('wx.order_work_time');
        if (in_array($order->order_status, [self::orderStatus['IS_FINISH'], self::orderStatus['IS_CANCEL']]) || ($order->order_status == 1 && $order->created_at + $expired < time())) {
            return true;
        }
        return false;
    }
    public function canFinish($order)
    {
        if (!$this->isExist($order)) {
            return false;
        }
        if (is_null($order->logistics_code)) {
            return false;
        }
        if ($order->order_status == self::orderStatus['WAIT_RECV']) {
            return true;
        }
        return false;
    }
    public function canSearch($order)
    {
        if (!$this->isExist($order)) {
            return false;
        }
        if (in_array($order->order_status, [self::orderStatus['WAIT_RECV'], self::orderStatus['IS_FINISH']])) {
            return true;
        }
        return false;
    }
    public function canCall($order)
    {
        if (!$this->isExist($order)) {
            return false;
        }
        if ($order->order_status == self::orderStatus['IS_FINISH']) {
            return true;
        }
        return false;
    }
    private function getExpress($expressId)
    {
        $express = null;
        if(!is_null($expressId)) {
            $express = (new Express())->get($expressId);
            $express->phone = config("wx.express_offic_phone.{$express->code}");
        }
        return $express;
    }
    protected function getPay($order)
    {
        $paymentModel = new Payment();
        $payment = $paymentModel->get($order->pay_id);
        $payTime = null;
        if(!is_null($order->pay_time))
            $payTime = formatTime($order->pay_time);
        $payInfo[] = ['name' => '订单状态:', 'value' => config('wx.order_status')[$order->order_status]];
        $payInfo[] = ['name' => '订单号:', 'value' => $order->id];
        $payInfo[] = ['name' => '支付方式:', 'value' => $payment->pay_name];
        $payInfo[] = ['name' => '支付时间:', 'value' => $payTime];
        return $payInfo;
    }
    public static function getCombinePayId($userId, $payId) {
        $str = time();
        $str .= $payId;
        if(strlen($userId) < 4) {
            $str .= sprintf('%04d', $userId);
        } else {
            $str .= substr($userId, -4);
        }
        for($i = 0; $i < 4; $i++) {
            $str .= mt_rand(0,9);
        }
        return $str;
    }
    public function getOrderInfo($order)
    {
        $priceInfos = $pay_info = [];
        $goodsModel = new Goods();
        $goodsInfo = $goodsModel->get($order->goods_id);
        if (is_null($goodsInfo)) {
            throw new ApiException(config('error.goods_info_exception.msg'), config('error.goods_info_exception.code'));
        }
        $couponPrice = $this->getPriceByCouponId($order->coupon_id, $goodsInfo->price*$order->goods_num)['couponPrice'];
        $allPrice = sprintf('%.2f', ($goodsInfo->price - $couponPrice) * $order->goods_num);
        $sendTime = formatY($goodsInfo->send_time);
        $express = $this->getExpress($order->express_id);
        //支付相关
        $payInfo = $this->getPay($order);
        //价格相关
        $priceInfos[] = ['name' => '订单总价', 'value' => '￥'.$allPrice];
        $priceInfos[] = ['name' => '配送费', 'value' => '￥00.00'];
        // $priceInfos[] = ['name' => '优惠券', 'value' => '-￥00.00'];
        $goodsCarInfos = $this->formatGoods($order, $goodsInfo);
        return [
            'send_time' => ["预计{$sendTime}发货", "预计{$goodsInfo->timespace}天后到货"],
            'pay_info' => $payInfo,
            'price_info' => $priceInfos,
            'goods_car_info' => $goodsCarInfos,
        ];
    }
    public function getTypeCount($userId)
    {
        $counts = [];
        foreach (self::orderStatus as $key => $value) {
            if ($value == 1) {
                $counts[$key] = DbOrder::count(['where' => [
                        ['user_id', '=', $userId],
                        ['order_status', '=', $value],
                        ['is_del', '=', 0],
                        ['created_at', '>', time() - config('wx.order_work_time')],
                    ]]);
            } else {
                $counts[$key] = DbOrder::count(['where' => [
                        ['is_del', '=', 0],
                        ['user_id', '=', $userId],
                        ['order_status', '=', $value],
                    ]]);
            }
        }
        return $counts;
    }
    public function mModify($orderIds, $arr)
    {
        $uarr['whereIn']['key'] = 'id';
        $uarr['whereIn']['values'] = $orderIds;
        $uarr['update'] = $arr;
        return DbOrder::mModify($uarr);
    }
    public function getByAgentId($agentId, $orderNum)
    {
        return DbOrder::getByAgentId($agentId, $orderNum);
    }
    /**
     * [getByTime description]
     * @param  [type] $agentId [description]
     * @param  [type] $start   [description]
     * @param  [type] $end     [description]
     * @param  [type] $limit   [description]
     * @param  [type] $page    [description]
     * @return [type]          [description]
     */
    public function getByTime($agentId, $start, $end, $limit, $page)
    {
        return DbOrder::getByTime($agentId, $start, $end, $limit, $page);
    }
    /**
     * [addLogistics description]
     * @param [type] $orderIds [description]
     * @param [type] $orderArr [description]
     */
    public function addLogistics($orderIds, $orderArr)
    {
        $uarr['whereIn']['key'] = 'id';
        $uarr['whereIn']['values'] = $orderIds;
        $uarr['update'] = $orderArr;
        return DbOrder::mModify($uarr);
    }
    public function updateByLogstics($logstics, $arr)
    {
        $uarr['where'] = ['logistics_code' => $logstics];
        $uarr['update'] = $arr;
        return DbOrder::modify($uarr);
    }
    public function modifyByUser($orderId, $userId, $arr)
    {
        $uarr['where'] = [
            ['id', '=', $orderId],
            ['user_id', '=', $userId],
        ];
        $uarr['update'] = $arr;
        return DbOrder::modify($uarr);
    }
    public function mgetPayOrderByIds($userId, $orderIds)
    {
        $arr['where'] = [
            ['user_id', '=', $userId],
            ['is_del', '=', 0],
            ['order_status', '=', 1],
            ['created_at', '>', time() - config('wx.order_work_time')],
        ];
        $arr['whereIn']['key'] = 'id';
        $arr['whereIn']['values'] = $orderIds;
        return DbOrder::mgetByOrderIds($arr);
    }
    public function modifyCombinePayId($orderIds, $combinePayId)
    {
        $arr['whereIn']['key'] = 'id';
        $arr['whereIn']['values'] = $orderIds;
        $arr['update'] = ['combine_pay_id' => $combinePayId];
        return DbOrder::mModify($arr);
    }
    public function mgetByCombinePayId($combinePayId)
    {
        return DbOrder::mgetByCombinePayId($combinePayId);
    }
    public function modifyByCombinePayId($combinePayId, $arr)
    {
        $uarr['where'] = ['combine_pay_id' => $combinePayId];
        $uarr['update'] = $arr;
        return DbOrder::modify($uarr);
    }
    public function mgetUnsendByIds($orderIds)
    {
        $arr['where'] = [
            ['order_status', '=', 2],
            ['is_del', '=', 0],
        ];
        $arr['whereIn']['key'] = 'id';
        $arr['whereIn']['values'] = $orderIds;
        return DbOrder::mgetByOrderIds($arr);
    }
    public function getOrdersInfoByAgent($orders)
    {
        $paymentModel = new Payment();
        $orderInfos = $goodsIds = [];
        //获取商品id,同时获取商品于id之间的映射
        foreach ($orders as $order) {
            $goodsIds[] = $order->goods_id;
        }
        $goodsIds = array_unique($goodsIds);
        $goodsMap = $this->getGoodsMap($goodsIds);
        if ($goodsMap === false) {
            throw new ApiException(config('error.goods_info_exception.msg'), config('error.goods_info_exception.code'));
        }
        foreach ($orders as $order) {
            $payment = $paymentModel->get($order->pay_id);
            $allPrice = 0;
            $goods = $goodsMap[$order->goods_id];
            $arr = $this->getPriceByCouponId($order->coupon_id);
            $coupon = $arr['coupon'];
            $allPrice = sprintf('%.2f', ($goods->price - $arr['couponPrice']) * $order->goods_num);
            $orderInfos[] =[
                'id' => $order->id,
                'order_num' => $order->order_num,
                'created_at' => formatTime($order->created_at),
                'pay_by' => $payment->pay_name,
                'goods_name' => $goods->title,
                'goods_num' => $order->goods_num,
                'coupon_price' => is_null($coupon) ? 0 : $coupon->price,
                'all_price' => $allPrice
            ];
        }
        return $orderInfos;
    }
    public function getOrderInfoByAgent($order)
    {
        $paymentModel = new Payment();
        $goodsModel = new Goods();
        $goods = $goodsModel->get($order->goods_id);
        if (is_null($goods)) {
            throw new ApiException(config('error.goods_info_exception.msg'), config('error.goods_info_exception.code'));
        }
        $allPrice = $goods->price*$order->goods_num;
        $arr = $this->getPriceByCouponId($order->coupon_id);
        $coupon = $arr['coupon'];
        $allPrice = sprintf('%.2f', ($goods->price - $arr['couponPrice']) * $order->goods_num);
        $payment = $paymentModel->get($order->pay_id);
        return [
            'id' => $order->id,
            'order_num' => $order->order_num,
            'created_at' => formatTime($order->created_at),
            'pay_by' => $payment->pay_name,
            'goods_name' => $goods->title,
            'goods_num' => $order->goods_num,
            'coupon_price' => is_null($coupon) ? 0 : $coupon->price,
            'all_price' => $allPrice
        ];
    }
    public function getAll($searchId)
    {
        $arr['where'] = [['agent_id', '=', $searchId]];
        $arr['whereIn']['key'] = 'order_status';
        $arr['whereIn']['values'] = [self::orderStatus['WAIT_SEND'], self::orderStatus['WAIT_RECV'], self::orderStatus['IS_FINISH']];
        return DbOrder::all($arr);
    }
    public function getTrade($agentId, $start, $end)
    {
        return DbOrder::getTrade($agentId, $start, $end);
    }
}
?>
