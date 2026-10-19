@extends('layouts.app')

@section('titulo', $box->exists ? 'Editar box' : 'Nuevo box')

@section('contenido')
    <div class="titulo-pagina">
        <h1>{{ $box->exists ? 'Editar box' : 'Nuevo box' }}</h1>
    </div>

    <form method="POST" class="panel" style="max-width: 640px;"
          action="{{ $box->exists ? route('box.update', $box) : route('box.store') }}">
        @csrf
        @if ($box->exists)
            @method('PUT')
        @endif

        @include('partials.campo', ['nombre' => 'nombre', 'etiqueta' => 'Nombre', 'valor' => $box->nombre])

        <div class="mb-3">
            <label for="sucursal_id" class="form-label">Sucursal</label>
            <select id="sucursal_id" name="sucursal_id" @class(['form-select', 'is-invalid' => $errors->has('sucursal_id')])>
                @foreach ($sucursales as $sucursal)
                    <option value="{{ $sucursal->id }}" @selected(old('sucursal_id', $box->sucursal_id) == $sucursal->id)>{{ $sucursal->nombre }}</option>
                @endforeach
            </select>
            @error('sucursal_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        @include('partials.activo', ['valor' => $box->exists ? $box->activo : true, 'etiqueta' => 'Habilitado para agendar'])

        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button>
        <a href="{{ route('box.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Cancelar</a>
    </form>
@endsection
