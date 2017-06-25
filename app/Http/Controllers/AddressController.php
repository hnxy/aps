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
        $limit = $request->input('limit', 10);
        $page  = $request->input('page', 1);
        $address = new Address();
        $addrs = $address->mget($limit, $page);
        $addrItems = [];
        foreach ($addrs as $addr) {
            $addrItems[] = $address->getFullAddr($addr->id);
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
            'sex' => 'integer|max:1',
            'name' => 'required|string',
            'phone' => 'required|string|regex:[\d{11}]',
            'province' => 'required|integer|max:820000|min:110000',
            'city' => 'required|integer|max:820100|min:110100',
            'area' => 'required|integer|max:820105|min:110101',
            'detail' => 'required|string|max:256',
        ];
        $this->validate($request, $rules);
        $name = $request->input('name');
        $phone = $request->input('phone');
        $sex = $request->input('sex', 0);
        $provinceId = $request->input('province');
        $cityId = $request->input('city');
        $areaId = $request->input('area');
        $detail = $request->input('detail');
        if(!Address::checkAddrWork($provinceId, $cityId, $areaId)) {
            return '该地址不合法';
        }
        return Address::add([
            'sex'=> $sex,
            'name' => $name,
            'tel' => $phone,
            'province_id' => $provinceId,
            'city_id' => $cityId,
            'area_id' => $areaId,
            'location' => $detail,
            'state' => 1,
            'created_at' => time(),
        ]) !== false ? 0 : 1;;
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
        return $address->getFullAddr($id);
    }
    /**
     * [更新地址信息]
     * @param  Request $request [Request实例]
     * @return [Boolean]           [0表示成功1表示失败]
     */
    public function update(Request $request)
    {
        $rules = [
            'sex' => 'integer|max:1',
            'name' => 'required|string',
            'phone' => 'required|string|regex:[\d{11}]',
            'province' => 'required|integer|max:820000|min:110000',
            'city' => 'required|integer|max:820100|min:110100',
            'area' => 'required|integer|max:820105|min:110101',
            'detail' => 'required|string|max:127',
        ];
        $this->validate($request, $rules);
        $id = $request->route()[2]['id'];
        $name = $request->input('name');
        $phone = $request->input('phone');
        $sex = $request->input('sex', 0);
        $provinceId = $request->input('province');
        $cityId = $request->input('city');
        $areaId = $request->input('area');
        $detail = $request->input('detail');
        if(!Address::checkAddrWork($provinceId, $cityId, $areaId)) {
            return '该地址不合法';
        }
        return Address::modify($id, [
            'sex'=> $sex,
            'name' => $name,
            'tel' => $phone,
            'province_id' => $provinceId,
            'city_id' => $cityId,
            'area_id' => $areaId,
            'location' => $detail,
            'state' => 1,
        ]) !== false ? 0 : 1;
    }
    /**
     * [删除商品信息]
     * @param  Request $request [Request实例]
     * @return [Integer]           [0表示成功1表示失败]
     */
    public function delete(Request $request)
    {
        $id = $request->route()[2]['id'];
        return Address::remove($id) !== false ? 0 : 1;
    }
}
?>