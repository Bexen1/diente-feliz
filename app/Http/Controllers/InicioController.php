<?php

namespace App\Http\Controllers;

use App\Services\AlertaService;

class InicioController extends Controller
{
    public function __invoke(AlertaService $alertas)
    {
        return view('inicio', [
            'alertas' => $alertas->paraUsuario(auth()->user()),
        ]);
    }
}
