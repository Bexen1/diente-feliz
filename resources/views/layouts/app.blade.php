<!doctype html>
@php
    $usuario = auth()->user();
    $tema = array_key_exists($usuario->tema, \App\Models\Usuario::TEMAS) ? $usuario->tema : 'auto';
    [$temaNombre, $temaIcono, $temaSiguiente] = \App\Models\Usuario::TEMAS[$tema];
@endphp
<html lang="es"@if ($tema !== 'auto') class="{{ $tema }}"@endif>
<head>
    @include('partials.head')
    <title>@yield('titulo') · Diente Feliz</title>
    @stack('estilos')
</head>
<body>
@php
    $esAdmin = $usuario->esAdministrador();
    $mantenedores = $usuario->puede('mantenedores.gestionar');
    $menu = array_filter([
        ['inicio', 'Inicio', 'bi-house-door', true],
        ['citas.index', 'Agenda', 'bi-calendar3', true],
        ['pacientes.index', 'Pacientes', 'bi-people', true],
        ['presupuestos.index', 'Presupuestos y pagos', 'bi-receipt', $usuario->puede('presupuestos.ver', 'presupuestos.gestionar', 'pagos.registrar')],
        ['dashboard', 'Dashboard', 'bi-bar-chart-line', $usuario->puede('dashboard.ver')],
        ['reportes.index', 'Reportes', 'bi-file-earmark-bar-graph', $usuario->puede('reportes.ver')],
    ], fn ($opcion) => $opcion[3]);
    $menuAdmin = array_filter([
        ['usuarios.index', 'Usuarios', 'bi-person-gear', $esAdmin],
        ['roles.index', 'Roles y permisos', 'bi-shield-lock', $esAdmin],
        ['doctores.index', 'Doctores y horarios', 'bi-person-badge', $esAdmin],
        ['bloqueos.index', 'Bloqueos de agenda', 'bi-calendar-x', $mantenedores],
        ['sucursales.index', 'Sucursales', 'bi-building', $mantenedores],
        ['box.index', 'Box', 'bi-door-open', $mantenedores],
        ['convenios.index', 'Convenios', 'bi-patch-check', $mantenedores],
        ['tratamientos.index', 'Tratamientos', 'bi-clipboard2-pulse', $mantenedores],
        ['auditoria.index', 'Auditoría', 'bi-shield-check', $usuario->puede('auditoria.ver')],
    ], fn ($opcion) => $opcion[3]);
    $palabras = array_values(array_filter(explode(' ', $usuario->nombre), fn ($p) => ! str_ends_with($p, '.')));
    $iniciales = mb_strtoupper(mb_substr($palabras[0] ?? '', 0, 1).mb_substr($palabras[1] ?? '', 0, 1));
@endphp
<div class="d-md-flex min-vh-100">
    <aside class="barra-lateral no-imprimir">
        <a href="{{ route('inicio') }}" class="marca">
            <span class="marca-logo">@include('partials.logo')</span>
            <span>
                <span class="marca-nombre">Diente Feliz</span>
                <small>Clínica dental</small>
            </span>
        </a>

        <nav class="menu" aria-label="Menú principal">
            @foreach ($menu as [$ruta, $texto, $icono])
                @if (Route::has($ruta))
                    <a href="{{ route($ruta) }}" @class(['activo' => request()->routeIs(Str::before($ruta, '.').'*')])>
                        <i class="bi {{ $icono }}"></i><span>{{ $texto }}</span>
                    </a>
                @endif
            @endforeach

            @if ($menuAdmin !== [])
                <div class="menu-titulo">Administración</div>
                @foreach ($menuAdmin as [$ruta, $texto, $icono])
                    @if (Route::has($ruta))
                        <a href="{{ route($ruta) }}" @class(['activo' => request()->routeIs(Str::before($ruta, '.').'*')])>
                            <i class="bi {{ $icono }}"></i><span>{{ $texto }}</span>
                        </a>
                    @endif
                @endforeach
            @endif
        </nav>

        <div class="usuario-actual">
            <div class="d-flex align-items-center gap-2">
                <span class="avatar" aria-hidden="true">{{ $iniciales }}</span>
                <div class="text-truncate">
                    <div class="usuario-nombre text-truncate">{{ $usuario->nombre }}</div>
                    <span class="etiqueta-rol">{{ $usuario->rol->nombre }}</span>
                </div>
            </div>
            <div class="usuario-acciones">
                <a href="{{ route('perfil.edit') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-person"></i> Perfil</a>
                <form method="POST" action="{{ route('perfil.tema') }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="tema" value="{{ $temaSiguiente }}">
                    <button type="submit" class="btn btn-sm btn-outline-secondary boton-tema" aria-label="{{ $temaNombre }}" title="{{ $temaNombre }}"><i class="bi {{ $temaIcono }}"></i></button>
                </form>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-right"></i> Salir</button>
                </form>
            </div>
        </div>
    </aside>

    <main class="flex-grow-1 p-3 p-md-4 p-xl-5 contenido">
        @if (session('exito'))
            <div class="alert alert-success align-items-center" role="status">
                <i class="bi bi-check-circle-fill"></i><span class="flex-grow-1">{{ session('exito') }}</span>
                @if (session('comprobante') && Route::has('pagos.comprobante'))
                    <a href="{{ route('pagos.comprobante', session('comprobante')) }}" class="btn btn-sm btn-outline-success" target="_blank" rel="noopener"><i class="bi bi-printer"></i> Imprimir comprobante</a>
                @endif
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle-fill"></i><span>{{ session('error') }}</span></div>
        @endif

        @yield('contenido')
    </main>
</div>
@stack('scripts')
</body>
</html>
