@extends('layouts.app')

@section('titulo', $detalle->tratamiento->nombre.' · '.$paciente->nombre)

@section('contenido')
    <div class="titulo-pagina">
        <div>
            <h1>{{ $detalle->tratamiento->nombre }}</h1>
            <small class="text-body-secondary">{{ $paciente->nombre }} · <span class="mono">{{ $paciente->rut }}</span> · Presupuesto N.° {{ $presupuesto->id }}</small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if ($puedeRegistrar)
                <form method="POST" action="{{ route('detalles.realizado', $detalle) }}">
                    @csrf
                    <input type="hidden" name="volver" value="detalle">
                    <button type="submit" class="btn btn-success"><i class="bi bi-check2-circle"></i> Registrar realizado</button>
                </form>
            @endif
            <a href="{{ route('fichas.show', $paciente) }}" class="btn btn-outline-primary"><i class="bi bi-arrow-left"></i> Volver a la ficha</a>
        </div>
    </div>

    @error('detalle') <div class="alert alert-danger"><i class="bi bi-x-octagon-fill"></i><span>{{ $message }}</span></div> @enderror

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="panel h-100">
                <h2><i class="bi bi-clipboard2-pulse"></i> Tratamiento</h2>
                <table class="table table-sm table-borderless align-middle mb-3">
                    <tr><th style="width: 45%;">Tratamiento</th><td>{{ $detalle->tratamiento->nombre }}</td></tr>
                    <tr><th>Valor unitario</th><td class="mono">@pesos($detalle->valorUnitario())</td></tr>
                    <tr><th>Cantidad presupuestada</th><td class="mono">{{ $detalle->cantidad }}</td></tr>
                    <tr><th>Realizados</th><td class="mono">{{ $detalle->cantidad_realizada }}</td></tr>
                    <tr><th>Pendientes</th><td class="mono">{{ $detalle->pendientes() }}</td></tr>
                    <tr><th>Subtotal</th><td class="mono fw-bold">@pesos($detalle->subtotal)</td></tr>
                </table>
                <div class="d-flex justify-content-between small mb-1">
                    <span>Avance</span>
                    <span class="mono">{{ $avance }} %</span>
                </div>
                <div class="barra" role="img" aria-label="Avance del tratamiento: {{ $avance }} por ciento">
                    <div style="width: {{ $avance }}%;"></div>
                </div>
                @if ($detalle->pendientes() === 0)
                    <span class="badge bg-success-subtle text-success-emphasis mt-2"><i class="bi bi-check-circle"></i> Completo</span>
                @endif
            </div>
        </div>

        <div class="col-lg-6">
            <div class="panel h-100">
                <h2><i class="bi bi-receipt"></i> Presupuesto N.° {{ $presupuesto->id }}</h2>
                <table class="table table-sm table-borderless align-middle mb-3">
                    <tr><th style="width: 45%;">Estado</th><td>@include('presupuestos.estado', ['estado' => $presupuesto->estado])</td></tr>
                    <tr><th>Fecha</th><td class="mono">{{ $presupuesto->created_at->format('d/m/Y') }}</td></tr>
                    <tr><th>Doctor tratante</th><td>{{ $presupuesto->doctor->nombre }}</td></tr>
                    <tr><th>Convenio</th><td>{{ $paciente->convenio?->institucion ?? 'Particular' }}</td></tr>
                    <tr><th>Total del presupuesto</th><td class="mono">@pesos($presupuesto->total_calculado)</td></tr>
                    <tr><th>Pagado</th><td class="mono">@pesos($pagado)</td></tr>
                    <tr><th>Saldo pendiente</th><td class="mono fw-bold">@pesos($saldo)</td></tr>
                </table>
                <div class="small text-body-secondary mb-1">Tratamientos del presupuesto</div>
                <ul class="list-unstyled small mb-0">
                    @foreach ($presupuesto->detalles as $linea)
                        <li>
                            @if ($linea->id === $detalle->id)
                                <strong>{{ $linea->cantidad }} × {{ $linea->tratamiento->nombre }}</strong>
                            @else
                                <a href="{{ route('detalles.show', $linea) }}">{{ $linea->cantidad }} × {{ $linea->tratamiento->nombre }}</a>
                            @endif
                            <span class="text-body-secondary">· {{ $linea->cantidad_realizada }} de {{ $linea->cantidad }} realizados</span>
                        </li>
                    @endforeach
                </ul>
                @if (auth()->user()->puede('presupuestos.ver') && Route::has('presupuestos.show'))
                    <a href="{{ route('presupuestos.show', $presupuesto) }}" class="btn btn-sm btn-outline-primary mt-3"><i class="bi bi-box-arrow-up-right"></i> Ver presupuesto</a>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="panel h-100">
                <h2><i class="bi bi-journal-medical"></i> Sesiones realizadas</h2>
                @forelse ($detalle->sesiones as $sesion)
                    <div class="fila-lista">
                        <span>
                            <strong class="mono">{{ $sesion->fecha->format('d/m/Y') }}</strong> · {{ $sesion->doctor->nombre }}<br>
                            <small class="text-body-secondary">{{ $sesion->tratamiento_realizado }}</small>
                        </span>
                        <a href="{{ route('atenciones.show', $sesion) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> Ver atención</a>
                    </div>
                @empty
                    <p class="text-body-secondary mb-0">Aún no hay sesiones registradas para este tratamiento.</p>
                @endforelse
            </div>
        </div>

        <div class="col-lg-5">
            <div class="panel h-100">
                <h2><i class="bi bi-cash-coin"></i> Pagos del presupuesto</h2>
                @forelse ($presupuesto->pagos as $pago)
                    <div class="fila-lista">
                        <span>
                            <span class="mono">{{ $pago->fecha->format('d/m/Y') }}</span> · {{ $pago->nombre_metodo }}
                            @if ($pago->estado === 'anulado') <span class="badge bg-danger-subtle text-danger-emphasis">Anulado</span> @endif
                        </span>
                        <span class="mono fw-bold">@pesos($pago->monto)</span>
                    </div>
                @empty
                    <p class="text-body-secondary mb-0">Sin pagos registrados.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
