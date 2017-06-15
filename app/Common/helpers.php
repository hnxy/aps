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
     * [myCurl description]
     * @param  [type] $url    [description]
     * @param  string $method [description]
     * @return [type]         [description]
     */
    function myCurl($url, $method = 'GET') {
        $ch = curl_init();
        if (strtolower($method) == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        }
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_TIMEOUT,10);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        $resText = curl_exec($ch);
        if (curl_errno($ch)) {
            curl_close($ch);
            return curl_error($ch);
        } else {
            curl_close($ch);
            return json_decode($resText,true);
        }
    }
?>