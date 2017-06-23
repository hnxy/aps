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
            'limit' => 'integer|min:1|max:100',
            'page' => 'integer|min:1',
        ];
        $this->validate($request, $rules);
        $limit = $request->input('limit', 10);
        $page  = $request->input('page', 1);
        $goods = new Goods();
        $goodsClasses= new GoodsClasses();
        $goodses = $this->formatGoodses($goods->mget($limit,$page));
        $goodsClassesSet = $goodsClasses->mget();
        return appendArrs($goodses, $goodsClassesSet, 'category', 'id', 'classes_id');
    }
    private function formatGoodses($goodses)
    {
        foreach ($goodses as &$goods) {
            if( time() < $goods->start_time) {
                $goods->status_text = '即将开售';
                $goods->buy = [
                    'text' => '立即购买',
                    'can_click' => 0,
                    ];
                $goods->car = [
                    'text' => '加入购物车',
                    'can_click' => 0,
                    ];
            } else {
                $goods->status_text = null;
                $goods->buy = [
                    'text' => '立即购买',
                    'can_click' => 1,
                    ];
                $goods->car = [
                    'text' => '加入购物车',
                    'can_click' => 1,
                    ];
            }
            $goods->send_time = formatM($goods->send_time);
            $goods->start_time_text =formatTime($goods->start_time);
            $goods->end_time_text =formatTime($goods->end_time);
            $goods->leave_end_time_text =formatD($goods->end_time);
        }
        unset($goods);
        return $goodses;
    }
    /**
     * [获取某个商品的详情]
     * @param  Request $request [注入Request实例]
     * @return [JsonObject]           [返回一个包含商品详细信息和商品轮播的json对象]
     */
    public function show(Request $request)
    {
        $id = (int) $request->route()[2]['id'];
        $goods = new Goods();
        $goodsImgs = new GoodsImg();
        $goodsClasses = new GoodsClasses();
        $goodsInfo = $goods->getDetail($id);
        if( time() < $goodsInfo->start_time) {
            $goodsInfo->status_text = '即将开售';
            $goodsDetail = [
                'buy' => [
                    'text' => '立即购买',
                    'can_click' => 0,
                    ],
                'car' => [
                    'text' => '加入购物车',
                    'can_click' => 0,
                    ],
                'exp' => [
                    'text' => null,
                    'can_click' => 0,
                    ],
            ];
        } else if(time() > $goodsInfo->end_time) {
            $goodsInfo->status_text = null;
            $goodsDetail = [
                'buy' => [
                    'text' => null,
                    'can_click' => 0,
                    ],
                'car' => [
                    'text' => null,
                    'can_click' => 0,
                    ],
                'exp' => [
                    'text' => '该商品已下价',
                    'can_click' => 0,
                    ],
            ];
        } else {
            $goodsInfo->status_text = null;
            $goodsDetail = [
                'buy' => [
                    'text' => '立即购买',
                    'can_click' => 1,
                    ],
                'car' => [
                    'text' => '加入购物车',
                    'can_click' => 1,
                    ],
                'exp' => [
                    'text' => null,
                    'can_click' => 0,
                    ],
            ];
        }
        $goodsInfo->send_time = formatM($goodsInfo->send_time);
        $goodsInfo->start_time_text = formatTime($goodsInfo->start_time);
        $goodsInfo->end_time_text = formatTime($goodsInfo->end_time);
        $goodsInfo->leave_end_time_text  = formatD($goodsInfo->end_time);
        $goodsDetail['detail'] = $goodsInfo;
        $goodsDetail['goods_imgs'] = array_map(function($img) {
            return $img['goods_img'];
        }, obj2arr($goodsImgs->mget($id)));
        $goodsDetail['category'] = $goodsClasses->get($goodsInfo->id);
        return $goodsDetail;
    }
}
?>