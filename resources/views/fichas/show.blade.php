@extends('layouts.app')

@section('titulo', 'Ficha · '.$paciente->nombre)

@php
    use App\Services\OdontogramaService;
@endphp

@section('contenido')
    <div class="titulo-pagina">
        <div>
            <h1>Ficha clínica</h1>
            <small class="text-body-secondary">{{ $paciente->nombre }} · <span class="mono">{{ $paciente->rut }}</span>
                · {{ $paciente->convenio?->institucion ?? 'Particular' }}</small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('fichas.exportar', $paciente) }}" class="btn btn-outline-primary" target="_blank" rel="noopener"><i class="bi bi-file-earmark-medical"></i> Exportar ficha</a>
            <a href="{{ route('fichas.exportar.json', $paciente) }}" class="btn btn-outline-primary"><i class="bi bi-filetype-json"></i> Descargar JSON</a>
            <a href="{{ route('pacientes.show', $paciente) }}" class="btn btn-outline-primary"><i class="bi bi-person-vcard"></i> Datos del paciente</a>
        </div>
    </div>

    <div class="panel mb-3">
        <div class="row small">
            <div class="col-md-4"><strong>Alergias:</strong> {{ $paciente->alergias ?: 'Sin registro' }}</div>
            <div class="col-md-4"><strong>Patologías base:</strong> {{ $paciente->patologias ?: 'Sin registro' }}</div>
            <div class="col-md-4"><strong>Medicamentos:</strong> {{ $paciente->medicamentos ?: 'Sin registro' }}</div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-8">
            <div class="panel h-100">
                <h2><i class="bi bi-grid-3x3-gap"></i> Odontograma</h2>

                <div class="odontograma" id="odontograma">
                    @foreach (OdontogramaService::FILAS as $titulo => [$derecha, $izquierda])
                        <div class="odontograma-titulo">{{ $titulo }}</div>
                        <div class="odontograma-fila">
                            @foreach ([$derecha, $izquierda] as $cuadrante)
                                <div class="odontograma-cuadrante">
                                    @foreach ($cuadrante as $pieza)
                                        @php($hallazgo = $odontograma[$pieza] ?? ['estado' => 'sano', 'caras' => []])
                                        <button type="button" class="pieza estado-{{ $hallazgo['estado'] }}"
                                                data-pieza="{{ $pieza }}" data-estado="{{ $hallazgo['estado'] }}"
                                                data-caras="{{ implode(',', $hallazgo['caras'] ?? []) }}"
                                                aria-pressed="false"
                                                aria-label="Pieza {{ $pieza }}: {{ OdontogramaService::ESTADOS[$hallazgo['estado']][1] }}">
                                            <span class="pieza-numero">{{ $pieza }}</span>
                                            @include('partials.diente', ['pieza' => $pieza, 'hallazgo' => $hallazgo])
                                            <span class="pieza-letra">{{ OdontogramaService::ESTADOS[$hallazgo['estado']][0] ?: '·' }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                <div class="leyenda small mt-3">
                    @foreach (OdontogramaService::ESTADOS as $clave => [$letra, $nombre])
                        <span class="leyenda-item">
                            @include('partials.diente', ['pieza' => 11, 'hallazgo' => ['estado' => $clave, 'caras' => in_array($clave, ['caries', 'restauracion']) ? ['oclusal', 'mesial'] : []]])
                            <strong>{{ $letra ?: '·' }}</strong> {{ $nombre }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <form method="POST" action="{{ route('fichas.hallazgo', $paciente) }}" class="panel h-100" id="form-hallazgo">
                @csrf
                @method('PUT')
                <h2><i class="bi bi-pencil-square"></i> Registrar hallazgo</h2>

                <div class="mb-3">
                    <label for="pieza" class="form-label">Pieza</label>
                    <select id="pieza" name="pieza" @class(['form-select', 'is-invalid' => $errors->has('pieza')])>
                        @foreach (OdontogramaService::piezas() as $pieza)
                            <option value="{{ $pieza }}" @selected(old('pieza') == $pieza)>{{ $pieza }}</option>
                        @endforeach
                    </select>
                    @error('pieza') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="estado" class="form-label">Estado</label>
                    <select id="estado" name="estado" class="form-select">
                        @foreach (OdontogramaService::ESTADOS as $clave => [$letra, $nombre])
                            <option value="{{ $clave }}" @selected(old('estado') === $clave)>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <fieldset class="mb-3">
                    <legend class="form-label fs-6">Caras afectadas</legend>
                    @foreach (OdontogramaService::CARAS as $cara)
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="caras[]" value="{{ $cara }}" id="cara-{{ $cara }}"
                                   @checked(in_array($cara, old('caras', [])))>
                            <label class="form-check-label" for="cara-{{ $cara }}">{{ ucfirst($cara) }}</label>
                        </div>
                    @endforeach
                </fieldset>

                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-lg"></i> Guardar hallazgo</button>
            </form>
        </div>
    </div>

    <div class="panel mb-3 tabla-contenedor">
        <h2><i class="bi bi-list-check"></i> Plan de tratamiento</h2>
        @error('detalle') <div class="alert alert-danger"><i class="bi bi-x-octagon-fill"></i><span>{{ $message }}</span></div> @enderror
        @forelse ($plan as $item)
            @php($presupuesto = $item['presupuesto'])
            <div @class(['mb-4' => ! $loop->last])>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <div>
                        <strong>Presupuesto N.° {{ $presupuesto->id }}</strong>
                        <span class="text-body-secondary small">· {{ $presupuesto->created_at->format('d/m/Y') }} · {{ $presupuesto->doctor->nombre }}</span>
                        @include('presupuestos.estado', ['estado' => $presupuesto->estado])
                    </div>
                    <span class="small mono">{{ $item['realizados'] }} de {{ $item['total'] }} realizados · {{ $item['avance'] }} %</span>
                </div>
                <div class="barra mb-2" role="img" aria-label="Avance del plan: {{ $item['avance'] }} por ciento">
                    <div style="width: {{ $item['avance'] }}%;"></div>
                </div>
                <table class="table table-sm align-middle mb-0">
                    <tbody>
                    @foreach ($presupuesto->detalles as $detalle)
                        <tr>
                            <td>{{ $detalle->tratamiento->nombre }}</td>
                            <td class="text-end mono" style="width: 120px;">{{ $detalle->cantidad_realizada }} / {{ $detalle->cantidad }}</td>
                            <td class="text-end" style="width: 190px;">
                                @if ($detalle->pendientes() > 0)
                                    <form method="POST" action="{{ route('detalles.realizado', $detalle) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success"><i class="bi bi-check2-circle"></i> Registrar realizado</button>
                                    </form>
                                @else
                                    <span class="badge bg-success-subtle text-success-emphasis"><i class="bi bi-check-circle"></i> Completo</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <p class="text-body-secondary mb-0">El paciente no tiene presupuestos aceptados.</p>
        @endforelse
    </div>

    <div class="panel mb-3 tabla-contenedor" id="historial">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <h2 class="mb-0"><i class="bi bi-clock-history"></i> Historial del odontograma</h2>
            <form method="GET" action="{{ route('fichas.show', $paciente) }}#historial" class="d-flex gap-2">
                <select name="pieza" class="form-select form-select-sm" aria-label="Filtrar por pieza">
                    <option value="">Todas las piezas</option>
                    @foreach (OdontogramaService::piezas() as $numero)
                        <option value="{{ $numero }}" @selected($piezaFiltro === $numero)>Pieza {{ $numero }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-funnel"></i> Filtrar</button>
            </form>
        </div>
        @if ($historial->isEmpty())
            <p class="text-body-secondary mb-0">Sin cambios registrados{{ $piezaFiltro ? ' para la pieza '.$piezaFiltro : '' }}.</p>
        @else
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Fecha</th><th>Pieza</th><th>Cambio</th><th>Caras</th><th>Registrado por</th></tr></thead>
                <tbody>
                @foreach ($historial as $cambio)
                    <tr>
                        <td class="mono">{{ $cambio['fecha']->format('d/m/Y H:i') }}</td>
                        <td class="mono">{{ $cambio['pieza'] }}</td>
                        <td>{{ $cambio['antes'] }} <i class="bi bi-arrow-right"></i> <strong>{{ $cambio['despues'] }}</strong></td>
                        <td>{{ implode(', ', $cambio['caras']) ?: '—' }}</td>
                        <td>{{ $cambio['usuario'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="panel mb-3">
                <h2><i class="bi bi-journal-plus"></i> Registrar atención</h2>
                <form method="POST" action="{{ route('fichas.evoluciones', $paciente) }}">
                    @csrf
                    <div class="row g-2">
                        <div class="col-sm-5">
                            @include('partials.campo', ['nombre' => 'fecha', 'etiqueta' => 'Fecha', 'tipo' => 'date', 'valor' => now()->toDateString()])
                        </div>
                        @if ($doctores->isNotEmpty())
                            <div class="col-sm-7 mb-3">
                                <label for="doctor_id" class="form-label">Doctor</label>
                                <select id="doctor_id" name="doctor_id" @class(['form-select', 'is-invalid' => $errors->has('doctor_id')])>
                                    @foreach ($doctores as $doctor)
                                        <option value="{{ $doctor->id }}" @selected(old('doctor_id') == $doctor->id)>{{ $doctor->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('doctor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        @endif
                    </div>
                    @include('partials.campo', ['nombre' => 'diagnostico', 'etiqueta' => 'Diagnóstico', 'tipo' => 'textarea', 'filas' => 2])
                    @include('partials.campo', ['nombre' => 'tratamiento_realizado', 'etiqueta' => 'Tratamiento realizado', 'tipo' => 'textarea', 'filas' => 2])
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar atención</button>
                </form>
            </div>

            <div class="panel">
                <h2><i class="bi bi-journal-medical"></i> Historial de atenciones</h2>
                @forelse ($ficha->evoluciones as $evolucion)
                    <div class="border-bottom py-2">
                        <div class="d-flex justify-content-between flex-wrap">
                            <strong class="mono">{{ $evolucion->fecha->format('d/m/Y') }}</strong>
                            <small class="text-body-secondary">{{ $evolucion->doctor->nombre }}</small>
                        </div>
                        <div><span class="text-body-secondary">Diagnóstico:</span> {{ $evolucion->diagnostico }}</div>
                        @if ($evolucion->tratamiento_realizado)
                            <div><span class="text-body-secondary">Tratamiento:</span> {{ $evolucion->tratamiento_realizado }}</div>
                        @endif
                    </div>
                @empty
                    <p class="text-body-secondary mb-0">Aún no hay atenciones registradas.</p>
                @endforelse
            </div>
        </div>

        <div class="col-xl-5">
            <div class="panel mb-3">
                <h2><i class="bi bi-image"></i> Radiografías</h2>
                @forelse ($ficha->radiografias as $radiografia)
                    <div class="d-flex gap-3 border-bottom py-2">
                        <a href="{{ route('radiografias.archivo', $radiografia) }}" target="_blank" rel="noopener">
                            <img src="{{ route('radiografias.archivo', $radiografia) }}" alt="Radiografía del {{ $radiografia->fecha->format('d/m/Y') }}"
                                 class="miniatura" loading="lazy">
                        </a>
                        <div>
                            <div class="mono">{{ $radiografia->fecha->format('d/m/Y') }}</div>
                            <div class="small">{{ $radiografia->diagnostico ?: 'Sin diagnóstico registrado' }}</div>
                        </div>
                    </div>
                @empty
                    <p class="text-body-secondary">No hay radiografías.</p>
                @endforelse

                <form method="POST" action="{{ route('fichas.radiografias', $paciente) }}" enctype="multipart/form-data" class="mt-3">
                    @csrf
                    <div class="mb-3">
                        <label for="archivo" class="form-label">Imagen (JPG o PNG, máximo 5 MB)</label>
                        <input type="file" id="archivo" name="archivo" accept="image/jpeg,image/png"
                               @class(['form-control', 'is-invalid' => $errors->has('archivo')])>
                        @error('archivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    @include('partials.campo', ['nombre' => 'fecha_radiografia', 'etiqueta' => 'Fecha de la radiografía', 'tipo' => 'date', 'valor' => now()->toDateString()])
                    @include('partials.campo', ['nombre' => 'diagnostico_radiografia', 'etiqueta' => 'Diagnóstico', 'tipo' => 'textarea', 'filas' => 2])
                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-upload"></i> Subir radiografía</button>
                </form>
            </div>

            <form method="POST" action="{{ route('fichas.update', $paciente) }}" class="panel">
                @csrf
                @method('PUT')
                <h2><i class="bi bi-chat-left-text"></i> Observaciones generales</h2>
                <textarea name="evolucion_general" rows="4" class="form-control mb-2" aria-label="Observaciones generales">{{ old('evolucion_general', $ficha->evolucion_general) }}</textarea>
                <button type="submit" class="btn btn-outline-primary"><i class="bi bi-check-lg"></i> Guardar observaciones</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/odontograma.js') }}"></script>
@endpush
