<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ACSController extends Controller
{
    public function index()
    {
        return view('acs.index', [
            'title' => 'ACS',
            'icon'  => 'fa fa-cogs'
        ]);
    }
}
