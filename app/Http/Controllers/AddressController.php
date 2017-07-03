<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Models\Address;
use App\Models\Province;
use App\Models\City;
use App\Models\Area;

class AddressController extends Controller
{
    /**
     * [获取地址信息]
     * @param  Request $request [Request实例]
     * @return [Array]           [包含购物车信息的数组]
     */
    public function index(Request $request)
    {
        $rules = [
            'limit' => 'integer|min:1|max:100',
            'page' => 'integer|min:1',
        ];
        $this->validate($request, $rules);
        $user = $request->user;
        $limit = $request->input('limit', 10);
        $page  = $request->input('page', 1);
        $address = new Address();
        $addrs = $address->mget($user->id, $limit, $page);
        $addrItems = [];
        foreach ($addrs as $addr) {
            $addrItems[] = $address->getFullAddr($user->id, $addr->id);
        }
        return $addrItems;
    }
    /**
     * [存贮地址信息]
     * @param  Request $request [Request实例]
     * @return [Integer]           [0表示成功1表示失败]
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string',
            'phone' => 'required|string|regex:[\d{11}]',
            'province' => 'required|integer|max:820000|min:110000',
            'city' => 'required|integer|max:820100|min:110100',
            'area' => 'required|integer|max:659004003|min:110101',
            'detail' => 'required|string|max:256',
        ];
        $this->validate($request, $rules);
        $user = $request->user;
        $name = $request->input('name');
        $phone = $request->input('phone');
        $provinceId = $request->input('province');
        $cityId = $request->input('city');
        $areaId = $request->input('area');
        $detail = $request->input('detail');
        $rsp = config('wx.msg');
        if(!Area::checkAddrWork($provinceId, $cityId, $areaId)) {
            $rsp['state'] = 1;
            $rsp['msg'] = '该地址不合法';
        } else {
            Address::add([
                'name' => $name,
                'tel' => $phone,
                'province_id' => $provinceId,
                'city_id' => $cityId,
                'area_id' => $areaId,
                'location' => $detail,
                'state' => 1,
                'user_id' => $user->id,
                'created_at' => time(),
            ]);
        }
        return $rsp;
    }
    /**
     * [查看地址信息]
     * @param  Request $request [Request实例]
     * @return [Array]           [包含地址信息的数组]
     */
    public function show(Request $request)
    {
        $id = $request->route()[2]['id'];
        $address = new Address();
        return $address->getFullAddr($request->user->id, $id);
    }
    /**
     * [更新地址信息]
     * @param  Request $request [Request实例]
     * @return [Boolean]           [0表示成功1表示失败]
     */
    public function update(Request $request)
    {
        $rules = [
            'name' => 'required|string',
            'phone' => 'required|string|regex:[\d{11}]',
            'province' => 'required|integer|max:820000|min:110000',
            'city' => 'required|integer|max:820100|min:110100',
            'area' => 'required|integer|max:659004003|min:110101',
            'detail' => 'required|string|max:127',
        ];
        $this->validate($request, $rules);
        $id = $request->route()[2]['id'];
        $user = $request->user;
        $name = $request->input('name');
        $phone = $request->input('phone');
        $provinceId = $request->input('province');
        $cityId = $request->input('city');
        $areaId = $request->input('area');
        $detail = $request->input('detail');
        $rsp = config('wx.msg');
        if(!Area::checkAddrWork($provinceId, $cityId, $areaId)) {
            $rsp['state'] = 1;
            $rsp['msg'] = '该地址不合法';
        } else {
            Address::modify([
                'id' => $id,
                'name' => $name,
                'tel' => $phone,
                'province_id' => $provinceId,
                'city_id' => $cityId,
                'area_id' => $areaId,
                'location' => $detail,
                'user_id' => $request->user->id,
                'state' => 1,
            ]);
        }
        return $rsp;
    }
    /**
     * [删除商品信息]
     * @param  Request $request [Request实例]
     * @return [Integer]           [0表示成功1表示失败]
     */
    public function delete(Request $request)
    {
        $id = $request->route()[2]['id'];
        if(Address::remove($request->user->id, $id)) {
             return config('wx.msg');
        }
    }
    /**
     * [设为默认收货地址]
     * @param Request $request [description]
     */
    public function setDefault(Request $request)
    {
        $addressId = $request->route()[2]['id'];
        Address::setDefault($request->user->id, $addressId);
        return config('wx.msg');
    }
}
?>