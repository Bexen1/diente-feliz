<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuditoriaFiltroRequest;
use App\Models\AuditoriaLog;
use App\Models\Usuario;

class AuditoriaController extends Controller
{
    public function index(AuditoriaFiltroRequest $request)
    {
        $filtros = $request->validated();

        $registros = AuditoriaLog::with('usuario')
            ->when($filtros['tabla'] ?? null, fn ($q, $tabla) => $q->where('tabla_afectada', $tabla))
            ->when($filtros['accion'] ?? null, fn ($q, $accion) => $q->where('accion', $accion))
            ->when($filtros['usuario_id'] ?? null, fn ($q, $id) => $q->where('usuario_id', $id))
            ->when($filtros['desde'] ?? null, fn ($q, $desde) => $q->whereDate('created_at', '>=', $desde))
            ->when($filtros['hasta'] ?? null, fn ($q, $hasta) => $q->whereDate('created_at', '<=', $hasta))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('auditoria.index', [
            'registros' => $registros,
            'filtros' => $filtros,
            'tablas' => AuditoriaLog::distinct()->orderBy('tabla_afectada')->pluck('tabla_afectada'),
            'acciones' => AuditoriaLog::distinct()->orderBy('accion')->pluck('accion'),
            'usuarios' => Usuario::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }
}
