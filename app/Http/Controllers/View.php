<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class View extends Controller
{
    public function index(){

    }
    public function test(){
        return view('test');
    }
}
