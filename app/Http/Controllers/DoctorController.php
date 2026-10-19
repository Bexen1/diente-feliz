<?php

namespace App\Http\Controllers;

use App\Http\Requests\DoctorRequest;
use App\Models\Doctor;
use App\Models\Horario;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\AuditoriaService;

class DoctorController extends Controller
{
    public function index()
    {
        return view('doctores.index', [
            'doctores' => Doctor::with(['usuario', 'horarios'])->get()->sortBy('nombre'),
        ]);
    }

    public function create()
    {
        return view('doctores.form', [
            'doctor' => new Doctor,
            'usuariosDisponibles' => $this->dentistasSinPerfil(),
        ]);
    }

    public function store(DoctorRequest $request)
    {
        $doctor = Doctor::create($request->validated());
        AuditoriaService::creado($doctor);

        return redirect()->route('doctores.edit', $doctor)
            ->with('exito', 'Doctor creado. Ahora agregue sus bloques de horario.');
    }

    public function edit(Doctor $doctor)
    {
        $doctor->load(['usuario', 'horarios' => fn ($q) => $q->orderBy('dia_semana')->orderBy('hora_inicio')]);

        return view('doctores.form', ['doctor' => $doctor, 'dias' => Horario::DIAS]);
    }

    public function update(DoctorRequest $request, Doctor $doctor)
    {
        $original = $doctor->getOriginal();
        $doctor->update($request->safe()->only(['especialidad', 'activo']));
        AuditoriaService::actualizado($doctor, $original);

        return redirect()->route('doctores.index')->with('exito', 'Doctor actualizado.');
    }

    private function dentistasSinPerfil()
    {
        return Usuario::whereHas('rol', fn ($q) => $q->where('nombre', Rol::DENTISTA))
            ->whereDoesntHave('doctor')
            ->orderBy('nombre')
            ->get();
    }
}
