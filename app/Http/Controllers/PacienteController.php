<?php

namespace App\Http\Controllers;

use App\Http\Requests\PacienteRequest;
use App\Models\Convenio;
use App\Models\FichaClinica;
use App\Models\Paciente;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PacienteController extends Controller
{
    public function index(Request $request)
    {
        $busqueda = trim((string) $request->query('buscar'));

        $pacientes = Paciente::with('convenio')
            ->when($busqueda !== '', function ($q) use ($busqueda) {
                $rut = str_replace('.', '', $busqueda);
                $q->where(fn ($q) => $q->where('nombre', 'ilike', "%{$busqueda}%")->orWhere('rut', 'ilike', "%{$rut}%"));
            })
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('pacientes.index', compact('pacientes', 'busqueda'));
    }

    public function create()
    {
        return view('pacientes.form', ['paciente' => new Paciente, 'convenios' => Convenio::orderBy('institucion')->get()]);
    }

    public function store(PacienteRequest $request)
    {
        $paciente = DB::transaction(function () use ($request) {
            $paciente = Paciente::create($request->validated());
            FichaClinica::create(['paciente_id' => $paciente->id]);
            AuditoriaService::creado($paciente);

            return $paciente;
        });

        return redirect()->route('pacientes.show', $paciente)->with('exito', 'Paciente registrado.');
    }

    public function show(Paciente $paciente)
    {
        $paciente->load([
            'convenio',
            'citas' => fn ($q) => $q->with(['doctor.usuario', 'box'])->orderByDesc('hora_inicio'),
            'presupuestos' => fn ($q) => $q->orderByDesc('created_at'),
        ]);

        if (auth()->user()->tieneRol('Dentista')) {
            AuditoriaService::registrar('ver', 'pacientes', $paciente->id);
        }

        return view('pacientes.show', compact('paciente'));
    }

    public function edit(Paciente $paciente)
    {
        return view('pacientes.form', ['paciente' => $paciente, 'convenios' => Convenio::orderBy('institucion')->get()]);
    }

    public function update(PacienteRequest $request, Paciente $paciente)
    {
        $original = $paciente->getOriginal();
        $paciente->update($request->validated());
        AuditoriaService::actualizado($paciente, $original);

        return redirect()->route('pacientes.show', $paciente)->with('exito', 'Datos del paciente actualizados.');
    }
}
