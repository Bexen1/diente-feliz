<?php

namespace App\Http\Requests;

use App\Models\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $reglas = [
            'especialidad' => ['required', 'string', 'max:100'],
            'activo' => ['required', 'boolean'],
        ];

        if (! $this->route('doctor')) {
            $reglas['usuario_id'] = [
                'required', 'integer', 'unique:doctores,usuario_id',
                Rule::exists('usuarios', 'id')->where('rol_id', Rol::where('nombre', Rol::DENTISTA)->value('id')),
            ];
        }

        return $reglas;
    }
}
