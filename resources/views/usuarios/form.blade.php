@extends('layouts.app')

@section('titulo', $usuario->exists ? 'Editar usuario' : 'Nuevo usuario')

@section('contenido')
    @php($esUnoMismo = $usuario->exists && $usuario->is(auth()->user()))

    <div class="titulo-pagina">
        <h1>{{ $usuario->exists ? 'Editar usuario' : 'Nuevo usuario' }}</h1>
    </div>

    <form method="POST" class="panel" style="max-width: 640px;"
          action="{{ $usuario->exists ? route('usuarios.update', $usuario) : route('usuarios.store') }}">
        @csrf
        @if ($usuario->exists)
            @method('PUT')
        @endif

        @include('partials.campo', ['nombre' => 'nombre', 'etiqueta' => 'Nombre', 'valor' => $usuario->nombre])
        @include('partials.campo', ['nombre' => 'email', 'etiqueta' => 'Correo', 'valor' => $usuario->email, 'tipo' => 'email'])

        <div class="mb-3">
            <label for="rol_id" class="form-label">Rol</label>
            <select id="rol_id" name="rol_id" @disabled($esUnoMismo) @class(['form-select', 'is-invalid' => $errors->has('rol_id')])>
                @foreach ($roles as $rol)
                    <option value="{{ $rol->id }}" @selected(old('rol_id', $usuario->rol_id) == $rol->id)>{{ $rol->nombre }}</option>
                @endforeach
            </select>
            @if ($esUnoMismo)
                <input type="hidden" name="rol_id" value="{{ $usuario->rol_id }}">
            @endif
            @error('rol_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        @if ($esUnoMismo)
            <input type="hidden" name="activo" value="1">
        @else
            @include('partials.activo', ['valor' => $usuario->exists ? $usuario->activo : true, 'etiqueta' => 'Cuenta activa'])
        @endif

        <hr>
        @include('partials.campo', ['nombre' => 'password', 'etiqueta' => $usuario->exists ? 'Nueva contraseña' : 'Contraseña', 'tipo' => 'password'])
        @include('partials.campo', ['nombre' => 'password_confirmation', 'etiqueta' => 'Repetir contraseña', 'tipo' => 'password'])

        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button>
        <a href="{{ route('usuarios.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Cancelar</a>
    </form>
@endsection
