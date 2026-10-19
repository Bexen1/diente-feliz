@extends('layouts.app')

@section('titulo', $presupuesto->exists ? 'Editar presupuesto' : 'Nuevo presupuesto')

@section('contenido')
    <div class="titulo-pagina">
        <h1>{{ $presupuesto->exists ? 'Editar presupuesto N.° '.$presupuesto->id : 'Nuevo presupuesto' }}</h1>
    </div>

    <form method="POST" action="{{ $presupuesto->exists ? route('presupuestos.update', $presupuesto) : route('presupuestos.store') }}">
        @csrf
        @if ($presupuesto->exists)
            @method('PUT')
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="panel mb-3 row g-3">
            <div class="col-md-6">
                <label for="paciente_id" class="form-label">Paciente</label>
                @if ($presupuesto->exists)
                    <input type="text" id="paciente_id" class="form-control" value="{{ $presupuesto->paciente->nombre }}" disabled>
                @else
                    <select id="paciente_id" name="paciente_id" class="form-select" required>
                        <option value="">Seleccione…</option>
                        @foreach ($pacientes as $paciente)
                            <option value="{{ $paciente->id }}" @selected(old('paciente_id', $presupuesto->paciente_id) == $paciente->id)>
                                {{ $paciente->nombre }} · {{ $paciente->rut }}
                                @if ($paciente->convenio) ({{ $paciente->convenio->institucion }}, {{ rtrim(rtrim($paciente->convenio->porcentaje_descuento, '0'), '.') }} %) @endif
                            </option>
                        @endforeach
                    </select>
                @endif
            </div>
            <div class="col-md-6">
                <label for="doctor_id" class="form-label">Doctor tratante</label>
                <select id="doctor_id" name="doctor_id" class="form-select" required>
                    @foreach ($doctores as $doctor)
                        <option value="{{ $doctor->id }}" @selected(old('doctor_id', $presupuesto->doctor_id) == $doctor->id)>{{ $doctor->nombre }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="panel tabla-contenedor mb-3">
            <h2><i class="bi bi-clipboard2-pulse"></i> Tratamientos</h2>
            <table class="table align-middle mb-0">
                <thead><tr><th>Tratamiento</th><th class="text-end">Costo base</th><th style="width: 120px;">Cantidad</th></tr></thead>
                <tbody>
                @foreach ($tratamientos as $tratamiento)
                    <tr>
                        <td><label for="cantidad-{{ $tratamiento->id }}">{{ $tratamiento->nombre }}</label></td>
                        <td class="text-end mono">@pesos($tratamiento->costo_base)</td>
                        <td>
                            <input type="number" min="0" max="99" id="cantidad-{{ $tratamiento->id }}"
                                   name="cantidades[{{ $tratamiento->id }}]" class="form-control form-control-sm mono"
                                   value="{{ old('cantidades.'.$tratamiento->id, $cantidades[$tratamiento->id] ?? 0) }}">
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar presupuesto</button>
        <a href="{{ $presupuesto->exists ? route('presupuestos.show', $presupuesto) : route('presupuestos.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Cancelar</a>
    </form>
@endsection
