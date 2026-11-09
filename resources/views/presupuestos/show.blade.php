@extends('layouts.app')

@section('titulo', 'Presupuesto N.° '.$presupuesto->id)

@section('contenido')
    @php
        $usuario = auth()->user();
        $gestiona = $usuario->tieneRol('Recepcionista');
        $subtotal = $presupuesto->subtotal();
        $descuento = $presupuesto->descuento();
        $saldo = $presupuesto->saldoPendiente();
    @endphp

    <div class="titulo-pagina">
        <div>
            <h1>Presupuesto N.° {{ $presupuesto->id }} @include('presupuestos.estado', ['estado' => $presupuesto->estado])</h1>
            <small class="text-body-secondary">
                <a href="{{ route('pacientes.show', $presupuesto->paciente) }}">{{ $presupuesto->paciente->nombre }}</a>
                · {{ $presupuesto->paciente->convenio?->institucion ?? 'Particular' }}
                · {{ $presupuesto->doctor->nombre }} · {{ $presupuesto->created_at->format('d/m/Y') }}
            </small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('presupuestos.imprimir', $presupuesto) }}" class="btn btn-outline-primary" target="_blank" rel="noopener"><i class="bi bi-printer"></i> Imprimir orden</a>
            @if ($gestiona && $presupuesto->estado === 'borrador')
                <a href="{{ route('presupuestos.edit', $presupuesto) }}" class="btn btn-outline-primary"><i class="bi bi-pencil"></i> Editar</a>
                <form method="POST" action="{{ route('presupuestos.aceptar', $presupuesto) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-primary"><i class="bi bi-hand-thumbs-up"></i> Aceptar presupuesto</button>
                </form>
            @endif
            @if ($gestiona && in_array($presupuesto->estado, ['borrador', 'aceptado']))
                <a href="{{ route('presupuestos.anular.confirmar', $presupuesto) }}" class="btn btn-outline-danger"><i class="bi bi-x-circle"></i> Anular</a>
            @endif
        </div>
    </div>

    @error('estado') <div class="alert alert-danger">{{ $message }}</div> @enderror

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="panel tabla-contenedor">
                <h2><i class="bi bi-clipboard2-pulse"></i> Tratamientos</h2>
                <table class="table align-middle mb-0">
                    <thead><tr><th>Tratamiento</th><th class="text-end">Cantidad</th><th class="text-end">Subtotal</th></tr></thead>
                    <tbody>
                    @foreach ($presupuesto->detalles as $detalle)
                        <tr>
                            <td>{{ $detalle->tratamiento->nombre }}</td>
                            <td class="text-end mono">
                                {{ $detalle->cantidad }}
                                @if (in_array($presupuesto->estado, ['aceptado', 'pagado']))
                                    <span @class(['small', 'text-success' => $detalle->pendientes() === 0, 'text-body-secondary' => $detalle->pendientes() > 0])>
                                        · {{ $detalle->cantidad_realizada }} realizado{{ $detalle->cantidad_realizada === 1 ? '' : 's' }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-end mono">@pesos($detalle->subtotal)</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr><td colspan="2" class="text-end">Subtotal</td><td class="text-end mono">@pesos($subtotal)</td></tr>
                    <tr>
                        <td colspan="2" class="text-end">Descuento por convenio
                            @if ($subtotal > 0 && $descuento > 0) ({{ round($descuento / $subtotal * 100, 2) }} %) @endif</td>
                        <td class="text-end mono">−@pesos($descuento)</td>
                    </tr>
                    <tr class="fw-bold"><td colspan="2" class="text-end">Total</td><td class="text-end mono">@pesos($presupuesto->total_calculado)</td></tr>
                    </tfoot>
                </table>
            </div>
        </div>

        @if ($gestiona)
            <div class="col-lg-5">
                <div class="panel mb-3">
                    <h2><i class="bi bi-cash-stack"></i> Pagos registrados</h2>
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Fecha</th><th class="text-end">Monto</th><th></th></tr></thead>
                        <tbody>
                        @forelse ($presupuesto->pagos as $pago)
                            <tr @class(['text-decoration-line-through text-body-secondary' => $pago->estado === 'anulado'])>
                                <td>
                                    <span class="mono">{{ $pago->fecha->format('d/m/Y') }}</span>
                                    <div class="small text-body-secondary">{{ $pago->nombre_metodo }}</div>
                                </td>
                                <td class="text-end mono">@pesos($pago->monto)</td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('pagos.comprobante', $pago) }}" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" aria-label="Comprobante del pago"><i class="bi bi-receipt-cutoff"></i></a>
                                    @if ($pago->estado === 'anulado')
                                        <span class="badge bg-secondary-subtle text-secondary-emphasis"><i class="bi bi-slash-circle"></i> Anulado</span>
                                    @elseif ($usuario->esAdministrador())
                                        <a href="{{ route('pagos.anular.confirmar', $pago) }}" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i> Anular</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-body-secondary">Sin pagos.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-between align-items-baseline border-top pt-3 mt-1">
                        <span class="fw-semibold">Saldo pendiente</span>
                        <span class="fs-4 fw-bold mono">@pesos(in_array($presupuesto->estado, ['aceptado', 'pagado']) ? $saldo : $presupuesto->total_calculado)</span>
                    </div>
                </div>

                @if ($presupuesto->estado === 'aceptado')
                    <form method="POST" action="{{ route('pagos.store', $presupuesto) }}" class="panel">
                        @csrf
                        <h2><i class="bi bi-cash-coin"></i> Registrar pago o abono</h2>
                        @include('partials.campo', ['nombre' => 'monto', 'etiqueta' => 'Monto (CLP)', 'tipo' => 'number', 'paso' => '1'])
                        @include('partials.campo', ['nombre' => 'fecha', 'etiqueta' => 'Fecha', 'tipo' => 'date', 'valor' => now()->toDateString()])
                        <div class="mb-3">
                            <label for="metodo" class="form-label">Método de pago</label>
                            <select id="metodo" name="metodo" @class(['form-select', 'is-invalid' => $errors->has('metodo')])>
                                @foreach (\App\Models\Pago::METODOS as $clave => $nombre)
                                    <option value="{{ $clave }}" @selected(old('metodo', 'efectivo') === $clave)>{{ $nombre }}</option>
                                @endforeach
                            </select>
                            @error('metodo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-cash-coin"></i> Registrar pago</button>
                    </form>
                @endif
            </div>
        @endif
    </div>
@endsection
