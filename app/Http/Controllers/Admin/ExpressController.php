<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Express;

class ExpressController extends Controller
{
    public function index(Request $request)
    {
        return (new Express())->mget();
    }
}