<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use app\Models\User;
use app\Models\Wx;
use App\Exceptions\ApiException;

class UserController extends Controller
{

    /**
     * @param  Request
     * @return [type]
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
     * @param  Request
     * @return [type]
     */
    public static function get(Request $request)
    {
        return (array) $request->user;
    }
    /**
     * @param  Request
     * @return [type]
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
     * @return [type]
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
        return redirect($url.http_build_query($params).'#wechat_redirect');
    }
    /**
     * @param  Request
     * @return [type]
     */
    public  function login3Callback(Request $request)
    {
        $code = $request->input('code');
        $wx = new Wx();
        $userMsg = $wx->getUserInfo($code);
        var_dump($userMsg);
    }
}
?>