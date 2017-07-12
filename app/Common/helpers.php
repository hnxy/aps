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
     * [自定义的curl操作]
     * @param  [String] $url    [请求的URL地址]
     * @param  string $method [请求方式]
     * @return [String|Array]         [执行结果]
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

    function obj2arr($obj) {
        return json_decode(json_encode($obj),TRUE);
    }
    /**
     * [添加映射]
     * @param  [Object] $arrs [需要映射的对象]
     * @param  string $key  [映射的字段]
     * @return [Array]       [映射后的数组]
     */
    function getMap($arrs, $key = 'id') {
        $map = [];
        foreach ($arrs as $arr) {
            $map[$arr->$key] = $arr;
        }
        return $map;
    }
    /**
     * [将一个对象的值映射到另一个对象]
     * @param  [Object] $arrs       [映射的对象]
     * @param  [Object] $appendArrs [被映射的对象]
     * @param  string $name       [映射后的字段名]
     * @param  string $appendKey  [被映射对象的字段名]
     * @param  string $key        [映射对象的字段名]
     * @return [Object]             [商品对象]
     */
    function appendArrs($arrs, $appendArrs, $name = 'append', $appendKey = 'id', $key = 'id') {
        $map = getMap($appendArrs, $appendKey);
        foreach ($arrs as &$arr) {
            $arr->$name = empty($map[$arr->$key]) ? [] : $map[$arr->$key];
        }
        unset($arr);
        return $arrs;
    }
    function formatTime($timestamp) {
        return date('Y年:m月:d日 H:i:s');
    }
    function formatD($timestamp) {
        $diff = $timestamp - time();
        $D =  intval($diff/86400);
        $H = intval($diff%86400/3600);
        return $D.'天'.$H.'小时';
    }
?>