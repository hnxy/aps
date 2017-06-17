<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Models\Goods;
use App\Models\GoodsImg;
use App\Models\GoodsClasses;

class GoodsController extends Controller
{
    /**
     * [获取商品的条目]
     * @param  Request $request [注入Request实例]
     * @return [JsonObject]           [返回一个包含商品条目的json对象]
     */
    public function index(Request $request)
    {
        $rules = [
            'limit' => 'integer|max:100',
            'page' => 'integer',
        ];
        $this->validate($request, $rules);
        $limit = $request->input('limit');
        $page  = $request->input('page');
        $limit = $limit ? $limit : 10;
        $page = $page ? $page : 1;
        $goods = new Goods();
        $goodsClasses= new GoodsClasses();
        return appendArrs($goods->mget($limit,$page), $goodsClasses->mget(), 'category', 'id', 'classes_id');
    }
    /**
     * [获取某个商品的详情]
     * @param  Request $request [注入Request实例]
     * @return [JsonObject]           [返回一个包含商品详细信息和商品轮播的json对象]
     */
    public function show(Request $request)
    {
        $goodsDetail = [];
        $id = (int) $request->route()[2]['id'];
        $goods = new Goods();
        $goodsImgs = new GoodsImg();
        $goodsDetail['detail'] = $goods->getDetail($id);
        $goodsDetail['goods_imgs'] = $goodsImgs->mget($id);
        return response()->json($goodsDetail);
    }
}
?>