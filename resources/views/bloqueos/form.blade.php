@extends('layouts.app')

@section('titulo', 'Nuevo bloqueo')

@section('contenido')
    <div class="titulo-pagina">
        <h1>Nuevo bloqueo de agenda</h1>
    </div>

    <form method="POST" action="{{ route('bloqueos.store') }}" class="panel" style="max-width: 640px;">
        @csrf

        <div class="row g-2">
            <div class="col-sm-6 mb-3">
                <label for="doctor_id" class="form-label">Doctor</label>
                <select id="doctor_id" name="doctor_id" @class(['form-select', 'is-invalid' => $errors->has('doctor_id')])>
                    <option value="">Todos los doctores</option>
                    @foreach ($doctores as $doctor)
                        <option value="{{ $doctor->id }}" @selected(old('doctor_id') == $doctor->id)>{{ $doctor->nombre }}</option>
                    @endforeach
                </select>
                @error('doctor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-sm-6 mb-3">
                <label for="box_id" class="form-label">Box</label>
                <select id="box_id" name="box_id" @class(['form-select', 'is-invalid' => $errors->has('box_id')])>
                    <option value="">Todos los box</option>
                    @foreach ($boxes as $box)
                        <option value="{{ $box->id }}" @selected(old('box_id') == $box->id)>{{ $box->nombre }} · {{ $box->sucursal->nombre }}</option>
                    @endforeach
                </select>
                @error('box_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-sm-6">
                @include('partials.campo', ['nombre' => 'fecha_inicio', 'etiqueta' => 'Desde', 'tipo' => 'date', 'valor' => today()->toDateString()])
            </div>
            <div class="col-sm-6">
                @include('partials.campo', ['nombre' => 'fecha_fin', 'etiqueta' => 'Hasta', 'tipo' => 'date', 'valor' => today()->toDateString()])
            </div>
        </div>

        @include('partials.campo', ['nombre' => 'motivo', 'etiqueta' => 'Motivo', 'valor' => ''])

        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button>
        <a href="{{ route('bloqueos.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Cancelar</a>
    </form>
@endsection
