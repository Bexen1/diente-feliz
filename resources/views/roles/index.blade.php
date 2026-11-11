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
                        <h2 class="panel-titulo mb-2"><i class="bi bi-shield-lock"></i> {{ $rol->nombre }}</h2>
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

    <form method="POST" action="{{ route('roles.permisos') }}" class="panel tabla-contenedor">
        @csrf
        @method('PUT')
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <h2 class="panel-titulo mb-0"><i class="bi bi-grid-3x3-gap"></i> Matriz de permisos</h2>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar permisos</button>
        </div>
        @error('permisos.*') <div class="alert alert-danger">{{ $message }}</div> @enderror

        <table class="table align-middle mb-0 matriz-permisos">
            <thead>
            <tr>
                <th>Permiso</th>
                @foreach ($roles as $rol)
                    <th class="text-center">{{ $rol->nombre }}</th>
                @endforeach
            </tr>
            </thead>
            <tbody>
            <tr class="matriz-grupo"><th colspan="{{ $roles->count() + 1 }}">Administración del sistema</th></tr>
            <tr>
                <td>Usuarios, roles, permisos, doctores y horarios</td>
                @foreach ($roles as $rol)
                    <td class="text-center">
                        @if ($rol->esAdministrador())
                            <i class="bi bi-lock-fill text-success-emphasis" aria-label="Siempre permitido"></i>
                        @else
                            <i class="bi bi-dash-circle text-body-secondary" aria-label="No disponible"></i>
                        @endif
                    </td>
                @endforeach
            </tr>
            @foreach ($grupos as $grupo => $permisos)
                <tr class="matriz-grupo"><th colspan="{{ $roles->count() + 1 }}">{{ $grupo }}</th></tr>
                @foreach ($permisos as $permiso)
                    <tr>
                        <td>{{ $permiso->nombre }}</td>
                        @foreach ($roles as $rol)
                            <td class="text-center">
                                @if ($rol->esAdministrador())
                                    <i class="bi bi-lock-fill text-success-emphasis" aria-label="Siempre permitido"></i>
                                @else
                                    <input type="checkbox" class="form-check-input" id="permiso-{{ $rol->id }}-{{ $permiso->id }}"
                                           name="permisos[{{ $rol->id }}][]" value="{{ $permiso->clave }}"
                                           aria-label="{{ $permiso->nombre }} para {{ $rol->nombre }}"
                                           @checked($rol->permisos->contains('id', $permiso->id))>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            @endforeach
            </tbody>
        </table>
    </form>
@endsection
