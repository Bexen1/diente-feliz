<!doctype html>
<html lang="es" class="claro">
<head>
    @include('partials.head')
    <title>{{ $reporte['titulo'] }} · Diente Feliz</title>
    <style>body { background: #fff; } .hoja { max-width: 1000px; margin: 2rem auto; } @page { margin: 1.5cm; }</style>
</head>
<body>
<main class="hoja px-3">
    <div class="no-imprimir mb-3">
        <a href="{{ route('reportes.index', ['tipo' => $tipo, 'desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString()]) }}" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>

    <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
        <div class="marca">
            <span class="marca-logo">@include('partials.logo')</span>
            <div class="fw-bold">Clínica Dental Diente Feliz</div>
        </div>
        <div class="text-end">
            <div class="h5 mb-0">{{ $reporte['titulo'] }}</div>
            <small>Período: {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }}</small><br>
            <small>Generado el {{ now()->format('d/m/Y H:i') }} por {{ auth()->user()->nombre }}</small>
        </div>
    </div>

    @include('reportes.tabla')
</main>
</body>
</html>
