@extends('layouts.app')

@section('titulo', 'Inicio')

@section('contenido')
    @php
        $usuario = auth()->user();
        $hora = now()->hour;
        $saludo = $hora < 12 ? 'Buenos días' : ($hora < 20 ? 'Buenas tardes' : 'Buenas noches');
    @endphp

    <div class="titulo-pagina">
        <div>
            <h1>{{ $saludo }}, {{ $usuario->nombre }}</h1>
            <small>{{ ucfirst(now()->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY')) }}</small>
        </div>
        @if (Route::has('citas.index'))
            <a href="{{ route('citas.index') }}" class="btn btn-primary"><i class="bi bi-calendar-plus"></i> Ir a la agenda</a>
        @endif
    </div>

    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-xl-4">
            <div class="panel indicador h-100">
                <span class="indicador-icono"><i class="bi bi-calendar-check"></i></span>
                <div>
                    <div class="indicador-etiqueta">Citas de hoy</div>
                    <div class="indicador-valor">{{ $alertas['citasHoy']->count() }}</div>
                </div>
            </div>
        </div>
        @isset($alertas['sinConfirmar'])
            <div class="col-sm-6 col-xl-4">
                <div class="panel indicador h-100">
                    <span class="indicador-icono" style="background:#fef3c7; color:#b45309;"><i class="bi bi-telephone-outbound"></i></span>
                    <div>
                        <div class="indicador-etiqueta">Por confirmar para mañana</div>
                        <div class="indicador-valor">{{ $alertas['sinConfirmar']->count() }}</div>
                    </div>
                </div>
            </div>
        @endisset
        @isset($alertas['saldosPendientes'])
            <div class="col-sm-6 col-xl-4">
                <div class="panel indicador h-100">
                    <span class="indicador-icono" style="background:#fdecea; color:#b42318;"><i class="bi bi-cash-coin"></i></span>
                    <div>
                        <div class="indicador-etiqueta">Presupuestos con saldo</div>
                        <div class="indicador-valor">{{ $alertas['saldosPendientes']->count() }}</div>
                    </div>
                </div>
            </div>
        @endisset
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="panel h-100">
                <h2><i class="bi bi-calendar3"></i> Citas de hoy</h2>
                @forelse ($alertas['citasHoy'] as $cita)
                    <div class="fila-lista">
                        <span><span class="mono fw-bold">{{ $cita->hora_inicio->format('H:i') }}</span> · {{ $cita->paciente->nombre }}</span>
                        <small class="text-body-secondary">{{ $cita->doctor->nombre }} · {{ $cita->box->nombre }}</small>
                    </div>
                @empty
                    <p class="vacio"><i class="bi bi-cup-hot"></i> No hay citas para hoy.</p>
                @endforelse
            </div>
        </div>

        @isset($alertas['sinConfirmar'])
            <div class="col-lg-6">
                <div class="panel h-100">
                    <h2><i class="bi bi-telephone-outbound"></i> Citas de mañana sin confirmar</h2>
                    @forelse ($alertas['sinConfirmar'] as $cita)
                        <div class="fila-lista">
                            <span><span class="mono fw-bold">{{ $cita->hora_inicio->format('H:i') }}</span> · {{ $cita->paciente->nombre }}</span>
                            <small class="mono text-body-secondary"><i class="bi bi-telephone"></i> {{ $cita->paciente->telefono }}</small>
                        </div>
                    @empty
                        <p class="vacio"><i class="bi bi-check-circle"></i> Todas las citas de mañana están confirmadas.</p>
                    @endforelse
                </div>
            </div>
        @endisset

        @isset($alertas['saldosPendientes'])
            <div class="col-12">
                <div class="panel">
                    <h2><i class="bi bi-cash-coin"></i> Presupuestos aceptados con saldo pendiente</h2>
                    @forelse ($alertas['saldosPendientes'] as $presupuesto)
                        <div class="fila-lista">
                            <a href="{{ route('presupuestos.show', $presupuesto) }}">{{ $presupuesto->paciente->nombre }}</a>
                            <span class="mono fw-bold">Saldo ${{ number_format($presupuesto->total_calculado - $presupuesto->pagado, 0, ',', '.') }}</span>
                        </div>
                    @empty
                        <p class="vacio"><i class="bi bi-check-circle"></i> No hay saldos pendientes.</p>
                    @endforelse
                </div>
            </div>
        @endisset
    </div>
@endsection
