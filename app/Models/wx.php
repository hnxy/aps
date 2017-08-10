<?php

namespace App\Models;

use App\Exceptions\ApiException;
use App\Models\Db\Wx as DbWx;

class Wx extends Model
{
    /**
     * [获取用户信息]
     * @param  [String] $code [用户授权的code]
     * @return [array]       [一个包含用户信息的数组]
     */
    public static $model = 'Wx';

    public function getUserInfo($arr)
    {
        $parsms = array(
           'access_token'=>$arr['web_access_token'],
           'openid'=>$arr['openid'],
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
    public function getWebAccessToken($code) {
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
            'refresh_token' => $rspMsg['refresh_token'],
        ];
    }
    /**
     * [获取基础的token]
     * @return [String] [返回基础的access_token]
     */
    public function getBasicAccessToken() {
        $arr['where'] = [
            ['name', '=', 'access_token'],
        ];
        $accessToken = DbWx::get($arr);
        //access_token存在且有效直接返回
        if (!empty($accessToken) && $accessToken->expired_time > time()) {
            return $accessToken->value;
        }
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
        $time = time();
        if (!empty($accessToken)) {
            $arr['where'] = ['name' => 'access_token'];
            $arr['update'] = [
                'value' => $rspMsg['access_token'],
                'created_at' => $time,
                'expired_time' => $time + $rspMsg['expires_in']
            ];
            DbWx::modify($arr);
        } else {
            DbWx::add([
                    'name' => 'access_token',
                    'value' => $rspMsg['access_token'],
                    'created_at' => $time,
                    'expired_time' => $time + $rspMsg['expires_in']
                ]);
        }
        return $rspMsg['access_token'];
    }
    /**
     * [处理返回信息]
     * @param  [String|Array] $rspMsg [curl请求的信息]
     * @return [String|Array]         [返回处理后的信息]
     */
    private function handleRspMsg($rspMsg) {
        if (is_array($rspMsg)) {
            // 获取信息失败
            if (array_key_exists('errcode',$rspMsg) && $rspMsg['errcode'] != 0) {
                throw new ApiException($rspMsg['errmsg'] . ',code:' . $rspMsg['errcode'], config('error.get_web_token_err')['code']);
            } else {
                return $rspMsg;
            }
        } else {
            throw new ApiException($rspMsg, config('error.curl_err')['code']);
        }
    }
    public function checkAccessTokenWork($accessToken, $openid)
    {
        $params = [
            'access_token' => $accessToken,
            'openid' => $openid,
        ];
        $url = 'https://api.weixin.qq.com/sns/auth?'.http_build_query($params);
        $res = myCurl($url);
        if (is_array($res)) {
            if (array_key_exists('errcode',$res) && $res['errcode'] != 0) {
                return false;
            }
            return true;
        }
        throw new ApiException($res, config('error.curl_err')['code']);
    }
    public function refreshAccesstoken($refreshToken)
    {
        $params = [
            'appid' => config('wx.appid'),
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ];
        $url = 'https://api.weixin.qq.com/sns/oauth2/refresh_token?'.http_build_query($params);
        $rspMsg = myCurl($url);
        if (is_array($rspMsg)) {
            if (array_key_exists('errcode',$rspMsg) && $rspMsg['errcode'] != 0) {
                return false;
            }
            return [
                'web_access_token' => $rspMsg['access_token'],
                'openid' => $rspMsg['openid'],
                'refresh_token' => $rspMsg['refresh_token'],
            ];
        }
        throw new ApiException($rspMsg, config('error.curl_err')['code']);
    }
    public function getTicket() {
        $arr['where'] = [
            ['name', '=', 'js_ticket'],
        ];
        $ticket = DbWx::get($arr);
        //ticket存在并且有效，直接返回
        if (!empty($ticket) && $ticket->expired_time > time()) {
            return $ticket->value;
        }
        //获取access_token
        $accessToken = $this->getBasicAccessToken();
        $params = [
            'access_token' => $accessToken,
            'type' => 'jsapi',
        ];
        $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?'.http_build_query($params);
        $result = myCurl($url);
        if ($result['errcode'] != 0) {
            throw new ApiException($result['errmsg'], config('error.get_ticket_fail.code'));
        }
        $time = time();
        //不为空，则进行更新操作
        if (!empty($ticket)) {
            $arr['where'] = ['name' => 'js_ticket'];
            $arr['update'] = [
                'value' => $result['ticket'],
                'created_at' => $time,
                'expired_time' => $time + $result['expires_in']
            ];
            DbWx::modify($arr);
        } else {
            DbWx::add([
                    'name' => 'js_ticket',
                    'value' => $result['ticket'],
                    'created_at' => $time,
                    'expired_time' => $time + $result['expires_in']
                ]);
        }
        return $result['ticket'];
    }
    public function queryByWx()
    {

    }
}
