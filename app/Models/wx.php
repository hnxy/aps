<?php

namespace App\Models;

use App\Exceptions\ApiException;

class Wx
{
    /**
     * [获取用户信息]
     * @param  [String] $code [用户授权的code]
     * @return [array]       [一个包含用户信息的数组]
     */
    public function getUserInfo($code)
    {
        $res = $this->getWebAccessToken($code);
        $parsms = array(
           'access_token'=>$res['web_access_token'],
           'openid'=>$res['openid'],
           'lang'=>'zh_CN'
        );
        $url =  'https://api.weixin.qq.com/sns/userinfo?'.http_build_query($parsms);
        $result = myCurl($url);
        $rspMsg = $this->handleRspMsg($result);
        return $rspMsg;
    }
    /**
     * [获取网页授权的token]
     * @param  [String] $code      [用户授权的code]
     * @return [type]            [返回网页授权的access_token和openid]
     */
    private function getWebAccessToken($code) {
        $baseUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token?';
        $appid = config('wx.appid');
        $appSecret = config('wx.appSecret');
        $queryParams = array(
            'appid'=>$appid,
            'secret'=>$appSecret,
            'code'=>$code,
            'grant_type'=>'authorization_code'
        );
        $url = $baseUrl.http_build_query($queryParams);
        $result = myCurl($url);
        $rspMsg = $this->handleRspMsg($result);
        return [
            'web_access_token' => $rspMsg['access_token'],
            'openid' => $rspMsg['openid'],
        ];
    }
    /**
     * [获取基础的token]
     * @return [String] [返回基础的access_token]
     */
    public function getBasicAccessToken() {
        $appid = config('wx.appid');
        $appSecret = config('wx.appSecret');
        $queryParams = [
            'grant_type' => 'client_credential',
            'appid' =>$appid,
            'secret'=>$appSecret
        ];
        $url = 'https://api.weixin.qq.com/cgi-bin/token?'.http_build_query($queryParams);
        $result = myCurl($url);
        $rspMsg = $this->handleRspMsg($result);
        return $rspMsg['access_token'];
    }
    /**
     * [处理返回信息]
     * @param  [String|Array] $rspMsg [curl请求的信息]
     * @return [String|Array]         [返回处理后的信息]
     */
    private function handleRspMsg($rspMsg) {
        if (is_array($rspMsg)) {
            // 获取用户信息失败
            if (array_key_exists('errcode',$rspMsg)) {
                throw new ApiException($rspMsg['errmsg'] . ',code:' . $rspMsg['errcode'], config('error.get_web_token_err')['code']);
            } else {
                return $rspMsg;
            }
        } else {
            throw new ApiException($rspMsg, config('error.curl_err')['code']);
        }
        return $rspMsg;
    }
}
