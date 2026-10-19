@extends('layouts.app')

@section('titulo', $paciente->nombre)

@section('contenido')
    @php($usuario = auth()->user())

    <div class="titulo-pagina">
        <div>
            <h1>{{ $paciente->nombre }}</h1>
            <small class="text-body-secondary mono">{{ $paciente->rut }}</small>
            @unless ($paciente->activo) <span class="badge text-bg-secondary ms-2">Inactivo</span> @endunless
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if ($usuario->tieneRol('Dentista') && Route::has('fichas.show'))
                <a href="{{ route('fichas.show', $paciente) }}" class="btn btn-primary"><i class="bi bi-clipboard2-pulse"></i> Ficha clínica</a>
            @endif
            @if ($usuario->tieneRol('Recepcionista') && Route::has('presupuestos.create'))
                <a href="{{ route('presupuestos.create', ['paciente_id' => $paciente->id]) }}" class="btn btn-outline-primary"><i class="bi bi-receipt"></i> Nuevo presupuesto</a>
            @endif
            <a href="{{ route('pacientes.edit', $paciente) }}" class="btn btn-outline-primary"><i class="bi bi-pencil"></i> Editar</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="panel h-100">
                <h2><i class="bi bi-person-vcard"></i> Datos</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Fecha de nacimiento</dt>
                    <dd class="col-sm-7">{{ $paciente->fecha_nacimiento?->format('d/m/Y') ?? '—' }}
                        @if ($paciente->fecha_nacimiento) ({{ $paciente->fecha_nacimiento->age }} años) @endif</dd>
                    <dt class="col-sm-5">Teléfono</dt><dd class="col-sm-7 mono">{{ $paciente->telefono }}</dd>
                    <dt class="col-sm-5">Correo</dt><dd class="col-sm-7">{{ $paciente->email ?? '—' }}</dd>
                    <dt class="col-sm-5">Dirección</dt><dd class="col-sm-7">{{ $paciente->direccion ?? '—' }}</dd>
                    <dt class="col-sm-5">Contacto de emergencia</dt>
                    <dd class="col-sm-7">{{ $paciente->contacto_emergencia_nombre ?? '—' }}
                        <span class="mono">{{ $paciente->contacto_emergencia_telefono }}</span></dd>
                    <dt class="col-sm-5">Convenio</dt>
                    <dd class="col-sm-7">{{ $paciente->convenio?->institucion ?? 'Particular' }}</dd>
                </dl>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="panel h-100">
                <h2><i class="bi bi-heart-pulse"></i> Antecedentes de salud</h2>
                @if ($usuario->tieneRol('Dentista'))
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Alergias</dt><dd class="col-sm-8">{{ $paciente->alergias ?: 'Sin registro' }}</dd>
                        <dt class="col-sm-4">Patologías base</dt><dd class="col-sm-8">{{ $paciente->patologias ?: 'Sin registro' }}</dd>
                        <dt class="col-sm-4">Medicamentos</dt><dd class="col-sm-8">{{ $paciente->medicamentos ?: 'Sin registro' }}</dd>
                    </dl>
                @else
                    <p class="text-body-secondary mb-0">Información restringida al personal clínico.</p>
                @endif
            </div>
        </div>

        <div class="col-lg-7">
            <div class="panel tabla-contenedor">
                <h2><i class="bi bi-clock-history"></i> Historial de citas</h2>
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Fecha</th><th>Doctor</th><th>Box</th><th>Estado</th></tr></thead>
                    <tbody>
                    @forelse ($paciente->citas as $cita)
                        <tr>
                            <td class="mono">{{ $cita->hora_inicio->format('d/m/Y H:i') }}</td>
                            <td>{{ $cita->doctor->nombre }}</td>
                            <td>{{ $cita->box->nombre }}</td>
                            <td>@include('partials.estado-cita', ['estado' => $cita->estado])</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-body-secondary">Sin citas registradas.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($usuario->tieneRol('Dentista', 'Recepcionista'))
            <div class="col-lg-5">
                <div class="panel tabla-contenedor">
                    <h2><i class="bi bi-receipt"></i> Presupuestos</h2>
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Fecha</th><th>Estado</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                        @forelse ($paciente->presupuestos as $presupuesto)
                            <tr>
                                <td>
                                    @if (Route::has('presupuestos.show'))
                                        <a href="{{ route('presupuestos.show', $presupuesto) }}">{{ $presupuesto->created_at->format('d/m/Y') }}</a>
                                    @else
                                        {{ $presupuesto->created_at->format('d/m/Y') }}
                                    @endif
                                </td>
                                <td>{{ ucfirst($presupuesto->estado) }}</td>
                                <td class="text-end mono">@pesos($presupuesto->total_calculado)</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-body-secondary">Sin presupuestos.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection
