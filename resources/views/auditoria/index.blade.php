@extends('layouts.app')

@section('titulo', 'Auditoría')

@section('contenido')
    <div class="titulo-pagina">
        <div>
            <h1>Registro de auditoría</h1>
        </div>
    </div>

    <form method="GET" class="panel mb-3 row g-2 align-items-end">
        <div class="col-md-2">
            <label for="tabla" class="form-label">Tabla</label>
            <select id="tabla" name="tabla" class="form-select">
                <option value="">Todas</option>
                @foreach ($tablas as $tabla)
                    <option value="{{ $tabla }}" @selected(($filtros['tabla'] ?? null) === $tabla)>{{ $tabla }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label for="accion" class="form-label">Acción</label>
            <select id="accion" name="accion" class="form-select">
                <option value="">Todas</option>
                @foreach ($acciones as $accion)
                    <option value="{{ $accion }}" @selected(($filtros['accion'] ?? null) === $accion)>{{ $accion }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="usuario_id" class="form-label">Usuario</label>
            <select id="usuario_id" name="usuario_id" class="form-select">
                <option value="">Todos</option>
                @foreach ($usuarios as $usuario)
                    <option value="{{ $usuario->id }}" @selected(($filtros['usuario_id'] ?? null) == $usuario->id)>{{ $usuario->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label for="desde" class="form-label">Desde</label>
            <input type="date" id="desde" name="desde" value="{{ $filtros['desde'] ?? '' }}" class="form-control">
        </div>
        <div class="col-md-2">
            <label for="hasta" class="form-label">Hasta</label>
            <input type="date" id="hasta" name="hasta" value="{{ $filtros['hasta'] ?? '' }}" class="form-control">
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-funnel"></i> Filtrar</button>
        </div>
    </form>

    <div class="panel tabla-contenedor">
        <table class="table table-sm align-middle mb-0">
            <thead>
            <tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Registro</th><th>IP</th><th>Cambios</th></tr>
            </thead>
            <tbody>
            @forelse ($registros as $registro)
                <tr>
                    <td class="mono text-nowrap">{{ $registro->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $registro->usuario?->nombre ?? '—' }}</td>
                    <td><span class="badge text-bg-light">{{ $registro->accion }}</span></td>
                    <td class="mono">{{ $registro->tabla_afectada }}@if ($registro->registro_id) #{{ $registro->registro_id }}@endif</td>
                    <td class="mono small">{{ $registro->ip }}</td>
                    <td>
                        @if ($registro->payload_cambios_json)
                            <code class="small text-break">{{ json_encode($registro->payload_cambios_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</code>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-body-secondary">No hay registros.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $registros->links() }}</div>
@endsection
