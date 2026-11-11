@extends('layouts.app')

@section('titulo', 'Sucursales')

@section('contenido')
    <div class="titulo-pagina">
        <h1>Sucursales</h1>
        @if (auth()->user()->puede('mantenedores.gestionar'))
            <a href="{{ route('sucursales.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nueva sucursal</a>
        @endif
    </div>

    <div class="panel tabla-contenedor">
        <table class="table align-middle mb-0">
            <thead>
            <tr><th>Nombre</th><th>Dirección</th><th>Box</th><th></th></tr>
            </thead>
            <tbody>
            @forelse ($sucursales as $sucursal)
                <tr>
                    <td>{{ $sucursal->nombre }}</td>
                    <td>{{ $sucursal->direccion }}</td>
                    <td>{{ $sucursal->boxes_count }}</td>
                    <td class="text-end text-nowrap">
                        @if (auth()->user()->puede('mantenedores.gestionar'))
                            <a href="{{ route('sucursales.edit', $sucursal) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Editar</a>
                            <a href="{{ route('sucursales.eliminar', $sucursal) }}" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3"></i> Eliminar</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-body-secondary">No hay sucursales registradas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
