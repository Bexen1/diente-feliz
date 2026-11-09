@extends('layouts.app')

@section('titulo', 'Roles y permisos')

@section('contenido')
    <div class="titulo-pagina">
        <h1>Roles y permisos</h1>
    </div>

    <div class="row g-3 mb-3">
        @foreach ($roles as $rol)
            <div class="col-lg-4">
                <div class="panel h-100 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <h2 class="mb-2"><i class="bi bi-shield-lock"></i> {{ $rol->nombre }}</h2>
                        <span class="badge bg-primary-subtle text-primary-emphasis">{{ $rol->usuarios_count }} {{ $rol->usuarios_count === 1 ? 'usuario' : 'usuarios' }}</span>
                    </div>
                    <p class="text-body-secondary flex-grow-1">{{ $rol->descripcion ?: 'Sin descripción.' }}</p>
                    <div>
                        <a href="{{ route('roles.edit', $rol) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Editar descripción</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="panel tabla-contenedor">
        <h2><i class="bi bi-grid-3x3-gap"></i> Matriz de permisos</h2>
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th>Módulo</th>
                @foreach ($roles as $rol)
                    <th>{{ $rol->nombre }}</th>
                @endforeach
            </tr>
            </thead>
            <tbody>
            @foreach ($matriz as $modulo => $permisos)
                <tr>
                    <td class="fw-semibold">{{ $modulo }}</td>
                    @foreach ($roles as $rol)
                        @php($permiso = $permisos[\App\Models\Rol::columnaMatriz($rol->nombre)] ?? '—')
                        <td>
                            @if ($permiso === '—')
                                <span class="text-body-secondary"><i class="bi bi-dash-circle"></i> Sin acceso</span>
                            @else
                                <span class="text-success-emphasis"><i class="bi bi-check-circle"></i> {{ $permiso }}</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
