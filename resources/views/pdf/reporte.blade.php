@extends('pdf.layout')

@section('titulo', $reporte['titulo'])
@section('documento', $reporte['titulo'])
@section('referencia', $desde->format('d/m/Y').' al '.$hasta->format('d/m/Y'))

@section('contenido')
    <table class="tabla">
        <thead>
        <tr>
            @foreach ($reporte['columnas'] as $columna)
                <th>{{ $columna }}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        @forelse ($reporte['filas'] as $fila)
            <tr>
                @foreach ($fila as $i => $valor)
                    @if (is_float($valor) && preg_match('/Monto|Pagado/', $reporte['columnas'][$i]))
                        <td class="numero">@pesos($valor)</td>
                    @elseif (is_int($valor) || is_float($valor))
                        <td class="numero">{{ $valor }}</td>
                    @else
                        <td>{{ $valor }}</td>
                    @endif
                @endforeach
            </tr>
        @empty
            <tr><td colspan="{{ count($reporte['columnas']) }}" class="vacio">Sin datos para el período.</td></tr>
        @endforelse
        </tbody>
    </table>
@endsection
