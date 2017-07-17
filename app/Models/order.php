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
    /**
     * [获取价格有关的信息]
     * @param  [Object] $goodsCars [购物车对象的集合]
     * @param  [String] $value     [$key字段的值]
     * @param  string $key       [字段名称]
     * @return [Array]            [价格有关的信息]
     */
    public function getPrice($goodsCars) {
        $priceInfos = $goodsIds = $goodsCarInfos = [];
        $allPrice = 0;
        $time = time();
        $sendTime = 99999999999;
        $timespace = 999;
        $goodsCarMap = getMap($goodsCars, 'goods_id');
        $goodsModel = new Goods();
        foreach ($goodsCars as $goodsCar) {
            $goodsIds[] = $goodsCar->goods_id;
        }
        $goodses = $goodsModel->mgetByIds($goodsIds);
        $goodsMap = getMap($goodses, 'id');
        foreach ($goodsCars as $goodsCar) {
            $goodsInfo = $goodsMap[$goodsCar->goods_id];
            $currentPrice = sprintf('%.2f', $goodsInfo->price*$goodsCar->goods_num);
            $allPrice += $currentPrice;
            $goodsCarInfo = $this->formatGoods($goodsCar, $goodsInfo);
            $goodsCarInfo['value'] = '￥'.$currentPrice;
            $goodsCarInfo['goods_car_id'] = $goodsCar->id;
            $goodsCarInfos[] = $goodsCarInfo;
            $sendTime = min($sendTime, $goodsInfo->send_time);
            $timespace = min($timespace, $goodsInfo->timespace);
        }
        $allPrice = sprintf('%.2f', $allPrice);
        $priceInfos[] = ['name' => '配送费', 'value' => '￥00.00'];
        $priceInfos[] =  ['name' => '订单总价格', 'value' => '￥'.$allPrice];
        $priceInfos[] = ['name' => '优惠金额', 'value' => '￥00.00'];
        $sendTime = formatY($sendTime);
        return [
            'send_time' => ["预计{$sendTime}发货", "预计{$timespace}天后到货"],
            'coupon' => '暂无可用的优惠码',
            'price_info' => $priceInfos,
            'goods_car_info' => $goodsCarInfos,
        ];
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
        $orderInfos = $otherInfo = $goodsIds = $goodsIds = [];
        $time = time();
        //获取商品id,同时获取商品与订单之间的映射
        $goodsModel = new Goods();
        foreach ($orders as $order) {
            $goodsIds[] = $order->goods_id;
        }
        $goodses = $goodsModel->mgetByIds($goodsIds);
        $goodsMap = getMap($goodses, 'id');
        foreach ($orders as $order) {
            $allPrice = 0;
            $goods = $goodsMap[$order->goods_id];
            $allPrice += sprintf('%.2f', $goods->price*$order->goods_num);
            $orderInfo = $this->formatOrder($order);
            $goodsInfo = $this->formatGoods($order, $goods);
            $express = $this->getExpress($order->express_id);
            $allPrice = $this->getAllPriceByCouponId($order->coupon_id, $allPrice);
            $goodsInfo['value'] = '￥'.$allPrice;
            $sendPrice = sprintf('%.2f', $order->send_price);
            $sendTime = formatTime($goods->send_time);
            $priceInfo[] = ['text' => '配送费', 'value' => '￥'.$sendPrice];
            $priceInfo[] = ['text' => "共1件商品", 'value' => "合计:￥{$allPrice}(含运费￥{$sendPrice})"];
            $otherInfo['buttons'] = $this->getButtons($order);
            $otherInfo['texts'] = $this->getTexts($order, $sendTime);
            $orderInfos[] =[
                'order_info' => $orderInfo,
                'goods_info' => $goodsInfo,
                'express_info' => $express,
                'price_info' => $priceInfo,
                'other_info' => $otherInfo,
            ];
            $goodsInfo = $priceInfo = [];
        }
        return $orderInfos;
    }
    protected function getAllPriceByCouponId($couponId, $allPrice)
    {
        if(!is_null($couponId)) {
            $coupon = (new Coupon())->getById($couponId);
            $allPrice -= $coupon->price;
        }
        return sprintf('%.2f', $allPrice);
    }
    protected function formatGoods($order, $goods)
    {
        $goodsInfo = [
                'goods_id' => $order->goods_id,
                'name' => $goods->title,
                'goods_desc' => $goods->description,
                'num' => $order->goods_num,
                'unit' => $goods->unit,
                'goods_img' => $goods->goods_img,
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
        $otherInfo[] = ['name' => '取消订单', 'state' => $this->canPay($order)];
        $otherInfo[] = ['name' => '立即支付', 'state' => $this->canPay($order)];
        $otherInfo[] = ['name' => '确认收货', 'state' => $this->canFinish($order)];
        $otherInfo[] = ['name' => '查看物流', 'state' => $this->canSearch($order)];
        $otherInfo[] = ['name' => '联系客服', 'state' => $this->canCall($order)];
        $otherInfo[] = ['name' => '删除订单', 'state' => $this->canDelete($order)];
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
        if ($order->order_status == 2) {
            return true;
        }
        return false;
    }
    public function canPay($order)
    {
        if (!$this->isExist($order)) {
            return false;
        }
        $expired = config('wx.order_work_time')*3600;
        if ($order->created_at + $expired < time() ) {
            return false;
        }
        if ($order->order_status == 1 ) {
            return true;
        }
        return false;
    }
    public function canDelete($order)
    {
        if (!$this->isExist($order)) {
            return false;
        }
        if ($order->order_status == 4) {
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
        if ($order->order_status == 3) {
            return true;
        }
        return false;
    }
    public function canSearch($order)
    {
        if (!$this->isExist($order)) {
            return false;
        }
        if (in_array($order->order_status, [3, 4])) {
            return true;
        }
        return false;
    }
    public function canCall($order)
    {
        if (!$this->isExist($order)) {
            return false;
        }
        if ($order->order_status == 4) {
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
        $payTime = null;
        if(!is_null($order->pay_time))
            $payTime = formatTime($order->pay_time);
        $payInfo[] = ['name' => '订单状态:', 'value' => config('wx.order_status')[$order->order_status]];
        $payInfo[] = ['name' => '订单号:', 'value' => $order->id];
        $payInfo[] = ['name' => '支付方式:', 'value' => config('wx.pay_by')[$order->pay_by]];
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
        $allPrice = $this->getAllPriceByCouponId(null, $goodsInfo->price*$order->goods_num);
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
    public function modifyByUser($orderId, $userId, $arr)
    {
        $uarr['where'] = [
            ['id', '=', $orderId],
            ['user_id', '=', $userId],
        ];
        $uarr['update'] = $arr;
        return DbOrder::modify($uarr);
    }
    public function mModify($orderIds, $arr)
    {
        $uarr['whereIn']['key'] = 'id';
        $uarr['whereIn']['values'] = $orderIds;
        $uarr['update'] = $arr;
        return DbOrder::mModify($uarr);
    }
    public function mModifyByUser($orderIds, $userId, $arr)
    {
        $uarr['where'] = ['user_id' => $userId];
        $uarr['whereIn']['key'] = 'id';
        $uarr['whereIn']['values'] = $orderIds;
        $uarr['update'] = $arr;
        return DbOrder::mModify($uarr);
    }
    public function getByAgentId($agentId, $id)
    {
        return DbOrder::getByAgentId($agentId, $id);
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
    public function mgetByOrderIds($userId, $orderIds)
    {
        return DbOrder::mgetByOrderIds($userId, $orderIds);
    }
    public static function modifyCombinePayId($orderIds, $combinePayId)
    {
        $arr['whereIn']['key'] = 'id';
        $arr['whereIn']['values'] = $orderIds;
        $arr['update'] = ['combine_pay_id' => $combinePayId];
        return DbOrder::modify($arr);
    }
}
?>
