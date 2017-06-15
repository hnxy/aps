<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Modles\Goods;

class GoodsController extends Controller
{
    public function getGoodsItems(Request $request)
    {
        $limit = $request->input('limit');
        $page  = $request->input('page');
        $limit = $limit ? $limit : 10;
        $page = $page ? $page : 1;
        $goods = new Goods();
        $rsp = config('error.get_goods_err');
        $goods_items = $goods->mget($limit,$page);
        var_dump($goods_items);
        exit;
        if(empty($goods_items)) {
            $rsp['code'] = 1;
            $rsp['msg'] = 'No goods';
            $rsp['value'] = [];
        } else {
            $rsp['code'] = 0;
            $rsp['msg'] = 'success';
            $rsp['value'] = json_encode($goods_items,true);
        }
        return $rsp;
    }
    public function getGoodsDetail(Request $request)
    {
        $id = $request->input('id');
        $goods = new Goods();
        $rsp = config('error.get_goods_err');
        $goods_detail = $good->getDetail($id);
        if(empty($goods_detail)) {
            $rsp['code'] = 1;
            $rsp['msg'] = 'No goods';
            $rsp['value'] = [];
        } else {
            $rsp['code'] = 0;
            $rsp['msg'] = 'success';
            $rsp['value'] = json_encode($goods_detail,true);
        }
        return $rsp;
    }
}
?>