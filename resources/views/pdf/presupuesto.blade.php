@extends('pdf.layout')

@section('titulo', 'Orden de tratamiento N.° '.$presupuesto->id)
@section('documento', 'Orden de tratamiento')
@section('referencia', 'N.° '.str_pad($presupuesto->id, 6, '0', STR_PAD_LEFT))

@section('contenido')
    @php
        $pagado = (float) $presupuesto->pagos->where('estado', 'vigente')->sum('monto');
    @endphp

    <h2>Paciente</h2>
    <table class="datos">
        <tr><th>Paciente</th><td>{{ $presupuesto->paciente->nombre }}</td></tr>
        <tr><th>RUT</th><td>{{ $presupuesto->paciente->rut }}</td></tr>
        <tr><th>Convenio</th><td>{{ $presupuesto->paciente->convenio?->institucion ?? 'Particular' }}</td></tr>
        <tr><th>Doctor tratante</th><td>{{ $presupuesto->doctor->nombre }} ({{ $presupuesto->doctor->especialidad }})</td></tr>
        <tr><th>Fecha</th><td>{{ $presupuesto->created_at->format('d/m/Y') }}</td></tr>
        <tr><th>Estado</th><td>{{ ucfirst($presupuesto->estado) }}</td></tr>
    </table>

    <h2>Tratamientos</h2>
    <table class="tabla">
        <thead><tr><th>Tratamiento</th><th class="numero">Cantidad</th><th class="numero">Realizados</th><th class="numero">Subtotal</th></tr></thead>
        <tbody>
        @foreach ($presupuesto->detalles as $detalle)
            <tr>
                <td>{{ $detalle->tratamiento->nombre }}</td>
                <td class="numero">{{ $detalle->cantidad }}</td>
                <td class="numero">{{ $detalle->cantidad_realizada }}</td>
                <td class="numero">@pesos($detalle->subtotal)</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr><td colspan="3" class="derecha">Subtotal</td><td class="numero">@pesos($presupuesto->subtotal())</td></tr>
        <tr><td colspan="3" class="derecha">Descuento por convenio</td><td class="numero">−@pesos($presupuesto->descuento())</td></tr>
        <tr><td colspan="3" class="derecha negrita">Total</td><td class="numero negrita">@pesos($presupuesto->total_calculado)</td></tr>
        @if (in_array($presupuesto->estado, ['aceptado', 'pagado']))
            <tr><td colspan="3" class="derecha">Pagado</td><td class="numero">@pesos($pagado)</td></tr>
            <tr><td colspan="3" class="derecha negrita">Saldo pendiente</td><td class="numero negrita">@pesos($presupuesto->total_calculado - $pagado)</td></tr>
        @endif
        </tfoot>
    </table>

    @if ($presupuesto->pagos->isNotEmpty())
        <h2>Pagos</h2>
        <table class="tabla">
            <thead><tr><th>N.°</th><th>Fecha</th><th>Método</th><th>Estado</th><th class="numero">Monto</th></tr></thead>
            <tbody>
            @foreach ($presupuesto->pagos as $pago)
                <tr>
                    <td>{{ str_pad($pago->id, 6, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $pago->fecha->format('d/m/Y') }}</td>
                    <td>{{ $pago->nombre_metodo }}</td>
                    <td>{{ ucfirst($pago->estado) }}</td>
                    <td class="numero">@pesos($pago->monto)</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <table class="firmas">
        <tr>
            <td><div class="firma">Firma del paciente</div></td>
            <td><div class="firma">{{ $presupuesto->doctor->nombre }}</div></td>
        </tr>
    </table>
@endsection
