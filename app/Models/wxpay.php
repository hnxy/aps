<?php

namespace App\Models;
use App\Exceptions\ApiException;

class WxPay extends Model
{
    public static $model = 'WxPay';

    public function getSign($params)
    {
        ksort($params, SORT_STRING);
        $signArr = [];
        foreach ($params as $key => $value) {
            $signArr[] = sprintf('%s=%s', $key, $value);
        }
        $sign = implode('&', $signArr);
        $sign = $sign . "&key=" . config('wx.apiSecret');
        $sign = strtoupper(md5($sign));
        return $sign;
    }
    public function pay($combinePayId, $orders, $openid)
    {
        $all = $this->resolveOrders($orders);
        $time = time();
        $params = [
            'appid' => config('wx.appid'),
            'mch_id' => config('wx.shopid'),
            'device_info' => 'WEB',
            'nonce_str' => getRandomString(16),
            'body' => config('wx.body'),
            'out_trade_no' => $combinePayId,
            'total_fee' => $all * 100,//支付金额，单位为分
            'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],
            'notify_url' => config('wx.notify_url'),
            'trade_type' => 'JSAPI',
            'openid' => $openid,
            'time_start' => date('YmdHis', $time),
            'time_expire' => date('YmdHis', $time + config('wx.order_work_time')),
            'fee_type' => 'CNY',
            'attach' => '鲜农达',
            'sign_type' => 'MD5',
        ];
        $sign = $this->getSign($params);
        $params['sign'] = $sign;
        $XMLDATA = $this->buildXMLData($params);
        $rsp = $this->sendXML($XMLDATA);
        $rspArr = obj2arr(simplexml_load_string($rsp, 'SimpleXMLElement', LIBXML_NOCDATA));
        if ($rspArr['return_code'] != 'SUCCESS') {
            throw new ApiException($rspArr['return_msg'], config('error.communicate_exception.code'));
        }
        if ($rspArr['result_code'] != 'SUCCESS') {
            throw new ApiException($rspArr['err_code_des'], config('error.transaction_exception.code'));
        }
        $nonceStr = getRandomString(16);
        //获取支付签名
        $paySignParams = [
            'timeStamp' => $time,
            'nonceStr' =>  $nonceStr,
            'signType' => 'MD5',
            'package' => 'prepay_id=' . $rspArr['prepay_id'],
            'appId' => config('wx.appid'),
        ];
        $paySign = $this->getSign($paySignParams);
        return [
            'appid' => config('wx.appid'),
            'timestamp' => strval($time),
            'nonce_str' => $nonceStr,
            'trade_type' => $rspArr['trade_type'],
            'prepay_id' => $rspArr['prepay_id'],
            'paySign' => $paySign,
        ];
    }
    protected function buildXMLData($params)
    {
        $XMLDATA = '<xml>';
        foreach ($params as $key => $value) {
            $XMLDATA .= "<{$key}>{$value}</{$key}>";
        }
        $XMLDATA .= '</xml>';
        return $XMLDATA;
    }
    protected function sendXML($XMLDATA)
    {
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $headers = [
            'Content-Type:application/xml'
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $XMLDATA);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
        $rsp = curl_exec($ch);
        curl_close($ch);
        return $rsp;
    }
    public function getAll($goodses, array $orders)
    {
        $couponModel = new Coupon();
        $goodsMap = getMap($goodses, 'id');
        //计算总金额
        $all = 0;
        foreach ($orders as $order) {
            $goods = $goodsMap[$order['goods_id']];
            $all += $goods->price * $order['goods_num'];
            if (!is_null($order['coupon_id'])) {
                $coupon = $couponModel->getById($order['coupon_id']);
                //防止该优惠券已经删除
                if (!empty($coupon)) {
                    $all -= $coupon->price * $order['goods_num'];
                }
            }
        }
        return $all;
    }
    protected function resolveOrders($orders)
    {
        $goodsIds = [];
        $orderModel = new Order();
        $goodsModel = new Goods();
        $couponModel = new Coupon();
        $orders = obj2arr($orders);
        foreach ($orders as $order) {
            $goodsIds[] = $order['goods_id'];
        }
        $goodsIds = array_unique($goodsIds);
        $goodses = $goodsModel->mgetByIds($goodsIds);
        //防止商品被删
        if (count(obj2arr($goodses)) != count($goodsIds)) {
            throw new ApiException(config('error.goods_info_exception.msg'), config('error.goods_info_exception.code'));
        }
        return $this->getAll($goodses, $orders);
    }
}