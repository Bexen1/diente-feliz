@php($tipo = $tipo ?? 'text')
<div class="mb-3">
    <label for="{{ $nombre }}" class="form-label">{{ $etiqueta }}</label>
    @if ($tipo === 'textarea')
        <textarea id="{{ $nombre }}" name="{{ $nombre }}" rows="{{ $filas ?? 3 }}"
                  @class(['form-control', 'is-invalid' => $errors->has($nombre)])>{{ old($nombre, $valor ?? '') }}</textarea>
    @else
        <input type="{{ $tipo }}" id="{{ $nombre }}" name="{{ $nombre }}"
               value="{{ $tipo === 'password' ? '' : old($nombre, $valor ?? '') }}"
               @if (isset($paso)) step="{{ $paso }}" @endif
               @class(['form-control', 'is-invalid' => $errors->has($nombre)])>
    @endif
    @error($nombre)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
