@extends('layouts.app')

@section('titulo', 'Reportes')

@section('contenido')
    @php($parametros = ['tipo' => $tipo, 'desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString()])

    <div class="titulo-pagina">
        <h1>Reportes</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('reportes.index', $parametros + ['formato' => 'csv']) }}" class="btn btn-outline-primary"><i class="bi bi-file-earmark-spreadsheet"></i> Exportar a Excel</a>
            <a href="{{ route('reportes.index', $parametros + ['formato' => 'pdf']) }}" class="btn btn-outline-primary"><i class="bi bi-file-earmark-pdf"></i> Descargar PDF</a>
            <a href="{{ route('reportes.index', $parametros + ['formato' => 'imprimir']) }}" class="btn btn-outline-primary" target="_blank" rel="noopener"><i class="bi bi-printer"></i> Imprimir</a>
        </div>
    </div>

    <form method="GET" class="panel mb-3 row g-2 align-items-end">
        <div class="col-md-4">
            <label for="tipo" class="form-label">Reporte</label>
            <select id="tipo" name="tipo" class="form-select">
                @foreach ($tipos as $clave => $nombre)
                    <option value="{{ $clave }}" @selected($tipo === $clave)>{{ $nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="desde" class="form-label">Desde</label>
            <input type="date" id="desde" name="desde" value="{{ $desde->toDateString() }}" @class(['form-control', 'is-invalid' => $errors->has('desde')])>
        </div>
        <div class="col-md-3">
            <label for="hasta" class="form-label">Hasta</label>
            <input type="date" id="hasta" name="hasta" value="{{ $hasta->toDateString() }}" @class(['form-control', 'is-invalid' => $errors->has('hasta')])>
            @error('hasta') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-play-fill"></i> Generar</button>
        </div>
    </form>

    <div class="panel tabla-contenedor">
        <h2><i class="bi bi-table"></i> {{ $reporte['titulo'] }} · {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }}</h2>
        @include('reportes.tabla')
    </div>
@endsection
