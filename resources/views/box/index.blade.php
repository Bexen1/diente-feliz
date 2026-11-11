@extends('layouts.app')

@section('titulo', 'Box')

@section('contenido')
    <div class="titulo-pagina">
        <h1>Box de atención</h1>
        @if (auth()->user()->puede('mantenedores.gestionar'))
            <a href="{{ route('box.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nuevo box</a>
        @endif
    </div>

    <div class="panel tabla-contenedor">
        <table class="table align-middle mb-0">
            <thead>
            <tr><th>Nombre</th><th>Sucursal</th><th>Estado</th><th></th></tr>
            </thead>
            <tbody>
            @forelse ($boxes as $box)
                <tr>
                    <td>{{ $box->nombre }}</td>
                    <td>{{ $box->sucursal->nombre }}</td>
                    <td>@include('partials.estado-activo', ['activo' => $box->activo])</td>
                    <td class="text-end">
                        @if (auth()->user()->puede('mantenedores.gestionar'))
                            <a href="{{ route('box.edit', $box) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Editar</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-body-secondary">No hay box registrados.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
