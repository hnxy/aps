<?php

namespace App\Http\Controllers;

use App\Events\ExampleEvent;
use Illuminate\Http\Request;

class PingController extends Controller
{

    public function ping(Request $request)
    {
        return 'ping is ok';
    }
}
