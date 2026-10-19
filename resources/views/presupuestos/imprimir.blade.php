<!doctype html>
<html lang="es">
<head>
    @include('partials.head')
    <title>Orden de tratamiento N.° {{ $presupuesto->id }} · Diente Feliz</title>
    <style>
        body { background: #fff; }
        .hoja { max-width: 800px; margin: 2rem auto; }
        .firma { border-top: 1px solid #333; padding-top: .3rem; text-align: center; margin-top: 4rem; }
        @page { margin: 1.5cm; }
    </style>
</head>
<body>
@php
    $subtotal = $presupuesto->subtotal();
    $descuento = $presupuesto->descuento();
    $pagado = (float) $presupuesto->pagos->where('estado', 'vigente')->sum('monto');
@endphp
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
            <div class="h5 mb-0">Orden de tratamiento</div>
            <div class="mono">N.° {{ str_pad($presupuesto->id, 6, '0', STR_PAD_LEFT) }}</div>
            <small>Emitida el {{ now()->format('d/m/Y') }}</small>
        </div>
    </div>

    <table class="table table-sm table-borderless mb-3">
        <tr><th style="width: 30%;">Paciente</th><td>{{ $presupuesto->paciente->nombre }}</td></tr>
        <tr><th>RUT</th><td class="mono">{{ $presupuesto->paciente->rut }}</td></tr>
        <tr><th>Convenio</th><td>{{ $presupuesto->paciente->convenio?->institucion ?? 'Particular' }}</td></tr>
        <tr><th>Doctor tratante</th><td>{{ $presupuesto->doctor->nombre }} ({{ $presupuesto->doctor->especialidad }})</td></tr>
        <tr><th>Estado</th><td>{{ ucfirst($presupuesto->estado) }}</td></tr>
    </table>

    <table class="table table-bordered">
        <thead class="table-light"><tr><th>Tratamiento</th><th class="text-end">Cantidad</th><th class="text-end">Subtotal</th></tr></thead>
        <tbody>
        @foreach ($presupuesto->detalles as $detalle)
            <tr>
                <td>{{ $detalle->tratamiento->nombre }}</td>
                <td class="text-end mono">{{ $detalle->cantidad }}</td>
                <td class="text-end mono">@pesos($detalle->subtotal)</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr><td colspan="2" class="text-end">Subtotal</td><td class="text-end mono">@pesos($subtotal)</td></tr>
        <tr><td colspan="2" class="text-end">Descuento por convenio</td><td class="text-end mono">−@pesos($descuento)</td></tr>
        <tr class="fw-bold"><td colspan="2" class="text-end">Total</td><td class="text-end mono">@pesos($presupuesto->total_calculado)</td></tr>
        @if (in_array($presupuesto->estado, ['aceptado', 'pagado']))
            <tr><td colspan="2" class="text-end">Pagado</td><td class="text-end mono">@pesos($pagado)</td></tr>
            <tr class="fw-bold"><td colspan="2" class="text-end">Saldo pendiente</td><td class="text-end mono">@pesos($presupuesto->total_calculado - $pagado)</td></tr>
        @endif
        </tfoot>
    </table>

    <div class="row mt-5">
        <div class="col-6"><div class="firma">Firma del paciente</div></div>
        <div class="col-6"><div class="firma">{{ $presupuesto->doctor->nombre }}</div></div>
    </div>

    <p class="small text-body-secondary mt-4">Documento generado por el sistema de gestión de la Clínica Dental Diente Feliz.</p>
</main>
</body>
</html>
