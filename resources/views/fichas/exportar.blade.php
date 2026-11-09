<!doctype html>
<html lang="es" class="claro">
<head>
    @include('partials.head')
    <title>Ficha clínica · {{ $datos['paciente']['nombre'] }} · Diente Feliz</title>
    <style>
        body { background: #fff; }
        .hoja { max-width: 860px; margin: 2rem auto; }
        .hoja h2 { font-size: 1.05rem; margin: 1.5rem 0 .6rem; border-bottom: 2px solid var(--acento-suave); padding-bottom: .3rem; }
        @page { margin: 1.5cm; }
    </style>
</head>
<body>
@php
    $estados = \App\Services\OdontogramaService::ESTADOS;
@endphp
<main class="hoja px-3">
    <div class="no-imprimir mb-3 d-flex gap-2">
        <a href="{{ route('fichas.show', $paciente) }}" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i> Volver</a>
        <a href="{{ route('fichas.exportar.json', $paciente) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-filetype-json"></i> Descargar JSON</a>
    </div>

    <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
        <div class="marca">
            <span class="marca-logo">@include('partials.logo')</span>
            <div>
                <div class="fw-bold">{{ $datos['clinica'] }}</div>
                <small>Copia de ficha clínica</small>
            </div>
        </div>
        <div class="text-end small">
            Generada el {{ \Carbon\Carbon::parse($datos['generado'])->format('d/m/Y H:i') }}<br>
            por {{ auth()->user()->nombre }}
        </div>
    </div>

    <h2>Datos del paciente</h2>
    <table class="table table-sm table-borderless mb-0">
        <tr><th style="width: 32%;">Nombre</th><td>{{ $datos['paciente']['nombre'] }}</td></tr>
        <tr><th>RUT</th><td class="mono">{{ $datos['paciente']['rut'] }}</td></tr>
        <tr><th>Fecha de nacimiento</th><td>{{ $datos['paciente']['fecha_nacimiento'] ? \Carbon\Carbon::parse($datos['paciente']['fecha_nacimiento'])->format('d/m/Y') : '—' }}</td></tr>
        <tr><th>Teléfono</th><td class="mono">{{ $datos['paciente']['telefono'] }}</td></tr>
        <tr><th>Correo</th><td>{{ $datos['paciente']['email'] ?? '—' }}</td></tr>
        <tr><th>Dirección</th><td>{{ $datos['paciente']['direccion'] ?? '—' }}</td></tr>
        <tr><th>Contacto de emergencia</th><td>{{ $datos['paciente']['contacto_emergencia']['nombre'] ?? '—' }} {{ $datos['paciente']['contacto_emergencia']['telefono'] }}</td></tr>
        <tr><th>Convenio</th><td>{{ $datos['paciente']['convenio'] ?? 'Particular' }}</td></tr>
    </table>

    <h2>Antecedentes de salud</h2>
    <table class="table table-sm table-borderless mb-0">
        <tr><th style="width: 32%;">Alergias</th><td>{{ $datos['antecedentes_salud']['alergias'] ?: 'Sin registro' }}</td></tr>
        <tr><th>Patologías base</th><td>{{ $datos['antecedentes_salud']['patologias'] ?: 'Sin registro' }}</td></tr>
        <tr><th>Medicamentos</th><td>{{ $datos['antecedentes_salud']['medicamentos'] ?: 'Sin registro' }}</td></tr>
        <tr><th>Observaciones generales</th><td>{{ $datos['ficha']['observaciones_generales'] ?: 'Sin registro' }}</td></tr>
    </table>

    <h2>Odontograma</h2>
    @php($odontograma = (array) $datos['ficha']['odontograma'])
    @if ($odontograma === [])
        <p class="text-body-secondary">Sin hallazgos registrados.</p>
    @else
        <table class="table table-sm">
            <thead><tr><th>Pieza</th><th>Estado</th><th>Caras</th><th>Fecha</th></tr></thead>
            <tbody>
            @foreach ($odontograma as $pieza => $hallazgo)
                <tr>
                    <td class="mono">{{ $pieza }}</td>
                    <td>{{ $estados[$hallazgo['estado']][1] ?? $hallazgo['estado'] }}</td>
                    <td>{{ implode(', ', $hallazgo['caras'] ?? []) ?: '—' }}</td>
                    <td class="mono">{{ isset($hallazgo['fecha']) ? \Carbon\Carbon::parse($hallazgo['fecha'])->format('d/m/Y') : '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <h2>Atenciones</h2>
    @forelse ($datos['atenciones'] as $atencion)
        <div class="border-bottom py-2">
            <strong class="mono">{{ \Carbon\Carbon::parse($atencion['fecha'])->format('d/m/Y') }}</strong> · {{ $atencion['doctor'] }}<br>
            Diagnóstico: {{ $atencion['diagnostico'] }}
            @if ($atencion['tratamiento_realizado'])<br>Tratamiento: {{ $atencion['tratamiento_realizado'] }}@endif
        </div>
    @empty
        <p class="text-body-secondary">Sin atenciones registradas.</p>
    @endforelse

    <h2>Radiografías</h2>
    @forelse ($datos['radiografias'] as $radiografia)
        <div class="border-bottom py-1">
            <span class="mono">{{ \Carbon\Carbon::parse($radiografia['fecha'])->format('d/m/Y') }}</span> · {{ $radiografia['diagnostico'] ?: 'Sin diagnóstico' }}
        </div>
    @empty
        <p class="text-body-secondary">Sin radiografías.</p>
    @endforelse

    <h2>Citas</h2>
    @if ($datos['citas'] === [])
        <p class="text-body-secondary">Sin citas.</p>
    @else
        <table class="table table-sm">
            <thead><tr><th>Fecha</th><th>Doctor</th><th>Box</th><th>Estado</th></tr></thead>
            <tbody>
            @foreach ($datos['citas'] as $cita)
                <tr>
                    <td class="mono">{{ \Carbon\Carbon::parse($cita['inicio'])->format('d/m/Y H:i') }}</td>
                    <td>{{ $cita['doctor'] }}</td>
                    <td>{{ $cita['box'] }} · {{ $cita['sucursal'] }}</td>
                    <td>{{ \App\Models\Cita::ETIQUETAS[$cita['estado']] ?? ucfirst($cita['estado']) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <h2>Presupuestos y pagos</h2>
    @forelse ($datos['presupuestos'] as $presupuesto)
        <div class="border-bottom py-2">
            <strong>N.° {{ $presupuesto['numero'] }}</strong> · {{ \Carbon\Carbon::parse($presupuesto['fecha'])->format('d/m/Y') }} ·
            {{ $presupuesto['doctor'] }} · {{ ucfirst($presupuesto['estado']) }} · Total <span class="mono">@pesos($presupuesto['total'])</span>
            <div class="small">
                @foreach ($presupuesto['tratamientos'] as $linea)
                    {{ $linea['cantidad'] }} × {{ $linea['tratamiento'] }}@if (! $loop->last), @endif
                @endforeach
            </div>
            @if ($presupuesto['pagos'] !== [])
                <div class="small text-body-secondary">
                    Pagos:
                    @foreach ($presupuesto['pagos'] as $pago)
                        {{ \Carbon\Carbon::parse($pago['fecha'])->format('d/m/Y') }} @pesos($pago['monto']){{ $pago['estado'] === 'anulado' ? ' (anulado)' : '' }}@if (! $loop->last); @endif
                    @endforeach
                </div>
            @endif
        </div>
    @empty
        <p class="text-body-secondary">Sin presupuestos.</p>
    @endforelse
</main>
</body>
</html>
