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
use PHPExcel_IOFactory;

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
            'limit' => 'integer|max:10',
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
        $headers = $this->getHeaders();
        $setting = config('wx.order_excel_table');
        return response()->download($this->buildExcel($orders, uniqid(), $headers));
    }
    private function getHeaders()
    {
        return ['用户id', '用户昵称', '订单id', '订单号', '订单的创建时间', '订单的价格', '订单状态', '商品数量', '商品单位', '用户收货地址', '代理id', '物流号'];
    }
    private function getColMap()
    {
        return ['user_id', 'nickname', 'id', 'order_num', 'created_at', 'all_price', 'order_status', 'goods_num', 'goods_unit', 'address.fullAddr', 'agent_id', 'logistics_code'];
    }
    protected function buildExcel($datas , $filename, array $header, array $setting = [])
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
        for ($i = 0, $len = count($header); $i < $len; $i++) {
            $objActSheet->setCellValue($cellMap[$i] . ($i + 1), $header[$i]);
        }
        foreach ($datas as $data) {
            for ($i = 0, $len = count($colMap); $i < $len; $i++) {
                $indexArr = explode('.', $colMap[$i]);
                $current = obj2arr($data);
                do {
                    $current = $current[current($indexArr)];
                } while(next($indexArr));
                $objActSheet->setCellValue($cellMap[$i] . ($i + 1), $current);
            }
        }
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $dir = config('wx.file_path');
        $file = $dir . '/' . $filename . '.xlsx';
        $objWriter->save($file);
        return $file;
    }
}