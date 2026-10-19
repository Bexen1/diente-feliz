<form method="POST" action="{{ route('citas.estado', $cita) }}" class="d-inline">
    @csrf
    @method('PATCH')
    <input type="hidden" name="estado" value="{{ $estado }}">
    <button type="submit" class="btn btn-sm {{ $clase }}"><i class="bi {{ $icono }}"></i> {{ $texto }}</button>
</form>
