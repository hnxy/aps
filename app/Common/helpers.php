<?php
    /**
     * [getIp description]
     * 获取真实 client ip地址
     * @author cg
     * @return [string] [ip地址]
     */
    function getIp()
    {
        return empty($_SERVER['HTTP_X_REAL_IP']) ? "": $_SERVER['HTTP_X_REAL_IP'];
    }

    /**
     * [getRandomString description]
     * 生成随机字符串
     * @author cg
     * @param  [int] $len [description]
     * @return [string]      [大小写字母加数字组合成的字符串]
     */
    function getRandomString($len)
    {
        $len = $len >= 64 ? 64 : $len;
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        mt_srand(10000000*(double)microtime());
        for ($i = 0, $str = '', $lc = strlen($chars)-1; $i < $len; $i++) {
            $str .= $chars[mt_rand(0, $lc)];
        }
        return $str;
    }


    /**
     * [genToken description]
     * 生成token,以后升级将会引入JWT,目前先使用普通的token
     * @author cg
     * @param  [type] $userId [description]
     * @return [type]         [description]
     */
    function genToken() {
        return password_hash(getRandomString(32), PASSWORD_DEFAULT);
    }
    /**
     * [getToken description]
     * 获取请求的token, 优先从header中获取
     * @author cg
     * @param  [type] $request [description]
     * @return [type]          [description]
     */
    function getToken($request) {
        $header = $request->header();
        $authorization = $request->header('authorization');
        $token = $request->input('token');
        return  $authorization ? $authorization : $token;
    }
           /**
     * 获取access_token
     */
    function get_basic_access_token($appid,$appSecret){
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$appSecret;
        $ch  = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt($ch,CURLOPT_TIMEOUT,10);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);
        $tmpStr = curl_exec($ch);
        if (curl_errno($ch)) {
            return 0;
        }
        $result = json_decode($tmpStr,true);
        if(array_key_exists('errcode',$result)){
            return 0;
        }
        else{
            return $result['access_token'];
        }
    }
    //获取网页授权的token
    function get_web_access_token($appid,$appSecret,$code){
        $rspMsg =array(
            'state'=>0,
            'msg'=>'success'
        );
        $baseUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token?';
        $queryParams = array(
            'appid'=>$appid,
            'secret'=>$appSecret,
            'code'=>$code,
            'grant_type'=>'authorization_code'
        );
        $url = $baseUrl.http_build_query($queryParams);
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_TIMEOUT,10);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        $content = curl_exec($ch);
        if(curl_errno($ch)){
            $rspMsg['state'] = -1;
            $rspMsg['msg'] = curl_error($ch);
            return $rspMsg;
        }
        $result = json_decode($content,true);
        if(array_key_exists('errcode',$result)){
            $rspMsg['state'] = $result['errcode'];
            $rspMsg['msg'] = $result['errmsg'];
            return $rspMsg;
        }
        $rspMsg['msg'] = $result;
        curl_close($ch);
        return $rspMsg;
    }
    //获取用户信息
    function get_user_info($web_access_token,$openid){
            $rspMsg =array(
                'state'=>0,
                'msg'=>'success'
            );
           $parsms = array(
               'access_token'=>$web_access_token,
               'openid'=>$openid,
               'lang'=>'zh_CN'
           );
           $url =  'https://api.weixin.qq.com/sns/userinfo?'.http_build_query($parsms);
           $ch = curl_init();
           curl_setopt($ch,CURLOPT_URL,$url);
           curl_setopt($ch,CURLOPT_HEADER,0);
           curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
           curl_setopt($ch,CURLOPT_TIMEOUT,10);
           curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
           $resText = curl_exec($ch);
           if(curl_errno($ch)){
               $rspMsg['state'] = -2;
               $rspMsg['msg'] = curl_error($ch);
               return $rspMsg;
           }
           $result = json_decode($resText,true);
           if(array_key_exists('errcode',$result)){
            $rspMsg['state'] = $result['errcode'];
            $rspMsg['msg'] = $result['errmsg'];
               return $rspMsg;
           }
           $rspMsg['msg'] = $result;
           curl_close($ch);
           return $rspMsg;
    }
?>