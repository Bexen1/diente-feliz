@extends('layouts.app')

@section('titulo', 'Presupuestos')

@section('contenido')
    @php($verPagos = auth()->user()->puede('pagos.registrar'))

    <div class="titulo-pagina">
        <h1>Presupuestos y pagos</h1>
        @if ($verPagos)
            <a href="{{ route('presupuestos.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nuevo presupuesto</a>
        @endif
    </div>

    <form method="GET" class="panel mb-3 d-flex gap-2 align-items-end flex-wrap">
        <div>
            <label for="estado" class="form-label">Estado</label>
            <select id="estado" name="estado" class="form-select">
                <option value="">Todos</option>
                @foreach (\App\Models\Presupuesto::ESTADOS as $opcion)
                    <option value="{{ $opcion }}" @selected($estado === $opcion)>{{ ucfirst($opcion) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-outline-primary"><i class="bi bi-funnel"></i> Filtrar</button>
    </form>

    <div class="panel tabla-contenedor">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th>N.°</th><th>Fecha</th><th>Paciente</th><th>Doctor</th><th>Estado</th>
                <th class="text-end">Total</th>
                @if ($verPagos) <th class="text-end">Saldo</th> @endif
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse ($presupuestos as $presupuesto)
                <tr>
                    <td class="mono">{{ $presupuesto->id }}</td>
                    <td class="mono">{{ $presupuesto->created_at->format('d/m/Y') }}</td>
                    <td>{{ $presupuesto->paciente->nombre }}</td>
                    <td>{{ $presupuesto->doctor->nombre }}</td>
                    <td>@include('presupuestos.estado', ['estado' => $presupuesto->estado])</td>
                    <td class="text-end mono">@pesos($presupuesto->total_calculado)</td>
                    @if ($verPagos)
                        <td class="text-end mono">
                            @if (in_array($presupuesto->estado, ['aceptado', 'pagado']))
                                @pesos($presupuesto->total_calculado - $presupuesto->pagado)
                            @else
                                —
                            @endif
                        </td>
                    @endif
                    <td class="text-end"><a href="{{ route('presupuestos.show', $presupuesto) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> Ver</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-body-secondary">No hay presupuestos.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $presupuestos->links() }}</div>
@endsection
