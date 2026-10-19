<table class="table table-sm align-middle mb-0">
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
                    <td class="mono text-end">@pesos($valor)</td>
                @elseif (is_int($valor) || is_float($valor))
                    <td class="mono text-end">{{ $valor }}</td>
                @else
                    <td>{{ $valor }}</td>
                @endif
            @endforeach
        </tr>
    @empty
        <tr><td colspan="{{ count($reporte['columnas']) }}" class="text-body-secondary">Sin datos para el período.</td></tr>
    @endforelse
    </tbody>
</table>
