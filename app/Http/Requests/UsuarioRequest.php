<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $usuario = $this->route('usuario');

        return [
            'nombre' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('usuarios', 'email')->ignore($usuario)],
            'rol_id' => ['required', 'integer', 'exists:roles,id'],
            'activo' => ['required', 'boolean'],
            'password' => [$usuario ? 'nullable' : 'required', 'confirmed', Password::min(12)->uncompromised()],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => strtolower((string) $this->input('email'))]);
    }
}
