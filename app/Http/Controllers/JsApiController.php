<?php


namespace App\Http\Controllers;

use App\Models\Wx;
use Illuminate\Http\Request;

class JsApiController extends Controller
{
    public function getParams(Request $request)
    {
        $wxModel = new Wx();
        $ticket = $wxModel->getTicket();
        $url = $request->input('url', 'http://aps.cg0.me/index.html');
        $time = time();
        $params = [];
        $msg = [
            'noncestr' => getRandomString(10),
            'jsapi_ticket' => $ticket,
            'timestamp' => $time,
            'url' => $url,
        ];
        ksort($msg, SORT_STRING);
        foreach ($msg as $key => $value) {
            $params[] = sprintf("%s=%s", $key, $value);
        }
        $signature = sha1(implode('&', $params));
        return [
            'ticket' => $ticket,
            'appid' => config('wx.appid'),
            'nonceStr' => $msg['noncestr'],
            'timestamp' => $time,
            'signature' => $signature,
        ];
    }
}