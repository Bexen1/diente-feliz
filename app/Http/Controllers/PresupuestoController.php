<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmacionRequest;
use App\Http\Requests\PresupuestoFiltroRequest;
use App\Http\Requests\PresupuestoRequest;
use App\Models\Doctor;
use App\Models\Paciente;
use App\Models\Presupuesto;
use App\Models\Tratamiento;
use App\Services\PresupuestoService;

class PresupuestoController extends Controller
{
    public function __construct(private PresupuestoService $servicio) {}

    public function index(PresupuestoFiltroRequest $request)
    {
        $estado = $request->validated('estado');

        $presupuestos = Presupuesto::with(['paciente', 'doctor.usuario'])
            ->withSum(['pagos as pagado' => fn ($q) => $q->where('estado', 'vigente')], 'monto')
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('presupuestos.index', compact('presupuestos', 'estado'));
    }

    public function create(PresupuestoFiltroRequest $request)
    {
        return view('presupuestos.form', [
            'presupuesto' => new Presupuesto(['paciente_id' => $request->validated('paciente_id')]),
            'pacientes' => Paciente::with('convenio')->where('activo', true)->orderBy('nombre')->get(),
            'doctores' => Doctor::with('usuario')->where('activo', true)->get()->sortBy('nombre'),
            'tratamientos' => Tratamiento::where('activo', true)->orderBy('nombre')->get(),
            'cantidades' => [],
        ]);
    }

    public function store(PresupuestoRequest $request)
    {
        $presupuesto = $this->servicio->crear($request->validated());

        return redirect()->route('presupuestos.show', $presupuesto)->with('exito', 'Presupuesto creado en borrador.');
    }

    public function show(Presupuesto $presupuesto)
    {
        return view('presupuestos.show', ['presupuesto' => $this->cargar($presupuesto)]);
    }

    public function edit(Presupuesto $presupuesto)
    {
        if ($presupuesto->estado !== 'borrador') {
            return redirect()->route('presupuestos.show', $presupuesto)->with('error', 'Solo se edita un presupuesto en borrador.');
        }

        $presupuesto->load(['paciente.convenio', 'detalles']);

        return view('presupuestos.form', [
            'presupuesto' => $presupuesto,
            'pacientes' => collect([$presupuesto->paciente]),
            'doctores' => Doctor::with('usuario')->where('activo', true)->get()->sortBy('nombre'),
            'tratamientos' => Tratamiento::where('activo', true)
                ->orWhereIn('id', $presupuesto->detalles->pluck('tratamiento_id'))
                ->orderBy('nombre')->get(),
            'cantidades' => $presupuesto->detalles->pluck('cantidad', 'tratamiento_id')->all(),
        ]);
    }

    public function update(PresupuestoRequest $request, Presupuesto $presupuesto)
    {
        $this->servicio->actualizar($presupuesto, $request->validated());

        return redirect()->route('presupuestos.show', $presupuesto)->with('exito', 'Presupuesto actualizado.');
    }

    public function aceptar(Presupuesto $presupuesto)
    {
        $this->servicio->aceptar($presupuesto);

        return back()->with('exito', 'Presupuesto aceptado. El total quedó fijo y ya puede recibir pagos.');
    }

    public function confirmarAnulacion(Presupuesto $presupuesto)
    {
        $presupuesto->load('paciente');

        return view('confirmar', [
            'titulo' => 'Anular presupuesto N.° '.$presupuesto->id,
            'mensaje' => 'El presupuesto quedará anulado: no se podrá editar ni recibir pagos.',
            'detalles' => [
                'Paciente' => $presupuesto->paciente->nombre,
                'Estado actual' => ucfirst($presupuesto->estado),
                'Total' => '$'.number_format((float) $presupuesto->total_calculado, 0, ',', '.'),
            ],
            'accion' => route('presupuestos.anular', $presupuesto),
            'metodo' => 'PATCH',
            'boton' => 'Sí, anular el presupuesto',
            'volver' => route('presupuestos.show', $presupuesto),
        ]);
    }

    public function anular(ConfirmacionRequest $request, Presupuesto $presupuesto)
    {
        $this->servicio->anular($presupuesto, $request->validated('motivo'));

        return redirect()->route('presupuestos.show', $presupuesto)->with('exito', 'Presupuesto anulado.');
    }

    public function imprimir(Presupuesto $presupuesto)
    {
        return view('presupuestos.imprimir', ['presupuesto' => $this->cargar($presupuesto)]);
    }

    private function cargar(Presupuesto $presupuesto): Presupuesto
    {
        return $presupuesto->load([
            'paciente.convenio',
            'doctor.usuario',
            'detalles.tratamiento',
            'pagos' => fn ($q) => $q->orderBy('fecha')->orderBy('id'),
        ]);
    }
}
