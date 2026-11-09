@extends('layouts.app')

@section('titulo', 'Editar rol')

@section('contenido')
    <div class="titulo-pagina">
        <h1>Rol {{ $rol->nombre }}</h1>
    </div>

    <form method="POST" action="{{ route('roles.update', $rol) }}" class="panel" style="max-width: 640px;">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre</label>
            <input type="text" id="nombre" class="form-control" value="{{ $rol->nombre }}" disabled>
        </div>

        @include('partials.campo', ['nombre' => 'descripcion', 'etiqueta' => 'Descripción', 'valor' => $rol->descripcion, 'tipo' => 'textarea', 'filas' => 3])

        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button>
        <a href="{{ route('roles.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Cancelar</a>
    </form>
@endsection
