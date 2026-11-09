@php
    $porDia = $citas->groupBy(fn ($c) => $c->hora_inicio->toDateString());
@endphp
<div class="panel tabla-contenedor">
    <div class="semana">
        @for ($i = 0; $i < 5; $i++)
            @php
                $dia = $desde->copy()->addDays($i);
                $delDia = $porDia->get($dia->toDateString(), collect());
                $vigentes = $delDia->whereNotIn('estado', \App\Models\Cita::ESTADOS_LIBERAN_HORARIO)->count();
            @endphp
            <div @class(['semana-dia', 'semana-hoy' => $dia->isToday()])>
                <a href="{{ route('citas.index', ['fecha' => $dia->toDateString(), 'vista' => 'dia'] + $filtros) }}" class="semana-encabezado">
                    <span>{{ ucfirst($dia->locale('es')->isoFormat('ddd D')) }}</span>
                    <span class="badge bg-primary-subtle text-primary-emphasis">{{ $vigentes }}</span>
                </a>
                @forelse ($delDia as $cita)
                    <div @class(['semana-cita', 'semana-cita-libre' => in_array($cita->estado, \App\Models\Cita::ESTADOS_LIBERAN_HORARIO)])
                         style="border-left-color: {{ $colorDoctor[$cita->doctor_id] ?? '#888' }};">
                        <div class="d-flex justify-content-between gap-1">
                            <strong class="mono">{{ $cita->hora_inicio->format('H:i') }}</strong>
                            <span class="small text-body-secondary">{{ $cita->box->nombre }}</span>
                        </div>
                        <div class="text-truncate">{{ $cita->paciente->nombre }}</div>
                        <div class="small text-body-secondary text-truncate">{{ $cita->doctor->nombre }}</div>
                        @if (in_array($cita->estado, \App\Models\Cita::ESTADOS_LIBERAN_HORARIO) || $cita->estado === 'atendida')
                            <div class="mt-1">@include('partials.estado-cita', ['estado' => $cita->estado])</div>
                        @endif
                    </div>
                @empty
                    <p class="small text-body-secondary mb-0 px-1">Sin citas</p>
                @endforelse
            </div>
        @endfor
    </div>
</div>
