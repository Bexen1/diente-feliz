<div class="form-check mb-3">
    <input type="hidden" name="activo" value="0">
    <input type="checkbox" class="form-check-input" id="activo" name="activo" value="1"
           @checked(old('activo', $valor ?? true))>
    <label class="form-check-label" for="activo">{{ $etiqueta ?? 'Activo' }}</label>
</div>
