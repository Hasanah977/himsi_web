<?php

namespace App\Http\Controllers\KetuaUmum;

use App\Http\Controllers\Administration\PortalController;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class MonitoringOrganisasiController extends Controller
{
    public function index(): View
    {
        return app(PortalController::class)->programs(request());
    }
}
