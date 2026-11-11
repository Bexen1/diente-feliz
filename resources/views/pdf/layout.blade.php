<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>@yield('titulo') · Diente Feliz</title>
    <style>
        @page { margin: 2.2cm 1.6cm 1.8cm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; color: #1f2d2b; line-height: 1.45; }
        header { position: fixed; top: -1.6cm; left: 0; right: 0; height: 1.3cm; border-bottom: 2px solid #0f766e; }
        footer { position: fixed; bottom: -1.2cm; left: 0; right: 0; height: .8cm; font-size: 8px; color: #6b7c79; border-top: 1px solid #d7e3e1; padding-top: 4px; }
        .encabezado td { vertical-align: middle; padding: 0; }
        .logo { width: 26px; height: 26px; }
        .clinica { font-size: 13px; font-weight: bold; color: #0f766e; }
        .direccion { font-size: 8px; color: #6b7c79; }
        .documento { text-align: right; }
        .documento .nombre { font-size: 13px; font-weight: bold; }
        h2 { font-size: 11px; color: #0f766e; text-transform: uppercase; letter-spacing: .5px; margin: 16px 0 6px; padding-bottom: 3px; border-bottom: 1px solid #cfe4e1; }
        table { width: 100%; border-collapse: collapse; }
        .datos th { text-align: left; width: 30%; color: #4b5d5a; font-weight: bold; padding: 3px 6px 3px 0; vertical-align: top; }
        .datos td { padding: 3px 0; vertical-align: top; }
        .tabla th { background: #e6f2f0; color: #0b5d57; text-align: left; font-size: 9px; padding: 5px 6px; border-bottom: 1px solid #b9d8d4; }
        .tabla td { padding: 4px 6px; border-bottom: 1px solid #e3ebea; vertical-align: top; }
        .tabla tfoot td { border-bottom: 0; }
        .tabla th.numero { text-align: right; }
        .derecha { text-align: right; }
        .numero { text-align: right; white-space: nowrap; }
        .negrita { font-weight: bold; }
        .tenue { color: #6b7c79; }
        .vacio { color: #6b7c79; font-style: italic; }
        .bloque { padding: 6px 0; border-bottom: 1px solid #e3ebea; page-break-inside: avoid; }
        .etiqueta { display: inline-block; padding: 1px 6px; border-radius: 8px; background: #e6f2f0; color: #0b5d57; font-size: 8px; font-weight: bold; }
        .firmas { margin-top: 60px; }
        .firmas td { width: 50%; padding: 0 20px; text-align: center; }
        .firma { border-top: 1px solid #333; padding-top: 4px; }
        .sello { border: 3px solid #b42318; color: #b42318; font-weight: bold; font-size: 16px; letter-spacing: 3px; padding: 4px 14px; display: inline-block; }
        .destacado { font-size: 24px; font-weight: bold; color: #0f766e; }
    </style>
</head>
<body>
<header>
    <table class="encabezado">
        <tr>
            <td style="width: 34px;"><img class="logo" src="data:image/svg+xml;base64,{{ base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="#0f766e" d="M7.4 2C4.4 2 2.4 4.4 2.4 7.5c0 2.1.7 3.6 1.4 5 .6 1.2 1 2.6 1.2 4.4.3 2.7 1 5.1 2.6 5.1 1.4 0 1.8-1.7 2.2-3.6.3-1.5.7-3 2.2-3s1.9 1.5 2.2 3c.4 1.9.8 3.6 2.2 3.6 1.6 0 2.3-2.4 2.6-5.1.2-1.8.6-3.2 1.2-4.4.7-1.4 1.4-2.9 1.4-5C21.6 4.4 19.6 2 16.6 2c-1.7 0-2.8.6-4.6 1.5C10.2 2.6 9.1 2 7.4 2z"/></svg>') }}" alt=""></td>
            <td>
                <div class="clinica">Clínica Dental Diente Feliz</div>
                <div class="direccion">Av. Principal 123, Concepción</div>
            </td>
            <td class="documento">
                <div class="nombre">@yield('documento')</div>
                <div>@yield('referencia')</div>
            </td>
        </tr>
    </table>
</header>
<footer>
    Generado el {{ now()->format('d/m/Y H:i') }}@auth por {{ auth()->user()->nombre }}@endauth
</footer>
<main>
    @yield('contenido')
</main>
</body>
</html>
