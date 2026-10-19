@extends('layouts.app')

@section('titulo', $tratamiento->exists ? 'Editar tratamiento' : 'Nuevo tratamiento')

@section('contenido')
    <div class="titulo-pagina">
        <h1>{{ $tratamiento->exists ? 'Editar tratamiento' : 'Nuevo tratamiento' }}</h1>
    </div>

    <form method="POST" class="panel" style="max-width: 640px;"
          action="{{ $tratamiento->exists ? route('tratamientos.update', $tratamiento) : route('tratamientos.store') }}">
        @csrf
        @if ($tratamiento->exists)
            @method('PUT')
        @endif

        @include('partials.campo', ['nombre' => 'nombre', 'etiqueta' => 'Nombre', 'valor' => $tratamiento->nombre])
        @include('partials.campo', ['nombre' => 'costo_base', 'etiqueta' => 'Costo base (CLP)',
            'valor' => $tratamiento->costo_base !== null ? (int) $tratamiento->costo_base : null, 'tipo' => 'number', 'paso' => '1'])
        @include('partials.activo', ['valor' => $tratamiento->exists ? $tratamiento->activo : true])

        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button>
        <a href="{{ route('tratamientos.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Cancelar</a>
    </form>
@endsection
