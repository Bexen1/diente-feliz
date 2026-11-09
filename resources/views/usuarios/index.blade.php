@extends('layouts.app')

@section('titulo', 'Usuarios')

@section('contenido')
    <div class="titulo-pagina">
        <h1>Usuarios</h1>
        <a href="{{ route('usuarios.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nuevo usuario</a>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="panel tabla-contenedor">
                <table class="table align-middle mb-0">
                    <thead>
                    <tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th></th></tr>
                    </thead>
                    <tbody>
                    @foreach ($usuarios as $usuario)
                        <tr>
                            <td>{{ $usuario->nombre }}</td>
                            <td>{{ $usuario->email }}</td>
                            <td>{{ $usuario->rol->nombre }}</td>
                            <td>@include('partials.estado-activo', ['activo' => $usuario->activo])</td>
                            <td class="text-end">
                                <a href="{{ route('usuarios.edit', $usuario) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Editar</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="panel">
                <h2><i class="bi bi-shield-lock"></i> Roles</h2>
                <ul class="list-group">
                    @foreach ($roles as $rol)
                        <li class="list-group-item d-flex justify-content-between">
                            {{ $rol->nombre }} <span class="badge bg-primary-subtle text-primary-emphasis">{{ $rol->usuarios_count }} {{ $rol->usuarios_count === 1 ? 'usuario' : 'usuarios' }}</span>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('roles.index') }}" class="btn btn-sm btn-outline-primary mt-3"><i class="bi bi-grid-3x3-gap"></i> Ver matriz de permisos</a>
            </div>
        </div>
    </div>
@endsection
