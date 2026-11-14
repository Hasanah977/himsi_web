<?php

namespace App\Http\Controllers\KetuaUmum;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('ketua-umum.dashboard');
    }
}
