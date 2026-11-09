@extends('layouts.app')

@section('titulo', 'Dashboard')

@section('contenido')
    @php
        $especialistas = $datos['porEspecialista'];
        $maxEspecialista = max(array_column($especialistas, 'total') ?: [1]);
        $estados = $datos['citasPorEstado'];
        $totalCitas = array_sum($estados) ?: 1;
        $tendencia = $datos['ingresosMensuales'];
        $maxTendencia = $tendencia['maximo'] > 0 ? $tendencia['maximo'] : 1;
        $convenios = $datos['pacientesPorConvenio'];
        $totalPacientes = array_sum(array_column($convenios, 'total')) ?: 1;
        $tratamientos = $datos['tratamientos'];
        $maxTratamiento = max(array_column($tratamientos, 'cantidad') ?: [1]);
        $demanda = $datos['demanda'];
        $abreviar = function (float $monto): string {
            if ($monto >= 1000000) {
                return '$'.number_format($monto / 1000000, 1, ',', '.').'M';
            }
            if ($monto >= 1000) {
                return '$'.number_format($monto / 1000, 0, ',', '.').' mil';
            }

            return '$'.number_format($monto, 0, ',', '.');
        };
    @endphp

    <div class="titulo-pagina">
        <div>
            <h1>Dashboard</h1>
            <small class="text-body-secondary">
                {{ ucfirst($mes->locale('es')->isoFormat('MMMM [de] YYYY')) }}
                · Actualizado a las {{ $datos['actualizado']->format('H:i') }}
            </small>
        </div>
        <form method="GET" class="d-flex gap-2">
            <input type="month" name="mes" value="{{ $mes->format('Y-m') }}" class="form-control" aria-label="Mes">
            <button type="submit" class="btn btn-outline-primary"><i class="bi bi-arrow-clockwise"></i> Ver</button>
        </form>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-xl-3">
            <div class="panel indicador h-100">
                <span class="indicador-icono"><i class="bi bi-door-open"></i></span>
                <div>
                    <div class="indicador-etiqueta">Sillones ocupados ahora</div>
                    <div class="indicador-valor mono">{{ $datos['sillones']['ocupadosAhora'] }} / {{ $datos['sillones']['activos'] }}</div>
                    <div class="small text-body-secondary">Ocupación de hoy: {{ $datos['sillones']['ocupacionDia'] }} %</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="panel indicador h-100">
                <span class="indicador-icono" style="background:#dbeafe; color:#1d4ed8;"><i class="bi bi-hand-thumbs-up"></i></span>
                <div>
                    <div class="indicador-etiqueta">Presupuestos aceptados (mes)</div>
                    <div class="indicador-valor mono">{{ $datos['presupuestos']['cantidad'] }}</div>
                    <div class="small text-body-secondary">Por @pesos($datos['presupuestos']['monto']) · {{ $datos['presupuestos']['borradores'] }} en borrador</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="panel indicador h-100">
                <span class="indicador-icono" style="background:#e7f6ee; color:#067647;"><i class="bi bi-cash-stack"></i></span>
                <div>
                    <div class="indicador-etiqueta">Caja: ingresos del mes</div>
                    <div class="indicador-valor mono">@pesos($datos['caja']['mes'])</div>
                    <div class="small text-body-secondary">
                        Hoy: @pesos($datos['caja']['hoy'])
                        @if ($tendencia['variacion'] !== null)
                            · <span @class(['text-success' => $tendencia['variacion'] >= 0, 'text-danger' => $tendencia['variacion'] < 0])>
                                <i class="bi {{ $tendencia['variacion'] >= 0 ? 'bi-arrow-up-short' : 'bi-arrow-down-short' }}"></i>{{ abs($tendencia['variacion']) }} % vs {{ $tendencia['comparacion'] }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="panel indicador h-100">
                <span class="indicador-icono" style="background:#fdecea; color:#b42318;"><i class="bi bi-hourglass-split"></i></span>
                <div>
                    <div class="indicador-etiqueta">Saldo por cobrar</div>
                    <div class="indicador-valor mono">@pesos($datos['caja']['porCobrar'])</div>
                    <div class="small text-body-secondary">Presupuestos aceptados con saldo</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-xl-3">
            <div class="panel indicador h-100">
                <span class="indicador-icono" style="background:#ede9fe; color:#6d28d9;"><i class="bi bi-percent"></i></span>
                <div>
                    <div class="indicador-etiqueta">Tasa de aceptación (mes)</div>
                    <div class="indicador-valor mono">{{ $datos['aceptacion']['tasa'] }} %</div>
                    <div class="small text-body-secondary">
                        {{ $datos['aceptacion']['aceptados'] }} de {{ $datos['aceptacion']['emitidos'] }} presupuestos · Promedio @pesos($datos['aceptacion']['promedio'])
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="panel indicador h-100">
                <span class="indicador-icono"><i class="bi bi-person-plus"></i></span>
                <div>
                    <div class="indicador-etiqueta">Pacientes nuevos (mes)</div>
                    <div class="indicador-valor mono">{{ $datos['pacientesMes']['nuevos'] }}</div>
                    <div class="small text-body-secondary">
                        {{ $datos['pacientesMes']['atendidos'] }} atendidos · {{ $datos['pacientesMes']['activos'] }} activos en total
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="panel indicador h-100">
                <span class="indicador-icono" style="background:#fef3c7; color:#b45309;"><i class="bi bi-calendar-x"></i></span>
                <div>
                    <div class="indicador-etiqueta">Tasa de cancelación (mes)</div>
                    <div class="indicador-valor mono">{{ number_format($datos['cancelacion']['tasa'], 1, ',', '.') }} %</div>
                    <div class="small text-body-secondary">
                        {{ $datos['cancelacion']['canceladas'] }} de {{ $datos['cancelacion']['total'] }} citas · {{ $datos['cancelacion']['inasistencias'] }} inasistencias
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="panel indicador h-100">
                <span class="indicador-icono" style="background:#fdecea; color:#b42318;"><i class="bi bi-exclamation-diamond"></i></span>
                <div>
                    <div class="indicador-etiqueta">Saldos de más de {{ \App\Services\DashboardService::DIAS_SALDO_ANTIGUO }} días</div>
                    <div class="indicador-valor mono">@pesos($datos['saldosAntiguos']['monto'])</div>
                    <div class="small text-body-secondary">{{ $datos['saldosAntiguos']['cantidad'] }} presupuestos por cobrar</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="panel h-100">
                <h2><i class="bi bi-graph-up-arrow"></i> Ingresos de los últimos 6 meses</h2>
                <svg viewBox="0 0 600 240" class="grafico" role="img"
                     aria-label="Ingresos por mes: @foreach ($tendencia['meses'] as $m){{ $m['etiqueta'] }} {{ number_format($m['total'], 0, ',', '.') }} pesos{{ $loop->last ? '' : ', ' }}@endforeach">
                    <defs>
                        <linearGradient id="degradado-barra" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#14b8a6"/>
                            <stop offset="100%" stop-color="#0f766e"/>
                        </linearGradient>
                    </defs>
                    <line x1="20" y1="195" x2="580" y2="195" stroke="#e1eae9" stroke-width="1"/>
                    @foreach ($tendencia['meses'] as $i => $m)
                        @php
                            $alto = $m['total'] > 0 ? max(4, $m['total'] / $maxTendencia * 160) : 0;
                            $x = 40 + $i * 93;
                        @endphp
                        <rect x="{{ $x }}" y="{{ 195 - $alto }}" width="56" height="{{ $alto }}" rx="6"
                              fill="{{ $loop->last ? 'url(#degradado-barra)' : '#99d5cd' }}"/>
                        <text x="{{ $x + 28 }}" y="{{ 195 - $alto - 8 }}" text-anchor="middle" class="grafico-valor">{{ $abreviar($m['total']) }}</text>
                        <text x="{{ $x + 28 }}" y="218" text-anchor="middle" class="grafico-etiqueta">{{ $m['etiqueta'] }}</text>
                    @endforeach
                </svg>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="panel h-100">
                <h2><i class="bi bi-people"></i> Pacientes por convenio</h2>
                @forelse ($convenios as $fila)
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small">
                            <span>{{ $fila['categoria'] }}</span>
                            <span class="mono">{{ $fila['total'] }} · {{ round($fila['total'] / $totalPacientes * 100) }} %</span>
                        </div>
                        <div class="barra" role="img" aria-label="{{ $fila['categoria'] }}: {{ $fila['total'] }} pacientes">
                            <div style="width: {{ round($fila['total'] / $totalPacientes * 100) }}%;"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-body-secondary mb-0">Sin pacientes registrados.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="panel h-100">
                <h2><i class="bi bi-person-badge"></i> Ingresos por especialista (mes)</h2>
                @forelse ($especialistas as $fila)
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small">
                            <span>{{ $fila['nombre'] }} <span class="text-body-secondary">· {{ $fila['especialidad'] }}</span></span>
                            <span class="mono">@pesos($fila['total'])</span>
                        </div>
                        <div class="barra" role="img" aria-label="{{ $fila['nombre'] }}: {{ number_format($fila['total'], 0, ',', '.') }} pesos">
                            <div style="width: {{ round($fila['total'] / $maxEspecialista * 100) }}%;"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-body-secondary mb-0">Sin pagos registrados en el mes.</p>
                @endforelse
            </div>
        </div>
        <div class="col-lg-6">
            <div class="panel h-100 tabla-contenedor">
                <h2><i class="bi bi-clipboard2-pulse"></i> Tratamientos más solicitados (mes)</h2>
                @if ($tratamientos === [])
                    <p class="text-body-secondary mb-0">Sin presupuestos aceptados en el mes.</p>
                @else
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Tratamiento</th><th style="width: 35%;"></th><th class="text-end">Cantidad</th><th class="text-end">Monto</th></tr></thead>
                        <tbody>
                        @foreach ($tratamientos as $fila)
                            <tr>
                                <td>{{ $fila['nombre'] }}</td>
                                <td>
                                    <div class="barra" role="img" aria-label="{{ $fila['nombre'] }}: {{ $fila['cantidad'] }}">
                                        <div style="width: {{ round($fila['cantidad'] / $maxTratamiento * 100) }}%;"></div>
                                    </div>
                                </td>
                                <td class="text-end mono">{{ $fila['cantidad'] }}</td>
                                <td class="text-end mono">@pesos($fila['monto'])</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="panel h-100">
                <h2><i class="bi bi-door-open"></i> Ocupación por box (mes)</h2>
                @forelse ($datos['ocupacionBox'] as $fila)
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small">
                            <span>{{ $fila['box'] }} <span class="text-body-secondary">· {{ $fila['sucursal'] }}</span></span>
                            <span class="mono">{{ $fila['porcentaje'] }} % · {{ number_format($fila['horas'], 1, ',', '.') }} h</span>
                        </div>
                        <div class="barra" role="img" aria-label="{{ $fila['box'] }}: {{ $fila['porcentaje'] }} por ciento">
                            <div style="width: {{ min(100, $fila['porcentaje']) }}%;"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-body-secondary mb-0">No hay box habilitados.</p>
                @endforelse
            </div>
        </div>
        <div class="col-lg-6">
            <div class="panel h-100">
                <h2><i class="bi bi-calendar3"></i> Citas del mes por estado</h2>
                @forelse ($estados as $estado => $cantidad)
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small">
                            <span>{{ \App\Models\Cita::ETIQUETAS[$estado] ?? ucfirst($estado) }}</span><span class="mono">{{ $cantidad }}</span>
                        </div>
                        <div class="barra" role="img" aria-label="{{ \App\Models\Cita::ETIQUETAS[$estado] ?? ucfirst($estado) }}: {{ $cantidad }} citas">
                            <div style="width: {{ round($cantidad / $totalCitas * 100) }}%;"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-body-secondary mb-0">Sin citas en el mes.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-8">
            <div class="panel h-100 tabla-contenedor">
                <h2><i class="bi bi-grid-3x3"></i> Días y horas de mayor demanda</h2>
                <p class="small text-body-secondary mb-2">Citas desde el {{ $demanda['desde']->format('d/m/Y') }}</p>
                <table class="mapa-calor">
                    <thead>
                    <tr>
                        <th></th>
                        @foreach ($demanda['horas'] as $hora)
                            <th class="mono">{{ str_pad($hora, 2, '0', STR_PAD_LEFT) }}</th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($demanda['dias'] as $dia => $nombreDia)
                        <tr>
                            <th>{{ mb_substr($nombreDia, 0, 3) }}</th>
                            @foreach ($demanda['horas'] as $hora)
                                @php
                                    $cantidad = $demanda['matriz'][$dia][$hora];
                                    $intensidad = $cantidad > 0 ? 0.12 + $cantidad / $demanda['maximo'] * 0.88 : 0;
                                @endphp
                                <td title="{{ $nombreDia }} {{ str_pad($hora, 2, '0', STR_PAD_LEFT) }}:00 · {{ $cantidad }} citas"
                                    style="background: rgba(15, 118, 110, {{ round($intensidad, 2) }}); color: {{ $intensidad > 0.55 ? '#fff' : 'var(--texto)' }};">
                                    {{ $cantidad > 0 ? $cantidad : '' }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="panel h-100">
                <h2><i class="bi bi-patch-check"></i> Descuentos por convenio (mes)</h2>
                @forelse ($datos['descuentos'] as $fila)
                    <div class="fila-lista">
                        <span>{{ $fila['institucion'] }} <span class="small text-body-secondary">· {{ $fila['presupuestos'] }} presupuestos</span></span>
                        <span class="mono fw-bold">@pesos($fila['descuento'])</span>
                    </div>
                @empty
                    <p class="text-body-secondary mb-0">Sin descuentos aplicados en el mes.</p>
                @endforelse
                @if ($datos['descuentos'] !== [])
                    <div class="d-flex justify-content-between border-top pt-2 mt-1">
                        <span class="fw-semibold">Total</span>
                        <span class="mono fw-bold">@pesos(array_sum(array_column($datos['descuentos'], 'descuento')))</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @php
        $metodos = $datos['porMetodo'];
        $totalMetodos = array_sum(array_column($metodos, 'total')) ?: 1;
    @endphp
    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="panel">
                <h2><i class="bi bi-wallet2"></i> Ingresos por método de pago (mes)</h2>
                <div class="row g-3">
                    @foreach ($metodos as $fila)
                        <div class="col-sm-6 col-xl-3">
                            <div class="d-flex justify-content-between small">
                                <span>{{ $fila['metodo'] }}</span>
                                <span class="mono">{{ round($fila['total'] / $totalMetodos * 100) }} %</span>
                            </div>
                            <div class="fs-5 fw-bold mono">@pesos($fila['total'])</div>
                            <div class="barra mb-1" role="img" aria-label="{{ $fila['metodo'] }}: {{ number_format($fila['total'], 0, ',', '.') }} pesos">
                                <div style="width: {{ round($fila['total'] / $totalMetodos * 100) }}%;"></div>
                            </div>
                            <div class="small text-body-secondary">{{ $fila['pagos'] }} pagos</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="panel h-100 tabla-contenedor">
                <h2><i class="bi bi-building"></i> Actividad por sucursal (mes)</h2>
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Sucursal</th><th class="text-end">Box</th><th class="text-end">Citas</th><th class="text-end">Pacientes</th><th class="text-end">Horas</th></tr></thead>
                    <tbody>
                    @foreach ($datos['sucursales'] as $fila)
                        <tr>
                            <td>{{ $fila['nombre'] }}</td>
                            <td class="text-end mono">{{ $fila['boxes'] }}</td>
                            <td class="text-end mono">{{ $fila['citas'] }}</td>
                            <td class="text-end mono">{{ $fila['pacientes'] }}</td>
                            <td class="text-end mono">{{ number_format($fila['horas'], 1, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="panel h-100">
                <h2><i class="bi bi-exclamation-diamond"></i> Saldos pendientes más antiguos</h2>
                @forelse ($datos['saldosAntiguos']['lista'] as $fila)
                    <div class="fila-lista">
                        <span>
                            <a href="{{ route('presupuestos.show', $fila['id']) }}">{{ $fila['paciente'] }}</a>
                            <span class="small text-body-secondary">· hace {{ $fila['dias'] }} días</span>
                        </span>
                        <span class="mono fw-bold">@pesos($fila['saldo'])</span>
                    </div>
                @empty
                    <p class="text-body-secondary mb-0">No hay saldos de más de {{ \App\Services\DashboardService::DIAS_SALDO_ANTIGUO }} días.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
