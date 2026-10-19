@extends('layouts.app')

@section('titulo', $convenio->exists ? 'Editar convenio' : 'Nuevo convenio')

@section('contenido')
    <div class="titulo-pagina">
        <h1>{{ $convenio->exists ? 'Editar convenio' : 'Nuevo convenio' }}</h1>
    </div>

    <form method="POST" class="panel" style="max-width: 640px;"
          action="{{ $convenio->exists ? route('convenios.update', $convenio) : route('convenios.store') }}">
        @csrf
        @if ($convenio->exists)
            @method('PUT')
        @endif

        @include('partials.campo', ['nombre' => 'institucion', 'etiqueta' => 'Institución', 'valor' => $convenio->institucion])
        @include('partials.campo', ['nombre' => 'porcentaje_descuento', 'etiqueta' => 'Porcentaje de descuento (0 a 100)',
            'valor' => $convenio->porcentaje_descuento, 'tipo' => 'number', 'paso' => '0.01'])

        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button>
        <a href="{{ route('convenios.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Cancelar</a>
    </form>
@endsection
