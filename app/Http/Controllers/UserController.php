<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use app\Models\User;
use app\Models\Wx;
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
     * @return [包含用户信息的数组]
     */
    public static function get(Request $request)
    {
        return (array) $request->user;
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
       $echostr   = $request->input('echostr','');
       $list      = array();
       array_push($list,$timestamp,$nonce,self::token);
       sort($list,SORT_STRING);
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
    public function login3()
    {
        $callbackUrl = url('v1/login3_callback');
        $params = array(
            'appid'=>'wx105138e40ec74f25',
            'redirect_uri'=>$callbackUrl,
            'response_type'=>'code',
            'scope'=>'snsapi_userinfo',
            'state'=>'1'
        );
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?";
        redirect($url.http_build_query($params).'#wechat_redirect');
    }
    /**
     * [用户同意授权后的回调函数]
     * @param  Request $request [注入Request实例]
     */
    public  function login3Callback(Request $request)
    {
        $code = $request->input('code');
        $wx = new Wx();
        $user = new User();
        $userMsg = $wx->getUserInfo($code);
        $user::updateByOpenid($userMsg);
    }
}
?>