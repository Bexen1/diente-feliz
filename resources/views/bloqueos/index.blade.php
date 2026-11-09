@extends('layouts.app')

@section('titulo', 'Bloqueos de agenda')

@section('contenido')
    <div class="titulo-pagina">
        <h1>Bloqueos de agenda</h1>
        <a href="{{ route('bloqueos.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nuevo bloqueo</a>
    </div>

    <div class="panel tabla-contenedor mb-3">
        <h2><i class="bi bi-calendar-x"></i> Vigentes y próximos</h2>
        <table class="table align-middle mb-0">
            <thead><tr><th>Período</th><th>Alcance</th><th>Motivo</th><th></th></tr></thead>
            <tbody>
            @forelse ($vigentes as $bloqueo)
                <tr>
                    <td class="mono">{{ $bloqueo->periodo }}</td>
                    <td>{{ $bloqueo->alcance }}</td>
                    <td>{{ $bloqueo->motivo }}</td>
                    <td class="text-end">
                        <a href="{{ route('bloqueos.eliminar', $bloqueo) }}" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3"></i> Eliminar</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-body-secondary">No hay bloqueos vigentes.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if ($pasados->isNotEmpty())
        <div class="panel tabla-contenedor">
            <h2><i class="bi bi-clock-history"></i> Bloqueos anteriores</h2>
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Período</th><th>Alcance</th><th>Motivo</th></tr></thead>
                <tbody>
                @foreach ($pasados as $bloqueo)
                    <tr>
                        <td class="mono">{{ $bloqueo->periodo }}</td>
                        <td>{{ $bloqueo->alcance }}</td>
                        <td>{{ $bloqueo->motivo }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
