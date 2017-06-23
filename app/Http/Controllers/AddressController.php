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
    public function index()
    {
         $rules = [
            'limit' => 'integer|min:1|max:100',
            'page' => 'integer|min:1',
        ];
        $this->validate($request, $rules);
        $limit = $request->input('limit', 10);
        $page  = $request->input('page', 1);
        $address = new Address();
        $province = new Province();
        $city = new City();
        $area = new Area();
        $addrs = $address->mget($limit, $page);
        $addrItems = [];
        foreach ($addrs as $addr) {
            $addrItems[] = $address->getFullAddr($$province, $city, $area, $addr->id);
        }
        return $addrItems;
    }
    public function store(Request $request)
    {
        $rules = [
            'sex' => 'integer|max:1',
            'name' => 'required|string',
            'phone' => 'required|string|regex:[\d{11}]',
            'province' => 'required|integer|max:64|min:0',
            'city' => 'required|integer|max:64|min:0',
            'area' => 'required|integer|max:64|min:0',
            'detail' => 'required|string|max:127',
        ];
        $this->validate($request, $rules);
        $name = $request->input('name');
        $phone = $request->input('phone');
        $sex = $request->input('sex', 0);
        $provinceId = $request->input('province');
        $cityId = $request->input('city');
        $areaId = $request->input('area');
        $detail = $request->input('detail');
        $address = new Address();
        $province = new Province();
        $city = new City();
        $area = new Area();
        return $address->add([
            'sex'=> $sex,
            'name' => $name,
            'tel' => $phone,
            'province_id' => $provinceId,
            'city_id' => $cityId,
            'area_id' => $areaId,
            'location' => $detail,
            'state' => 1,
            'created_at' => time(),
        ]);
    }
    public function show(Request $request)
    {
        $id = $request->route()[2]['id'];
        $address = new Address();
        $province = new Province();
        $city = new City();
        $area = new Area();
        return $address->getFullAddr($province, $city, $area, $id);
    }
    public function update()
    {

    }
    public function delete(Request $request)
    {
        $id = $request->route()[2]['id'];
        $address = new Address();
        return $address->remove($id);
    }
}
?>