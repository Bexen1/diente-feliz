@extends('layouts.app')

@section('titulo', 'Pacientes')

@section('contenido')
    <div class="titulo-pagina">
        <h1>Pacientes</h1>
        <a href="{{ route('pacientes.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nuevo paciente</a>
    </div>

    <form method="GET" class="panel mb-3 d-flex gap-2">
        <input type="search" name="buscar" value="{{ $busqueda }}" class="form-control" placeholder="Buscar por nombre o RUT" aria-label="Buscar paciente">
        <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i> Buscar</button>
    </form>

    <div class="panel tabla-contenedor">
        <table class="table align-middle mb-0">
            <thead>
            <tr><th>RUT</th><th>Nombre</th><th>Teléfono</th><th>Convenio</th><th>Estado</th><th></th></tr>
            </thead>
            <tbody>
            @forelse ($pacientes as $paciente)
                <tr>
                    <td class="mono">{{ $paciente->rut }}</td>
                    <td>{{ $paciente->nombre }}</td>
                    <td class="mono">{{ $paciente->telefono }}</td>
                    <td>{{ $paciente->convenio?->institucion ?? 'Particular' }}</td>
                    <td>@include('partials.estado-activo', ['activo' => $paciente->activo])</td>
                    <td class="text-end"><a href="{{ route('pacientes.show', $paciente) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> Ver</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-body-secondary">No se encontraron pacientes.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $pacientes->links() }}</div>
@endsection
