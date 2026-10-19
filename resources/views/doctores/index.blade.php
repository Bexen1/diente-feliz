@extends('layouts.app')

@section('titulo', 'Doctores')

@section('contenido')
    <div class="titulo-pagina">
        <h1>Doctores y horarios</h1>
        <a href="{{ route('doctores.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nuevo doctor</a>
    </div>

    <div class="panel tabla-contenedor">
        <table class="table align-middle mb-0">
            <thead>
            <tr><th>Nombre</th><th>Especialidad</th><th>Horario semanal</th><th>Estado</th><th></th></tr>
            </thead>
            <tbody>
            @forelse ($doctores as $doctor)
                <tr>
                    <td>{{ $doctor->nombre }}</td>
                    <td>{{ $doctor->especialidad }}</td>
                    <td class="small">
                        @forelse ($doctor->horarios->sortBy('dia_semana') as $horario)
                            {{ $horario->nombre_dia }} <span class="mono">{{ substr($horario->hora_inicio, 0, 5) }}–{{ substr($horario->hora_fin, 0, 5) }}</span>@if (! $loop->last)<br>@endif
                        @empty
                            <span class="text-danger">Sin horario: no se le pueden agendar citas.</span>
                        @endforelse
                    </td>
                    <td>@include('partials.estado-activo', ['activo' => $doctor->activo])</td>
                    <td class="text-end">
                        <a href="{{ route('doctores.edit', $doctor) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Editar</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-body-secondary">No hay doctores registrados.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
