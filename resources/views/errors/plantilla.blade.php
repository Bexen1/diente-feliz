<!doctype html>
<html lang="es">
<head>
    @include('partials.head')
    <title>@yield('codigo') · Diente Feliz</title>
</head>
<body class="d-flex align-items-center min-vh-100">
<main class="container" style="max-width: 480px;">
    <div class="panel text-center p-5">
        <span class="marca-logo mb-3" style="width: 56px; height: 56px;">@include('partials.logo', ['tamano' => 28])</span>
        <div class="display-6 fw-bold" style="color: var(--acento);">@yield('codigo')</div>
        <h1 class="h4 mt-2">@yield('titulo')</h1>
        <p class="text-body-secondary">@yield('mensaje')</p>
        <a href="{{ url('/') }}" class="btn btn-primary"><i class="bi bi-house-door"></i> Volver al inicio</a>
    </div>
</main>
</body>
</html>
