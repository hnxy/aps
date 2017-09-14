<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\Goods;
use App\Models\Agent;
use App\Models\Order;
use App\Models\Wx;
use App\Models\GoodsCar;
use App\Execptions\ApiException;
use App\Http\Controllers\Controller;
use PHPExcel;
use PHPExcel_Writer_Excel2007;

class OrderController extends Controller
{
    /**
     * [商家获取订单]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function index(Request $request)
    {
        $rules = [
            'created_at_start' => 'required|date',
            'created_at_end' => 'required|date',
            'status' => 'integer|max:5|min:1',
            'limit' => 'integer|max:10000',
            'page' => 'integer',
        ];
        $this->validate($request, $rules);
        $rsp = config('error.success');
        $orderModel = new Order();
        $status = $request->input('status', 2);
        $start = strtotime($request->input('created_at_start'));
        $end = strtotime($request->input('created_at_end'));
        $rsp['code'] = 0;
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);
        $orders = $orderModel->mgetByTimeWithStatus($start, $end, $status, $limit, $page);
        $rsp['items'] = $orderModel->getOrdersInfoByAdmin($orders);
        $rsp['num'] = count($rsp['items']);
        $totel = $orderModel->getAllBetweenTimeWithStatus($start, $end, $status);
        $rsp['totel'] = $totel;
        $rsp['pages'] =  intval($totel/$limit) + ($totel % $limit == 0 ? 0 : 1);
        return $rsp;
    }
    /**
     * [获取某个订单]
     * @param  Request $request [description]
     * @param  [type]  $agent   [description]
     * @param  [type]  $orderId [description]
     * @return [type]           [description]
     */
    public function show(Request $request, $admin, $orderNum)
    {
        $orderModel = new Order();
        $order = $orderModel->getByOrderNum($orderNum);
        if (!$orderModel->isExist($order)) {
            return config('error.order_not_exist');
        }
        return $orderModel->getOrderInfoByAdmin($order);
    }
    /**
     * [添加物流号]
     * @param Request $request [description]
     */
    public function update(Request $request)
    {
        $rules = [
            'order_ids' => 'required|string',
            'express_id' => 'integer',
            'logistics_code' => 'string|max:32',
        ];
        $this->validate($request, $rules);
        $rsp = config('error.success');
        $orderModel = new Order();
        $orderIds = explode(',', $request->input('order_ids'));
        array_pop($orderIds);
        $orders = $orderModel->getByIds($orderIds);
        if (count(obj2arr($orders)) != count($orderIds)) {
            throw new ApiException(config('error.contain_order_not_work_exception.msg'), config('error.contain_order_not_work_exception.code'));
        }
        $orderArr = $request->all();
        array_filter($orderArr);
        $orderArr['status'] = 3;
        $orderModel->mModify($orderIds, $orderArr);
        return $rsp;
    }
    public function loadExcel(Request $request)
    {
        $orders = $this->index($request)['items'];
        $setting = config('wx.order_excel_config');
        $filename = getFilename();
        return response()->download($this->buildExcel($orders, $filename, $setting));
    }
    private function getColMap()
    {
        return ['user_id' => '用户id', 'nickname' => '用户昵称', 'id' => '订单id', 'order_num' => '订单号', 'created_at' => '订单的创建时间', 'all_price' => '订单的价格', 'order_status' => '订单状态', 'goods_num' => '商品数量', 'goods_unit' => '商品单位', 'address.fullAddr' => '用户收货地址', 'address.name' => '收货人姓名', 'address.phone' => '收货人电话', 'agent_id' => '代理id', 'agent.name' => '代理名称', 'agent.phone' => '代理的电话', 'agent.level' => '代理的等级', 'logistics_code' => '物流号'];
    }
    protected function buildExcel($datas , $filename, array $setting = [])
    {
        $objPHPExcel = new PHPExcel();
        $colMap = $this->getColMap();
        $cellMap = getExcelCellMap();
        $objPHPExcel->getProperties()->setCreator(isset($setting['creator']) ? $setting['creator'] : '')
                                     ->setLastModifiedBy(isset($setting['lastModifiedBy']) ? $setting['lastModifiedBy'] : '')
                                     ->setTitle(isset($setting['title']) ? $setting['title'] : '')
                                     ->setSubject(isset($setting['subject']) ? $setting['subject'] : '')
                                     ->setDescription(isset($setting['description']) ? $setting['description'] : '');
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0);
        $header = array_values($colMap);
        $keys = array_keys($colMap);
        $index = 1;
        for ($i = 0, $len = count($header); $i < $len; $i++) {
            $objActSheet->setCellValue($cellMap[$i] . $index, $header[$i]);
        }
        $index++;
        foreach ($datas as $data) {
            foreach ($keys as $i => $key) {
                $indexArr = explode('.', $key);
                $current = obj2arr($data);
                do {
                    $current = $current[current($indexArr)];
                } while(next($indexArr));
                $objActSheet->setCellValue($cellMap[$i] . $index, $current);
            }
            $index++;
        }
        $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
        $dir = config('wx.file_path');
        $file = $dir . '/' . $filename . '.xlsx';
        $objWriter->save($file);
        return $file;
    }
}