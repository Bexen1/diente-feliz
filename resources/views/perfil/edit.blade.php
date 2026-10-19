@extends('layouts.app')

@section('titulo', 'Mi perfil')

@section('contenido')
    <div class="titulo-pagina">
        <h1>Mi perfil</h1>
    </div>

    <form method="POST" action="{{ route('perfil.update') }}" class="panel" style="max-width: 640px;">
        @csrf
        @method('PUT')

        <p class="mb-1"><strong>{{ auth()->user()->nombre }}</strong> · {{ auth()->user()->email }}</p>
        <p class="text-body-secondary small">Rol: {{ auth()->user()->rol->nombre }}</p>

        <h2 class="h5 mt-3">Cambiar contraseña</h2>
        @include('partials.campo', ['nombre' => 'password_actual', 'etiqueta' => 'Contraseña actual', 'tipo' => 'password'])
        @include('partials.campo', ['nombre' => 'password', 'etiqueta' => 'Nueva contraseña', 'tipo' => 'password'])
        @include('partials.campo', ['nombre' => 'password_confirmation', 'etiqueta' => 'Repetir nueva contraseña', 'tipo' => 'password'])

        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button>
    </form>
@endsection
