<!doctype html>
<html lang="es" class="claro">
<head>
    @include('partials.head')
    <title>Comprobante de pago N.° {{ $pago->id }} · Diente Feliz</title>
    <style>
        body { background: #fff; }
        .hoja { max-width: 620px; margin: 2rem auto; }
        .monto-pagado { font-size: 2rem; font-weight: 800; }
        .sello-anulado { border: 3px solid #b42318; color: #b42318; font-weight: 800; letter-spacing: .1em;
            display: inline-block; padding: .2rem .8rem; transform: rotate(-4deg); border-radius: 6px; }
        @page { margin: 1.5cm; }
    </style>
</head>
<body>
@php($presupuesto = $pago->presupuesto)
<main class="hoja px-3">
    <div class="no-imprimir mb-3">
        <a href="{{ route('presupuestos.show', $presupuesto) }}" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>

    <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
        <div class="marca">
            <span class="marca-logo">@include('partials.logo')</span>
            <div>
                <div class="fw-bold">Clínica Dental Diente Feliz</div>
                <small>Av. Principal 123, Concepción</small>
            </div>
        </div>
        <div class="text-end">
            <div class="h5 mb-0">Comprobante de pago</div>
            <div class="mono">N.° {{ str_pad($pago->id, 6, '0', STR_PAD_LEFT) }}</div>
        </div>
    </div>

    @if ($pago->estado === 'anulado')
        <p class="text-center my-3"><span class="sello-anulado">ANULADO</span></p>
    @endif

    <div class="text-center my-4">
        <div class="text-body-secondary">Monto recibido</div>
        <div class="monto-pagado mono">@pesos($pago->monto)</div>
        <div>{{ $pago->nombre_metodo }} · {{ $pago->fecha->format('d/m/Y') }}</div>
    </div>

    <table class="table table-sm table-borderless">
        <tr><th style="width: 40%;">Paciente</th><td>{{ $presupuesto->paciente->nombre }}</td></tr>
        <tr><th>RUT</th><td class="mono">{{ $presupuesto->paciente->rut }}</td></tr>
        <tr><th>Presupuesto</th><td class="mono">N.° {{ $presupuesto->id }}</td></tr>
        <tr><th>Doctor tratante</th><td>{{ $presupuesto->doctor->nombre }}</td></tr>
        <tr><th>Total del presupuesto</th><td class="mono">@pesos($presupuesto->total_calculado)</td></tr>
        <tr><th>Saldo pendiente</th><td class="mono fw-bold">@pesos($saldo)</td></tr>
    </table>

    <div class="row mt-5">
        <div class="col-6 offset-6">
            <div style="border-top: 1px solid #333; padding-top: .3rem; text-align: center; margin-top: 3rem;">Recibido por</div>
        </div>
    </div>
</main>
</body>
</html>
