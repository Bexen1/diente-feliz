<!doctype html>
<html lang="es">
<head>
    @include('partials.head')
    <title>@yield('titulo') · Diente Feliz</title>
    @stack('estilos')
</head>
<body>
@php
    $usuario = auth()->user();
    $menu = [
        ['inicio', 'Inicio', 'bi-house-door', []],
        ['citas.index', 'Agenda', 'bi-calendar3', []],
        ['pacientes.index', 'Pacientes', 'bi-people', []],
        ['presupuestos.index', 'Presupuestos y pagos', 'bi-receipt', ['Dentista', 'Recepcionista']],
        ['dashboard', 'Dashboard', 'bi-bar-chart-line', ['Administrador']],
        ['reportes.index', 'Reportes', 'bi-file-earmark-bar-graph', ['Administrador']],
    ];
    $menuAdmin = [
        ['usuarios.index', 'Usuarios', 'bi-person-gear'],
        ['roles.index', 'Roles y permisos', 'bi-shield-lock'],
        ['doctores.index', 'Doctores y horarios', 'bi-person-badge'],
        ['bloqueos.index', 'Bloqueos de agenda', 'bi-calendar-x'],
        ['sucursales.index', 'Sucursales', 'bi-building'],
        ['box.index', 'Box', 'bi-door-open'],
        ['convenios.index', 'Convenios', 'bi-patch-check'],
        ['tratamientos.index', 'Tratamientos', 'bi-clipboard2-pulse'],
        ['auditoria.index', 'Auditoría', 'bi-shield-check'],
    ];
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
            @foreach ($menu as [$ruta, $texto, $icono, $roles])
                @if (Route::has($ruta) && ($roles === [] || $usuario->tieneRol(...$roles)))
                    <a href="{{ route($ruta) }}" @class(['activo' => request()->routeIs(Str::before($ruta, '.').'*')])>
                        <i class="bi {{ $icono }}"></i><span>{{ $texto }}</span>
                    </a>
                @endif
            @endforeach

            @if ($usuario->esAdministrador())
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
