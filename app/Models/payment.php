<?php

namespace App\Models;

use App\Models\Db\Payment as DbPayment;

class Payment extends Model
{
    public static $model = "Payment";

    public function get($id)
    {
        return DbPayment::get(['where' => ['pay_id' => $id] ]);
    }
    public function payEnable($id)
    {
        $payment = $this->get($id);
        if (empty($payment) || $payment->enabled == 0) {
            return false;
        }
        return true;
    }
}