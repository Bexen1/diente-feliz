@extends('pdf.layout')

@section('titulo', 'Ficha clínica de '.$datos['paciente']['nombre'])
@section('documento', 'Ficha clínica')
@section('referencia', $datos['paciente']['rut'])

@section('contenido')
    @php
        $estados = \App\Services\OdontogramaService::ESTADOS;
        $odontograma = (array) $datos['ficha']['odontograma'];
        ksort($odontograma);
        $fecha = fn ($valor, $formato = 'd/m/Y') => $valor ? \Carbon\Carbon::parse($valor)->format($formato) : '—';
    @endphp

    <h2>Datos del paciente</h2>
    <table class="datos">
        <tr><th>Nombre</th><td>{{ $datos['paciente']['nombre'] }}</td></tr>
        <tr><th>RUT</th><td>{{ $datos['paciente']['rut'] }}</td></tr>
        <tr><th>Fecha de nacimiento</th><td>{{ $fecha($datos['paciente']['fecha_nacimiento']) }}</td></tr>
        <tr><th>Teléfono</th><td>{{ $datos['paciente']['telefono'] }}</td></tr>
        <tr><th>Correo</th><td>{{ $datos['paciente']['email'] ?? '—' }}</td></tr>
        <tr><th>Dirección</th><td>{{ $datos['paciente']['direccion'] ?? '—' }}</td></tr>
        <tr><th>Contacto de emergencia</th><td>{{ $datos['paciente']['contacto_emergencia']['nombre'] ?? '—' }} {{ $datos['paciente']['contacto_emergencia']['telefono'] }}</td></tr>
        <tr><th>Convenio</th><td>{{ $datos['paciente']['convenio'] ?? 'Particular' }}</td></tr>
    </table>

    <h2>Antecedentes de salud</h2>
    <table class="datos">
        <tr><th>Alergias</th><td>{{ $datos['antecedentes_salud']['alergias'] ?: 'Sin registro' }}</td></tr>
        <tr><th>Patologías base</th><td>{{ $datos['antecedentes_salud']['patologias'] ?: 'Sin registro' }}</td></tr>
        <tr><th>Medicamentos</th><td>{{ $datos['antecedentes_salud']['medicamentos'] ?: 'Sin registro' }}</td></tr>
        <tr><th>Observaciones generales</th><td>{{ $datos['ficha']['observaciones_generales'] ?: 'Sin registro' }}</td></tr>
    </table>

    <h2>Odontograma</h2>
    @if ($odontograma === [])
        <p class="vacio">Sin hallazgos registrados.</p>
    @else
        <table class="tabla">
            <thead><tr><th>Pieza</th><th>Estado</th><th>Caras</th><th>Fecha</th></tr></thead>
            <tbody>
            @foreach ($odontograma as $pieza => $hallazgo)
                <tr>
                    <td class="negrita">{{ $pieza }}</td>
                    <td>{{ $estados[$hallazgo['estado']][1] ?? $hallazgo['estado'] }}</td>
                    <td>{{ implode(', ', array_map('ucfirst', $hallazgo['caras'] ?? [])) ?: '—' }}</td>
                    <td>{{ $fecha($hallazgo['fecha'] ?? null) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <h2>Atenciones</h2>
    @forelse ($datos['atenciones'] as $atencion)
        <div class="bloque">
            <span class="negrita">{{ $fecha($atencion['fecha']) }}</span> <span class="tenue">· {{ $atencion['doctor'] }}</span><br>
            <span class="tenue">Diagnóstico:</span> {{ $atencion['diagnostico'] }}
            @if ($atencion['tratamiento_realizado'])
                <br><span class="tenue">Tratamiento:</span> {{ $atencion['tratamiento_realizado'] }}
            @endif
        </div>
    @empty
        <p class="vacio">Sin atenciones registradas.</p>
    @endforelse

    <h2>Plan de tratamiento y presupuestos</h2>
    @forelse ($datos['presupuestos'] as $presupuesto)
        <div class="bloque">
            <span class="negrita">Presupuesto N.° {{ $presupuesto['numero'] }}</span>
            <span class="tenue">· {{ $fecha($presupuesto['fecha']) }} · {{ $presupuesto['doctor'] }}</span>
            <span class="etiqueta">{{ ucfirst($presupuesto['estado']) }}</span>
            <table class="tabla" style="margin-top: 4px;">
                <thead><tr><th>Tratamiento</th><th class="numero">Cantidad</th><th class="numero">Realizados</th><th class="numero">Subtotal</th></tr></thead>
                <tbody>
                @foreach ($presupuesto['tratamientos'] as $linea)
                    <tr>
                        <td>{{ $linea['tratamiento'] }}</td>
                        <td class="numero">{{ $linea['cantidad'] }}</td>
                        <td class="numero">{{ $linea['realizados'] }}</td>
                        <td class="numero">@pesos($linea['subtotal'])</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot><tr><td colspan="3" class="derecha negrita">Total</td><td class="numero negrita">@pesos($presupuesto['total'])</td></tr></tfoot>
            </table>
            @if ($presupuesto['pagos'] !== [])
                <div class="tenue" style="margin-top: 3px;">
                    Pagos:
                    @foreach ($presupuesto['pagos'] as $pago)
                        {{ $fecha($pago['fecha']) }} @pesos($pago['monto']) ({{ $pago['metodo'] }}{{ $pago['estado'] === 'anulado' ? ', anulado' : '' }})@if (! $loop->last); @endif
                    @endforeach
                </div>
            @endif
        </div>
    @empty
        <p class="vacio">Sin presupuestos.</p>
    @endforelse

    <h2>Radiografías</h2>
    @forelse ($datos['radiografias'] as $radiografia)
        <div class="bloque">{{ $fecha($radiografia['fecha']) }} · {{ $radiografia['diagnostico'] ?: 'Sin diagnóstico' }}</div>
    @empty
        <p class="vacio">Sin radiografías.</p>
    @endforelse

    <h2>Historial de citas</h2>
    @if ($datos['citas'] === [])
        <p class="vacio">Sin citas.</p>
    @else
        <table class="tabla">
            <thead><tr><th>Fecha y hora</th><th>Doctor</th><th>Box</th><th>Estado</th></tr></thead>
            <tbody>
            @foreach ($datos['citas'] as $cita)
                <tr>
                    <td>{{ $fecha($cita['inicio'], 'd/m/Y H:i') }}</td>
                    <td>{{ $cita['doctor'] }}</td>
                    <td>{{ $cita['box'] }} · {{ $cita['sucursal'] }}</td>
                    <td>{{ \App\Models\Cita::ETIQUETAS[$cita['estado']] ?? ucfirst($cita['estado']) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
@endsection
