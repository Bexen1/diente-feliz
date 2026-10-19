<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CambiarContrasenaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password_actual' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:password_actual', Password::min(12)->uncompromised()],
        ];
    }

    public function attributes(): array
    {
        return ['password_actual' => 'contraseña actual', 'password' => 'nueva contraseña'];
    }

    public function messages(): array
    {
        return ['password_actual.current_password' => 'La contraseña actual no es correcta.'];
    }
}
