<?php

namespace App\Models;

class Pay extends Model
{
    public static $model = 'Pay';
    protected function getSign($params)
    {
        ksort($params, SORT_STRING);
        $signArr = '';
        foreach ($params as $key => $value) {
            $signArr[] = sprintf('%s=%s', $key, $value);
        }
        $sign = implode('&', $signArr);
        $sign = $sign . "&key=" . config('wx.apiSecret');
        $sign = strtoupper(md5($sign));
        return $sign;
    }
    public function pay($combinePayId, $all, $openid)
    {
        $params = [
            'appid' => config('wx.appid'),
            'mch_id' => config('wx.shopid'),
            'nonce_str' => getRandomString(16),
            'body' => '玩乐广厦Uit-海鲜',
            'out_trade_no' => $combinePayId,
            'total_fee' => $all * 100,
            'spbill_create_ip' => '121.42.163.116',
            'notify_url' => url('/notify'),
            'trade_type' => 'JSAPI',
            'openid' => $openid,
        ];
        $sign = $this->getSign($params);
        $params['sign'] = $sign;
        $XMLDATA = <<<DATA
        <xml>
           <appid>%s</appid>
           <body>%s</body>
           <mch_id>%s</mch_id>
           <nonce_str>%s</nonce_str>
           <notify_url>%s</notify_url>
           <openid>%s</openid>
           <out_trade_no>%s</out_trade_no>
           <spbill_create_ip>%s</spbill_create_ip>
           <total_fee>%s</total_fee>
           <trade_type>%s</trade_type>
           <sign>%s</sign>
        </xml>
DATA;
        $XMLDATA = sprintf($XMLDATA, $params['appid'], $params['body'], $params['mch_id'], $params['nonce_str'],
            $params['notify_url'],  $params['openid'], $params['out_trade_no'], $params['spbill_create_ip'],
            $params['total_fee'], $params['trade_type'], $params['sign']
            );
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $headers = [
            'Content-Type:text/xml'
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $XMLDATA);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
        $rsp = curl_exec($ch);
        var_dump($rsp);
        curl_close($ch);
    }
}