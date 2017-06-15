<?php
namespace App\Modles;

class Goods
{
    private static $modle = 'goods';

    /**
     * [mget description]
     * @param  integer $limit [description]
     * @param  integer $page  [description]
     * @return [type]         [description]
     */
    public function mget($limit, $page)
    {
        $goods = app('db')->table(self::$modle)
        ->select(
            ['title','description','origin_price','price','start_time','end_time','goods_img','classes']
        )
        ->offset($page - 1)
        ->limit($limit)
        ->get();
        return (array) $goods;
    }

    /**
     * [getDetaile description]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function getDetaile($id)
    {
        $goods_datail = [];
        $goods = app('db')->table(self::$modle)
        ->where(['id',$id])
        ->select(['detail'])
        ->first();
        $goods_imgs = app('db')->table('goods_img')
        ->where(['goods_id',$id])
        ->select(['goods_imgs'])
        ->first();
        $goods_datail['detail'] = $goods->detail;
        $goods_datail['goods_imgs'] = $goods_imgs->goods_imgs;
        return $goods_datail;
    }
}
?>
