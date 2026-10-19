@extends('layouts.app')

@section('titulo', $sucursal->exists ? 'Editar sucursal' : 'Nueva sucursal')

@section('contenido')
    <div class="titulo-pagina">
        <h1>{{ $sucursal->exists ? 'Editar sucursal' : 'Nueva sucursal' }}</h1>
    </div>

    <form method="POST" class="panel" style="max-width: 640px;"
          action="{{ $sucursal->exists ? route('sucursales.update', $sucursal) : route('sucursales.store') }}">
        @csrf
        @if ($sucursal->exists)
            @method('PUT')
        @endif

        @include('partials.campo', ['nombre' => 'nombre', 'etiqueta' => 'Nombre', 'valor' => $sucursal->nombre])
        @include('partials.campo', ['nombre' => 'direccion', 'etiqueta' => 'Dirección', 'valor' => $sucursal->direccion])

        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button>
        <a href="{{ route('sucursales.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Cancelar</a>
    </form>
@endsection
