<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use app\Models\User;
use App\Exceptions\ApiException;

class UserController extends Controller
{
    const token = 'shop';
    const appid = 'wx105138e40ec74f25';
    const appSecret = 'adbe1d78ea45b581955cbbd27b189e36';
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

    public static function get(Request $request)
    {
        return (array) $request->user;
    }
    public function check(Request $request){
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
        // $this->getMessage();
    }
    //    引导用户进入授权页面，同意授权后会产生相应的code
    public function test(){
        $redirUrl = route('userToken');
        echo $redirUrl;
        $params = array(
            'appid'=>'wx105138e40ec74f25',
            'redirect_uri'=>$redirUrl,
            'response_type'=>'code',
            'scope'=>'snsapi_userinfo',
            'state'=>'1'
        );

        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?";
//        $access_token = get_basic_access_token("wx105138e40ec74f25","adbe1d78ea45b581955cbbd27b189e36");
//        if($access_token){
//           return 0;
//        }
//        echo $url.http_build_query($params).'#wechat_redirect';
        return response()->redirectTo($url.http_build_query($params).'#wechat_redirect');
    }
//    通过授权获得的code参数，获取token
    public  function userToken(Request $request){
         $code = $request->input('code');
         $Msg = get_web_access_token(self::appid,self::appSecret,$code);
         if($Msg['state'] == 0){
             $tokenMsg = $Msg['msg'];
             $web_access_token = $tokenMsg['access_token'];
             $expires_in = $tokenMsg['expires_in'];
             $refresh_token = $tokenMsg['refresh_token'];
             $openid = $tokenMsg['openid'];
             $this->get_user_info($web_access_token,$openid);
         }
    }
    public function get_user_info($web_access_token,$openid){
           $userMsg = get_user_info($web_access_token,$openid);
           echo '<pre>';
           var_dump($userMsg);
//           echo $web_access_token,$openid;
    }
}