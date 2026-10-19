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
                        <div class="col-sm-4">
                            <label for="dia_semana" class="form-label">Día</label>
                            <select id="dia_semana" name="dia_semana" class="form-select">
                                @foreach ($dias as $numero => $dia)
                                    <option value="{{ $numero }}" @selected(old('dia_semana') == $numero)>{{ $dia }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-3">
                            <label for="hora_inicio" class="form-label">Desde</label>
                            <input type="time" id="hora_inicio" name="hora_inicio" value="{{ old('hora_inicio', '09:00') }}"
                                   @class(['form-control', 'is-invalid' => $errors->has('hora_inicio')])>
                        </div>
                        <div class="col-sm-3">
                            <label for="hora_fin" class="form-label">Hasta</label>
                            <input type="time" id="hora_fin" name="hora_fin" value="{{ old('hora_fin', '18:00') }}"
                                   @class(['form-control', 'is-invalid' => $errors->has('hora_fin')])>
                        </div>
                        <div class="col-sm-2">
                            <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-plus-lg"></i> Agregar</button>
                        </div>
                        @if ($errors->hasAny(['hora_inicio', 'hora_fin', 'dia_semana']))
                            <div class="col-12 text-danger small">
                                {{ $errors->first('hora_inicio') ?: ($errors->first('hora_fin') ?: $errors->first('dia_semana')) }}
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection
