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
        if(empty($goodsInfo)) {
            throw new ApiException('该商品已过期,请选择其他商品',config('error.goods_expire_err.code'));
        }
        if( time() < $goodsInfo->start_time) {
            $goodsDetail = [
                'status_text' => '即将开售',
                'buy' => [
                    'text' => '立即购买',
                    'can_click' => 0,
                    ],
                'car' => [
                    'text' => '加入购物车',
                    'can_click' => 0,
                    ],
            ];
        } else {
            $goodsDetail = [
                'status_text' => null,
                'buy' => [
                    'text' => '立即购买',
                    'can_click' => 0,
                    ],
                'car' => [
                    'text' => '加入购物车',
                    'can_click' => 0,
                    ],
            ];
        }
        $goodsDetail['detail'] = $goodsInfo;
        $goodsDetail['start_time_text'] = formatTime($goodsInfo->start_time);
        $goodsDetail['end_time_text'] = formatTime($goodsInfo->end_time);
        $goodsDetail['leave_end_time_text'] = formatD($goodsInfo->end_time);
        $goodsDetail['goods_imgs'][] = $goodsImgs->mget($id);
        $goodsDetail['category'] = $goodsClasses->get($goodsInfo->id);
        return $goodsDetail;
    }
}
?>