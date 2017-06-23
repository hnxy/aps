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
        return date('Y年n月d日 H:i:s', $timestamp);
    }
    function formatM($timestamp) {
        return date('n月d日', $timestamp);
    }
    function formatD($timestamp) {
        $diff = $timestamp - time();
        $D =  intval($diff/86400);
        $H = intval($diff%86400/3600);
        return $D.'天'.$H.'小时';
    }

    function getFullAddr($province, $city, $area, $address, $addrID) {
        if(!empty($addrID)) {
            $addrDetail = $address->get();
        } else {
            $addrDetail = $address->get($addrID);
        }
        if(empty($addrDetail)) {
            return [
                'state' => 1,
                'msg' => '请填写你的收获地址',
            ];
        }
        // 根据获取的地址的详细信息来获取省,市,县区的名称
        $provinceName = $province->get($addrDetail->province_id)->name;
        $cityName = $city->get($addrDetail->city_id)->name;
        $areaName = $area->get($addrDetail->area_id)->name;
        $fullAddr = $provinceName.$cityName.$areaName.$addrDetail->location;
        return [
                'state' => 0,
                'msg' => [
                    'id' => $addrDetail->id,
                    'name' => $addrDetail->name,
                    'phone' => $addrDetail->tel,
                    'fullAddr' => $fullAddr,
                ],
            ];
    }
    function getAddrId($address, $addrID) {
        if(is_null($addrID)) {
            $addrDetail = $address->get();
        } else {
            $addrDetail = $address->get($addrID);
        }
        if(empty($addrDetail)) {
            return null;
        } else {
            return $addrDetail->id;
        }
    }
    function getPrice($goodsCars, $couponModel, $goods, $value, $key = 'id') {
        $goodsInfos = [];
        $all_price = 0;
        foreach ($goodsCars as $goodsCar) {
            $goodsInfo = $goods->get($goodsCar->goods_id);
            $all_price += $goodsInfo->price*$goodsCar->goods_num;
            $tmp['id'] = $goodsCar->id;
            $tmp['title'] = $goodsInfo->title;
            $tmp['price'] = $goodsInfo->price;
            $tmp['unit'] = $goodsInfo->unit;
            $tmp['num'] = $goodsCar->goods_num;
            $goodsInfos[] = $tmp;
        }
        if(!is_null($value)) {
            $result = checkValue($couponModel, $value, $key);
            if(!empty($result)) {
                $couponValue = $result->price;
                $couponCode = $result->code;
            } else {
                $couponValue = 0;
                $couponCode = null;
            }
        } else {
            $couponValue = 0;
            $couponCode = null;
        }
        $all_price -= $couponValue;
        $send_time = getSendTime($goodsCars, $goods);
        return [
            'coupon_code' => $value,
            'coupon_value' => $couponValue,
            'all_price' => $all_price,
            'send_price' => 0,
            'goods_info' => $goodsInfos,
            'send_time' => $send_time,
        ];
    }
    function checkValue($couponModel, $value, $key) {
        if($key == 'id') {
            $result = $couponModel->validateId($value);
        } else{
            $result = $couponModel->validateCode($value);
        }
        return $result;
    }
    function getSendTime($goodsCars, $goods) {
        $send_time = 99999999999;
        foreach ($goodsCars as $goodsCar) {
            $goodsInfo = $goods->get($goodsCar->goods_id);
            $send_time = min($send_time, $goodsInfo->send_time);
        }
        return formatM($send_time);
    }
    function getGoodsCarIds($orderGoodsObj) {
        $goodsCars = [];
        foreach ($orderGoodsObj as $orderGoods) {
            $goodsCars[] = $orderGoods->goods_car_id;
        }
        return $goodsCars;
    }
?>