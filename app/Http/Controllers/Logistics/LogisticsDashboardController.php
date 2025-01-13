<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LogisticsDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('logistics/dashboard');
    }

    public function view(){
        return view('logistics/dashboard');
    }
}
