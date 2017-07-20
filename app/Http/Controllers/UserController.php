<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Wx;
use App\Exceptions\ApiException;

class UserController extends Controller
{

    /**
     * @param  Request [注入Request实例]
     * @return [Object] [返回用户信息的对象]
     */
    public function login(Request $request)
    {
        $rules = [
            'username' => 'required|max:32|string',
            'passwd' => 'required|max:32|string',
        ];
        $this->validate($request, $rules);
        $lastIp = getIp();
        $userAgent = $request->header('User-Agent');
        $userArr = [
            'username' => $request->username,
            'passwd' => $request->passwd,
            'last_ip' => $lastIp,
            'user_agent' => $userAgent,
        ];
        $userModel = new User();
        $user = $userModel->login($userArr);
        if (empty($user)) {
            throw new ApiException("账号或密码错误", 1);
        }
        return $user;
    }
    /**
     * @param  Request [注入Request实例]
     * @param  Request [注入\App\Models\User实例]
     * @return [包含用户信息的数组]
     */
    public static function get(Request $request, $user)
    {
        return $user;
    }

    /**
     * @param  Request [注入Request实例]
     * @return [包含用户信息的数组]
     */
    public static function mget(Request $request, $user)
    {
        return (new User())->mget();
    }

    /**
     * [token验证]
     * @param  Request $request [获取request实例]
     * @return [String]           [返回空串或者echostr]
     */
    public function check(Request $request)
    {
        $timestamp = $request->input('timestamp');
        $signature = $request->input('signature');
        $nonce     = $request->input('nonce');
        $echostr   = $request->input('echostr', '');
        $list      = array();
        array_push($list,$timestamp, $nonce, config('wx.token'));
        sort($list, SORT_STRING);
        $lstring = sha1(implode($list));
        if($signature == $lstring){
           return $echostr;
        }
        else{
           return '';
        }
    }
    /**
    * [引导用户进入授权页面]
    */
    public function login3(Request $request)
    {
        $callbackUrl = url('v1/login3_callback?my_callback='.$request->input('my_callback', config('wx.index')));
        $params = array(
            'appid'=> config('wx.appid'),
            'redirect_uri' => $callbackUrl,
            'response_type' => 'code',
            'scope' => 'snsapi_userinfo',
            'state' => '1'
        );
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?";
        return redirect($url.http_build_query($params).'#wechat_redirect');
    }
    /**
     * [用户同意授权后的回调函数]
     * @param  Request $request [注入Request实例]
     */
    public  function login3Callback(Request $request)
    {
        $wx = new Wx();
        $user = new User();
        $code = $request->input('code');
        $callback = $request->input('my_callback');
        //判断该code是否存在
        if(($userInfo = $user->hasCode($code)) === false) {
            //不存在就通过该code获取access_token
            $res = $wx->getWebAccessToken($code);
            //获取用户信息
            $userMsg = $wx->getUserInfo($res);
            $userMsg['User-Agent'] = $request->header('User-Agent');
            $userMsg['code'] = $code;
            $userMsg['access_token'] = $res['web_access_token'];
            $userMsg['refresh_token'] = $res['refresh_token'];
        } else {
            //检查access_token是否有效
            if(!$wx->checkAccessTokenWork($userInfo->access_token, $userInfo->openid)) {
                //刷新access_token,刷新失败则重新授权
                if(($rspMsp = $wx->refreshAccesstoken($userInfo->refresh_token)) === false) {
                    return redirect(url('/v1/login3'));
                }
                $userInfo->openid = $rspMsp['openid'];
                $userInfo->access_token = $rspMsp['web_access_token'];
                $userInfo->refresh_token = $rspMsp['refresh_token'];
            }
            $userMsg = $wx->getUserInfo(['openid' => $userInfo->openid, 'web_access_token' => $userInfo->access_token]);
            $userMsg['User-Agent'] = $request->header('User-Agent');
            $userMsg['code'] = $userInfo->code;
            $userMsg['access_token'] = $userInfo->access_token;
            $userMsg['refresh_token'] = $userInfo->refresh_token;
        }
        $userInfo = $user->loginBy3($userMsg);
        $params = [
            'token' => $userInfo->token,
            'uid' => $userInfo->id,
            'headimgurl' => $userInfo->headimgurl,
        ];
        return redirect($callback.'?'.http_build_query($params));
    }
}
?>