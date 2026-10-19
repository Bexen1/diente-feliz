@extends('layouts.app')

@section('titulo', 'Tratamientos')

@section('contenido')
    <div class="titulo-pagina">
        <h1>Tratamientos</h1>
        <a href="{{ route('tratamientos.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nuevo tratamiento</a>
    </div>

    <div class="panel tabla-contenedor">
        <table class="table align-middle mb-0">
            <thead>
            <tr><th>Nombre</th><th class="text-end">Costo base</th><th>Estado</th><th></th></tr>
            </thead>
            <tbody>
            @forelse ($tratamientos as $tratamiento)
                <tr>
                    <td>{{ $tratamiento->nombre }}</td>
                    <td class="text-end mono">@pesos($tratamiento->costo_base)</td>
                    <td>@include('partials.estado-activo', ['activo' => $tratamiento->activo])</td>
                    <td class="text-end">
                        <a href="{{ route('tratamientos.edit', $tratamiento) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Editar</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-body-secondary">No hay tratamientos registrados.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
