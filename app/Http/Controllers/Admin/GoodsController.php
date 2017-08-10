<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\FileUpload\Upload;
use App\Models\Goods;
use App\Models\GoodsImg;
use App\Http\Controllers\Controller;
use App\Exceptions\ApiException;

class GoodsController extends Controller
{
    /**
     * [发布新的商品]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function store(Request $request)
    {
        $rules = [
            'title' => 'required|string|max:256',
            'description' => 'required|string|max:256',
            'origin_price' => 'required|numeric',
            'price' => 'required|numeric',
            'start_time' => 'required|date',
            'end_time' => 'required|date',
            'goods_img' => 'required|string',
            'detail' => 'required|string',
            'classes_id' => 'integer',
            'unit' => 'required|string',
            'send_time' => 'required|date',
            'timespace' => 'required|integer',
            'stock' => 'required|integer',
        ];
        $this->validate($request, $rules);
        $goodsModel = new Goods();
        $goodsInfo = $request->all();
        if(!array_key_exists('classes_id', $goodsInfo)) {
            $goodsInfo['classes_id'] = 0;
        }
        $goodsInfo['start_time'] = strtotime($goodsInfo['start_time']);
        $goodsInfo['end_time'] = strtotime($goodsInfo['end_time']);
        $goodsInfo['send_time'] = strtotime($goodsInfo['send_time']);
        $goodsInfo['create_time'] = time();
        $goodsModel->add($goodsInfo);
        return config('error.success');
    }
    public function saveImg(Request $request, Upload $upload, $admin, $goodsId)
    {
        $goodsModel = new Goods();
        $goodsImgModel = new GoodsImg();
        if (!$goodsModel->has($goodsId)) {
            throw new ApiException(config('error.goods_empty_exception.msg'), config('error.goods_empty_exception.code'));
        }
        $imgs = $upload->save('image');
        if (count($imgs) != count($imgs, 1)) {
            $arr = [];
            foreach ($imgs as &$img) {
                $fullname = 'http://' . config('wx.host') . config('wx.image_visit_path') . '/goods/' . $img['filename'];
                $arr[] = [
                    'goods_id' => $goodsId,
                    'goods_img' => $fullname,
                ];
                $img['visit_path'] = $fullname;
            }
            // $goodsImgModel->add($arr);
        } else {
            $fullname = 'http://' . config('wx.host') . config('wx.image_visit_path') . '/goods/' . $img['filename'];
            // $goodsImgModel->add([
            //     'goods_id' => $goodsId,
            //     'goods_img' => $fullname,
            // ]);
            $imgs['visit_path'] = $fullname;
        }
        return $imgs;
    }
}