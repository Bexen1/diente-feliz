<?php

namespace App\Http\Controllers;

use App\Http\Requests\MesRequest;
use App\Services\DashboardService;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __invoke(MesRequest $request, DashboardService $dashboard)
    {
        $mes = $request->validated('mes') ? Carbon::createFromFormat('Y-m', $request->validated('mes')) : now();

        return view('dashboard', [
            'mes' => $mes,
            'datos' => $dashboard->indicadores($mes),
        ]);
    }
}
