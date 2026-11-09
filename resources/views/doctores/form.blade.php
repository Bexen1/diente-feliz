@extends('layouts.app')

@section('titulo', $doctor->exists ? 'Editar doctor' : 'Nuevo doctor')

@section('contenido')
    <div class="titulo-pagina">
        <h1>{{ $doctor->exists ? $doctor->nombre : 'Nuevo doctor' }}</h1>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <form method="POST" class="panel"
                  action="{{ $doctor->exists ? route('doctores.update', $doctor) : route('doctores.store') }}">
                @csrf
                @if ($doctor->exists)
                    @method('PUT')
                @else
                    <div class="mb-3">
                        <label for="usuario_id" class="form-label">Usuario (rol Dentista)</label>
                        <select id="usuario_id" name="usuario_id" @class(['form-select', 'is-invalid' => $errors->has('usuario_id')])>
                            @forelse ($usuariosDisponibles as $usuario)
                                <option value="{{ $usuario->id }}" @selected(old('usuario_id') == $usuario->id)>{{ $usuario->nombre }} ({{ $usuario->email }})</option>
                            @empty
                                <option value="">No hay usuarios Dentista sin perfil</option>
                            @endforelse
                        </select>
                        @error('usuario_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                @endif

                @include('partials.campo', ['nombre' => 'especialidad', 'etiqueta' => 'Especialidad', 'valor' => $doctor->especialidad])
                @include('partials.activo', ['valor' => $doctor->exists ? $doctor->activo : true, 'etiqueta' => 'Habilitado para agendar'])

                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button>
                <a href="{{ route('doctores.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Volver</a>
            </form>
        </div>

        @if ($doctor->exists)
            <div class="col-lg-7">
                <div class="panel">
                    <h2><i class="bi bi-clock"></i> Bloques de horario</h2>
                    <table class="table align-middle">
                        <thead><tr><th>Día</th><th>Desde</th><th>Hasta</th><th></th></tr></thead>
                        <tbody>
                        @forelse ($doctor->horarios as $horario)
                            <tr>
                                <td>{{ $horario->nombre_dia }}</td>
                                <td class="mono">{{ substr($horario->hora_inicio, 0, 5) }}</td>
                                <td class="mono">{{ substr($horario->hora_fin, 0, 5) }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('doctores.horarios.destroy', [$doctor, $horario]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i> Quitar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-body-secondary">Sin bloques de horario.</td></tr>
                        @endforelse
                        </tbody>
                    </table>

                    <form method="POST" action="{{ route('doctores.horarios.store', $doctor) }}" class="row g-2 align-items-end">
                        @csrf
                        <fieldset class="col-12 mb-1">
                            <legend class="form-label fs-6">Días</legend>
                            @foreach ($dias as $numero => $dia)
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="dias[]" value="{{ $numero }}" id="dia-{{ $numero }}"
                                           @checked(in_array($numero, old('dias', [1, 2, 3, 4, 5])))>
                                    <label class="form-check-label" for="dia-{{ $numero }}">{{ $dia }}</label>
                                </div>
                            @endforeach
                        </fieldset>
                        <div class="col-sm-5">
                            <label for="hora_inicio" class="form-label">Desde</label>
                            <input type="time" id="hora_inicio" name="hora_inicio" value="{{ old('hora_inicio', '09:00') }}"
                                   @class(['form-control', 'is-invalid' => $errors->has('hora_inicio')])>
                        </div>
                        <div class="col-sm-4">
                            <label for="hora_fin" class="form-label">Hasta</label>
                            <input type="time" id="hora_fin" name="hora_fin" value="{{ old('hora_fin', '18:00') }}"
                                   @class(['form-control', 'is-invalid' => $errors->has('hora_fin')])>
                        </div>
                        <div class="col-sm-3">
                            <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-plus-lg"></i> Agregar</button>
                        </div>
                        @if ($errors->hasAny(['hora_inicio', 'hora_fin', 'dias', 'dias.*']))
                            <div class="col-12 text-danger small">
                                {{ $errors->first('hora_inicio') ?: ($errors->first('hora_fin') ?: ($errors->first('dias') ?: $errors->first('dias.*'))) }}
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection
