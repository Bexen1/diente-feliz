<?php

namespace App\Http\Controllers;

use App\Http\Requests\BloqueoRequest;
use App\Http\Requests\ConfirmacionRequest;
use App\Models\Bloqueo;
use App\Models\Box;
use App\Models\Cita;
use App\Models\Doctor;
use App\Services\AuditoriaService;

class BloqueoController extends Controller
{
    public function index()
    {
        return view('bloqueos.index', [
            'vigentes' => Bloqueo::with(['doctor.usuario', 'box'])
                ->whereDate('fecha_fin', '>=', today())
                ->orderBy('fecha_inicio')
                ->get(),
            'pasados' => Bloqueo::with(['doctor.usuario', 'box'])
                ->whereDate('fecha_fin', '<', today())
                ->orderByDesc('fecha_inicio')
                ->limit(10)
                ->get(),
        ]);
    }

    public function create()
    {
        return view('bloqueos.form', [
            'doctores' => Doctor::with('usuario')->where('activo', true)->get()->sortBy('nombre'),
            'boxes' => Box::with('sucursal')->where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    public function store(BloqueoRequest $request)
    {
        $bloqueo = Bloqueo::create($request->validated());
        AuditoriaService::creado($bloqueo);

        $afectadas = $this->citasAfectadas($bloqueo);
        $mensaje = 'Bloqueo creado.';
        if ($afectadas > 0) {
            $mensaje .= " Hay {$afectadas} citas vigentes en ese período que deben reagendarse.";
        }

        return redirect()->route('bloqueos.index')->with('exito', $mensaje);
    }

    public function confirmarEliminacion(Bloqueo $bloqueo)
    {
        $bloqueo->load(['doctor.usuario', 'box']);

        return view('confirmar', [
            'titulo' => 'Eliminar bloqueo',
            'mensaje' => 'El período volverá a estar disponible para agendar citas.',
            'detalles' => ['Alcance' => $bloqueo->alcance, 'Período' => $bloqueo->periodo, 'Motivo' => $bloqueo->motivo],
            'accion' => route('bloqueos.destroy', $bloqueo),
            'metodo' => 'DELETE',
            'boton' => 'Sí, eliminar el bloqueo',
            'volver' => route('bloqueos.index'),
        ]);
    }

    public function destroy(ConfirmacionRequest $request, Bloqueo $bloqueo)
    {
        $antes = $bloqueo->getAttributes();
        $bloqueo->delete();
        AuditoriaService::registrar('eliminar', 'bloqueos', $bloqueo->id, $antes, ['motivo' => $request->validated('motivo')]);

        return redirect()->route('bloqueos.index')->with('exito', 'Bloqueo eliminado.');
    }

    private function citasAfectadas(Bloqueo $bloqueo): int
    {
        return Cita::whereIn('estado', Cita::ESTADOS_VIGENTES)
            ->whereDate('hora_inicio', '>=', $bloqueo->fecha_inicio)
            ->whereDate('hora_inicio', '<=', $bloqueo->fecha_fin)
            ->when($bloqueo->doctor_id || $bloqueo->box_id, function ($q) use ($bloqueo) {
                $q->where(function ($q) use ($bloqueo) {
                    if ($bloqueo->doctor_id) {
                        $q->orWhere('doctor_id', $bloqueo->doctor_id);
                    }
                    if ($bloqueo->box_id) {
                        $q->orWhere('box_id', $bloqueo->box_id);
                    }
                });
            })
            ->count();
    }
}
