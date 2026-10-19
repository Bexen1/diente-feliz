<!doctype html>
<html lang="es">
<head>
    @include('partials.head')
    <title>Iniciar sesión · Diente Feliz</title>
</head>
<body>
<div class="login">
    <section class="login-portada" aria-hidden="true">
        <div class="marca text-white">
            <span class="marca-logo" style="background: rgba(255,255,255,.15); box-shadow: none;">@include('partials.logo')</span>
            <span><span class="marca-nombre">Diente Feliz</span><small class="text-white-50">Clínica dental</small></span>
        </div>

        <div>
            <h1>Fichas clínicas, agenda y finanzas en un solo lugar.</h1>
            <p class="mb-4">Sistema de gestión para el personal de la Clínica Dental Diente Feliz.</p>
            <div class="beneficio"><i class="bi bi-calendar-check"></i> Agenda sin cruces de horario</div>
            <div class="beneficio"><i class="bi bi-clipboard2-pulse"></i> Ficha clínica con odontograma digital</div>
            <div class="beneficio"><i class="bi bi-shield-lock"></i> Datos de salud cifrados y auditados</div>
        </div>

        <small class="text-white-50">Proyecto Integrado · INACAP</small>
        @include('partials.logo', ['tamano' => 320, 'clase' => 'diente-fondo'])
    </section>

    <main class="login-formulario">
        <div class="login-tarjeta">
            <div class="marca mb-4 d-lg-none">
                <span class="marca-logo">@include('partials.logo')</span>
                <span><span class="marca-nombre">Diente Feliz</span><small>Clínica dental</small></span>
            </div>

            <h2 class="h3 mb-1">Bienvenido</h2>
            <p class="text-body-secondary mb-4">Ingrese con su cuenta del personal de la clínica.</p>

            @if ($errors->any())
                <div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle-fill"></i><span>{{ $errors->first() }}</span></div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="panel p-4">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">Correo</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" id="email" name="email" value="{{ old('email') }}"
                               class="form-control" required autofocus autocomplete="username" placeholder="nombre@dientefeliz.test">
                    </div>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" id="password" name="password"
                               class="form-control" required autocomplete="current-password">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100"><i class="bi bi-box-arrow-in-right"></i> Ingresar</button>
            </form>
        </div>
    </main>
</div>
</body>
</html>
