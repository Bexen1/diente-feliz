@extends('layouts.app')

@section('titulo', 'Reagendar cita')

@section('contenido')
    @php($soloPropia = ! auth()->user()->veTodaLaAgenda())

    <div class="titulo-pagina">
        <div>
            <h1>Reagendar cita</h1>
            <small class="text-body-secondary">{{ $cita->paciente->nombre }} · actualmente el
                {{ $cita->hora_inicio->format('d/m/Y') }} a las <span class="mono">{{ $cita->hora_inicio->format('H:i') }}</span></small>
        </div>
    </div>

    <form method="POST" action="{{ route('citas.update', $cita) }}" class="panel" style="max-width: 640px;">
        @csrf
        @method('PUT')

        @if ($errors->any())
            <div class="alert alert-danger py-2" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="mb-3">
            <label for="doctor_id" class="form-label">Dentista</label>
            @if ($soloPropia)
                <input type="hidden" name="doctor_id" value="{{ $cita->doctor_id }}">
                <input type="text" id="doctor_id" class="form-control" value="{{ $cita->doctor->nombre }}" disabled>
            @else
                <select id="doctor_id" name="doctor_id" class="form-select">
                    @foreach ($doctores as $doctor)
                        <option value="{{ $doctor->id }}" @selected(old('doctor_id', $cita->doctor_id) == $doctor->id)>{{ $doctor->nombre }}</option>
                    @endforeach
                </select>
            @endif
        </div>

        <div class="row g-2 mb-3">
            <div class="col-sm-6">
                <label for="box_id" class="form-label">Box</label>
                <select id="box_id" name="box_id" class="form-select">
                    @foreach ($boxes as $box)
                        <option value="{{ $box->id }}" @selected(old('box_id', $cita->box_id) == $box->id)>{{ $box->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6">
                <label for="fecha" class="form-label">Fecha</label>
                <input type="date" id="fecha" name="fecha" class="form-control" value="{{ old('fecha', $cita->hora_inicio->toDateString()) }}">
            </div>
            <div class="col-sm-6">
                <label for="hora" class="form-label">Hora</label>
                <select id="hora" name="hora" class="form-select mono">
                    @foreach ($franjas as $franja)
                        <option value="{{ $franja }}" @selected(old('hora', $cita->hora_inicio->format('H:i')) === $franja)>{{ $franja }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6">
                <label for="duracion" class="form-label">Duración</label>
                <select id="duracion" name="duracion" class="form-select">
                    @foreach (\App\Models\Cita::DURACIONES as $minutos)
                        <option value="{{ $minutos }}" @selected(old('duracion', $cita->duracion) == $minutos)>{{ $minutos }} minutos</option>
                    @endforeach
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary"><i class="bi bi-arrow-repeat"></i> Reagendar</button>
        <a href="{{ route('citas.index', ['fecha' => $cita->hora_inicio->toDateString()]) }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Volver</a>
    </form>
@endsection
