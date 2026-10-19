@extends('layouts.app')

@section('titulo', 'Convenios')

@section('contenido')
    <div class="titulo-pagina">
        <h1>Convenios</h1>
        @if (auth()->user()->esAdministrador())
            <a href="{{ route('convenios.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nuevo convenio</a>
        @endif
    </div>

    <div class="panel tabla-contenedor">
        <table class="table align-middle mb-0">
            <thead>
            <tr><th>Institución</th><th class="text-end">Descuento</th><th class="text-end">Pacientes</th><th></th></tr>
            </thead>
            <tbody>
            @forelse ($convenios as $convenio)
                <tr>
                    <td>{{ $convenio->institucion }}</td>
                    <td class="text-end mono">{{ rtrim(rtrim($convenio->porcentaje_descuento, '0'), '.') }} %</td>
                    <td class="text-end">{{ $convenio->pacientes_count }}</td>
                    <td class="text-end text-nowrap">
                        @if (auth()->user()->esAdministrador())
                            <a href="{{ route('convenios.edit', $convenio) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Editar</a>
                            <a href="{{ route('convenios.eliminar', $convenio) }}" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3"></i> Eliminar</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-body-secondary">No hay convenios registrados.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
