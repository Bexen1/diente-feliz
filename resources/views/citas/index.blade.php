@extends('layouts.app')

@section('titulo', 'Agenda')

@php
    $usuario = auth()->user();
    $soloPropia = ! $usuario->veTodaLaAgenda();
    $paleta = ['#0f766e', '#2563eb', '#c2410c', '#7c3aed', '#be185d', '#15803d'];
    $colorDoctor = $doctores->values()->mapWithKeys(fn ($d, $i) => [$d->id => $paleta[$i % count($paleta)]]);
    $indiceFranja = array_flip($franjas);
@endphp

@section('contenido')
    @php
        $filtros = ['doctor_id' => $filtroDoctor, 'box_id' => $filtroBox, 'sucursal_id' => $filtroSucursal, 'vista' => $vista];
        $paso = $vista === 'semana' ? 7 : 1;
    @endphp

    <div class="titulo-pagina">
        <div>
            <h1>Agenda</h1>
            <small class="text-body-secondary">
                @if ($vista === 'semana')
                    Semana del {{ $desde->locale('es')->isoFormat('D [de] MMMM') }} al {{ $hasta->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}
                @else
                    {{ ucfirst($fecha->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY')) }}
                @endif
            </small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <div class="btn-group" role="group" aria-label="Vista de la agenda">
                <a href="{{ route('citas.index', ['fecha' => $fecha->toDateString(), 'vista' => 'dia'] + $filtros) }}" @class(['btn', 'btn-primary' => $vista === 'dia', 'btn-outline-primary' => $vista !== 'dia'])><i class="bi bi-calendar-day"></i> Día</a>
                <a href="{{ route('citas.index', ['fecha' => $fecha->toDateString(), 'vista' => 'semana'] + $filtros) }}" @class(['btn', 'btn-primary' => $vista === 'semana', 'btn-outline-primary' => $vista !== 'semana'])><i class="bi bi-calendar-week"></i> Semana</a>
            </div>
            <a class="btn btn-outline-primary" href="{{ route('citas.index', ['fecha' => $fecha->copy()->subDays($paso)->toDateString()] + $filtros) }}" aria-label="Anterior"><i class="bi bi-chevron-left"></i></a>
            <a class="btn btn-outline-primary" href="{{ route('citas.index', $filtros) }}"><i class="bi bi-calendar-event"></i> Hoy</a>
            <a class="btn btn-outline-primary" href="{{ route('citas.index', ['fecha' => $fecha->copy()->addDays($paso)->toDateString()] + $filtros) }}" aria-label="Siguiente"><i class="bi bi-chevron-right"></i></a>
        </div>
    </div>

    <form method="GET" class="panel mb-3 row g-2 align-items-end">
        <input type="hidden" name="vista" value="{{ $vista }}">
        <div class="col-sm-6 col-lg-2">
            <label for="filtro-fecha" class="form-label">Día</label>
            <input type="date" id="filtro-fecha" name="fecha" value="{{ $fecha->toDateString() }}" class="form-control">
        </div>
        @unless ($soloPropia)
            <div class="col-sm-6 col-lg-3">
                <label for="filtro-doctor" class="form-label">Profesional</label>
                <select id="filtro-doctor" name="doctor_id" class="form-select">
                    <option value="">Todos</option>
                    @foreach ($doctores as $doctor)
                        <option value="{{ $doctor->id }}" @selected($filtroDoctor == $doctor->id)>{{ $doctor->nombre }}</option>
                    @endforeach
                </select>
            </div>
        @endunless
        <div class="col-sm-6 col-lg-3">
            <label for="filtro-sucursal" class="form-label">Sucursal</label>
            <select id="filtro-sucursal" name="sucursal_id" class="form-select">
                <option value="">Todas</option>
                @foreach ($sucursales as $sucursal)
                    <option value="{{ $sucursal->id }}" @selected($filtroSucursal == $sucursal->id)>{{ $sucursal->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-6 col-lg-2">
            <label for="filtro-box" class="form-label">Box</label>
            <select id="filtro-box" name="box_id" class="form-select">
                <option value="">Todos</option>
                @foreach ($todosLosBox as $box)
                    <option value="{{ $box->id }}" @selected($filtroBox == $box->id)>{{ $box->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-12 col-lg-2">
            <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-funnel"></i> Filtrar</button>
        </div>
    </form>

    @foreach ($bloqueos as $bloqueo)
        <div class="alert alert-warning align-items-center">
            <i class="bi bi-calendar-x"></i>
            <span><strong>{{ $bloqueo->alcance }}</strong> · bloqueado {{ $bloqueo->periodo }} · {{ $bloqueo->motivo }}</span>
        </div>
    @endforeach

    <div class="row g-3">
        <div class="col-xl-8">
            @if ($vista === 'semana')
                @include('citas.semana')
            @else
            <div class="panel agenda-contenedor">
                <div class="agenda" id="agenda"
                     style="grid-template-columns: 56px repeat({{ max($boxes->count(), 1) }}, minmax(140px, 1fr)); grid-template-rows: auto repeat({{ count($franjas) }}, 22px);">
                    <div class="agenda-encabezado" style="grid-column: 1; grid-row: 1;"></div>
                    @foreach ($boxes as $i => $box)
                        <div class="agenda-encabezado" style="grid-column: {{ $i + 2 }}; grid-row: 1;">{{ $box->nombre }}</div>
                    @endforeach

                    @foreach ($franjas as $f => $franja)
                        <div class="agenda-hora mono" style="grid-column: 1; grid-row: {{ $f + 2 }};">
                            {{ str_ends_with($franja, ':00') ? $franja : '' }}
                        </div>
                        @foreach ($boxes as $i => $box)
                            <button type="button" class="agenda-celda" style="grid-column: {{ $i + 2 }}; grid-row: {{ $f + 2 }};"
                                    data-box="{{ $box->id }}" data-hora="{{ $franja }}"
                                    aria-label="Agendar en {{ $box->nombre }} a las {{ $franja }}"></button>
                        @endforeach
                    @endforeach

                    @foreach ($citas->whereNotIn('estado', \App\Models\Cita::ESTADOS_LIBERAN_HORARIO) as $cita)
                        @php
                            $columna = $boxes->search(fn ($b) => $b->id === $cita->box_id);
                            $fila = $indiceFranja[$cita->hora_inicio->format('H:i')] ?? null;
                        @endphp
                        @if ($columna !== false && $fila !== null)
                            <div class="agenda-cita" style="grid-column: {{ $columna + 2 }}; grid-row: {{ $fila + 2 }} / span {{ intdiv($cita->duracion, 15) }}; border-left-color: {{ $colorDoctor[$cita->doctor_id] ?? '#888' }};">
                                <strong class="mono">{{ $cita->hora_inicio->format('H:i') }}</strong> · {{ $cita->paciente->nombre }}
                                <div class="small text-body-secondary">{{ $cita->doctor->nombre }}</div>
                            </div>
                        @endif
                    @endforeach
                </div>
                @if ($boxes->isEmpty())
                    <p class="text-body-secondary mt-2 mb-0">No hay box habilitados.</p>
                @endif
            </div>
            @endif
        </div>

        <div class="col-xl-4">
            <form method="POST" action="{{ route('citas.store') }}" class="panel" id="form-cita">
                @csrf
                <h2 class="panel-titulo"><i class="bi bi-calendar-plus"></i> Agendar cita</h2>

                @if ($errors->any())
                    <div class="alert alert-danger py-2" role="alert">{{ $errors->first() }}</div>
                @endif

                <div class="mb-2">
                    <label for="rut" class="form-label">RUT del paciente</label>
                    <input type="text" id="rut" name="rut" class="form-control mono" list="pacientes-rut" value="{{ old('rut', request('rut')) }}" placeholder="12345678-5" autocomplete="off" required>
                    <datalist id="pacientes-rut">
                        @foreach ($pacientes as $paciente)
                            <option value="{{ $paciente->rut }}">{{ $paciente->nombre }}</option>
                        @endforeach
                    </datalist>
                </div>

                <div class="mb-2">
                    <label for="doctor_id" class="form-label">Dentista</label>
                    @if ($soloPropia)
                        <input type="hidden" name="doctor_id" value="{{ $usuario->doctor?->id }}">
                        <input type="text" id="doctor_id" class="form-control" value="{{ $usuario->nombre }}" disabled>
                    @else
                        <select id="doctor_id" name="doctor_id" class="form-select" required>
                            @foreach ($doctores as $doctor)
                                <option value="{{ $doctor->id }}" @selected(old('doctor_id', $filtroDoctor) == $doctor->id)>{{ $doctor->nombre }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label for="box_id" class="form-label">Box</label>
                        <select id="box_id" name="box_id" class="form-select" required>
                            @foreach ($todosLosBox as $box)
                                <option value="{{ $box->id }}" @selected(old('box_id', $filtroBox) == $box->id)>{{ $box->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label for="hora" class="form-label">Hora</label>
                        <select id="hora" name="hora" class="form-select mono" required>
                            @foreach ($franjas as $franja)
                                <option value="{{ $franja }}" @selected(old('hora', '09:00') === $franja)>{{ $franja }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label for="fecha" class="form-label">Fecha</label>
                        <input type="date" id="fecha" name="fecha" class="form-control" value="{{ old('fecha', $fecha->toDateString()) }}" required>
                    </div>
                    <div class="col-6">
                        <label for="duracion" class="form-label">Duración</label>
                        <select id="duracion" name="duracion" class="form-select">
                            @foreach (\App\Models\Cita::DURACIONES as $minutos)
                                <option value="{{ $minutos }}" @selected(old('duracion') == $minutos)>{{ $minutos }} minutos</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-calendar-check"></i> Agendar</button>
            </form>
        </div>
    </div>

    @if ($vista === 'dia')
    <div class="panel mt-3 tabla-contenedor">
        <h2><i class="bi bi-list-check"></i> Citas del día</h2>
        <table class="table align-middle mb-0">
            <thead><tr><th>Hora</th><th>Paciente</th><th>RUT</th><th>Dentista</th><th>Box</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            @forelse ($citas as $cita)
                <tr>
                    <td class="mono">{{ $cita->hora_inicio->format('H:i') }}–{{ $cita->hora_fin->format('H:i') }}</td>
                    <td><a href="{{ route('pacientes.show', $cita->paciente) }}">{{ $cita->paciente->nombre }}</a></td>
                    <td class="mono">{{ $cita->paciente->rut }}</td>
                    <td>{{ $cita->doctor->nombre }}</td>
                    <td>{{ $cita->box->nombre }}</td>
                    <td>
                        @include('partials.estado-cita', ['estado' => $cita->estado])
                        @if (\App\Services\CitaService::pendienteDeCierre($cita))
                            <span class="badge bg-warning-subtle text-warning-emphasis"><i class="bi bi-hourglass-bottom"></i> Pendiente de cierre</span>
                        @endif
                    </td>
                    <td class="text-end text-nowrap">
                        @can('modificar', $cita)
                            @if (\App\Services\CitaService::pendienteDeCierre($cita))
                                @include('citas.boton-estado', ['estado' => 'no_asistio', 'texto' => 'No asistió', 'clase' => 'btn-outline-secondary', 'icono' => 'bi-person-x'])
                            @endif
                            @if (in_array($cita->estado, ['agendada', 'reagendada']))
                                @include('citas.boton-estado', ['estado' => 'confirmada', 'texto' => 'Confirmar', 'clase' => 'btn-outline-primary', 'icono' => 'bi-telephone-check'])
                            @endif
                            @if (in_array($cita->estado, ['agendada', 'reagendada', 'confirmada']))
                                @include('citas.boton-estado', ['estado' => 'atendida', 'texto' => 'Atendida', 'clase' => 'btn-outline-success', 'icono' => 'bi-check2-circle'])
                                <a href="{{ route('citas.edit', $cita) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-repeat"></i> Reagendar</a>
                                <a href="{{ route('citas.cancelar', $cita) }}" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i> Cancelar</a>
                            @endif
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-body-secondary">No hay citas para este día.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('js/agenda.js') }}"></script>
@endpush
