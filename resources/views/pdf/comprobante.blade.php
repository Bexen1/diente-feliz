@extends('pdf.layout')

@section('titulo', 'Comprobante de pago N.° '.$pago->id)
@section('documento', 'Comprobante de pago')
@section('referencia', 'N.° '.str_pad($pago->id, 6, '0', STR_PAD_LEFT))

@section('contenido')
    @php($presupuesto = $pago->presupuesto)

    @if ($pago->estado === 'anulado')
        <p style="text-align: center; margin: 14px 0;"><span class="sello">ANULADO</span></p>
    @endif

    <p style="text-align: center; margin: 20px 0;">
        <span class="tenue">Monto recibido</span><br>
        <span class="destacado">@pesos($pago->monto)</span><br>
        {{ $pago->nombre_metodo }} · {{ $pago->fecha->format('d/m/Y') }}
    </p>

    <table class="datos">
        <tr><th>Paciente</th><td>{{ $presupuesto->paciente->nombre }}</td></tr>
        <tr><th>RUT</th><td>{{ $presupuesto->paciente->rut }}</td></tr>
        <tr><th>Presupuesto</th><td>N.° {{ $presupuesto->id }}</td></tr>
        <tr><th>Doctor tratante</th><td>{{ $presupuesto->doctor->nombre }}</td></tr>
        <tr><th>Total del presupuesto</th><td>@pesos($presupuesto->total_calculado)</td></tr>
        <tr><th>Saldo pendiente</th><td class="negrita">@pesos($saldo)</td></tr>
    </table>

    <table class="firmas">
        <tr>
            <td></td>
            <td><div class="firma">Recibido por</div></td>
        </tr>
    </table>
@endsection
