@extends('layouts.app')

@section('titulo', 'Dashboard')

@section('contenido')
    @php
        $especialistas = $datos['porEspecialista'];
        $maximo = max(array_column($especialistas, 'total') ?: [1]);
        $estados = $datos['citasPorEstado'];
        $totalCitas = array_sum($estados) ?: 1;
    @endphp

    <div class="titulo-pagina">
        <div>
            <h1>Dashboard</h1>
            <small class="text-body-secondary">{{ ucfirst($mes->locale('es')->isoFormat('MMMM [de] YYYY')) }}</small>
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
                    <div class="small text-body-secondary">Hoy: @pesos($datos['caja']['hoy'])</div>
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

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="panel h-100">
                <h2><i class="bi bi-person-badge"></i> Ingresos por especialista (mes)</h2>
                @forelse ($especialistas as $fila)
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small">
                            <span>{{ $fila['nombre'] }} <span class="text-body-secondary">· {{ $fila['especialidad'] }}</span></span>
                            <span class="mono">@pesos($fila['total'])</span>
                        </div>
                        <div class="barra" role="img" aria-label="{{ $fila['nombre'] }}: {{ number_format($fila['total'], 0, ',', '.') }} pesos">
                            <div style="width: {{ round($fila['total'] / $maximo * 100) }}%;"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-body-secondary mb-0">Sin pagos registrados en el mes.</p>
                @endforelse
            </div>
        </div>
        <div class="col-lg-5">
            <div class="panel h-100">
                <h2><i class="bi bi-calendar3"></i> Citas del mes por estado</h2>
                @forelse ($estados as $estado => $cantidad)
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small">
                            <span>{{ ucfirst($estado) }}</span><span class="mono">{{ $cantidad }}</span>
                        </div>
                        <div class="barra" role="img" aria-label="{{ ucfirst($estado) }}: {{ $cantidad }} citas">
                            <div style="width: {{ round($cantidad / $totalCitas * 100) }}%;"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-body-secondary mb-0">Sin citas en el mes.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
