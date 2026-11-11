@extends('layouts.app')

@section('titulo', 'Atención · '.$paciente->nombre)

@section('contenido')
    <div class="titulo-pagina">
        <div>
            <h1>Atención del {{ $evolucion->fecha->format('d/m/Y') }}</h1>
            <small class="text-body-secondary">{{ $paciente->nombre }} · <span class="mono">{{ $paciente->rut }}</span></small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if ($anterior)
                <a href="{{ route('atenciones.show', $anterior) }}" class="btn btn-outline-secondary"><i class="bi bi-chevron-left"></i> Anterior</a>
            @endif
            @if ($siguiente)
                <a href="{{ route('atenciones.show', $siguiente) }}" class="btn btn-outline-secondary">Siguiente <i class="bi bi-chevron-right"></i></a>
            @endif
            <a href="{{ route('fichas.show', $paciente) }}" class="btn btn-outline-primary"><i class="bi bi-arrow-left"></i> Volver a la ficha</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="panel h-100">
                <h2><i class="bi bi-journal-medical"></i> Detalle de la atención</h2>
                <table class="table table-sm table-borderless align-middle mb-0">
                    <tr><th style="width: 30%;">Fecha</th><td class="mono">{{ $evolucion->fecha->format('d/m/Y') }}</td></tr>
                    <tr><th>Doctor</th><td>{{ $evolucion->doctor->nombre }} <span class="text-body-secondary">· {{ $evolucion->doctor->especialidad }}</span></td></tr>
                    <tr><th>Diagnóstico</th><td style="white-space: pre-line;">{{ $evolucion->diagnostico }}</td></tr>
                    <tr><th>Tratamiento realizado</th><td style="white-space: pre-line;">{{ $evolucion->tratamiento_realizado ?: 'Sin registro' }}</td></tr>
                    <tr><th>Registrada el</th><td class="mono">{{ $evolucion->created_at->format('d/m/Y H:i') }}</td></tr>
                </table>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="panel h-100">
                <h2><i class="bi bi-list-check"></i> Plan de tratamiento</h2>
                @if ($evolucion->detalle)
                    @php($detalle = $evolucion->detalle)
                    <p class="mb-2">
                        <strong>{{ $detalle->tratamiento->nombre }}</strong><br>
                        <span class="text-body-secondary small">Presupuesto N.° {{ $detalle->presupuesto->id }} · {{ $detalle->cantidad_realizada }} de {{ $detalle->cantidad }} realizados</span>
                    </p>
                    <a href="{{ route('detalles.show', $detalle) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> Ver tratamiento</a>
                @else
                    <p class="text-body-secondary mb-0">Atención libre, sin tratamiento del plan asociado.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="panel mb-3">
                <h2><i class="bi bi-calendar-check"></i> Citas de ese día</h2>
                @forelse ($citas as $cita)
                    <div class="fila-lista">
                        <span><strong class="mono">{{ $cita->hora_inicio->format('H:i') }}–{{ $cita->hora_fin->format('H:i') }}</strong> · {{ $cita->doctor->nombre }} · {{ $cita->box->nombre }}, {{ $cita->box->sucursal->nombre }}</span>
                        @include('partials.estado-cita', ['estado' => $cita->estado])
                    </div>
                @empty
                    <p class="text-body-secondary mb-0">No hubo citas agendadas ese día.</p>
                @endforelse
            </div>

            <div class="panel">
                <h2><i class="bi bi-journals"></i> Otras atenciones del mismo día</h2>
                @forelse ($otras as $otra)
                    <div class="fila-lista">
                        <span>{{ $otra->doctor->nombre }} · {{ \Illuminate\Support\Str::limit($otra->diagnostico, 60) }}</span>
                        <a href="{{ route('atenciones.show', $otra) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> Ver</a>
                    </div>
                @empty
                    <p class="text-body-secondary mb-0">No hay otras atenciones ese día.</p>
                @endforelse
            </div>
        </div>

        <div class="col-lg-6">
            <div class="panel mb-3 tabla-contenedor">
                <h2><i class="bi bi-grid-3x3-gap"></i> Cambios en el odontograma ese día</h2>
                @if ($cambios->isEmpty())
                    <p class="text-body-secondary mb-0">Sin cambios en el odontograma ese día.</p>
                @else
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Hora</th><th>Pieza</th><th>Cambio</th><th>Caras</th></tr></thead>
                        <tbody>
                        @foreach ($cambios as $cambio)
                            <tr>
                                <td class="mono">{{ $cambio['fecha']->format('H:i') }}</td>
                                <td class="mono">{{ $cambio['pieza'] }}</td>
                                <td>{{ $cambio['antes'] }} <i class="bi bi-arrow-right"></i> <strong>{{ $cambio['despues'] }}</strong></td>
                                <td>{{ implode(', ', $cambio['caras']) ?: '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div class="panel">
                <h2><i class="bi bi-image"></i> Radiografías de ese día</h2>
                @forelse ($radiografias as $radiografia)
                    <div class="fila-lista">
                        <span>{{ $radiografia->diagnostico ?: 'Sin diagnóstico' }}</span>
                        <a href="{{ route('radiografias.archivo', $radiografia) }}" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener"><i class="bi bi-eye"></i> Ver</a>
                    </div>
                @empty
                    <p class="text-body-secondary mb-0">Sin radiografías ese día.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
