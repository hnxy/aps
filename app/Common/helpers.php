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
    function getCombinePayId($userId, $payId) {
        $str = time();
        $str .= $payId;
        if(strlen($userId) < 4) {
            $str .= sprintf('%04d', $userId);
        } else {
            $str .= substr($userId, -4);
        }
        for($i = 0; $i < 4; $i++) {
            $str .= mt_rand(0,9);
        }
        return $str;
    }
    /**
     * [自定义的curl操作]
     * @param  [String] $url    [请求的URL地址]
     * @param  string $method [请求方式]
     * @return [String|Array]         [执行结果]
     */
    function myCurl($url, $method = 'GET') {
        $ch = curl_init();
        if (strtolower($method) == 'post') {
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
    /**
     * [formatTime description]
     * @param  [type] $timestamp [description]
     * @return [type]            [description]
     */
    function formatTime($timestamp) {
        return date('Y年n月d日 H:i:s', $timestamp);
    }
    /**
     * [formatM description]
     * @param  [type] $timestamp [description]
     * @return [type]            [description]
     */
    function formatM($timestamp) {
        return date('n月d日', $timestamp);
    }
    function formatY($timestamp) {
        return date('Y年n月d日', $timestamp);
    }
    /**
     * [formatD description]
     * @param  [type] $timestamp [description]
     * @return [type]            [description]
     */
    function formatD($timestamp) {
        $diff = $timestamp - time();
        $D =  intval($diff/86400);
        $H = intval($diff%86400/3600);
        return $D.'天'.$H.'小时';
    }
    /**
     * [getGoodsCarIds description]
     * @param  [type] $orderGoodsObj [description]
     * @return [type]                [description]
     */
    function getGoodsCarIds($orderGoodsObj) {
        $goodsCars = [];
        foreach ($orderGoodsObj as $orderGoods) {
            $goodsCars[] = $orderGoods->goods_car_id;
        }
        return $goodsCars;
    }
        /**
     *  post提交数据
     * @param  string $url 请求Url
     * @param  array $datas 提交的数据
     * @return url响应返回的html
     */
    function sendPost($url, $datas) {
        $temps = array();
        foreach ($datas as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);
        }
        $post_data = implode('&', $temps);
        $url_info = parse_url($url);
        if(empty($url_info['port']))
        {
            $url_info['port']=80;
        }
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader.= "Host:" . $url_info['host'] . "\r\n";
        $httpheader.= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader.= "Content-Length:" . strlen($post_data) . "\r\n";
        $httpheader.= "Connection:close\r\n\r\n";
        $httpheader.= $post_data;
        $fd = fsockopen($url_info['host'], $url_info['port']);
        fwrite($fd, $httpheader);
        $gets = "";
        $headerFlag = true;
        while (!feof($fd)) {
            if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
                break;
            }
        }
        while (!feof($fd)) {
            $gets.= fread($fd, 128);
        }
        fclose($fd);

        return $gets;
    }

    /**
     * 电商Sign签名生成
     * @param data 内容
     * @param appkey Appkey
     * @return DataSign签名
     */
    function myEncrypt($data, $appkey) {
        return urlencode(base64_encode(md5($data.$appkey)));
    }
?>