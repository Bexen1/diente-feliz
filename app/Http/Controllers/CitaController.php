<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgendaFiltroRequest;
use App\Http\Requests\CitaRequest;
use App\Http\Requests\EstadoCitaRequest;
use App\Models\Box;
use App\Models\Cita;
use App\Models\Doctor;
use App\Models\Paciente;
use App\Services\CitaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;

class CitaController extends Controller
{
    public function __construct(private CitaService $citas) {}

    public function index(AgendaFiltroRequest $request)
    {
        $usuario = $request->user();
        $fecha = Carbon::parse($request->validated('fecha') ?? today());

        $doctorId = $usuario->esDentista() ? $usuario->doctor?->id : $request->validated('doctor_id');
        $boxId = $request->validated('box_id');

        $boxes = Box::with('sucursal')->where('activo', true)
            ->when($boxId, fn ($q) => $q->where('id', $boxId))
            ->orderBy('sucursal_id')->orderBy('nombre')->get();

        $citasDelDia = Cita::with(['paciente', 'doctor.usuario', 'box'])
            ->whereDate('hora_inicio', $fecha)
            ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))
            ->when($boxId, fn ($q) => $q->where('box_id', $boxId))
            ->orderBy('hora_inicio')
            ->get();

        return view('citas.index', [
            'fecha' => $fecha,
            'boxes' => $boxes,
            'citas' => $citasDelDia,
            'doctores' => Doctor::with('usuario')->where('activo', true)->get()->sortBy('nombre'),
            'todosLosBox' => Box::where('activo', true)->orderBy('nombre')->get(),
            'pacientes' => Paciente::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'rut']),
            'filtroDoctor' => $doctorId,
            'filtroBox' => $boxId,
            'franjas' => $this->franjas(),
        ]);
    }

    public function store(CitaRequest $request)
    {
        Gate::authorize('agendarPara', [Cita::class, (int) $request->validated('doctor_id')]);

        $cita = $this->citas->agendar($request->validated());

        return redirect()->route('citas.index', ['fecha' => $cita->hora_inicio->toDateString()])
            ->with('exito', 'Cita agendada para '.$cita->paciente->nombre.' a las '.$cita->hora_inicio->format('H:i').'.');
    }

    public function edit(Cita $cita)
    {
        Gate::authorize('modificar', $cita);

        return view('citas.edit', [
            'cita' => $cita->load(['paciente', 'doctor.usuario']),
            'doctores' => Doctor::with('usuario')->where('activo', true)->get()->sortBy('nombre'),
            'boxes' => Box::where('activo', true)->orderBy('nombre')->get(),
            'franjas' => $this->franjas(),
        ]);
    }

    public function update(CitaRequest $request, Cita $cita)
    {
        Gate::authorize('modificar', $cita);
        Gate::authorize('agendarPara', [Cita::class, (int) $request->validated('doctor_id')]);

        $this->citas->reagendar($cita, $request->validated());

        return redirect()->route('citas.index', ['fecha' => $cita->hora_inicio->toDateString()])
            ->with('exito', 'Cita reagendada.');
    }

    public function cambiarEstado(EstadoCitaRequest $request, Cita $cita)
    {
        $this->citas->cambiarEstado($cita, $request->validated('estado'), $request->validated('motivo'));

        return redirect()->route('citas.index', ['fecha' => $cita->hora_inicio->toDateString()])
            ->with('exito', 'Cita marcada como '.$request->validated('estado').'.');
    }

    public function confirmarCancelacion(Cita $cita)
    {
        Gate::authorize('modificar', $cita);
        $cita->load(['paciente', 'doctor.usuario', 'box']);

        return view('confirmar', [
            'titulo' => 'Cancelar cita',
            'mensaje' => 'La cita quedará cancelada y el horario se liberará para otros pacientes.',
            'detalles' => [
                'Paciente' => $cita->paciente->nombre,
                'Fecha y hora' => $cita->hora_inicio->format('d/m/Y H:i').' a '.$cita->hora_fin->format('H:i'),
                'Dentista' => $cita->doctor->nombre,
                'Box' => $cita->box->nombre,
            ],
            'accion' => route('citas.estado', $cita),
            'metodo' => 'PATCH',
            'ocultos' => ['estado' => 'cancelada'],
            'boton' => 'Sí, cancelar la cita',
            'volver' => route('citas.index', ['fecha' => $cita->hora_inicio->toDateString()]),
        ]);
    }

    private function franjas(): array
    {
        $franjas = [];
        $hora = Carbon::parse(CitaService::HORA_APERTURA);
        $cierre = Carbon::parse(CitaService::HORA_CIERRE);

        while ($hora < $cierre) {
            $franjas[] = $hora->format('H:i');
            $hora->addMinutes(15);
        }

        return $franjas;
    }
}
