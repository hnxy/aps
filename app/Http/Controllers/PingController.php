<?php

namespace App\Http\Controllers;

use App\Events\ExampleEvent;

class PingController extends Controller
{

    public function ping()
    {
        return 'ping is ok';
    }
     /**
     * [notify description]
     * @return [type] [description]
     */
    public function notify()
    {
        $rsp = file_get_contents('php://input');
        var_dump($rsp);
    }
}
