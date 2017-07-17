<?php

namespace App\Http\Controllers;

use App\Events\ExampleEvent;

class PingController extends Controller
{

    public function ping()
    {
        return 'ping is ok';
    }
}
