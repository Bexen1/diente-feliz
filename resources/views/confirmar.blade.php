@extends('layouts.app')

@section('titulo', $titulo)

@section('contenido')
    <div class="titulo-pagina">
        <h1>{{ $titulo }}</h1>
    </div>

    <form method="POST" action="{{ $accion }}" class="panel" style="max-width: 640px;">
        @csrf
        @method($metodo)
        @foreach ($ocultos ?? [] as $campo => $valor)
            <input type="hidden" name="{{ $campo }}" value="{{ $valor }}">
        @endforeach

        @foreach ($errors->getMessages() as $campo => $mensajes)
            @if ($campo !== 'motivo')
                <div class="alert alert-danger" role="alert"><i class="bi bi-x-octagon-fill"></i><span>{{ $mensajes[0] }}</span></div>
            @endif
        @endforeach

        <div class="alert alert-warning align-items-center">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>{{ $mensaje }}</span>
        </div>

        <dl class="row mb-3">
            @foreach ($detalles as $detalleEtiqueta => $detalleValor)
                <dt class="col-sm-4 text-body-secondary fw-semibold">{{ $detalleEtiqueta }}</dt>
                <dd class="col-sm-8">{{ $detalleValor }}</dd>
            @endforeach
        </dl>

        @include('partials.campo', ['nombre' => 'motivo', 'etiqueta' => 'Motivo', 'valor' => '', 'tipo' => 'textarea', 'filas' => 3])

        <div class="d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-danger"><i class="bi bi-check2-circle"></i> {{ $boton }}</button>
            <a href="{{ $volver }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> No, volver</a>
        </div>
    </form>
@endsection
