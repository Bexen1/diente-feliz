@extends('layouts.app')

@section('titulo', $paciente->exists ? 'Editar paciente' : 'Nuevo paciente')

@section('contenido')
    @php($verSalud = auth()->user()->tieneRol('Dentista'))

    <div class="titulo-pagina">
        <h1>{{ $paciente->exists ? 'Editar paciente' : 'Nuevo paciente' }}</h1>
    </div>

    <form method="POST" action="{{ $paciente->exists ? route('pacientes.update', $paciente) : route('pacientes.store') }}">
        @csrf
        @if ($paciente->exists)
            @method('PUT')
        @endif

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="panel h-100">
                    <h2><i class="bi bi-person-vcard"></i> Datos personales</h2>
                    @include('partials.campo', ['nombre' => 'rut', 'etiqueta' => 'RUT', 'valor' => $paciente->rut])
                    @include('partials.campo', ['nombre' => 'nombre', 'etiqueta' => 'Nombre completo', 'valor' => $paciente->nombre])
                    @include('partials.campo', ['nombre' => 'fecha_nacimiento', 'etiqueta' => 'Fecha de nacimiento', 'tipo' => 'date',
                        'valor' => $paciente->fecha_nacimiento?->format('Y-m-d')])
                    @include('partials.campo', ['nombre' => 'direccion', 'etiqueta' => 'Dirección', 'valor' => $paciente->direccion])

                    <div class="mb-3">
                        <label for="convenio_id" class="form-label">Convenio (categoría)</label>
                        <select id="convenio_id" name="convenio_id" @class(['form-select', 'is-invalid' => $errors->has('convenio_id')])>
                            <option value="">Particular (sin convenio)</option>
                            @foreach ($convenios as $convenio)
                                <option value="{{ $convenio->id }}" @selected(old('convenio_id', $paciente->convenio_id) == $convenio->id)>
                                    {{ $convenio->institucion }} ({{ rtrim(rtrim($convenio->porcentaje_descuento, '0'), '.') }} %)
                                </option>
                            @endforeach
                        </select>
                        @error('convenio_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @if (auth()->user()->esAdministrador() && $paciente->exists)
                        @include('partials.activo', ['valor' => $paciente->activo, 'etiqueta' => 'Paciente activo'])
                    @endif
                </div>
            </div>

            <div class="col-lg-6">
                <div class="panel mb-3">
                    <h2><i class="bi bi-telephone"></i> Contacto</h2>
                    @include('partials.campo', ['nombre' => 'telefono', 'etiqueta' => 'Teléfono', 'valor' => $paciente->telefono])
                    @include('partials.campo', ['nombre' => 'email', 'etiqueta' => 'Correo', 'valor' => $paciente->email, 'tipo' => 'email'])
                    @include('partials.campo', ['nombre' => 'contacto_emergencia_nombre', 'etiqueta' => 'Contacto de emergencia', 'valor' => $paciente->contacto_emergencia_nombre])
                    @include('partials.campo', ['nombre' => 'contacto_emergencia_telefono', 'etiqueta' => 'Teléfono de emergencia', 'valor' => $paciente->contacto_emergencia_telefono])
                </div>

                @if ($verSalud)
                    <div class="panel">
                        <h2><i class="bi bi-heart-pulse"></i> Antecedentes de salud</h2>
                        @include('partials.campo', ['nombre' => 'alergias', 'etiqueta' => 'Alergias', 'valor' => $paciente->alergias, 'tipo' => 'textarea', 'filas' => 2])
                        @include('partials.campo', ['nombre' => 'patologias', 'etiqueta' => 'Patologías base', 'valor' => $paciente->patologias, 'tipo' => 'textarea', 'filas' => 2])
                        @include('partials.campo', ['nombre' => 'medicamentos', 'etiqueta' => 'Medicamentos', 'valor' => $paciente->medicamentos, 'tipo' => 'textarea', 'filas' => 2])
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button>
            <a href="{{ $paciente->exists ? route('pacientes.show', $paciente) : route('pacientes.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Cancelar</a>
        </div>
    </form>
@endsection
